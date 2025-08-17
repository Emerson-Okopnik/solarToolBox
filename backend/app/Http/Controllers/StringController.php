<?php

namespace App\Http\Controllers;

use App\Models\StringModel;
use App\Models\Arranjo;
use Illuminate\Http\Request;

class StringController extends Controller
{
    /**
     * Lista strings de um arranjo
     */
    public function index(Arranjo $arranjo)
    {
        // Verificar acesso ao projeto
        if ($arranjo->projeto->user_id !== auth()->id() && !auth()->user()->isEngineer()) {
            return $this->errorResponse('Acesso negado', 403);
        }

        $strings = $arranjo->strings()
                          ->with('mppt')
                          ->orderBy('nome')
                          ->get();

        return $this->successResponse($strings);
    }

    /**
     * Cria nova string
     */
    public function store(Request $request, Arranjo $arranjo)
    {
        // Verificar acesso ao projeto
        if ($arranjo->projeto->user_id !== auth()->id() && !auth()->user()->isEngineer()) {
            return $this->errorResponse('Acesso negado', 403);
        }

        $request->validate([
            'nome' => 'required|string|max:255',
            'tipo_conexao' => 'required|in:serie,paralelo',
            'num_modulos_serie' => 'required|integer|min:1|max:50',
            'num_strings_paralelo' => 'required|integer|min:1|max:20',
            'mppt_id' => 'nullable|exists:mppts,id',
        ]);

        // Validar se MPPT pertence ao inversor do arranjo
        if ($request->filled('mppt_id')) {
            $mpptValido = $arranjo->inversor->mppts()
                                           ->where('id', $request->mppt_id)
                                           ->exists();
            if (!$mpptValido) {
                return $this->validationErrorResponse([
                    'mppt_id' => ['MPPT não pertence ao inversor selecionado.']
                ]);
            }
        }

        $totalModulos = $request->num_modulos_serie * $request->num_strings_paralelo;

        $string = $arranjo->strings()->create([
            'nome' => $request->nome,
            'tipo_conexao' => $request->tipo_conexao,
            'num_modulos_serie' => $request->num_modulos_serie,
            'num_strings_paralelo' => $request->num_strings_paralelo,
            'total_modulos' => $totalModulos,
            'mppt_id' => $request->mppt_id,
        ]);

        $string->load('mppt');

        return $this->successResponse($string, 'String criada com sucesso', 201);
    }

    /**
     * Exibe string específica
     */
    public function show(StringModel $string)
    {
        // Verificar acesso ao projeto
        if ($string->arranjo->projeto->user_id !== auth()->id() && !auth()->user()->isEngineer()) {
            return $this->errorResponse('Acesso negado', 403);
        }

        $string->load(['arranjo.modulo.fabricante', 'arranjo.inversor.fabricante', 'mppt']);

        return $this->successResponse($string);
    }

    /**
     * Atualiza string
     */
    public function update(Request $request, StringModel $string)
    {
        // Verificar acesso ao projeto
        if ($string->arranjo->projeto->user_id !== auth()->id() && !auth()->user()->isEngineer()) {
            return $this->errorResponse('Acesso negado', 403);
        }

        $request->validate([
            'nome' => 'required|string|max:255',
            'tipo_conexao' => 'required|in:serie,paralelo',
            'num_modulos_serie' => 'required|integer|min:1|max:50',
            'num_strings_paralelo' => 'required|integer|min:1|max:20',
            'mppt_id' => 'nullable|exists:mppts,id',
        ]);

        // Validar se MPPT pertence ao inversor do arranjo
        if ($request->filled('mppt_id')) {
            $mpptValido = $string->arranjo->inversor->mppts()
                                                   ->where('id', $request->mppt_id)
                                                   ->exists();
            if (!$mpptValido) {
                return $this->validationErrorResponse([
                    'mppt_id' => ['MPPT não pertence ao inversor selecionado.']
                ]);
            }
        }

        $totalModulos = $request->num_modulos_serie * $request->num_strings_paralelo;

        $string->update([
            'nome' => $request->nome,
            'tipo_conexao' => $request->tipo_conexao,
            'num_modulos_serie' => $request->num_modulos_serie,
            'num_strings_paralelo' => $request->num_strings_paralelo,
            'total_modulos' => $totalModulos,
            'mppt_id' => $request->mppt_id,
        ]);

        $string->load('mppt');

        return $this->successResponse($string, 'String atualizada com sucesso');
    }

    /**
     * Remove string
     */
    public function destroy(StringModel $string)
    {
        // Verificar acesso ao projeto
        if ($string->arranjo->projeto->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return $this->errorResponse('Acesso negado', 403);
        }

        $string->delete();

        return $this->successResponse(null, 'String excluída com sucesso');
    }
}
