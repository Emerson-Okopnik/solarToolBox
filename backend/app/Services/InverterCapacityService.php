<?php

namespace App\Services;

use App\Models\Inversor;
use App\Models\Arranjo;
use App\Exceptions\SolarCalculationException;
use Illuminate\Support\Collection;

class InverterCapacityService
{
    /**
     * Valida capacidade técnica do inversor por MPPT
     */
    public function validarCapacidade(Inversor $inversor, Collection $arranjos, array $configuracoes = [])
    {
        $resultados = [
            'inversor_id' => $inversor->id,
            'status_geral' => 'aprovado',
            'mppts' => [],
            'resumo' => [
                'potencia_dc_total' => 0,
                'potencia_ac_disponivel' => $inversor->potencia_ac_nominal,
                'utilizacao_percentual' => 0,
                'mppts_utilizados' => 0,
                'mppts_disponiveis' => $inversor->num_mppts
            ]
        ];

        // Validar cada MPPT
        foreach ($inversor->mppts as $mppt) {
            $stringsDoMppt = $this->getStringsPorMppt($arranjos, $mppt->id);
            $resultadoMppt = $this->validarMppt($mppt, $stringsDoMppt, $configuracoes);
            
            $resultados['mppts'][$mppt->id] = $resultadoMppt;
            $resultados['resumo']['potencia_dc_total'] += $resultadoMppt['potencia_total'];
            
            if ($resultadoMppt['status'] !== 'aprovado') {
                $resultados['status_geral'] = 'reprovado';
            }
            
            if ($stringsDoMppt->isNotEmpty()) {
                $resultados['resumo']['mppts_utilizados']++;
            }
        }

        // Calcular utilização percentual
        if ($inversor->potencia_ac_nominal > 0) {
            $resultados['resumo']['utilizacao_percentual'] = 
                ($resultados['resumo']['potencia_dc_total'] / $inversor->potencia_ac_nominal) * 100;
        }

        // Validações globais do inversor
        $this->validarLimitesGlobais($inversor, $resultados);

        return $resultados;
    }

    /**
     * Valida um MPPT específico
     */
    private function validarMppt($mppt, Collection $strings, array $configuracoes)
    {
        $resultado = [
            'mppt_id' => $mppt->id,
            'numero' => $mppt->numero,
            'status' => 'aprovado',
            'strings_conectadas' => $strings->count(),
            'strings_max' => $mppt->strings_max,
            'tensao_operacao' => null,
            'corrente_total' => 0,
            'potencia_total' => 0,
            'validacoes' => []
        ];

        if ($strings->isEmpty()) {
            return $resultado;
        }

        // Calcular parâmetros agregados
        $tensaoOperacao = $this->calcularTensaoOperacao($strings, $configuracoes);
        $correnteTotal = $strings->sum(function($string) {
            return $string->corrente_maxima_potencia * $string->num_strings_paralelo;
        });
        $potenciaTotal = $tensaoOperacao * $correnteTotal;

        $resultado['tensao_operacao'] = $tensaoOperacao;
        $resultado['corrente_total'] = $correnteTotal;
        $resultado['potencia_total'] = $potenciaTotal;

        // Validar janela MPPT
        $validacaoTensao = $this->validarJanelaMppt($mppt, $tensaoOperacao);
        $resultado['validacoes']['janela_mppt'] = $validacaoTensao;
        
        if (!$validacaoTensao['aprovado']) {
            $resultado['status'] = 'reprovado';
        }

        // Validar corrente máxima
        $validacaoCorrente = $this->validarCorrenteMppt($mppt, $correnteTotal);
        $resultado['validacoes']['corrente_maxima'] = $validacaoCorrente;
        
        if (!$validacaoCorrente['aprovado']) {
            $resultado['status'] = 'reprovado';
        }

        // Validar número de strings
        $validacaoStrings = $this->validarNumeroStrings($mppt, $strings->count());
        $resultado['validacoes']['numero_strings'] = $validacaoStrings;
        
        if (!$validacaoStrings['aprovado']) {
            $resultado['status'] = 'reprovado';
        }

        return $resultado;
    }

    /**
     * Calcula tensão de operação média das strings
     */
    private function calcularTensaoOperacao(Collection $strings, array $configuracoes)
    {
        if ($strings->isEmpty()) {
            return 0;
        }

        // Usar a tensão da primeira string como referência
        // (assumindo que strings no mesmo MPPT têm tensões similares)
        $stringReferencia = $strings->first();
        return $stringReferencia->tensao_maxima_potencia ?? 0;
    }

