<?php

namespace App\Services;

use App\Models\Inversor;
use App\Services\LinearProgramming\TransportationSolver;
use Illuminate\Support\Arr;

class InverterRecommendationService
{
    private const OVERSIZING_LIMIT = 1.30; // 130%

    public function __construct(private readonly TransportationSolver $solver)
    {
    }

    /**
     * @param  int    $quantidadeModulos
     * @param  float  $potenciaTotal
     * @param  array<int, mixed>  $orientacoes
     */
    public function recomendar(int $quantidadeModulos, float $potenciaTotal, array $orientacoes): array
    {
        $orientacoesNormalizadas = $this->normalizarOrientacoes($orientacoes);

        if (empty($orientacoesNormalizadas)) {
            throw new \InvalidArgumentException('Informe ao menos uma orientação de string.');
        }

        $totalStrings = array_sum(array_column($orientacoesNormalizadas, 'quantidade'));

        if ($totalStrings <= 0) {
            throw new \InvalidArgumentException('Quantidade de strings inválida.');
        }

        if ($quantidadeModulos % $totalStrings !== 0) {
            throw new \InvalidArgumentException('A quantidade de módulos deve ser múltipla do total de strings informadas.');
        }

        $modulosPorString = (int) ($quantidadeModulos / $totalStrings);
        $potenciaPorString = $potenciaTotal / $totalStrings;
        $potenciaPorModulo = $quantidadeModulos > 0 ? $potenciaTotal / $quantidadeModulos : 0.0;

        $inversores = Inversor::query()
            ->with(['fabricante', 'mppts' => fn ($query) => $query->orderBy('numero')])
            ->ativos()
            ->get();

        $recomendacoes = [];

        foreach ($inversores as $inversor) {
            $recomendacao = $this->avaliarInversor(
                $inversor,
                $orientacoesNormalizadas,
                $totalStrings,
                $potenciaTotal
            );

            if ($recomendacao !== null) {
                $recomendacao['pontuacao'] = $this->calcularPontuacao($inversor, $potenciaTotal, $totalStrings);
                $recomendacoes[] = $recomendacao;
            }
        }

        if (empty($recomendacoes)) {
            return [
                'quantidade_modulos' => $quantidadeModulos,
                'potencia_total' => round($potenciaTotal, 2),
                'total_strings' => $totalStrings,
                'modulos_por_string' => $modulosPorString,
                'potencia_por_string' => round($potenciaPorString, 2),
                'potencia_por_modulo' => round($potenciaPorModulo, 2),
                'inversores' => [],
            ];
        }

        usort($recomendacoes, fn ($a, $b) => $a['pontuacao'] <=> $b['pontuacao']);

        $recomendacoes = array_map(fn ($item) => Arr::except($item, 'pontuacao'), $recomendacoes);

        return [
            'quantidade_modulos' => $quantidadeModulos,
            'potencia_total' => round($potenciaTotal, 2),
            'total_strings' => $totalStrings,
            'modulos_por_string' => $modulosPorString,
            'potencia_por_string' => round($potenciaPorString, 2),
            'potencia_por_modulo' => round($potenciaPorModulo, 2),
            'inversores' => $recomendacoes,
        ];
    }

