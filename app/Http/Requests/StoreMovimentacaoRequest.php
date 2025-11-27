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
}
