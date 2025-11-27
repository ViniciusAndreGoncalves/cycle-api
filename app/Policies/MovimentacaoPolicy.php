<?php

namespace App\Policies;

use App\Models\Movimentacao;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MovimentacaoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Apenas retorna TRUE se a carteira da movimentação for do usuário
     */
    public function view(User $user, Movimentacao $movimentacao): bool
    {
        return $movimentacao->carteira->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Movimentacao $movimentacao): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Movimentacao $movimentacao): bool
    {
        return $movimentacao->carteira->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Movimentacao $movimentacao): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Movimentacao $movimentacao): bool
    {
        return false;
    }
}
