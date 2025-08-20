<?php

namespace App\Services;

use App\Models\StringModel;
use App\Models\Modulo;
use App\Exceptions\SolarCalculationException;
use App\Exceptions\CompatibilityException;
use Illuminate\Support\Collection;

class ParallelCalculatorService
{
    /**
     * Calcula parâmetros elétricos para conexão em paralelo
     */
    public function calcular(Collection $strings, array $configuracoes = [])
    {
        if ($strings->isEmpty()) {
            throw new SolarCalculationException("Nenhuma string fornecida para cálculo em paralelo");
        }

        // Validar compatibilidade entre strings
        $this->validarCompatibilidade($strings, $configuracoes);

        // Calcular parâmetros agregados
        $resultados = $this->calcularParametrosAgregados($strings, $configuracoes);

        // Validar limites do MPPT
        $this->validarLimitesMppt($strings->first(), $resultados);

        return $resultados;
    }

    /**
     * Valida compatibilidade entre strings em paralelo
     */
    private function validarCompatibilidade(Collection $strings, array $configuracoes)
    {
        $limiteCorrente = $configuracoes['limite_compatibilidade_corrente'] ?? 5.0;
        $limiteTensao = $configuracoes['limite_compatibilidade_tensao'] ?? 5.0;

        $modulos = $strings->map(fn($string) => $string->modulo)->unique('id');
        
        if ($modulos->count() > 1) {
            // Verificar compatibilidade de corrente (Imp)
            $correntes = $modulos->pluck('imp');
            $correnteMedia = $correntes->avg();
            $incompativeis = [];

            foreach ($modulos as $modulo) {
                $diferenca = abs(($modulo->imp - $correnteMedia) / $correnteMedia * 100);
                if ($diferenca > $limiteCorrente) {
                    $incompativeis[] = [
                        'modulo_id' => $modulo->id,
                        'modelo' => $modulo->modelo,
                        'imp' => $modulo->imp,
                        'diferenca_percentual' => $diferenca
                    ];
                }
            }

            if (!empty($incompativeis)) {
                throw new CompatibilityException(
                    "Módulos incompatíveis para conexão em paralelo (diferença de corrente > {$limiteCorrente}%)",
                    $incompativeis,
                    [
                        'sugestao' => 'Utilize módulos com correntes Imp similares ou ajuste o limite de compatibilidade',
                        'corrente_media' => $correnteMedia,
                        'limite_aplicado' => $limiteCorrente
                    ]
                );
            }

            // Verificar compatibilidade de tensão (Vmp)
            $tensoes = $modulos->pluck('vmp');
            $tensaoMedia = $tensoes->avg();
            $incompativeis = [];

            foreach ($modulos as $modulo) {
                $diferenca = abs(($modulo->vmp - $tensaoMedia) / $tensaoMedia * 100);
                if ($diferenca > $limiteTensao) {
                    $incompativeis[] = [
                        'modulo_id' => $modulo->id,
                        'modelo' => $modulo->modelo,
                        'vmp' => $modulo->vmp,
                        'diferenca_percentual' => $diferenca
                    ];
                }
            }

            if (!empty($incompativeis)) {
                throw new CompatibilityException(
                    "Módulos incompatíveis para conexão em paralelo (diferença de tensão > {$limiteTensao}%)",
                    $incompativeis,
                    [
                        'sugestao' => 'Utilize módulos com tensões Vmp similares ou ajuste o limite de compatibilidade',
                        'tensao_media' => $tensaoMedia,
                        'limite_aplicado' => $limiteTensao
                    ]
                );
            }
        }
    }

