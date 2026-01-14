<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ActivationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $items = Activation::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get()
            ->map(function (Activation $activation) {
                return [
                    'id' => $activation->id,
                    'amount' => (float) $activation->amount,
                    'txHash' => $activation->tx_hash,
                    'voucherUrl' => $activation->voucher_path ? Storage::disk('public')->url($activation->voucher_path) : null,
                    'status' => $activation->status,
                    'createdAt' => $activation->created_at?->toISOString(),
                    'updatedAt' => $activation->updated_at?->toISOString(),
                ];
            });

        return response()->json(['items' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'txHash' => ['nullable', 'string', 'max:255'],
            'voucher' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif', 'max:5120'],
        ]);

        $voucherPath = null;
        if ($request->hasFile('voucher')) {
            $voucherPath = $request->file('voucher')->store('vouchers', 'public');
        }

        $activation = Activation::query()->create([
            'user_id' => $user->id,
            'amount' => (float) config('mzy.activation_amount', 300),
            'tx_hash' => $validated['txHash'] ?? null,
            'voucher_path' => $voucherPath,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Activation submitted',
            'activation' => [
                'id' => $activation->id,
                'amount' => (float) $activation->amount,
                'txHash' => $activation->tx_hash,
                'voucherUrl' => $activation->voucher_path ? Storage::disk('public')->url($activation->voucher_path) : null,
                'status' => $activation->status,
                'createdAt' => $activation->created_at?->toISOString(),
                'updatedAt' => $activation->updated_at?->toISOString(),
            ],
        ], 201);
    }
}
