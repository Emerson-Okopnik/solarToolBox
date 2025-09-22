<?php

namespace App\Services;

use App\Models\Inversor;
use App\Exceptions\SolarCalculationException;
use Illuminate\Support\Collection;

class DistributionService
{
    /**
     * Distribui strings por orientação/tilt nos MPPTs
     */
    public function distribuir(Inversor $inversor, Collection $arranjos, array $configuracoes = [])
    {
        $configuracoes = array_merge([
            'somente_recomendacao' => false,
            'preencher_somente_vazios' => false,
        ], $configuracoes);

        // Agrupar strings por orientação
        $gruposOrientacao = $this->agruparPorOrientacao($arranjos, $configuracoes);
        
        // Obter MPPTs disponíveis
        $mppts = $inversor->mppts()->orderBy('numero')->get();
        
        if ($mppts->isEmpty()) {
            throw new SolarCalculationException("Inversor não possui MPPTs configurados");
        }

        // Aplicar algoritmo de distribuição
        $distribuicao = $this->aplicarAlgoritmoDistribuicao($gruposOrientacao, $mppts, $arranjos, $configuracoes);
        
        // Validar distribuição resultante
        $this->validarDistribuicao($distribuicao, $configuracoes);
        
        return $distribuicao;
    }

    /**
     * Agrupa strings por orientação (azimute + inclinação)
     */
    private function agruparPorOrientacao(Collection $arranjos, array $configuracoes = [])
    {
        $considerarSomenteSemMppt = $configuracoes['preencher_somente_vazios'] ?? false;

        $strings = $arranjos->flatMap(function ($arranjo) use ($considerarSomenteSemMppt) {
            return $arranjo->strings
                ->filter(function ($string) use ($arranjo, $considerarSomenteSemMppt) {
                    if (!$string->relationLoaded('arranjo')) {
                        $string->setRelation('arranjo', $arranjo);
                    }

                    if ($considerarSomenteSemMppt && $string->mppt_id !== null) {
                        return false;
                    }

                    return $string->azimute !== null && $string->inclinacao !== null;
                });
        });

        return $strings->groupBy(function ($string) {
            return $string->getOrientacaoGrupo();
        })->map(function ($grupo, $orientacao) {
            $primeiraString = $grupo->first();
            $arranjos = $grupo->map->arranjo->filter()->unique('id')->values();
            return [
                'orientacao' => $orientacao,
                'azimute' => $primeiraString->azimute,
                'inclinacao' => $primeiraString->inclinacao,
                'arranjos' => $arranjos,
                'strings' => $grupo,
                'total_strings' => $grupo->count(),
                'potencia_total' => $grupo->sum(function ($string) {
                    return $string->potencia_total ?? 0;
                })
            ];
        })->sortByDesc('potencia_total'); // Priorizar grupos com maior potência
    }

    /**
     * Aplica algoritmo guloso de distribuição
     */
    private function aplicarAlgoritmoDistribuicao($gruposOrientacao, Collection $mppts, Collection $arranjos, array $configuracoes)
    {
        $distribuicao = [
            'mppts' => [],
            'grupos_nao_alocados' => [],
            'avisos' => [],
            'estatisticas' => [
                'grupos_processados' => 0,
                'grupos_alocados' => 0,
                'orientacoes_mistas' => 0,
                'strings_processadas' => 0,
                'strings_preexistentes' => 0,
            ],
            'contexto' => [
                'somente_recomendacao' => (bool) ($configuracoes['somente_recomendacao'] ?? false),
                'preencher_somente_vazios' => (bool) ($configuracoes['preencher_somente_vazios'] ?? false),
            ],
        ];

        // Inicializar MPPTs
        foreach ($mppts as $mppt) {
            $distribuicao['mppts'][$mppt->id] = [
                'mppt' => $mppt,
                'grupos_alocados' => [],
                'strings_total' => 0,
                'potencia_total' => 0,
                'corrente_total' => 0,
                'orientacoes' => [],
                'status' => 'disponivel'
            ];
        }

        if ($distribuicao['contexto']['preencher_somente_vazios']) {
            $distribuicao['estatisticas']['strings_preexistentes'] =
                $this->carregarEstadoExistente($distribuicao['mppts'], $arranjos);
        }

        // Algoritmo guloso: alocar cada grupo no melhor MPPT disponível
        foreach ($gruposOrientacao as $grupo) {
            $distribuicao['estatisticas']['grupos_processados']++;

            $distribuicao['estatisticas']['strings_processadas'] += $grupo['total_strings'];
            
            $melhorMppt = $this->encontrarMelhorMppt($grupo, $distribuicao['mppts'], $configuracoes);
            
            if ($melhorMppt) {
                $this->alocarGrupoNoMppt($grupo, $melhorMppt, $distribuicao, $configuracoes);
                $distribuicao['estatisticas']['grupos_alocados']++;
            } else {
                $distribuicao['grupos_nao_alocados'][] = $grupo;
            }
        }

        // Detectar orientações mistas
        $this->detectarOrientacoesMistas($distribuicao);

        return $distribuicao;
    }

