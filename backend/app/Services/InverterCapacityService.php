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
                'mppts_disponiveis' => $inversor->num_mppts,
                'modulos_conectados' => 0,
                'modulos_disponiveis' => 0,
                'modulos_excedentes' => 0,
            ]
        ];

        // Validar cada MPPT
        foreach ($inversor->mppts as $mppt) {
            $stringsDoMppt = $this->getStringsPorMppt($arranjos, $mppt->id);
            $resultadoMppt = $this->validarMppt($mppt, $stringsDoMppt, $configuracoes);
            
            $resultados['mppts'][$mppt->id] = $resultadoMppt;
            $resultados['resumo']['potencia_dc_total'] += $resultadoMppt['potencia_total'];
            $resultados['resumo']['modulos_conectados'] += $resultadoMppt['modulos_conectados'];
            $resultados['resumo']['modulos_disponiveis'] += $resultadoMppt['modulos_disponiveis'];
            $resultados['resumo']['modulos_excedentes'] += $resultadoMppt['modulos_excedentes'];
            
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
        $validacaoStrings = $this->validarNumeroStrings($mppt, $strings);

        $resultado = [
            'mppt_id' => $mppt->id,
            'numero' => $mppt->numero,
            'status' => 'aprovado',
            'strings_conectadas' => $validacaoStrings['strings_conectadas'],
            'strings_max' => $validacaoStrings['strings_max'],
            'modulos_conectados' => $validacaoStrings['modulos_conectados'],
            'strings_disponiveis' => $validacaoStrings['strings_disponiveis'],
            'strings_excedentes' => $validacaoStrings['strings_excedentes'],
            'modulos_disponiveis' => $validacaoStrings['modulos_disponiveis'],
            'modulos_excedentes' => $validacaoStrings['modulos_excedentes'],
            'tensao_operacao' => null,
            'corrente_total' => 0,
            'potencia_total' => 0,
            'validacoes' => [
                'numero_strings' => $validacaoStrings,
            ]
        ];

        if (!$validacaoStrings['aprovado']) {
            $resultado['status'] = 'reprovado';
        }

        if ($strings->isEmpty()) {
            return $resultado;
        }

        $validacaoModulosPorString = $this->validarModulosPorString($strings);
        $resultado['validacoes']['modulos_por_string'] = $validacaoModulosPorString;

        if (!$validacaoModulosPorString['aprovado']) {
            $resultado['status'] = 'reprovado';
        }

        $potenciasNormalizadas = [];
        $stringsPorPotencia = [];
        $stringsSemPotencia = [];

        foreach ($strings->values() as $indice => $string) {
            $potenciaNominal = null;

            if (isset($string->modulo) && isset($string->modulo->potencia_nominal)) {
                $potenciaNominal = (float) $string->modulo->potencia_nominal;
            } elseif (isset($string->potencia_total) && $string->potencia_total !== null) {
                $stringsParalelo = (float) ($string->num_strings_paralelo ?? 1);
                $stringsParalelo = $stringsParalelo > 0 ? $stringsParalelo : 1;

                $potenciaNominal = (float) $string->potencia_total / $stringsParalelo;
            }

            $identificadorString = null;

            if (isset($string->nome) && $string->nome !== null) {
                $identificadorString = (string) $string->nome;
            } elseif (isset($string->id) && $string->id !== null) {
                $identificadorString = sprintf('string_%s', $string->id);
            } else {
                $identificadorString = sprintf('string_%d', $indice + 1);
            }

            if ($potenciaNominal === null) {
                $stringsSemPotencia[] = $identificadorString;
                continue;
            }

            $potenciaNormalizada = round($potenciaNominal, 2);
            $potenciaChave = number_format($potenciaNormalizada, 2, '.', '');

            $potenciasNormalizadas[] = $potenciaChave;

            if (!array_key_exists($potenciaChave, $stringsPorPotencia)) {
                $stringsPorPotencia[$potenciaChave] = [];
            }

            $stringsPorPotencia[$potenciaChave][] = $identificadorString;
        }

        $potenciasDistintasChave = array_values(array_unique($potenciasNormalizadas));
        sort($potenciasDistintasChave);
        $potenciasDistintas = array_map('floatval', $potenciasDistintasChave);

        $validacaoPotenciaModulo = [
            'aprovado' => true,
            'potencias_distintas' => $potenciasDistintas,
            'strings_por_potencia' => $stringsPorPotencia,
            'strings_sem_potencia' => $stringsSemPotencia,
        ];

        if (count($potenciasDistintas) > 1) {
            $detalhes = [];

            foreach ($stringsPorPotencia as $potencia => $identificadores) {
                $detalhes[] = sprintf(
                    '%s W (%s)',
                    number_format((float) $potencia, 2, ',', '.'),
                    implode(', ', $identificadores)
                );
            }

            $validacaoPotenciaModulo['aprovado'] = false;
            $validacaoPotenciaModulo['mensagem'] = sprintf(
                'Strings conectadas possuem potências nominais distintas: %s.',
                implode('; ', $detalhes)
            );
            $resultado['status'] = 'reprovado';
        } elseif (count($potenciasDistintas) === 1) {
            $validacaoPotenciaModulo['mensagem'] = sprintf(
                'Todas as strings conectadas utilizam módulos de %s W.',
                number_format($potenciasDistintas[0], 2, ',', '.')
            );
        } else {
            $validacaoPotenciaModulo['mensagem'] = 'Não foi possível determinar a potência nominal das strings conectadas.';
        }

        $resultado['validacoes']['potencia_modulo_uniforme'] = $validacaoPotenciaModulo;
        // Calcular parâmetros agregados
        $tensaoOperacao = $this->calcularTensaoOperacao($strings, $configuracoes);
        $correnteTotal = $strings->sum(function($string) {
            $corrente = (float) ($string->corrente_maxima_potencia ?? 0);
            $paralelos = (int) ($string->num_strings_paralelo ?? 0);
            $paralelos = $paralelos > 0 ? $paralelos : 1;

            return $corrente * $paralelos;
        });
        $potenciaTotal = $tensaoOperacao * $correnteTotal;

        $resultado['tensao_operacao'] = $tensaoOperacao;
        $resultado['corrente_total'] = $correnteTotal;
        $resultado['potencia_total'] = $potenciaTotal;

                $tensoesPorString = $strings->map(function ($string) {
            return (float) ($string->tensao_maxima_potencia ?? 0);
        })->values()->all();

        $correntesPorString = $strings->map(function ($string) {
            $corrente = (float) ($string->corrente_maxima_potencia ?? 0);
            $paralelos = (int) ($string->num_strings_paralelo ?? 0);
            $paralelos = $paralelos > 0 ? $paralelos : 1;

            return $corrente * $paralelos;
        })->values()->all();

        $limiteDesbalanceamentoTensao = (float) ($configuracoes['limite_compatibilidade_tensao']
            ?? $configuracoes['limite_compatibilidade']
            ?? 5.0);

        $limiteDesbalanceamentoCorrente = (float) ($configuracoes['limite_compatibilidade_corrente']
            ?? $configuracoes['limite_compatibilidade']
            ?? 5.0);

        $validacaoDesbalanceamentoTensao = $this->validarDesbalanceamento(
            $tensoesPorString,
            $limiteDesbalanceamentoTensao,
            'tensão'
        );

        $validacaoDesbalanceamentoCorrente = $this->validarDesbalanceamento(
            $correntesPorString,
            $limiteDesbalanceamentoCorrente,
            'corrente'
        );

        $resultado['validacoes']['desbalanceamento_tensao'] = $validacaoDesbalanceamentoTensao;
        $resultado['validacoes']['desbalanceamento_corrente'] = $validacaoDesbalanceamentoCorrente;

        if (!$validacaoDesbalanceamentoTensao['aprovado'] || !$validacaoDesbalanceamentoCorrente['aprovado']) {
            $resultado['status'] = 'reprovado';
        }
        
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

        return $resultado;
    }

    /**
     * Valida consistência de módulos por string dentro do MPPT
     */
    private function validarModulosPorString(Collection $strings)
    {
        $distribuicao = [];

        foreach ($strings as $string) {
            $modulosSerie = (int) ($string->num_modulos_serie ?? 0);
            $quantidadeStrings = (int) ($string->num_strings_paralelo ?? 0);
            $quantidadeStrings = $quantidadeStrings > 0 ? $quantidadeStrings : 1;

            if (!array_key_exists($modulosSerie, $distribuicao)) {
                $distribuicao[$modulosSerie] = 0;
            }

            $distribuicao[$modulosSerie] += $quantidadeStrings;
        }

        if (empty($distribuicao)) {
            return [
                'aprovado' => true,
                'valor_esperado' => null,
                'valores_divergentes' => [],
                'strings_ajustar' => 0,
                'strings_por_valor' => [],
                'mensagem' => 'Nenhuma string avaliada para módulos em série',
            ];
        }

        $distribuicaoOrdenada = $distribuicao;
        arsort($distribuicaoOrdenada);

        $valorEsperado = (int) array_key_first($distribuicaoOrdenada);
        $valoresDivergentes = [];
        $stringsAjustar = 0;

        foreach ($distribuicao as $valor => $quantidade) {
            if ((int) $valor === $valorEsperado) {
                continue;
            }

            $valoresDivergentes[] = (int) $valor;
            $stringsAjustar += (int) $quantidade;
        }

        sort($valoresDivergentes);

        $aprovado = empty($valoresDivergentes);
        $stringsPorValor = [];

        foreach ($distribuicao as $valor => $quantidade) {
            $stringsPorValor[(int) $valor] = (int) $quantidade;
        }

        if ($aprovado) {
            $mensagem = sprintf(
                'Todas as strings conectadas possuem %d módulos em série.',
                $valorEsperado
            );
        } else {
            $mensagem = sprintf(
                'Strings com quantidades distintas de módulos em série: esperado %d, divergentes %s. ' .
                'Ajuste necessário em %d string(s).',
                $valorEsperado,
                implode(', ', $valoresDivergentes),
                $stringsAjustar
            );
        }

        return [
            'aprovado' => $aprovado,
            'valor_esperado' => $valorEsperado,
            'valores_divergentes' => $valoresDivergentes,
            'strings_ajustar' => $stringsAjustar,
            'strings_por_valor' => $stringsPorValor,
            'mensagem' => $mensagem,
        ];
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
     * Avalia o desbalanceamento entre os valores informados e compara com o limite permitido.
     */
    private function validarDesbalanceamento(array $valores, float $limitePercentual, string $tipo)
    {
        $analise = $this->calcularDesvioEntreExtremos($valores);

        $desvio = $analise['desvio_percentual'];
        $aprovado = $desvio <= $limitePercentual;
        $excesso = $aprovado ? 0.0 : $desvio - $limitePercentual;

        $analise['aprovado'] = $aprovado;
        $analise['limite_percentual'] = $limitePercentual;
        $analise['excesso_percentual'] = $excesso;
        $analise['mensagem'] = $aprovado
            ? sprintf(
                'Desbalanceamento de %s dentro do limite (desvio %.2f%%, limite %.2f%%).',
                $tipo,
                $desvio,
                $limitePercentual
            )
            : sprintf(
                'Desbalanceamento de %s excede o limite permitido em %.2f%% (desvio %.2f%%, limite %.2f%%).',
                $tipo,
                $excesso,
                $desvio,
                $limitePercentual
            );

        return $analise;
    }

    /**
     * Calcula o desvio percentual entre o menor e o maior valor da lista.
     */
    private function calcularDesvioEntreExtremos(array $valores)
    {
        $valoresFiltrados = array_map('floatval', array_filter($valores, static function ($valor) {
            return $valor !== null && $valor !== '';
        }));

        if (empty($valoresFiltrados)) {
            return [
                'valor_min' => 0.0,
                'valor_max' => 0.0,
                'desvio_percentual' => 0.0,
                'valores' => [],
            ];
        }

        $valorMinimo = min($valoresFiltrados);
        $valorMaximo = max($valoresFiltrados);

        $desvioPercentual = 0.0;

        if (count($valoresFiltrados) > 1 && $valorMaximo > 0) {
            $desvioPercentual = (($valorMaximo - $valorMinimo) / $valorMaximo) * 100;
        }

        return [
            'valor_min' => $valorMinimo,
            'valor_max' => $valorMaximo,
            'desvio_percentual' => $desvioPercentual,
            'valores' => array_values($valoresFiltrados),
        ];
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
     private function validarNumeroStrings($mppt, Collection $strings)
    {
        $stringsMax = (int) ($mppt->strings_max ?? 0);

        $stringsConectadas = (int) $strings->sum(function ($string) {
            $paralelos = (int) ($string->num_strings_paralelo ?? 0);
            return $paralelos > 0 ? $paralelos : 1;
        });

        $modulosConectadosFloat = $strings->reduce(function ($carry, $string) {
            $paralelos = (int) ($string->num_strings_paralelo ?? 0);
            $paralelos = $paralelos > 0 ? $paralelos : 1;

            $modulosSerie = (int) ($string->num_modulos_serie ?? 0);
            $totalInformado = $string->total_modulos;

            if (!is_numeric($totalInformado)) {
                $totalInformado = $modulosSerie * $paralelos;
            }

            $totalInformado = is_numeric($totalInformado) ? (float) $totalInformado : 0.0;

            return $carry + $totalInformado;
        }, 0.0);

        $modulosConectados = (int) round($modulosConectadosFloat);
        $modulosPorStringReferencia = 0.0;

        if ($stringsConectadas > 0) {
            $modulosPorStringReferencia = $modulosConectadosFloat / $stringsConectadas;
        } elseif ($strings->isNotEmpty()) {
            $stringReferencia = $strings->first();
            $paralelosRef = (int) ($stringReferencia->num_strings_paralelo ?? 0);
            $paralelosRef = $paralelosRef > 0 ? $paralelosRef : 1;
            $modulosSerieRef = (int) ($stringReferencia->num_modulos_serie ?? 0);
            $totalRef = $stringReferencia->total_modulos;

            if (!is_numeric($totalRef)) {
                $totalRef = $modulosSerieRef * $paralelosRef;
            }

            $totalRef = is_numeric($totalRef) ? (float) $totalRef : 0.0;

            $modulosPorStringReferencia = $paralelosRef > 0
                ? $totalRef / $paralelosRef
                : $modulosSerieRef;
        }

        $stringsDisponiveis = max(0, $stringsMax - $stringsConectadas);
        $stringsExcedentes = max(0, $stringsConectadas - $stringsMax);

        $modulosDisponiveis = (int) round($stringsDisponiveis * $modulosPorStringReferencia);
        $modulosExcedentes = (int) round($stringsExcedentes * $modulosPorStringReferencia);

        $aprovado = $stringsExcedentes === 0;

        if ($stringsMax <= 0 && $stringsConectadas === 0) {
            $mensagem = 'Nenhuma string conectada ao MPPT';
        } elseif ($aprovado && $stringsDisponiveis > 0) {
            $mensagem = sprintf(
                'Capacidade disponível para %d strings adicionais (aprox. %d módulos).',
                $stringsDisponiveis,
                $modulosDisponiveis
            );
        } elseif ($aprovado) {
            $mensagem = 'Número de strings dentro do limite';
        } else {
            $mensagem = sprintf(
                'Muitas strings conectadas ao MPPT: excede em %d strings (aprox. %d módulos).',
                $stringsExcedentes,
                $modulosExcedentes
            );
        }

        return [
            'aprovado' => $aprovado,
            'strings_conectadas' => $stringsConectadas,
            'strings_max' => $stringsMax,
            'strings_disponiveis' => $stringsDisponiveis,
            'strings_excedentes' => $stringsExcedentes,
            'modulos_conectados' => $modulosConectados,
            'modulos_disponiveis' => $modulosDisponiveis,
            'modulos_excedentes' => $modulosExcedentes,
            'mensagem' => $mensagem,
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
