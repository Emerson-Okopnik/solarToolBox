<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;


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

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        $token = $user->createToken('solar-toolbox')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'token' => $token,
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
            'role' => 'user', // Role padrão
        ]);

        $token = $user->createToken('solar-toolbox')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'token' => $token,
        ], 'Usuário registrado com sucesso', 201);
    }

    /**
     * Logout do usuário
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logout realizado com sucesso');
    }
}
