<?php

namespace App\Services;

use App\Models\Projeto;
use App\Models\Execucao;
use App\Models\Checagem;
use App\Exceptions\SolarCalculationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculationEngineService
{
    protected $seriesCalculator;
    protected $parallelCalculator;
    protected $inverterCapacityService;
    protected $distributionService;

    public function __construct(
        SeriesCalculatorService $seriesCalculator,
        ParallelCalculatorService $parallelCalculator,
        InverterCapacityService $inverterCapacityService,
        DistributionService $distributionService
    ) {
        $this->seriesCalculator = $seriesCalculator;
        $this->parallelCalculator = $parallelCalculator;
        $this->inverterCapacityService = $inverterCapacityService;
        $this->distributionService = $distributionService;
    }

    /**
     * Executa análise completa do projeto
     */
    public function executarAnalise(Projeto $projeto, array $configuracoes = [])
    {
        // Criar execução
        $execucao = $this->criarExecucao($projeto, $configuracoes);

        try {
            DB::beginTransaction();

            // 1. Calcular parâmetros das strings
            $this->calcularParametrosStrings($execucao, $configuracoes);

            // 2. Distribuir strings por MPPT
            $this->distribuirStringsPorMppt($execucao, $configuracoes);

            // 3. Validar capacidade dos inversores
            $this->validarCapacidadeInversores($execucao, $configuracoes);

            // 4. Gerar checagens
            $this->gerarChecagens($execucao, $configuracoes);

            // Finalizar execução
            $this->finalizarExecucao($execucao, 'concluida');

            DB::commit();

            return $execucao->fresh('checagens');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro na execução da análise', [
                'projeto_id' => $projeto->id,
                'execucao_id' => $execucao->id,
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->finalizarExecucao($execucao, 'erro');
            throw $e;
        }
    }

    /**
     * Cria nova execução
     */
    private function criarExecucao(Projeto $projeto, array $configuracoes)
    {
        return Execucao::create([
            'projeto_id' => $projeto->id,
            'user_id' => auth()->id(),
            'status' => 'executando',
            'iniciada_em' => now(),
            'configuracoes' => $configuracoes
        ]);
    }

    /**
     * Calcula parâmetros elétricos de todas as strings
     */
    private function calcularParametrosStrings(Execucao $execucao, array $configuracoes)
    {
        $projeto = $execucao->projeto;
        
        foreach ($projeto->arranjos as $arranjo) {
            foreach ($arranjo->strings as $string) {
                try {
                    if ($string->tipo_conexao === 'serie') {
                        $resultados = $this->seriesCalculator->calcular($string, $configuracoes);
                    } else {
                        // Para paralelo, agrupar strings do mesmo arranjo
                        $stringsParalelo = $arranjo->strings->where('tipo_conexao', 'paralelo');
                        $resultados = $this->parallelCalculator->calcular($stringsParalelo, $configuracoes);
                    }

                    // Atualizar string com resultados
                    $string->update([
                        'tensao_circuito_aberto' => $resultados['tensao_circuito_aberto_frio'] ?? null,
                        'tensao_maxima_potencia' => $resultados['tensao_maxima_potencia_operacao'] ?? null,
                        'corrente_curto_circuito' => $resultados['corrente_curto_circuito'] ?? $resultados['corrente_curto_circuito_total'] ?? null,
                        'corrente_maxima_potencia' => $resultados['corrente_maxima_potencia'] ?? $resultados['corrente_maxima_potencia_total'] ?? null,
                        'potencia_total' => $resultados['potencia_total'] ?? null
                    ]);

                    // Criar checagem de compatibilidade
                    $this->criarChecagemCompatibilidade($execucao, $string, $resultados);

                } catch (SolarCalculationException $e) {
                    // Criar checagem reprovada
                    $this->criarChecagemReprovada($execucao, $string, $e);
                }
            }
        }
    }

    /**
     * Distribui strings por MPPT
     */
    private function distribuirStringsPorMppt(Execucao $execucao, array $configuracoes)
    {
        $projeto = $execucao->projeto;
        
        // Agrupar por inversor
        $arranjosPorInversor = $projeto->arranjos->groupBy('inversor_id');
        
        foreach ($arranjosPorInversor as $inversorId => $arranjos) {
            $inversor = $arranjos->first()->inversor;
            
            try {
                $distribuicao = $this->distributionService->distribuir($inversor, $arranjos, $configuracoes);
                
                // Criar checagem de distribuição
                $this->criarChecagemDistribuicao($execucao, $inversor, $distribuicao);
                
            } catch (SolarCalculationException $e) {
                // Criar checagem reprovada para distribuição
                $this->criarChecagemReprovada($execucao, null, $e, 'distribuicao_orientacao');
            }
        }
    }

    /**
     * Valida capacidade dos inversores
     */
    private function validarCapacidadeInversores(Execucao $execucao, array $configuracoes)
    {
        $projeto = $execucao->projeto;
        
        $arranjosPorInversor = $projeto->arranjos->groupBy('inversor_id');
        
        foreach ($arranjosPorInversor as $inversorId => $arranjos) {
            $inversor = $arranjos->first()->inversor;
            
            try {
                $resultados = $this->inverterCapacityService->validarCapacidade($inversor, $arranjos, $configuracoes);
                
                // Criar checagens de capacidade
                $this->criarChecagensCapacidade($execucao, $inversor, $resultados);
                
            } catch (SolarCalculationException $e) {
                $this->criarChecagemReprovada($execucao, null, $e, 'capacidade_mppt');
            }
        }
    }

    /**
     * Atualiza estatísticas de checagens
     */
    private function gerarChecagens(Execucao $execucao, array $configuracoes)
    {
        $totalChecagens = $execucao->checagens()->count();
        $checagensAprovadas = $execucao->checagens()->where('resultado', 'aprovado')->count();
        $checagensReprovadas = $execucao->checagens()->where('resultado', 'reprovado')->count();

        $execucao->update([
            'total_checagens' => $totalChecagens,
            'checagens_aprovadas' => $checagensAprovadas,
            'checagens_reprovadas' => $checagensReprovadas,
        ]);
    }

    /**
     * Cria checagem de compatibilidade
     */
    private function criarChecagemCompatibilidade(Execucao $execucao, $string, array $resultados)
    {
        Checagem::create([
            'execucao_id' => $execucao->id,
            'string_id' => $string->id,
            'arranjo_id' => $string->arranjo_id,
            'tipo' => 'compatibilidade_modulos',
            'resultado' => 'aprovado',
            'titulo' => 'Compatibilidade de Módulos',
            'descricao' => 'Módulos compatíveis para conexão em série/paralelo',
            'valores_calculados' => $resultados,
            'limites_referencia' => [
                'limite_compatibilidade' => $string->arranjo->projeto->limite_compatibilidade_tensao
            ]
        ]);
    }

    /**
     * Cria checagem reprovada
     */
    private function criarChecagemReprovada(Execucao $execucao, $string, SolarCalculationException $e, $tipo = null)
    {
        $tipo = $tipo ?? 'compatibilidade_modulos';
        
        Checagem::create([
            'execucao_id' => $execucao->id,
            'string_id' => $string?->id,
            'arranjo_id' => $string?->arranjo_id,
            'tipo' => $tipo,
            'resultado' => 'reprovado',
            'titulo' => 'Erro de Compatibilidade',
            'descricao' => $e->getMessage(),
            'valores_calculados' => $e->getDetails(),
            'limites_referencia' => []
        ]);
    }

    /**
     * Cria checagem de distribuição
     */
    private function criarChecagemDistribuicao(Execucao $execucao, $inversor, array $distribuicao)
    {
        $resultado = empty($distribuicao['avisos']) ? 'aprovado' : 'aviso';
        
        Checagem::create([
            'execucao_id' => $execucao->id,
            'tipo' => 'distribuicao_orientacao',
            'resultado' => $resultado,
            'titulo' => 'Distribuição por Orientação',
            'descricao' => 'Análise da distribuição de strings por MPPT considerando orientações',
            'valores_calculados' => $distribuicao,
            'limites_referencia' => [
                'num_mppts' => $inversor->num_mppts
            ]
        ]);

    }

    /**
     * Cria checagens de capacidade
     */
    private function criarChecagensCapacidade(Execucao $execucao, $inversor, array $resultados)
    {
        // Checagem geral do inversor
        Checagem::create([
            'execucao_id' => $execucao->id,
            'tipo' => 'capacidade_mppt',
            'resultado' => $resultados['status_geral'] === 'aprovado' ? 'aprovado' : 'reprovado',
            'titulo' => 'Capacidade do Inversor',
            'descricao' => 'Validação da capacidade técnica do inversor',
            'valores_calculados' => $resultados,
            'limites_referencia' => [
                'potencia_dc_max' => $inversor->potencia_dc_max,
                'num_mppts' => $inversor->num_mppts
            ]
        ]);
    }

    /**
     * Finaliza execução
     */
    private function finalizarExecucao(Execucao $execucao, string $status)
    {
        $execucao->update([
            'status' => $status,
            'concluida_em' => now()
        ]);
    }
}
