<?php

namespace App\Http\Controllers;

use App\Models\Modulo;
use Illuminate\Http\Request;

class ModuloController extends Controller
{
    /**
     * Lista todos os módulos
     */
    public function index(Request $request)
    {
        $query = Modulo::with('fabricante');

        // Filtros
        if ($request->filled('fabricante_id')) {
            $query->where('fabricante_id', $request->get('fabricante_id'));
        }

        if ($request->filled('tecnologia')) {
            $query->where('tecnologia', $request->get('tecnologia'));
        }

        if ($request->filled('ativo')) {
            $query->where('ativo', $request->boolean('ativo'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('modelo', 'like', "%{$search}%")
                  ->orWhereHas('fabricante', function($fab) use ($search) {
                      $fab->where('nome', 'like', "%{$search}%");
                  });
            });
        }

        // Filtros de potência
        if ($request->filled('potencia_min')) {
            $query->where('potencia_nominal', '>=', $request->get('potencia_min'));
        }

        if ($request->filled('potencia_max')) {
            $query->where('potencia_nominal', '<=', $request->get('potencia_max'));
        }

        // Ordenação
        $sortBy = $request->get('sort_by', 'modelo');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $modulos = $query->paginate($perPage);

        return $this->successResponse($modulos);
    }

    /**
     * Cria novo módulo
     */
    public function store(Request $request)
    {
        $request->validate([
            'fabricante_id' => 'required|exists:fabricantes,id',
            'modelo' => 'required|string|max:255',
            'tecnologia' => 'required|in:mono,poly,thin-film',
            'potencia_nominal' => 'required|numeric|min:1|max:1000',
            'voc' => 'required|numeric|min:1|max:100',
            'vmp' => 'required|numeric|min:1|max:100',
            'isc' => 'required|numeric|min:0.1|max:50',
            'imp' => 'required|numeric|min:0.1|max:50',
            'coef_temp_voc' => 'required|numeric|min:-1|max:0',
            'coef_temp_vmp' => 'required|numeric|min:-1|max:0',
            'coef_temp_isc' => 'required|numeric|min:0|max:1',
            'coef_temp_imp' => 'required|numeric|min:-1|max:1',
            'coef_temp_potencia' => 'required|numeric|min:-1|max:0',
            'comprimento' => 'required|numeric|min:100|max:3000',
            'largura' => 'required|numeric|min:100|max:3000',
            'espessura' => 'required|numeric|min:1|max:100',
            'peso' => 'required|numeric|min:1|max:100',
            'temp_operacao_min' => 'required|numeric|min:-50|max:0',
            'temp_operacao_max' => 'required|numeric|min:50|max:100',
            'tensao_maxima_sistema' => 'required|numeric|min:100|max:2000',
            'ativo' => 'boolean',
        ]);

        // Verificar unicidade do modelo por fabricante
        $exists = Modulo::where('fabricante_id', $request->fabricante_id)
                        ->where('modelo', $request->modelo)
                        ->exists();

        if ($exists) {
            return $this->validationErrorResponse([
                'modelo' => ['Este modelo já existe para o fabricante selecionado.']
            ]);
        }

        $modulo = Modulo::create($request->all());
        $modulo->load('fabricante');

        return $this->successResponse($modulo, 'Módulo criado com sucesso', 201);
    }

    /**
     * Exibe módulo específico
     */
    public function show(Modulo $modulo)
    {
        $modulo->load('fabricante');
        return $this->successResponse($modulo);
    }

    /**
     * Atualiza módulo
     */
    public function update(Request $request, Modulo $modulo)
    {
        $request->validate([
            'fabricante_id' => 'required|exists:fabricantes,id',
            'modelo' => 'required|string|max:255',
            'tecnologia' => 'required|in:mono,poly,thin-film',
            'potencia_nominal' => 'required|numeric|min:1|max:1000',
            'voc' => 'required|numeric|min:1|max:100',
            'vmp' => 'required|numeric|min:1|max:100',
            'isc' => 'required|numeric|min:0.1|max:50',
            'imp' => 'required|numeric|min:0.1|max:50',
            'coef_temp_voc' => 'required|numeric|min:-1|max:0',
            'coef_temp_vmp' => 'required|numeric|min:-1|max:0',
            'coef_temp_isc' => 'required|numeric|min:0|max:1',
            'coef_temp_imp' => 'required|numeric|min:-1|max:1',
            'coef_temp_potencia' => 'required|numeric|min:-1|max:0',
            'comprimento' => 'required|numeric|min:100|max:3000',
            'largura' => 'required|numeric|min:100|max:3000',
            'espessura' => 'required|numeric|min:1|max:100',
            'peso' => 'required|numeric|min:1|max:100',
            'temp_operacao_min' => 'required|numeric|min:-50|max:0',
            'temp_operacao_max' => 'required|numeric|min:50|max:100',
            'tensao_maxima_sistema' => 'required|numeric|min:100|max:2000',
            'ativo' => 'boolean',
        ]);

        // Verificar unicidade do modelo por fabricante (exceto o atual)
        $exists = Modulo::where('fabricante_id', $request->fabricante_id)
                        ->where('modelo', $request->modelo)
                        ->where('id', '!=', $modulo->id)
                        ->exists();

        if ($exists) {
            return $this->validationErrorResponse([
                'modelo' => ['Este modelo já existe para o fabricante selecionado.']
            ]);
        }

        $modulo->update($request->all());
        $modulo->load('fabricante');

        return $this->successResponse($modulo, 'Módulo atualizado com sucesso');
    }

    /**
     * Remove módulo
     */
    public function destroy(Modulo $modulo)
    {
        // Verificar se há arranjos usando este módulo
        if ($modulo->arranjos()->exists()) {
            return $this->errorResponse(
                'Não é possível excluir módulo que está sendo usado em arranjos',
                400
            );
        }

        $modulo->delete();

        return $this->successResponse(null, 'Módulo excluído com sucesso');
    }
}
