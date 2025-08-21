<?php

namespace App\Http\Controllers;

use App\Models\Fabricante;
use Illuminate\Http\Request;

class FabricanteController extends Controller
{
    //Lista todos os fabricantes
    public function index(Request $request)
    {
        $query = Fabricante::query();

        // Filtros
        if ($request->filled('ativo')) {
            $query->where('ativo', $request->boolean('ativo'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('pais', 'like', "%{$search}%");
            });
        }

        // Ordenação
        $sortBy = $request->get('sort_by', 'nome');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginação
        $perPage = (int) $request->get('per_page', 15);
        $fabricantes = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $fabricantes,
        ]);
    }

    //Cria novo fabricante
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255|unique:fabricantes',
            'pais' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'descricao' => 'nullable|string|max:1000',
            'ativo' => 'boolean',
        ]);

        $fabricante = Fabricante::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Fabricante criado com sucesso',
            'data' => $fabricante,
        ], 201);
    }

    //Exibe fabricante específico
    public function show(Fabricante $fabricante)
    {
        $fabricante->load([
            'modulos' => function ($query) {
                $query->where('ativo', true)->orderBy('modelo');
            },
            'inversores' => function ($query) {
                $query->where('ativo', true)->orderBy('modelo');
            },
        ]);

        return response()->json([
            'success' => true,
            'data' => $fabricante,
        ]);
    }

    //Atualiza fabricante
    public function update(Request $request, Fabricante $fabricante)
    {
        $request->validate([
            'nome' => 'required|string|max:255|unique:fabricantes,nome,' . $fabricante->id,
            'pais' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'descricao' => 'nullable|string|max:1000',
            'ativo' => 'boolean',
        ]);

        $fabricante->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Fabricante atualizado com sucesso',
            'data' => $fabricante,
        ]);
    }

    //Remove fabricante
    public function destroy(Fabricante $fabricante)
    {
        // Verificar se há módulos ou inversores associados
        if ($fabricante->modulos()->exists() || $fabricante->inversores()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir fabricante com módulos ou inversores associados',
            ], 400);
        }

        $fabricante->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fabricante excluído com sucesso',
        ]);
    }
}
