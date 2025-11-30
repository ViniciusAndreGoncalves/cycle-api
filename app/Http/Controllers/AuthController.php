<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // 1. Validar os dados que chegaram do React
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email', // Garante e-mail único
            'password' => 'required|string|confirmed' // Confirma se password == password_confirmation
        ]);

        // 2. Criar o usuário no Banco
        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => Hash::make($fields['password']), // Criptografa a senha
        ]);

        // 3. Criar um Token (Crachá) para ele já entrar logado (Opcional, mas recomendado)
        $token = $user->createToken('myapptoken')->plainTextToken;

        // 4. Responder para o React: "Deu certo!"
        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Usuário cadastrado com sucesso!'
        ], 201);
    }
}