<?php

namespace App\Http\Controllers;

use App\Services\PeopleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PeopleController extends Controller
{
    private PeopleService $peopleService;

    public function __construct(PeopleService $peopleService) {
        $this->peopleService = $peopleService;
    }

    public function index(): JsonResponse {
        $people = $this->peopleService->findAll();
        return response()->json($people);
    }

    public function store(Request $request): JsonResponse {
        $data = $request->validate([
            'nome' => 'required|string',
            'cpf' => 'required|string|size:11|unique:people,cpf',
            'tipo' => 'required|string|in:fisica,juridica',
            'telefone' => 'required|string|max:15',
            'email' => 'required|string|email|unique:people,email',
        ]);

        $id = $this->peopleService->create($data);
        return response()->json(['id' => $id], 201);
    }

    public function show(int $id): JsonResponse {
        $person = $this->peopleService->findById($id);

        if (!$person) {
            return response()->json(['message' => 'Pessoa não encontrada'], 404);
        }

        return response()->json($person);
    }

    public function update(Request $request, int $id): JsonResponse {
        $data = $request->validate([
            'nome' => 'sometimes|required|string',
            'cpf' => 'sometimes|required|string|size:11|unique:people,cpf,' . $id,
            'tipo' => 'sometimes|required|string|in:fisica,juridica',
            'telefone' => 'sometimes|required|string|max:15',
            'email' => 'sometimes|required|string|email|unique:people,email,' . $id,
        ]);

        $updated = $this->peopleService->update($id, $data);

        if (!$updated) {
            return response()->json(['message' => 'Pessoa não encontrada ou não atualizada'], 404);
        }

        return response()->json(['message' => 'Pessoa atualizada com sucesso']);
    }

    public function destroy(int $id): JsonResponse {
        $deleted = $this->peopleService->delete($id);

        if (!$deleted) {
            return response()->json(['message' => 'Pessoa não encontrada ou não removida'], 404);
        }

        return response()->json(['message' => 'Pessoa removida com sucesso']);
    }
}
