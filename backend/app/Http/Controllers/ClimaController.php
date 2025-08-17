<?php

namespace App\Http\Controllers;

use App\Models\Clima;
use Illuminate\Http\Request;

class ClimaController extends Controller
{
    /**
     * Lista todos os climas
     */
    public function index(Request $request)
    {
        $query = Clima::query();

        // Filtros
        if ($request->filled('ativo')) {
            $query->where('ativo', $request->boolean('ativo'));
        }

        if ($request->filled('pais')) {
            $query->where('pais', $request->get('pais'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->get('estado'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('cidade', 'like', "%{$search}%")
                  ->orWhere('estado', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'nome');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $climas = $query->paginate($perPage);

        return $this->successResponse($climas);
    }

    /**
     * Cria novo clima
     */
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'cidade' => 'required|string|max:255',
            'estado' => 'required|string|max:100',
            'pais' => 'required|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'altitude' => 'nullable|numeric|min:0|max:10000',
            'temp_min_historica' => 'required|numeric|min:-50|max:50',
            'temp_max_historica' => 'required|numeric|min:0|max:60',
            'temp_media_anual' => 'required|numeric|min:-20|max:50',
            'irradiacao_global_horizontal' => 'required|numeric|min:1|max:10',
            'irradiacao_direta_normal' => 'nullable|numeric|min:1|max:15',
            'ativo' => 'boolean',
        ]);

        $clima = Clima::create($request->all());

        return $this->successResponse($clima, 'Clima criado com sucesso', 201);
    }

    /**
     * Exibe clima específico
     */
    public function show(Clima $clima)
    {
        return $this->successResponse($clima);
    }

    /**
     * Atualiza clima
     */
    public function update(Request $request, Clima $clima)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'cidade' => 'required|string|max:255',
            'estado' => 'required|string|max:100',
            'pais' => 'required|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'altitude' => 'nullable|numeric|min:0|max:10000',
            'temp_min_historica' => 'required|numeric|min:-50|max:50',
            'temp_max_historica' => 'required|numeric|min:0|max:60',
            'temp_media_anual' => 'required|numeric|min:-20|max:50',
            'irradiacao_global_horizontal' => 'required|numeric|min:1|max:10',
            'irradiacao_direta_normal' => 'nullable|numeric|min:1|max:15',
            'ativo' => 'boolean',
        ]);

        $clima->update($request->all());

        return $this->successResponse($clima, 'Clima atualizado com sucesso');
    }

    /**
     * Remove clima
     */
    public function destroy(Clima $clima)
    {
        // Verificar se há projetos usando este clima
        if ($clima->projetos()->exists()) {
            return $this->errorResponse(
                'Não é possível excluir clima que está sendo usado em projetos',
                400
            );
        }

        $clima->delete();

        return $this->successResponse(null, 'Clima excluído com sucesso');
    }
}
