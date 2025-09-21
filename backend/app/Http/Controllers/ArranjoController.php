<?php

namespace App\Http\Controllers;

use App\Models\Arranjo;
use App\Models\Projeto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ArranjoController extends Controller
{
    // Lista arranjos de um projeto
    public function index(Projeto $projeto)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user || ($projeto->user_id !== $user->id && !$user->isEngineer())) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $arranjos = $projeto->arranjos()
            ->with(['inversor.fabricante', 'inversor.mppts', 'strings.modulo.fabricante', 'strings.mppt'])
            ->orderBy('nome')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $arranjos,
        ]);
    }

    // Cria novo arranjo
    public function store(Request $request, Projeto $projeto)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user || ($projeto->user_id !== $user->id && !$user->isEngineer())) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $request->validate([
            'nome' => 'required|string|max:255',
            'inversor_id' => 'required|exists:inversores,id',
            'descricao' => 'nullable|string|max:1000',
            'fator_sombreamento' => 'nullable|numeric|min:0|max:1',
        ]);

        $arranjo = $projeto->arranjos()->create([
            'nome' => $request->nome,
            'inversor_id' => $request->inversor_id,
            'descricao' => $request->descricao,
            'fator_sombreamento' => $request->fator_sombreamento ?? 1.0,
        ]);

        $arranjo->load(['inversor.fabricante', 'inversor.mppts', 'strings.modulo.fabricante', 'strings.mppt']);

        return response()->json([
            'success' => true,
            'message' => 'Arranjo criado com sucesso',
            'data' => $arranjo,
        ], 201);
    }

    // Exibe arranjo específico
    public function show(Arranjo $arranjo)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (
            !$user ||
            ($arranjo->projeto->user_id !== $user->id && !$user->isEngineer())
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $arranjo->load([
            'projeto',
            'inversor.fabricante',
            'inversor.mppts',
            'strings.mppt',
        ]);

        return response()->json([
            'success' => true,
            'data' => $arranjo,
        ]);
    }

    // Atualiza arranjo
    public function update(Request $request, Arranjo $arranjo)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (
            !$user ||
            ($arranjo->projeto->user_id !== $user->id && !$user->isEngineer())
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $request->validate([
            'nome' => 'required|string|max:255',
            'inversor_id' => 'required|exists:inversores,id',
            'descricao' => 'nullable|string|max:1000',
            'fator_sombreamento' => 'nullable|numeric|min:0|max:1',
        ]);

        $arranjo->update([
            'nome' => $request->nome,
            'inversor_id' => $request->inversor_id,
            'descricao' => $request->input('descricao', $arranjo->descricao),
            'fator_sombreamento' => $request->has('fator_sombreamento')
                ? ($request->fator_sombreamento ?? 1.0)
                : $arranjo->fator_sombreamento,
        ]);
        $arranjo->load(['inversor.fabricante', 'inversor.mppts', 'strings.modulo.fabricante', 'strings.mppt']);

        return response()->json([
            'success' => true,
            'message' => 'Arranjo atualizado com sucesso',
            'data' => $arranjo,
        ]);
    }

    // Remove arranjo
    public function destroy(Arranjo $arranjo)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (
            !$user ||
            ($arranjo->projeto->user_id !== $user->id && !$user->isAdmin())
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        if ($arranjo->strings()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir arranjo com strings configuradas',
            ], 400);
        }

        $arranjo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Arranjo excluído com sucesso',
        ]);
    }
}
