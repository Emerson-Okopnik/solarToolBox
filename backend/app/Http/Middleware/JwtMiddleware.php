<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;

class JwtMiddleware
{
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'Token não fornecido'], 401);
        }

        try {
            $credentials = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Token inválido: '.$e->getMessage()], 401);
        }

        $user = User::find($credentials->sub);
        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado'], 401);
        }

        // Injetar user na request
        $request->setUserResolver(fn() => $user);

        return $next($request);
    }
}
