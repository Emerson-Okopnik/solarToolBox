<?php

namespace App\Services\LinearProgramming;

/**
* Resolve a viabilidade de problemas de transporte usando uma estratégia de alocação baseada em simplex.
*
* O solucionador utiliza uma abordagem construtiva equivalente à resolução de um modelo de programação linear
* para a atribuição de suprimentos às demandas, garantindo soluções inteiras porque a
* matriz de restrições subjacente é totalmente unimodular. Isso nos permite validar a
* compatibilidade do inversor, verificando se as strings solicitadas podem ser distribuídas entre as entradas do MPPT.
 */
class TransportationSolver
{
    /**
     * Tenta encontrar uma matriz de alocação viável para os suprimentos e capacidades fornecidos.
     *
     * @param  array<int, int|float>  $supplies   Quantidades que devem ser totalmente alocadas (uma por grupo de orientação).
     * @param  array<int, int|float>  $capacities Capacidade máxima para cada entrada MPPT.
     * @return array<int, array<int, int>>|null  Uma matriz viável ou nula quando nenhuma alocação é possível.
     */
    public function solve(array $supplies, array $capacities): ?array
    {
        $supplies = array_values(array_map([$this, 'sanitizeValue'], $supplies));
        $capacities = array_values(array_map([$this, 'sanitizeValue'], $capacities));

        if (empty($supplies) || empty($capacities)) {
            return null;
        }

        $totalSupply = array_sum($supplies);
        $totalCapacity = array_sum($capacities);

        if ($totalSupply <= 0 || $totalCapacity < $totalSupply) {
            return null;
        }

        $solution = array_fill(0, count($capacities), array_fill(0, count($supplies), 0));

        $mpptIndex = 0;
        $orientationIndex = 0;

        while ($mpptIndex < count($capacities) && $orientationIndex < count($supplies)) {
            if ($capacities[$mpptIndex] === 0) {
                $mpptIndex++;
                continue;
            }

            if ($supplies[$orientationIndex] === 0) {
                $orientationIndex++;
                continue;
            }

            $allocation = min($capacities[$mpptIndex], $supplies[$orientationIndex]);

            if ($allocation > 0) {
                $solution[$mpptIndex][$orientationIndex] = $allocation;
                $capacities[$mpptIndex] -= $allocation;
                $supplies[$orientationIndex] -= $allocation;
            }
        }

        foreach ($supplies as $remaining) {
            if ($remaining > 0) {
                return null;
            }
        }

        return $solution;
    }

    private function sanitizeValue(int|float $value): int
    {
        $rounded = (int) round($value);

        return max(0, $rounded);
    }
}