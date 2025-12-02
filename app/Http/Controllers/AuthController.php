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

        // 3. Criar um Token para ele já entrar logado (Opcional, mas recomendado)
        $token = $user->createToken('myapptoken')->plainTextToken;

        // 4. Responder para o React: "Deu certo!"
        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Usuário cadastrado com sucesso!'
        ], 201);
    }

    public function login(Request $request)
    {
        // 1. Validar inputs
        $fields = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        // 2. Tentar encontrar o usuário pelo e-mail
        $user = User::where('email', $fields['email'])->first();

        // 3. Verificar se o usuário existe E se a senha bate
        // Hash::check compara a senha digitada ('123') com a criptografada no banco ('$2y$10$...')
        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response()->json([
                'message' => 'E-mail ou senha incorretos.'
            ], 401); // 401 = Não autorizado
        }

        // 4. Se passou, cria um Novo Token
        // $user->tokens()->delete(); 
        
        $token = $user->createToken('myapptoken')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Login realizado com sucesso!'
        ], 200);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        // 1. Validar os dados que chegaram do React
        $fields = $request->validate([
            'name' => 'sometimes|required|string',
            'email' => 'sometimes|required|string|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|string|min:6|confirmed'
        ]);

        $user->name = $fields['name'];
        $user->email = $fields['email'];

        if (!empty($fields['password'])) {
            $user->password = Hash::make($fields['password']);
        }

        $user->save();        

        return response()->json([
            'user' => $user,
            'message' => 'Usuário atualizado com sucesso!'
        ], 200);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete(); // Revoga todos os tokens do usuário
        $user->delete(); // Deleta o usuário

        return response()->json([
            'message' => 'Logout realizado com sucesso!'
        ], 200);

    }
}