    /**
     * Valida janela MPPT
     */
    private function validarJanelaMppt($mppt, $tensaoOperacao)
    {
        $aprovado = $tensaoOperacao >= $mppt->tensao_mppt_min && 
                   $tensaoOperacao <= $mppt->tensao_mppt_max;

        return [
            'aprovado' => $aprovado,
            'tensao_operacao' => $tensaoOperacao,
            'tensao_min' => $mppt->tensao_mppt_min,
            'tensao_max' => $mppt->tensao_mppt_max,
            'margem_inferior' => $tensaoOperacao - $mppt->tensao_mppt_min,
            'margem_superior' => $mppt->tensao_mppt_max - $tensaoOperacao,
            'mensagem' => $aprovado ? 
                'Tensão dentro da janela MPPT' : 
                'Tensão fora da janela MPPT'
        ];
    }

    /**
     * Valida corrente máxima do MPPT
     */
    private function validarCorrenteMppt($mppt, $correnteTotal)
    {
        $aprovado = $correnteTotal <= $mppt->corrente_entrada_max;
        $utilizacao = ($correnteTotal / $mppt->corrente_entrada_max) * 100;

        return [
            'aprovado' => $aprovado,
            'corrente_total' => $correnteTotal,
            'corrente_max' => $mppt->corrente_entrada_max,
            'utilizacao_percentual' => $utilizacao,
            'margem_disponivel' => $mppt->corrente_entrada_max - $correnteTotal,
            'mensagem' => $aprovado ? 
                'Corrente dentro do limite' : 
                'Corrente excede o limite do MPPT'
        ];
    }

    /**
     * Valida número de strings conectadas
     */
    private function validarNumeroStrings($mppt, $numStrings)
    {
        $aprovado = $numStrings <= $mppt->strings_max;

        return [
            'aprovado' => $aprovado,
            'strings_conectadas' => $numStrings,
            'strings_max' => $mppt->strings_max,
            'strings_disponiveis' => $mppt->strings_max - $numStrings,
            'mensagem' => $aprovado ? 
                'Número de strings dentro do limite' : 
                'Muitas strings conectadas ao MPPT'
        ];
    }

    /**
     * Valida limites globais do inversor
     */
    private function validarLimitesGlobais(Inversor $inversor, array &$resultados)
    {
        $potenciaDcTotal = $resultados['resumo']['potencia_dc_total'];
        
        // Validar potência DC máxima
        if ($potenciaDcTotal > $inversor->potencia_dc_max) {
            $resultados['status_geral'] = 'reprovado';
            $resultados['validacoes_globais']['potencia_dc'] = [
                'aprovado' => false,
                'potencia_total' => $potenciaDcTotal,
                'potencia_max' => $inversor->potencia_dc_max,
                'excesso' => $potenciaDcTotal - $inversor->potencia_dc_max,
                'mensagem' => 'Potência DC total excede o limite do inversor'
            ];
        } else {
            $resultados['validacoes_globais']['potencia_dc'] = [
                'aprovado' => true,
                'potencia_total' => $potenciaDcTotal,
                'potencia_max' => $inversor->potencia_dc_max,
                'margem_disponivel' => $inversor->potencia_dc_max - $potenciaDcTotal,
                'mensagem' => 'Potência DC dentro do limite'
            ];
        }

        // Validar dimensionamento (oversizing)
        $oversizing = ($potenciaDcTotal / $inversor->potencia_ac_nominal) * 100;
        $oversize_recomendado_min = 110; // 110%
        $oversize_recomendado_max = 130; // 130%

        if ($oversizing < $oversize_recomendado_min) {
            $resultados['validacoes_globais']['dimensionamento'] = [
                'status' => 'aviso',
                'oversizing_percentual' => $oversizing,
                'recomendado_min' => $oversize_recomendado_min,
                'recomendado_max' => $oversize_recomendado_max,
                'mensagem' => 'Dimensionamento abaixo do recomendado (subdimensionado)'
            ];
        } elseif ($oversizing > $oversize_recomendado_max) {
            $resultados['validacoes_globais']['dimensionamento'] = [
                'status' => 'aviso',
                'oversizing_percentual' => $oversizing,
                'recomendado_min' => $oversize_recomendado_min,
                'recomendado_max' => $oversize_recomendado_max,
                'mensagem' => 'Dimensionamento acima do recomendado (sobredimensionado)'
            ];
        } else {
            $resultados['validacoes_globais']['dimensionamento'] = [
                'status' => 'aprovado',
                'oversizing_percentual' => $oversizing,
                'recomendado_min' => $oversize_recomendado_min,
                'recomendado_max' => $oversize_recomendado_max,
                'mensagem' => 'Dimensionamento adequado'
            ];
        }
    }

    /**
     * Obtém strings por MPPT
     */
    private function getStringsPorMppt(Collection $arranjos, $mpptId)
    {
        return $arranjos->flatMap(function($arranjo) use ($mpptId) {
            return $arranjo->strings->where('mppt_id', $mpptId);
        });
    }
}
