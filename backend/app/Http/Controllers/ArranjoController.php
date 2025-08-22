<?php

namespace App\Http\Controllers;

use App\Models\Arranjo;
use App\Models\Projeto;
use App\Controllers\AuthController;

use Illuminate\Http\Request;

class ArranjoController extends Controller
{
    //Lista arranjos de um projeto

    public function index(Projeto $projeto)
    {
        // Verificar acesso ao projeto
        if ($projeto->user_id !== auth()->id() && !auth()->user()->isEngineer()) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $arranjos = $projeto->arranjos()
                            ->with(['modulo.fabricante', 'inversor.fabricante', 'strings'])
                            ->orderBy('nome')
                            ->get();

        return response()->json([
            'success' => true,
            'data' => $arranjos,
        ]);
    }

    //Cria novo arranjo

    public function store(Request $request, Projeto $projeto)
    {
        // Verificar acesso ao projeto
        if ($projeto->user_id !== auth()->id() && !auth()->user()->isEngineer()) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $request->validate([
            'nome' => 'required|string|max:255',
            'modulo_id' => 'required|exists:modulos,id',
            'inversor_id' => 'required|exists:inversores,id',
            'azimute' => 'required|numeric|min:0|max:360',
            'inclinacao' => 'required|numeric|min:0|max:90',
            'descricao' => 'nullable|string|max:1000',
            'fator_sombreamento' => 'nullable|numeric|min:0|max:1',
        ]);

        $arranjo = $projeto->arranjos()->create([
            'nome' => $request->nome,
            'modulo_id' => $request->modulo_id,
            'inversor_id' => $request->inversor_id,
            'azimute' => $request->azimute,
            'inclinacao' => $request->inclinacao,
            'descricao' => $request->descricao,
            'fator_sombreamento' => $request->fator_sombreamento ?? 1.0,
        ]);

        $arranjo->load(['modulo.fabricante', 'inversor.fabricante']);

        return response()->json([
            'success' => true,
            'message' => 'Arranjo criado com sucesso',
            'data' => $arranjo,
        ], 201);
    }

    //Exibe arranjo específico

    public function show(Arranjo $arranjo)
    {
        // Verificar acesso ao projeto
        if ($arranjo->projeto->user_id !== auth()->id() && !auth()->user()->isEngineer()) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $arranjo->load([
            'projeto',
            'modulo.fabricante',
            'inversor.fabricante',
            'inversor.mppts',
            'strings.mppt',
        ]);

        return response()->json([
            'success' => true,
            'data' => $arranjo,
        ]);
    }

    //Atualiza arranjo

    public function update(Request $request, Arranjo $arranjo)
    {
        // Verificar acesso ao projeto
        if ($arranjo->projeto->user_id !== auth()->id() && !auth()->user()->isEngineer()) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $request->validate([
            'nome' => 'required|string|max:255',
            'modulo_id' => 'required|exists:modulos,id',
            'inversor_id' => 'required|exists:inversores,id',
            'azimute' => 'required|numeric|min:0|max:360',
            'inclinacao' => 'required|numeric|min:0|max:90',
            'descricao' => 'nullable|string|max:1000',
            'fator_sombreamento' => 'nullable|numeric|min:0|max:1',
        ]);

        $arranjo->update($request->all());
        $arranjo->load(['modulo.fabricante', 'inversor.fabricante']);

        return response()->json([
            'success' => true,
            'message' => 'Arranjo atualizado com sucesso',
            'data' => $arranjo,
        ]);
    }

    //Remove arranjo

    public function destroy(Arranjo $arranjo)
    {
        // Verificar acesso ao projeto
        if ($arranjo->projeto->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        // Verificar se há strings
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
