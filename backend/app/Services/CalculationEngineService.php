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
    protected $inverterCapacityService;
    protected $distributionService;

    public function __construct(
        SeriesCalculatorService $seriesCalculator,
        InverterCapacityService $inverterCapacityService,
        DistributionService $distributionService
    ) {
        $this->seriesCalculator = $seriesCalculator;
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

            $execucao->load([
                'projeto.arranjos.strings.modulo',
                'projeto.arranjos.strings.mppt',
                'projeto.arranjos.inversor.mppts',
            ]);

            // 1. Calcular parâmetros das strings
            $this->calcularParametrosStrings($execucao, $configuracoes);

            // 2. Distribuir strings por MPPT
            $this->distribuirStringsPorMppt($execucao, $configuracoes);

            // 3. Validar capacidade dos inversores
            $this->validarCapacidadeInversores($execucao, $configuracoes);

            // 4. Gerar checagens
            $this->gerarChecagens($execucao, $configuracoes);

            $this->atualizarStatusProjeto($execucao);

            // Finalizar execução
            $this->finalizarExecucao($execucao, 'concluida');

            DB::commit();

            return $execucao->fresh([
                'checagens' => function ($query) {
                    $query
                        ->with(['string', 'arranjo'])
                        ->orderBy('tipo')
                        ->orderBy('resultado', 'desc');
                },
            ]);

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
                    if ($string->tipo_conexao !== 'serie') {
                        throw new SolarCalculationException(
                            "Tipo de conexão '{$string->tipo_conexao}' não é suportado para cálculo.",
                            [
                                'string_id' => $string->id,
                                'tipo_conexao' => $string->tipo_conexao,
                            ]
                        );
                    }

                    $resultados = $this->seriesCalculator->calcular($string, $configuracoes);

                    // Atualizar string com resultados
                    $string->update([
                        'tensao_circuito_aberto' => $resultados['tensao_circuito_aberto_frio'] ?? null,
                        'tensao_maxima_potencia' => $resultados['tensao_maxima_potencia_operacao'] ?? null,
                        'corrente_curto_circuito' => $resultados['corrente_curto_circuito'] ?? null,
                        'corrente_maxima_potencia' => $resultados['corrente_maxima_potencia'] ?? null,
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
            
            $strings = $arranjos->flatMap(function ($arranjo) {
                return $arranjo->strings;
            });

            $stringsSemMppt = $strings->filter(function ($string) {
                return $string->mppt_id === null;
            });

            $usaDistribuicaoManual = $stringsSemMppt->isEmpty();
            $configDistribuicao = array_merge($configuracoes, [
                'preencher_somente_vazios' => !$usaDistribuicaoManual,
                'somente_recomendacao' => $usaDistribuicaoManual,
            ]);

            $resumoDistribuicaoManual = $this->montarResumoDistribuicaoManual($arranjos, $inversor);
            $contextoBase = [
                'fonte' => $usaDistribuicaoManual ? 'manual' : 'automatico_fallback',
                'strings_sem_mppt_iniciais' => $stringsSemMppt->count(),
                'distribuicao_manual' => $resumoDistribuicaoManual,
            ];

            try {
                $distribuicao = $this->distributionService->distribuir($inversor, $arranjos, $configDistribuicao);

                $distribuicao['contexto'] = array_merge($contextoBase, $distribuicao['contexto'] ?? []);
                
                // Criar checagem de distribuição
                $this->criarChecagemDistribuicao($execucao, $inversor, $distribuicao);
                
            } catch (SolarCalculationException $e) {
                // Criar checagem reprovada para distribuição
                $this->criarChecagemReprovada($execucao, null, $e, 'distribuicao_orientacao', $contextoBase);
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
     * Atualiza o status do projeto de acordo com os resultados
     */
    private function atualizarStatusProjeto(Execucao $execucao)
    {
        $projeto = $execucao->projeto;

        $checagensReprovadas = $execucao->checagens()->where('resultado', 'reprovado')->count();
        if ($checagensReprovadas > 0) {
            $projeto->update(['status' => 'rejeitado']);
            return;
        }

        $totalChecagens = $execucao->checagens()->count();
        $checagensAprovadas = $execucao->checagens()->where('resultado', 'aprovado')->count();

        if ($totalChecagens > 0 && $totalChecagens === $checagensAprovadas) {
            $projeto->update(['status' => 'aprovado']);
        }
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
            'descricao' => 'Módulos compatíveis para conexão em série',
            'valores_calculados' => $resultados,
            'limites_referencia' => [
                'limite_compatibilidade' => $string->arranjo->projeto->limite_compatibilidade_tensao
            ]
        ]);
    }

    /**
     * Cria checagem reprovada
     */
    private function criarChecagemReprovada(Execucao $execucao, $string, SolarCalculationException $e, $tipo = null, array $contextoExtra = [])
    {
        $tipo = $tipo ?? 'compatibilidade_modulos';

        $detalhes = $e->getDetails();

        if (!empty($contextoExtra)) {
            $detalhes = array_merge($detalhes ?? [], ['contexto' => $contextoExtra]);
        }
        
        Checagem::create([
            'execucao_id' => $execucao->id,
            'string_id' => $string?->id,
            'arranjo_id' => $string?->arranjo_id,
            'tipo' => $tipo,
            'resultado' => 'reprovado',
            'titulo' => 'Erro de Compatibilidade',
            'descricao' => $e->getMessage(),
            'valores_calculados' => $detalhes,
            'limites_referencia' => []
        ]);
    }

    /**
     * Gera resumo da distribuição cadastrada manualmente pelo usuário
     */
    private function montarResumoDistribuicaoManual($arranjos, $inversor)
    {
        $resumo = [
            'total_strings_sem_mppt' => 0,
            'mppts' => [],
        ];

        foreach ($inversor->mppts as $mppt) {
            $resumo['mppts'][$mppt->id] = [
                'mppt_id' => $mppt->id,
                'numero' => $mppt->numero,
                'strings_conectadas' => 0,
                'potencia_total' => 0,
            ];
        }

        foreach ($arranjos as $arranjo) {
            foreach ($arranjo->strings as $string) {
                if ($string->mppt_id !== null && isset($resumo['mppts'][$string->mppt_id])) {
                    $resumo['mppts'][$string->mppt_id]['strings_conectadas']++;
                    $resumo['mppts'][$string->mppt_id]['potencia_total'] += $string->potencia_total ?? 0;
                } else {
                    $resumo['total_strings_sem_mppt']++;
                }
            }
        }

        return $resumo;
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
