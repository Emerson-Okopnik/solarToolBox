<?php

namespace App\Http\Controllers;

use App\Models\Projeto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjetoController extends Controller
{
    // Lista projetos do usuário
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user(); // ou Auth::guard('api')->user()

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $query = Projeto::with(['clima', 'user'])
            ->where('user_id', $user->id);

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('cliente', 'like', "%{$search}%");
            });
        }

        // Ordenação (opcional: valide colunas/ordem para evitar valores inválidos)
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginação
        $perPage = (int) $request->get('per_page', 15);
        $projetos = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $projetos,
        ]);
    }

    // Cria novo projeto
    public function store(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $request->validate([
            'nome' => 'required|string|max:255',
            'cliente' => 'required|string|max:255',
            'clima_id' => 'required|exists:climas,id',
            'descricao' => 'nullable|string|max:2000',
            'endereco' => 'nullable|string|max:500',
            'limite_compatibilidade_tensao' => 'nullable|numeric|min:1|max:20',
            'limite_compatibilidade_corrente' => 'nullable|numeric|min:1|max:20',
        ]);

        $projeto = Projeto::create([
            'user_id' => $user->id,
            'nome' => $request->nome,
            'cliente' => $request->cliente,
            'clima_id' => $request->clima_id,
            'descricao' => $request->descricao,
            'endereco' => $request->endereco,
            'limite_compatibilidade_tensao' => $request->limite_compatibilidade_tensao ?? 5.0,
            'limite_compatibilidade_corrente' => $request->limite_compatibilidade_corrente ?? 5.0,
            'status' => 'rascunho',
        ]);

        $projeto->load(['clima', 'user']);

        return response()->json([
            'success' => true,
            'message' => 'Projeto criado com sucesso',
            'data' => $projeto,
        ], 201);
    }

    // Exibe projeto específico
    public function show(Projeto $projeto)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // Verificar acesso ao projeto
        if (!$user || ($projeto->user_id !== $user->id && !$user->isEngineer())) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $projeto->load([
            'clima',
            'user',
            'arranjos.strings.modulo.fabricante',
            'arranjos.inversor.fabricante',
            'arranjos.inversor.mppts',
            'arranjos.strings.mppt',
            'execucoes' => function ($query) {
                $query
                    ->latest()
                    ->limit(5)
                    ->with([
                        'checagens' => function ($checagensQuery) {
                            $checagensQuery
                                ->with(['string', 'arranjo'])
                                ->orderBy('tipo')
                                ->orderBy('resultado', 'desc');
                        },
                    ]);
            },
        ]);

        // Estatísticas do projeto
        $estatisticas = [
            'total_arranjos' => $projeto->arranjos->count(),
            'total_strings' => $projeto->arranjos->sum(fn ($arranjo) => $arranjo->strings->count()),
            'potencia_total' => $projeto->arranjos->sum(fn ($arranjo) => $arranjo->strings->sum('potencia_total')),
            'ultima_execucao' => $projeto->execucoes->first()?->created_at,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'projeto' => $projeto,
                'estatisticas' => $estatisticas,
            ],
        ]);
    }

    // Atualiza projeto
    public function update(Request $request, Projeto $projeto)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // Verificar acesso ao projeto
        if (!$user || ($projeto->user_id !== $user->id && !$user->isEngineer())) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $request->validate([
            'nome' => 'required|string|max:255',
            'cliente' => 'required|string|max:255',
            'clima_id' => 'required|exists:climas,id',
            'descricao' => 'nullable|string|max:2000',
            'endereco' => 'nullable|string|max:500',
            'status' => 'in:rascunho,em_analise,aprovado,rejeitado',
            'limite_compatibilidade_tensao' => 'nullable|numeric|min:1|max:20',
            'limite_compatibilidade_corrente' => 'nullable|numeric|min:1|max:20',
        ]);

        $projeto->update($request->all());
        $projeto->load(['clima', 'user']);

        return response()->json([
            'success' => true,
            'message' => 'Projeto atualizado com sucesso',
            'data' => $projeto,
        ]);
    }

    // Remove projeto
    public function destroy(Projeto $projeto)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // Verificar se o usuário tem acesso ao projeto
        if (!$user || ($projeto->user_id !== $user->id && !$user->isAdmin())) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        // Verificar se há execuções
        if ($projeto->execucoes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir projeto com execuções. Considere alterar o status.',
            ], 400);
        }

        $projeto->delete();

        return response()->json([
            'success' => true,
            'message' => 'Projeto excluído com sucesso',
        ]);
    }
}