    /**
     * Encontra o melhor MPPT para alocar um grupo
     */
    private function encontrarMelhorMppt($grupo, array &$mppts, array $configuracoes)
    {
        $candidatos = [];

        foreach ($mppts as $mpptId => &$mpptInfo) {
            if ($mpptInfo['status'] === 'lotado') {
                continue;
            }

            // Verificar se cabe no MPPT
            $stringsTotais = $mpptInfo['strings_total'] + $grupo['total_strings'];
            if ($stringsTotais > $mpptInfo['mppt']->strings_max) {
                continue;
            }

            // Calcular score de adequação
            $score = $this->calcularScoreAdequacao($grupo, $mpptInfo, $configuracoes);
            
            if ($score > 0) {
                $candidatos[] = [
                    'mppt_id' => $mpptId,
                    'mppt_info' => &$mpptInfo,
                    'score' => $score
                ];
            }
        }

        if (empty($candidatos)) {
            return null;
        }

        // Retornar candidato com maior score
        usort($candidatos, fn($a, $b) => $b['score'] <=> $a['score']);
        return $candidatos[0]['mppt_info'];
    }

    /**
     * Calcula score de adequação de um grupo para um MPPT
     */
    private function calcularScoreAdequacao($grupo, $mpptInfo, array $configuracoes)
    {
        $score = 100; // Score base

        // Penalizar se já há orientações diferentes no MPPT
        if (!empty($mpptInfo['orientacoes']) && 
            !in_array($grupo['orientacao'], $mpptInfo['orientacoes'])) {
            $score -= 50; // Penalidade por misturar orientações
        }

        // Bonificar se é a mesma orientação
        if (in_array($grupo['orientacao'], $mpptInfo['orientacoes'])) {
            $score += 30;
        }

        // Bonificar MPPTs menos utilizados (balanceamento)
        $utilizacao = $mpptInfo['strings_total'] / $mpptInfo['mppt']->strings_max;
        $score += (1 - $utilizacao) * 20;

        // Verificar limites técnicos (corrente, potência)
        $correnteFutura = $mpptInfo['corrente_total'] + $this->estimarCorrenteGrupo($grupo);
        if ($correnteFutura > $mpptInfo['mppt']->corrente_entrada_max) {
            return 0; // Inviável
        }

        return $score;
    }

    /**
     * Aloca um grupo em um MPPT
     */
    private function alocarGrupoNoMppt($grupo, &$mpptInfo, array &$distribuicao, array $configuracoes)
    {
        $mpptInfo['grupos_alocados'][] = $grupo;
        $mpptInfo['strings_total'] += $grupo['total_strings'];
        $mpptInfo['potencia_total'] += $grupo['potencia_total'];
        $mpptInfo['corrente_total'] += $this->estimarCorrenteGrupo($grupo);
        
        if (!in_array($grupo['orientacao'], $mpptInfo['orientacoes'])) {
            $mpptInfo['orientacoes'][] = $grupo['orientacao'];
        }

        // Atualizar strings com o MPPT alocado
        $devePersistir = !($configuracoes['somente_recomendacao'] ?? false);
        $considerarSomenteSemMppt = $configuracoes['preencher_somente_vazios'] ?? false;

        if ($devePersistir) {
            collect($grupo['strings'])->each(function ($string) use ($mpptInfo, $considerarSomenteSemMppt) {
                if ($considerarSomenteSemMppt && $string->mppt_id !== null) {
                    return;
                }

                $string->mppt_id = $mpptInfo['mppt']->id;
                $string->save();
            });
        }

        // Verificar se MPPT está lotado
        if ($mpptInfo['strings_total'] >= $mpptInfo['mppt']->strings_max) {
            $mpptInfo['status'] = 'lotado';
        }
    }

