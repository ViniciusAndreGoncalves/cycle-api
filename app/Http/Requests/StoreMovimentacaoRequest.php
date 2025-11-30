<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovimentacaoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'carteira_id' => 'sometimes|exists:carteiras,id',
            'ativo_id' => 'sometimes|exists:ativos,id',
            'tipo' => 'sometimes|in:Compra,Venda,Aporte,Resgate',
            'quantidade' => 'sometimes|numeric|gt:0',
            'preco_unitario' => 'sometimes|numeric|gte:0',
            'data_movimentacao' => 'sometimes|date',
        ];
    }

    public function messages(): array
    {
        return [
            'carteira_id.exists' => 'A carteira selecionada não existe no nosso sistema.',
            'ativo_id.exists' => 'O ativo especificado não existe.',
            'tipo.in' => 'Tipo inválido. Escolha entre Compra, Venda, Aporte ou Resgate.',
            'quantidade.numeric' => 'Ei, a quantidade precisa ser um número.',
            'quantidade.gt' => 'Ei, a quantidade precisa ser maior que zero!',
            'preco_unitario.numeric' => 'Ei, o preço unitário deve ser um número.',
            'preco_unitario.gte' => 'O preço unitário deve ser maior ou igual a zero.',
            'data_movimentacao.date' => 'A data da movimentação deve ser uma data válida.',
        ];
    }
}
