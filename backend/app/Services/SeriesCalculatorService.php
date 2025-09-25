<?php

namespace App\Services;

use App\Models\StringModel;
use App\Models\Modulo;
use App\Exceptions\SolarCalculationException;
use App\Exceptions\CompatibilityException;

class SeriesCalculatorService
{
    /**
     * Calcula parâmetros elétricos para conexão em série
     */
    public function calcular(StringModel $string, array $configuracoes = [])
    {
        $modulo = $string->modulo;
        $clima = $string->arranjo->projeto->clima;
        $limiteCompatibilidade = $configuracoes['limite_compatibilidade'] ?? 
                                $string->arranjo->projeto->limite_compatibilidade_tensao ?? 5.0;

        // Validar compatibilidade (mesmo módulo em série, então sempre compatível)
        $this->validarCompatibilidade($modulo, $string->num_modulos_serie, $limiteCompatibilidade);

        // Calcular parâmetros elétricos
        $resultados = $this->calcularParametrosEletricos($modulo, $string, $clima, $configuracoes);

        // Validar limites do inversor
        $this->validarLimitesInversor($string, $resultados);

        return $resultados;
    }

    /**
     * Valida compatibilidade entre módulos (para série, todos são iguais)
     */
    private function validarCompatibilidade(Modulo $modulo, int $numModulos, float $limite)
    {
        // Em série, todos os módulos são iguais, então sempre compatível
        return true;
    }

    /**
     * Calcula parâmetros elétricos da string em série
     */
    private function calcularParametrosEletricos(Modulo $modulo, StringModel $string, $clima, array $configuracoes)
    {
        $ns = $string->num_modulos_serie;
        $np = $this->calcularNumeroStringsParaleloEfetivo($string);

        // Temperaturas de operação
        $tempMin = $configuracoes['temp_min'] ?? $clima->temp_min_historica ?? -5;
        $tempMax = $configuracoes['temp_max'] ?? $clima->temp_max_historica ?? 70;
        $tempOperacao = $this->calcularTemperaturaOperacao($clima, $configuracoes);

        // Cálculos STC (25°C)
        $vocStringSTC = $modulo->voc * $ns;
        $vmpStringSTC = $modulo->vmp * $ns;
        $iscString = $modulo->isc; // Corrente não muda em série
        $impString = $modulo->imp; // Corrente não muda em série

        // Ajustes por temperatura
        $vocStringFrio = $this->calcularVocFrio($modulo, $ns, $tempMin);
        $vmpStringOperacao = $this->calcularVmpOperacao($modulo, $ns, $tempOperacao);

        // Para múltiplas strings em paralelo
        $corrente_total = $impString * $np;
        $potencia_total = $vmpStringOperacao * $corrente_total;

        return [
            'tensao_circuito_aberto_stc' => $vocStringSTC,
            'tensao_maxima_potencia_stc' => $vmpStringSTC,
            'tensao_circuito_aberto_frio' => $vocStringFrio,
            'tensao_maxima_potencia_operacao' => $vmpStringOperacao,
            'corrente_curto_circuito' => $iscString,
            'corrente_maxima_potencia' => $impString,
            'corrente_total' => $corrente_total,
            'potencia_total' => $potencia_total,
            'temperatura_minima' => $tempMin,
            'temperatura_operacao' => $tempOperacao,
            'num_modulos_serie' => $ns,
            'num_strings_paralelo' => $np,
        ];
    }

    private function calcularNumeroStringsParaleloEfetivo(StringModel $string): int
    {
        $np = (int) ($string->num_strings_paralelo ?? 0);

        if (!$string->mppt_id) {
            return $np;
        }

        $string->loadMissing(['arranjo.projeto.arranjos.strings']);

        $arranjo = $string->arranjo;
        $projeto = $arranjo?->projeto;

        if (!$arranjo || !$projeto) {
            return $np;
        }

        $total = $projeto->arranjos
            ->filter(fn ($arr) => $arr->projeto_inversor_id === $arranjo->projeto_inversor_id)
            ->flatMap(fn ($arr) => $arr->strings)
            ->filter(fn ($str) => $str->mppt_id === $string->mppt_id)
            ->sum(fn ($str) => (int) ($str->num_strings_paralelo ?? 0));

        return $total > 0 ? $total : $np;
    }