    /**
     * @param  array<int, array{identificador: string, quantidade: int}>  $orientacoes
     */
    private function avaliarInversor(Inversor $inversor, array $orientacoes, int $totalStrings, float $potenciaTotal): ?array
    {
        if ($inversor->potencia_dc_max > 0 && $potenciaTotal > (float) $inversor->potencia_dc_max) {
            return null;
        }

        if ($inversor->potencia_ac_nominal <= 0) {
            return null;
        }

        $oversizing = $potenciaTotal / (float) $inversor->potencia_ac_nominal;

        if ($oversizing > self::OVERSIZING_LIMIT) {
            return null;
        }

        $capacidades = $inversor->mppts
            ->map(fn ($mppt) => (int) max(0, $mppt->strings_max ?? 0))
            ->filter(fn ($capacidade) => $capacidade > 0)
            ->values();

        if ($capacidades->isEmpty()) {
            return null;
        }

        if ($capacidades->sum() < $totalStrings) {
            return null;
        }

        $solucao = $this->solver->solve(
            array_column($orientacoes, 'quantidade'),
            $capacidades->all()
        );

        if ($solucao === null) {
            return null;
        }

        return [
            'inversor' => [
                'id' => $inversor->id,
                'modelo' => $inversor->modelo,
                'fabricante' => $inversor->fabricante?->nome,
                'potencia_dc_max' => (float) $inversor->potencia_dc_max,
                'potencia_ac_nominal' => (float) $inversor->potencia_ac_nominal,
                'num_mppts' => $inversor->num_mppts,
            ],
            'metricas' => [
                'strings_utilizadas' => $totalStrings,
                'capacidade_strings_total' => $capacidades->sum(),
                'margem_potencia_dc' => round((float) $inversor->potencia_dc_max - $potenciaTotal, 2),
                'fator_oversizing' => round($oversizing * 100, 2),
            ],
            'distribuicao' => $this->montarDistribuicao($inversor, $orientacoes, $solucao),
        ];
    }

    /**
     * @param  array<int, array{identificador: string, quantidade: int}>  $orientacoes
     * @param  array<int, array<int, int>>  $solucao
     */
    private function montarDistribuicao(Inversor $inversor, array $orientacoes, array $solucao): array
    {
        $resultado = [];
        $mppts = $inversor->mppts->values();

        foreach ($solucao as $mpptIndex => $alocacoes) {
            $mppt = $mppts[$mpptIndex] ?? null;

            if ($mppt === null) {
                continue;
            }

            $stringsAlocadas = array_sum($alocacoes);

            if ($stringsAlocadas === 0) {
                continue;
            }

            $orientacoesAlocadas = [];

            foreach ($alocacoes as $orientacaoIndex => $quantidade) {
                if ($quantidade <= 0) {
                    continue;
                }

                $orientacao = $orientacoes[$orientacaoIndex]['identificador'] ?? "Orientação {$orientacaoIndex}";

                $orientacoesAlocadas[] = [
                    'identificador' => $orientacao,
                    'strings' => $quantidade,
                ];
            }

            $resultado[] = [
                'mppt' => $mppt->numero,
                'capacidade_strings' => (int) ($mppt->strings_max ?? 0),
                'strings_alocadas' => $stringsAlocadas,
                'orientacoes' => $orientacoesAlocadas,
            ];
        }

        return $resultado;
    }

    private function calcularPontuacao(Inversor $inversor, float $potenciaTotal, int $totalStrings): float
    {
        $margemDc = max(0.0, (float) $inversor->potencia_dc_max - $potenciaTotal);
        $capacidadeSobras = max(0, ($inversor->mppts->sum('strings_max') ?? 0) - $totalStrings);
        $diferencaAc = abs((float) $inversor->potencia_ac_nominal - $potenciaTotal);

        return $margemDc + $capacidadeSobras + $diferencaAc;
    }

    /**
     * @param  array<int, mixed>  $orientacoes
     * @return array<int, array{identificador: string, quantidade: int}>
     */
    private function normalizarOrientacoes(array $orientacoes): array
    {
        $resultado = [];

        foreach ($orientacoes as $entrada) {
            if (is_string($entrada)) {
                $identificador = trim($entrada);
                $quantidade = 1;
            } elseif (is_array($entrada)) {
                $identificador = trim((string) ($entrada['identificador'] ?? $entrada['valor'] ?? ''));
                $quantidade = (int) ($entrada['quantidade'] ?? 1);
            } else {
                continue;
            }

            if ($identificador === '' || $quantidade <= 0) {
                continue;
            }

            if (!array_key_exists($identificador, $resultado)) {
                $resultado[$identificador] = [
                    'identificador' => $identificador,
                    'quantidade' => 0,
                ];
            }

            $resultado[$identificador]['quantidade'] += $quantidade;
        }

        return array_values(array_filter($resultado, fn ($item) => $item['quantidade'] > 0));
    }
}