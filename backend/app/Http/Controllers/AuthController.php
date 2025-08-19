<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Login do usuário
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        $user = Auth::user();

        return $this->successResponse([
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ], 'Login realizado com sucesso');
    }

    /**
     * Registro de novo usuário
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company' => $request->company,
            'phone' => $request->phone,
            'role' => 'user',
        ]);

        $token = JWTAuth::fromUser($user);

        return $this->successResponse([
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ], 'Usuário registrado com sucesso', 201);
    }

    /**
     * Logout do usuário
     */
    public function logout(Request $request)
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return $this->successResponse(null, 'Logout realizado com sucesso');
    }

    /**
     * Refresh token
     */
    public function refresh()
    {
        $token = JWTAuth::refresh(JWTAuth::getToken());

        return $this->successResponse([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ], 'Token renovado com sucesso');
    }

    /**
     * Get user profile
     */
    public function me()
    {
        return $this->successResponse(Auth::user(), 'Perfil do usuário');
    }
}