    /**
     * Calcula Voc a frio (temperatura mínima)
     */
    private function calcularVocFrio(Modulo $modulo, int $ns, float $tempMin)
    {
        $deltaT = $tempMin - 25; // STC = 25°C
        $coeficiente = $modulo->coef_temp_voc / 100; // Converter % para decimal
        
        return $modulo->voc * $ns * (1 + $coeficiente * $deltaT);
    }

    /**
     * Calcula Vmp em operação (NOCT)
     */
    private function calcularVmpOperacao(Modulo $modulo, int $ns, float $tempOperacao)
    {
        $deltaT = $tempOperacao - 25; // STC = 25°C
        $coeficiente = $modulo->coef_temp_vmp / 100; // Converter % para decimal
        
        return $modulo->vmp * $ns * (1 + $coeficiente * $deltaT);
    }

    /**
     * Calcula temperatura de operação baseada no NOCT
     */
    private function calcularTemperaturaOperacao($clima, array $configuracoes)
    {
        $tempAmbiente = $configuracoes['temp_ambiente'] ?? $clima->temp_media_anual ?? 25;
        $irradiancia = $configuracoes['irradiancia'] ?? 800; // W/m²
        $noct = $configuracoes['noct'] ?? 45; // °C
        
        // Fórmula: T_cel = T_amb + (NOCT - 20) * (G / 800)
        return $tempAmbiente + (($noct - 20) * ($irradiancia / 800));
    }

    /**
     * Valida limites do inversor
     */
    private function validarLimitesInversor(StringModel $string, array $resultados)
    {
        $inversor = $string->arranjo->inversor;
        $mppt = $string->mppt;

        // Validar tensão DC máxima
        if ($resultados['tensao_circuito_aberto_frio'] > $inversor->tensao_dc_max) {
            throw new SolarCalculationException(
                "Tensão de circuito aberto a frio ({$resultados['tensao_circuito_aberto_frio']}V) excede o limite do inversor ({$inversor->tensao_dc_max}V)",
                [
                    'voc_frio_calculado' => $resultados['tensao_circuito_aberto_frio'],
                    'vdc_max_inversor' => $inversor->tensao_dc_max,
                    'string_id' => $string->id
                ]
            );
        }

        // Validar janela MPPT se MPPT definido
        if ($mppt) {
            $vmpOp = $resultados['tensao_maxima_potencia_operacao'];
            if ($vmpOp < $mppt->tensao_mppt_min || $vmpOp > $mppt->tensao_mppt_max) {
                throw new SolarCalculationException(
                    "Tensão de operação ({$vmpOp}V) fora da janela MPPT ({$mppt->tensao_mppt_min}V - {$mppt->tensao_mppt_max}V)",
                    [
                        'vmp_operacao' => $vmpOp,
                        'vmppt_min' => $mppt->tensao_mppt_min,
                        'vmppt_max' => $mppt->tensao_mppt_max,
                        'string_id' => $string->id,
                        'mppt_id' => $mppt->id
                    ]
                );
            }

            // Validar corrente máxima do MPPT
            if ($resultados['corrente_total'] > $mppt->corrente_entrada_max) {
                throw new SolarCalculationException(
                    "Corrente total ({$resultados['corrente_total']}A) excede o limite do MPPT ({$mppt->corrente_entrada_max}A)",
                    [
                        'corrente_total' => $resultados['corrente_total'],
                        'idc_max_mppt' => $mppt->corrente_entrada_max,
                        'string_id' => $string->id,
                        'mppt_id' => $mppt->id
                    ]
                );
            }
        }
    }
}
