<?php

namespace App\Services;

use App\Models\Inversor;
use App\Exceptions\SolarCalculationException;
use Illuminate\Support\Collection;

class DistributionService
{
    /**
     * Valida a orientação das strings conectadas em cada MPPT
     */
    public function distribuir(Inversor $inversor, Collection $arranjos, array $configuracoes = [])
    {
        $configuracoes = array_merge([
            'permitir_orientacoes_mistas' => false,
        ], $configuracoes);

        $mppts = $inversor->mppts()->orderBy('numero')->get();
        
        if ($mppts->isEmpty()) {
            throw new SolarCalculationException('Inversor não possui MPPTs configurados');
        }

        $distribuicao = [
            'mppts' => [],
            'avisos' => [],
            'estatisticas' => [
                'mppts_avaliados' => 0,
                'orientacoes_mistas' => 0,
                'strings_avaliadas' => 0,
                'strings_sem_orientacao' => 0,
            ],
            'contexto' => [
                'validacao' => 'orientacao_por_mppt',
                'permitir_orientacoes_mistas' => (bool) $configuracoes['permitir_orientacoes_mistas'],
            ],
        ];

        $orientacoesInternas = 0;

        foreach ($mppts as $mppt) {

            $infoMppt = $this->avaliarOrientacoesDoMppt($mppt, $arranjos);
            $distribuicao['estatisticas']['mppts_avaliados']++;
            $distribuicao['estatisticas']['strings_avaliadas'] += $infoMppt['strings_total'];
            $distribuicao['estatisticas']['strings_sem_orientacao'] += $infoMppt['strings_sem_orientacao'];

            if (count($infoMppt['orientacoes']) > 1) {
                $orientacoesInternas++;
                $distribuicao['avisos'][] = [
                    'tipo' => 'orientacao_mista',
                    'mppt_id' => $mppt->id,
                    'mppt_numero' => $mppt->numero,
                    'orientacoes' => $infoMppt['orientacoes'],
                    'mensagem' => "MPPT {$mppt->numero} possui orientações distintas",
                    'recomendacao' => 'Unifique a orientação das strings conectadas a este MPPT',
                ];
            }
            $distribuicao['mppts'][$mppt->id] = $infoMppt;
        }

        $orientacoesEntreMppts = $this->contarOrientacoesEntreMppts($distribuicao['mppts']);

        $distribuicao['estatisticas']['orientacoes_mistas_internas'] = $orientacoesInternas;
        $distribuicao['estatisticas']['orientacoes_mistas_entre_mppts'] = $orientacoesEntreMppts;
        $distribuicao['estatisticas']['orientacoes_mistas'] = $orientacoesInternas + $orientacoesEntreMppts;

        $this->validarOrientacoes($distribuicao, $configuracoes);

        return $distribuicao;
    }

    /**
     * Avalia as orientações das strings de um MPPT específico
     */
    private function avaliarOrientacoesDoMppt($mppt, Collection $arranjos)
    {
        $strings = collect();

        foreach ($arranjos as $arranjo) {
            foreach ($arranjo->strings as $string) {
                if ($string->mppt_id !== $mppt->id) {
                    continue;
                }

                if (!$string->relationLoaded('arranjo')) {
                    $string->setRelation('arranjo', $arranjo);
                }

                $strings->push($string);
            }
        }

        $orientacoes = [];
        $stringsSemOrientacao = 0;
        $detalhesStrings = [];

        foreach ($strings as $string) {
            $orientacao = $string->getOrientacaoGrupo();

            if ($orientacao === null) {
                $stringsSemOrientacao++;
            } elseif (!in_array($orientacao, $orientacoes, true)) {
                $orientacoes[] = $orientacao;
            }

            $detalhesStrings[] = [
                'string_id' => $string->id,
                'orientacao' => $orientacao,
                'azimute' => $string->azimute,
                'inclinacao' => $string->inclinacao,
            ];
        }

        return [
            'mppt' => $mppt,
            'orientacoes' => $orientacoes,
            'strings_total' => $strings->count(),
            'strings_sem_orientacao' => $stringsSemOrientacao,
            'detalhes_strings' => $detalhesStrings,
        ];
    }

    /**
     * Valida se existem orientações mistas não permitidas
     */
    private function validarOrientacoes(array $distribuicao, array $configuracoes)
    {
        $permitirOrientacoesMistas = (bool) ($configuracoes['permitir_orientacoes_mistas'] ?? false);
        $misturasInternas = $distribuicao['estatisticas']['orientacoes_mistas_internas']
            ?? $distribuicao['estatisticas']['orientacoes_mistas']
            ?? 0;
       if (!$permitirOrientacoesMistas && $misturasInternas > 0) {
            throw new SolarCalculationException(
               "Detectadas orientações mistas em {$misturasInternas} MPPT(s)",
                [
                    'avisos' => $distribuicao['avisos'],
                    'sugestao' => 'Reorganize as strings ou permita orientações mistas nas configurações',
                ]
            );
        }
    }

    private function contarOrientacoesEntreMppts(array $mpptsInfo): int
    {
        $mpptsComStrings = collect($mpptsInfo)
            ->filter(fn ($info) => ($info['strings_total'] ?? 0) > 0);

        if ($mpptsComStrings->count() <= 1) {
            return 0;
        }

        $assinaturasOrientacoes = $mpptsComStrings->map(function ($info) {
            $orientacoes = collect($info['orientacoes'] ?? [])
                ->filter(fn ($orientacao) => $orientacao !== null && $orientacao !== '')
                ->unique()
                ->sort()
                ->values();

            return $orientacoes->implode('|');
        });

        $orientacoesDistintas = $assinaturasOrientacoes->unique();

        if ($orientacoesDistintas->count() <= 1) {
            return 0;
        }

        return $orientacoesDistintas->count() - 1;
    }
}
