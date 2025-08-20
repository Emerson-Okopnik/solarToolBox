<?php

namespace App\Http\Controllers;

use App\Models\Inversor;
use App\Models\Mppt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InversorController extends Controller
{
    /**
     * Lista todos os inversores
     */
    public function index(Request $request)
    {
        $query = Inversor::with('fabricante');

        // Filtros
        if ($request->filled('fabricante_id')) {
            $query->where('fabricante_id', $request->get('fabricante_id'));
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->get('tipo'));
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
            $query->where('potencia_ac_nominal', '>=', $request->get('potencia_min'));
        }

        if ($request->filled('potencia_max')) {
            $query->where('potencia_ac_nominal', '<=', $request->get('potencia_max'));
        }

        $sortBy = $request->get('sort_by', 'modelo');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $inversores = $query->paginate($perPage);

        return $this->successResponse($inversores);
    }

    /**
     * Cria novo inversor
     */
    public function store(Request $request)
    {
        $request->validate([
            'fabricante_id' => 'required|exists:fabricantes,id',
            'modelo' => 'required|string|max:255',
            'tipo' => 'required|in:string,central,micro,otimizador',
            'potencia_dc_max' => 'required|numeric|min:100|max:1000000',
            'tensao_dc_max' => 'required|numeric|min:100|max:2000',
            'tensao_dc_min' => 'required|numeric|min:50|max:1000',
            'corrente_dc_max' => 'required|numeric|min:1|max:1000',
            'num_mppts' => 'required|integer|min:1|max:20',
            'potencia_ac_nominal' => 'required|numeric|min:100|max:1000000',
            'tensao_ac_nominal' => 'required|numeric|min:100|max:1000',
            'corrente_ac_max' => 'required|numeric|min:1|max:1000',
            'frequencia_nominal' => 'required|numeric|min:50|max:60',
            'eficiencia_max' => 'required|numeric|min:80|max:100',
            'temp_operacao_min' => 'required|numeric|min:-50|max:0',
            'temp_operacao_max' => 'required|numeric|min:40|max:100',
            'altitude_max' => 'nullable|numeric|min:0|max:10000',
            'umidade_max' => 'nullable|numeric|min:0|max:100',
            'ativo' => 'boolean',
            // Dados dos MPPTs
            'mppts' => 'required|array|min:1',
            'mppts.*.numero' => 'required|integer|min:1',
            'mppts.*.tensao_mppt_min' => 'required|numeric|min:50|max:1000',
            'mppts.*.tensao_mppt_max' => 'required|numeric|min:100|max:1500',
            'mppts.*.corrente_entrada_max' => 'required|numeric|min:1|max:100',
            'mppts.*.strings_max' => 'required|integer|min:1|max:50',
        ]);

        // Verificar unicidade do modelo por fabricante
        $exists = Inversor::where('fabricante_id', $request->fabricante_id)
                          ->where('modelo', $request->modelo)
                          ->exists();

        if ($exists) {
            return $this->validationErrorResponse([
                'modelo' => ['Este modelo já existe para o fabricante selecionado.']
            ]);
        }

        // Validar número de MPPTs
        if (count($request->mppts) != $request->num_mppts) {
            return $this->validationErrorResponse([
                'mppts' => ['O número de MPPTs deve corresponder ao valor informado.']
            ]);
        }

        DB::beginTransaction();
        try {
            // Criar inversor
            $inversor = Inversor::create($request->except('mppts'));

            // Criar MPPTs
            foreach ($request->mppts as $mpptData) {
                $inversor->mppts()->create($mpptData);
            }

            DB::commit();

            $inversor->load(['fabricante', 'mppts']);
            return $this->successResponse($inversor, 'Inversor criado com sucesso', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Erro ao criar inversor: ' . $e->getMessage());
        }
    }

    /**
     * Exibe inversor específico
     */
    public function show(Inversor $inversor)
    {
        $inversor->load(['fabricante', 'mppts' => function($query) {
            $query->orderBy('numero');
        }]);

        return $this->successResponse($inversor);
    }

    /**
     * Atualiza inversor
     */
    public function update(Request $request, Inversor $inversor)
    {
        $request->validate([
            'fabricante_id' => 'required|exists:fabricantes,id',
            'modelo' => 'required|string|max:255',
            'tipo' => 'required|in:string,central,micro,otimizador',
            'potencia_dc_max' => 'required|numeric|min:100|max:1000000',
            'tensao_dc_max' => 'required|numeric|min:100|max:2000',
            'tensao_dc_min' => 'required|numeric|min:50|max:1000',
            'corrente_dc_max' => 'required|numeric|min:1|max:1000',
            'num_mppts' => 'required|integer|min:1|max:20',
            'potencia_ac_nominal' => 'required|numeric|min:100|max:1000000',
            'tensao_ac_nominal' => 'required|numeric|min:100|max:1000',
            'corrente_ac_max' => 'required|numeric|min:1|max:1000',
            'frequencia_nominal' => 'required|numeric|min:50|max:60',
            'eficiencia_max' => 'required|numeric|min:80|max:100',
            'temp_operacao_min' => 'required|numeric|min:-50|max:0',
            'temp_operacao_max' => 'required|numeric|min:40|max:100',
            'altitude_max' => 'nullable|numeric|min:0|max:10000',
            'umidade_max' => 'nullable|numeric|min:0|max:100',
            'ativo' => 'boolean',
            'mppts' => 'required|array|min:1',
            'mppts.*.numero' => 'required|integer|min:1',
            'mppts.*.tensao_mppt_min' => 'required|numeric|min:50|max:1000',
            'mppts.*.tensao_mppt_max' => 'required|numeric|min:100|max:1500',
            'mppts.*.corrente_entrada_max' => 'required|numeric|min:1|max:100',
            'mppts.*.strings_max' => 'required|integer|min:1|max:50',
        ]);

        // Verificar unicidade do modelo por fabricante (exceto o atual)
        $exists = Inversor::where('fabricante_id', $request->fabricante_id)
                          ->where('modelo', $request->modelo)
                          ->where('id', '!=', $inversor->id)
                          ->exists();

        if ($exists) {
            return $this->validationErrorResponse([
                'modelo' => ['Este modelo já existe para o fabricante selecionado.']
            ]);
        }

        if (count($request->mppts) != $request->num_mppts) {
            return $this->validationErrorResponse([
                'mppts' => ['O número de MPPTs deve corresponder ao valor informado.']
            ]);
        }

        DB::beginTransaction();
        try {
            // Atualizar inversor
            $inversor->update($request->except('mppts'));

            // Remover MPPTs antigos e criar novos
            $inversor->mppts()->delete();
            foreach ($request->mppts as $mpptData) {
                $inversor->mppts()->create($mpptData);
            }

            DB::commit();

            $inversor->load(['fabricante', 'mppts']);
            return $this->successResponse($inversor, 'Inversor atualizado com sucesso');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Erro ao atualizar inversor: ' . $e->getMessage());
        }
    }

    /**
     * Remove inversor
     */
    public function destroy(Inversor $inversor)
    {
        // Verificar se há arranjos usando este inversor
        if ($inversor->arranjos()->exists()) {
            return $this->errorResponse(
                'Não é possível excluir inversor que está sendo usado em arranjos',
                400
            );
        }

        DB::beginTransaction();
        try {
            $inversor->mppts()->delete();
            $inversor->delete();
            DB::commit();

            return $this->successResponse(null, 'Inversor excluído com sucesso');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Erro ao excluir inversor: ' . $e->getMessage());
        }
    }

    /**
     * Retorna MPPTs de um inversor
     */
    public function getMppts(Inversor $inversor)
    {
        $mppts = $inversor->mppts()->orderBy('numero')->get();
        return $this->successResponse($mppts);
    }
}
