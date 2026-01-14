<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApiUserPresenter;
use App\Services\InviteCodeService;
use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request, InviteCodeService $inviteCodeService, ReferralService $referralService, ApiUserPresenter $presenter): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('users', 'username')],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'inviteCode' => ['nullable', 'string', 'max:64'],
            'walletAddress' => ['nullable', 'string', 'max:255'],
        ]);

        $user = DB::transaction(function () use ($validated, $inviteCodeService, $referralService) {
            $user = User::query()->create([
                'name' => $validated['name'],
                'username' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'user',
                'status' => true,
                'activation_status' => 'pending',
                'wallet_address' => $validated['walletAddress'] ?? null,
            ]);

            $inviteCodeService->ensureCodesForUser($user, (int) config('mzy.invite_codes_per_user', 5));

            $code = $validated['inviteCode'] ?? null;
            if ($code) {
                $inviteCode = $inviteCodeService->claim($code, $user);
                if ($inviteCode) {
                    $inviter = User::query()->find($inviteCode->owner_user_id);
                    if ($inviter) {
                        $referralService->link($inviter, $user);
                    }
                }
            }

            return $user;
        });

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $presenter->present($user),
        ], 201);
    }

    public function login(Request $request, ApiUserPresenter $presenter): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (! $user->status) {
            return response()->json(['message' => 'Account is inactive'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $presenter->present($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request, ApiUserPresenter $presenter): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json($presenter->present($user));
    }

    public function updateProfile(Request $request, ApiUserPresenter $presenter): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'walletAddress' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $payload = [];
        if (array_key_exists('username', $validated)) {
            $payload['username'] = $validated['username'];
            $payload['name'] = $validated['username'];
        }
        if (array_key_exists('walletAddress', $validated)) {
            $payload['wallet_address'] = $validated['walletAddress'];
        }

        $user->forceFill($payload)->save();

        return response()->json([
            'message' => 'Profile updated',
            'user' => $presenter->present($user),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'oldPassword' => ['required', 'string', 'max:255'],
            'newPassword' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        if (! Hash::check($validated['oldPassword'], $user->password)) {
            return response()->json(['message' => 'Old password is incorrect'], 422);
        }

        $user->forceFill(['password' => Hash::make($validated['newPassword'])])->save();

        return response()->json(['message' => 'Password updated']);
    }
}
