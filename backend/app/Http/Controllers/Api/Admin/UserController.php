<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()
            ->where('role', 'user')
            ->orderByDesc('id')
            ->get()
            ->map(function (User $user) {
                $directCount = Referral::query()->where('inviter_id', $user->id)->count();

                return [
                    'id' => $user->id,
                    'username' => $user->username ?: $user->name,
                    'email' => $user->email,
                    'status' => (bool) $user->status,
                    'role' => $user->role,
                    'activationStatus' => $user->activation_status,
                    'walletAddress' => $user->wallet_address,
                    'directCount' => $directCount,
                    'createdAt' => $user->created_at?->toISOString(),
                ];
            });

        return response()->json(['items' => $users]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'walletAddress' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'boolean'],
            'role' => ['sometimes', 'string', 'max:64'],
            'activationStatus' => ['sometimes', 'string', 'max:64'],
        ]);

        $payload = [];
        if (array_key_exists('username', $validated)) {
            $payload['username'] = $validated['username'];
            $payload['name'] = $validated['username'];
        }
        if (array_key_exists('walletAddress', $validated)) {
            $payload['wallet_address'] = $validated['walletAddress'];
        }
        if (array_key_exists('status', $validated)) {
            $payload['status'] = (bool) $validated['status'];
        }
        if (array_key_exists('role', $validated)) {
            $payload['role'] = $validated['role'];
        }
        if (array_key_exists('activationStatus', $validated)) {
            $payload['activation_status'] = $validated['activationStatus'];
        }

        $user->forceFill($payload)->save();

        return response()->json([
            'message' => 'User updated',
            'user' => [
                'id' => $user->id,
                'username' => $user->username ?: $user->name,
                'email' => $user->email,
                'status' => (bool) $user->status,
                'role' => $user->role,
                'activationStatus' => $user->activation_status,
                'walletAddress' => $user->wallet_address,
            ],
        ]);
    }
}
