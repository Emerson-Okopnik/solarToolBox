<?php

namespace App\Http\Controllers;

use App\Models\Execucao;
use App\Models\Projeto;
use App\Services\CalculationEngineService;
use Illuminate\Http\Request;

class ExecucaoController extends Controller
{
    protected $calculationEngine;

    public function __construct(CalculationEngineService $calculationEngine)
    {
        $this->calculationEngine = $calculationEngine;
    }

    //Lista execuções de um projeto
    public function index(Projeto $projeto)
    {
        // Verificar acesso ao projeto
        if ($projeto->user_id !== auth()->id() && !auth()->user()->isEngineer()) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $execucoes = $projeto->execucoes()
                             ->with('user')
                             ->orderBy('created_at', 'desc')
                             ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $execucoes,
        ]);
    }

    //Executa análise do projeto
    public function executar(Request $request, Projeto $projeto)
    {
        // Verificar acesso ao projeto
        if ($projeto->user_id !== auth()->id() && !auth()->user()->isEngineer()) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        // Validar se projeto tem arranjos e strings
        if ($projeto->arranjos()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Projeto deve ter pelo menos um arranjo configurado',
            ], 400);
        }

        $temStrings = $projeto->arranjos()
                              ->whereHas('strings')
                              ->exists();

        if (!$temStrings) {
            return response()->json([
                'success' => false,
                'message' => 'Projeto deve ter pelo menos uma string configurada',
            ], 400);
        }

        $request->validate([
            'configuracoes' => 'nullable|array',
            'configuracoes.temp_min' => 'nullable|numeric|min:-50|max:10',
            'configuracoes.temp_max' => 'nullable|numeric|min:30|max:80',
            'configuracoes.temp_ambiente' => 'nullable|numeric|min:10|max:50',
            'configuracoes.irradiancia' => 'nullable|numeric|min:200|max:1200',
            'configuracoes.noct' => 'nullable|numeric|min:40|max:50',
            'configuracoes.limite_compatibilidade' => 'nullable|numeric|min:1|max:20',
            'configuracoes.permitir_orientacoes_mistas' => 'nullable|boolean',
        ]);

        try {
            $configuracoes = array_merge([
                'temp_min' => $projeto->clima->temp_min_historica ?? -5,
                'temp_max' => $projeto->clima->temp_max_historica ?? 70,
                'temp_ambiente' => $projeto->clima->temp_media_anual ?? 25,
                'irradiancia' => 800,
                'noct' => 45,
                'limite_compatibilidade' => $projeto->limite_compatibilidade_tensao ?? 5.0,
                'permitir_orientacoes_mistas' => false,
            ], $request->get('configuracoes', []));

            $execucao = $this->calculationEngine->executarAnalise($projeto, $configuracoes);

            return response()->json([
                'success' => true,
                'message' => 'Análise executada com sucesso',
                'data' => $execucao,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao executar análise: ' . $e->getMessage(),
                'details' => method_exists($e, 'getDetails') ? $e->getDetails() : [],
            ], 400);
        }
    }

    //Exibe resultado de uma execução
    public function show(Execucao $execucao)
    {
        // Verificar acesso ao projeto
        if ($execucao->projeto->user_id !== auth()->id() && !auth()->user()->isEngineer()) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $execucao->load([
            'projeto',
            'user',
            'checagens' => function ($query) {
                $query->orderBy('tipo')->orderBy('resultado', 'desc');
            },
            'recomendacoes' => function ($query) {
                $query->orderBy('prioridade')->orderBy('categoria');
            },
        ]);

        // Estatísticas detalhadas
        $estatisticas = [
            'resumo_checagens' => [
                'total' => $execucao->checagens->count(),
                'aprovadas' => $execucao->checagens->where('resultado', 'aprovado')->count(),
                'reprovadas' => $execucao->checagens->where('resultado', 'reprovado')->count(),
                'avisos' => $execucao->checagens->where('resultado', 'aviso')->count(),
            ],
            'checagens_por_tipo' => $execucao->checagens->groupBy('tipo')->map->count(),
            'recomendacoes_por_prioridade' => $execucao->recomendacoes->groupBy('prioridade')->map->count(),
            'recomendacoes_por_categoria' => $execucao->recomendacoes->groupBy('categoria')->map->count(),
            'tempo_execucao' => $execucao->concluida_em
                ? $execucao->concluida_em->diffInSeconds($execucao->iniciada_em)
                : null,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'execucao' => $execucao,
                'estatisticas' => $estatisticas,
            ],
        ]);
    }
}