    /**
     * Calcula parâmetros elétricos agregados para strings em paralelo
     */
    private function calcularParametrosAgregados(Collection $strings, array $configuracoes)
    {
        $totalStrings = $strings->count();
        
        // Usar o primeiro módulo como referência (já validamos compatibilidade)
        $moduloReferencia = $strings->first()->modulo;
        $clima = $strings->first()->arranjo->projeto->clima;

        // Calcular médias ponderadas
        $impMedia = $strings->map(fn($s) => $s->modulo->imp)->avg();
        $vmpMedia = $strings->map(fn($s) => $s->modulo->vmp)->avg();
        $vocMedia = $strings->map(fn($s) => $s->modulo->voc)->avg();
        $iscMedia = $strings->map(fn($s) => $s->modulo->isc)->avg();

        // Temperaturas
        $tempMin = $configuracoes['temp_min'] ?? $clima->temp_min_historica ?? -5;
        $tempOperacao = $this->calcularTemperaturaOperacao($clima, $configuracoes);

        // Cálculos para o conjunto em paralelo
        $tensaoOperacao = $this->calcularVmpOperacao($moduloReferencia, 1, $tempOperacao);
        $tensaoFrio = $this->calcularVocFrio($moduloReferencia, 1, $tempMin);
        
        // Somar correntes (característica do paralelo)
        $correnteTotal = $impMedia * $totalStrings;
        $correnteCurtoTotal = $iscMedia * $totalStrings;
        
        // Potência total
        $potenciaTotal = $tensaoOperacao * $correnteTotal;

        return [
            'tensao_circuito_aberto_frio' => $tensaoFrio,
            'tensao_maxima_potencia_operacao' => $tensaoOperacao,
            'corrente_curto_circuito_total' => $correnteCurtoTotal,
            'corrente_maxima_potencia_total' => $correnteTotal,
            'potencia_total' => $potenciaTotal,
            'num_strings_paralelo' => $totalStrings,
            'temperatura_minima' => $tempMin,
            'temperatura_operacao' => $tempOperacao,
            'modulo_referencia' => [
                'id' => $moduloReferencia->id,
                'modelo' => $moduloReferencia->modelo,
                'imp_media' => $impMedia,
                'vmp_media' => $vmpMedia
            ]
        ];
    }

    /**
     * Calcula Voc a frio
     */
    private function calcularVocFrio(Modulo $modulo, int $ns, float $tempMin)
    {
        $deltaT = $tempMin - 25;
        $coeficiente = $modulo->coef_temp_voc / 100;
        
        return $modulo->voc * $ns * (1 + $coeficiente * $deltaT);
    }

    /**
     * Calcula Vmp em operação
     */
    private function calcularVmpOperacao(Modulo $modulo, int $ns, float $tempOperacao)
    {
        $deltaT = $tempOperacao - 25;
        $coeficiente = $modulo->coef_temp_vmp / 100;
        
        return $modulo->vmp * $ns * (1 + $coeficiente * $deltaT);
    }

    /**
     * Calcula temperatura de operação
     */
    private function calcularTemperaturaOperacao($clima, array $configuracoes)
    {
        $tempAmbiente = $configuracoes['temp_ambiente'] ?? $clima->temp_media_anual ?? 25;
        $irradiancia = $configuracoes['irradiancia'] ?? 800;
        $noct = $configuracoes['noct'] ?? 45;
        
        return $tempAmbiente + (($noct - 20) * ($irradiancia / 800));
    }

    /**
     * Valida limites do MPPT
     */
    private function validarLimitesMppt(StringModel $stringReferencia, array $resultados)
    {
        $mppt = $stringReferencia->mppt;
        
        if (!$mppt) {
            return; // Sem MPPT definido, não há o que validar
        }

        // Validar janela MPPT
        $vmpOp = $resultados['tensao_maxima_potencia_operacao'];
        if ($vmpOp < $mppt->tensao_mppt_min || $vmpOp > $mppt->tensao_mppt_max) {
            throw new SolarCalculationException(
                "Tensão de operação ({$vmpOp}V) fora da janela MPPT ({$mppt->tensao_mppt_min}V - {$mppt->tensao_mppt_max}V)",
                [
                    'vmp_operacao' => $vmpOp,
                    'vmppt_min' => $mppt->tensao_mppt_min,
                    'vmppt_max' => $mppt->tensao_mppt_max,
                    'mppt_id' => $mppt->id
                ]
            );
        }

        // Validar corrente máxima
        $correnteTotal = $resultados['corrente_maxima_potencia_total'];
        if ($correnteTotal > $mppt->corrente_entrada_max) {
            throw new SolarCalculationException(
                "Corrente total ({$correnteTotal}A) excede o limite do MPPT ({$mppt->corrente_entrada_max}A)",
                [
                    'corrente_total' => $correnteTotal,
                    'idc_max_mppt' => $mppt->corrente_entrada_max,
                    'mppt_id' => $mppt->id,
                    'num_strings' => $resultados['num_strings_paralelo']
                ]
            );
        }
    }
}
