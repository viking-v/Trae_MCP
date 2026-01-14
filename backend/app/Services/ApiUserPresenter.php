<?php

namespace App\Services;

use App\Models\User;

class ApiUserPresenter
{
    public function present(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username ?: $user->name,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => (bool) $user->status,
            'activationStatus' => $user->activation_status,
            'walletAddress' => $user->wallet_address,
            'createdAt' => $user->created_at?->toISOString(),
        ];
    }
}
