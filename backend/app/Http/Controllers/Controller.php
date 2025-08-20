<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Resposta de sucesso padronizada
     */
    protected function successResponse($data = null, $message = 'Operação realizada com sucesso', $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Resposta de erro padronizada
     */
    protected function errorResponse($message = 'Erro interno do servidor', $code = 500, $errors = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $code);
    }

    /**
     * Resposta de validação padronizada
     */
    protected function validationErrorResponse($errors, $message = 'Dados inválidos')
    {
        return $this->errorResponse($message, 422, $errors);
    }
}