    /**
     * Estima corrente de um grupo
     */
    private function estimarCorrenteGrupo($grupo)
    {
        return collect($grupo['strings'])->sum(function ($string) {
            return $this->estimarCorrenteString($string);
        });
    }

    /**
     * Estima corrente individual de uma string
     */
    private function estimarCorrenteString($string)
    {
        $modulo = $string->modulo;
        $corrente = $string->corrente_maxima_potencia
            ?? ($modulo ? $modulo->imp : null);

        if ($corrente === null) {
            return 0;
        }

        $paralelos = $string->num_strings_paralelo ?: 1;

        return $corrente * $paralelos;
    }

    /**
     * Considera distribuição já existente nos MPPTs
     */
    private function carregarEstadoExistente(array &$mpptsInfo, Collection $arranjos)
    {
        $stringsPreexistentes = 0;

        foreach ($arranjos as $arranjo) {
            foreach ($arranjo->strings as $string) {
                if ($string->mppt_id === null) {
                    continue;
                }

                if (!isset($mpptsInfo[$string->mppt_id])) {
                    continue;
                }

                if (!$string->relationLoaded('arranjo')) {
                    $string->setRelation('arranjo', $arranjo);
                }

                $stringsPreexistentes++;
                $mpptInfo = &$mpptsInfo[$string->mppt_id];

                $mpptInfo['strings_total'] += 1;
                $mpptInfo['potencia_total'] += $string->potencia_total ?? 0;
                $mpptInfo['corrente_total'] += $this->estimarCorrenteString($string);

                $orientacao = $string->getOrientacaoGrupo();
                if (!in_array($orientacao, $mpptInfo['orientacoes'])) {
                    $mpptInfo['orientacoes'][] = $orientacao;
                }

                if ($mpptInfo['strings_total'] >= $mpptInfo['mppt']->strings_max) {
                    $mpptInfo['status'] = 'lotado';
                }
            }
        }
        return $stringsPreexistentes;
    }

    /**
     * Detecta orientações mistas nos MPPTs
     */
    private function detectarOrientacoesMistas(array &$distribuicao)
    {
        foreach ($distribuicao['mppts'] as $mpptId => &$mpptInfo) {
            if (count($mpptInfo['orientacoes']) > 1) {
                $distribuicao['estatisticas']['orientacoes_mistas']++;
                $distribuicao['avisos'][] = [
                    'tipo' => 'orientacao_mista',
                    'mppt_id' => $mpptId,
                    'mppt_numero' => $mpptInfo['mppt']->numero,
                    'orientacoes' => $mpptInfo['orientacoes'],
                    'mensagem' => "MPPT {$mpptInfo['mppt']->numero} possui orientações mistas",
                    'recomendacao' => 'Considere redistribuir as strings para evitar perdas de performance'
                ];
            }
        }
    }

    /**
     * Valida a distribuição resultante
     */
    private function validarDistribuicao(array $distribuicao, array $configuracoes)
    {
        $permitirOrientacoesMistas = $configuracoes['permitir_orientacoes_mistas'] ?? false;

        // Validar se todos os grupos foram alocados
        if (!empty($distribuicao['grupos_nao_alocados'])) {
            $gruposNaoAlocados = collect($distribuicao['grupos_nao_alocados'])
                ->pluck('orientacao')
                ->implode(', ');
                
            throw new SolarCalculationException(
                "Não foi possível alocar todos os grupos de orientação: {$gruposNaoAlocados}",
                [
                    'grupos_nao_alocados' => $distribuicao['grupos_nao_alocados'],
                    'sugestao' => 'Reduza o número de strings ou utilize um inversor com mais MPPTs'
                ]
            );
        }

        // Validar orientações mistas se não permitido
        if (!$permitirOrientacoesMistas && $distribuicao['estatisticas']['orientacoes_mistas'] > 0) {
            throw new SolarCalculationException(
                "Detectadas orientações mistas em {$distribuicao['estatisticas']['orientacoes_mistas']} MPPT(s)",
                [
                    'avisos' => $distribuicao['avisos'],
                    'sugestao' => 'Reorganize as strings ou permita orientações mistas nas configurações'
                ]
            );
        }
    }
}
