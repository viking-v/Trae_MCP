<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activation;
use App\Models\AdminLog;
use App\Models\User;
use App\Services\CommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ActivationController extends Controller
{
    public function index(): JsonResponse
    {
        $items = Activation::query()
            ->with('user:id,username,name,email')
            ->orderByDesc('id')
            ->get()
            ->map(function (Activation $a) {
                return [
                    'id' => $a->id,
                    'userId' => $a->user_id,
                    'username' => $a->user?->username ?: $a->user?->name,
                    'email' => $a->user?->email,
                    'amount' => (float) $a->amount,
                    'txHash' => $a->tx_hash,
                    'voucherUrl' => $a->voucher_path ? Storage::disk('public')->url($a->voucher_path) : null,
                    'status' => $a->status,
                    'createdAt' => $a->created_at?->toISOString(),
                    'updatedAt' => $a->updated_at?->toISOString(),
                ];
            });

        return response()->json(['items' => $items]);
    }

    public function approve(Request $request, Activation $activation, CommissionService $commissionService): JsonResponse
    {
        $admin = $request->user();
        if (! $admin) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($activation->status !== 'pending') {
            return response()->json(['message' => 'Activation is not pending'], 409);
        }

        $activation->forceFill([
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ])->save();

        User::query()->where('id', $activation->user_id)->update(['activation_status' => 'active']);

        $commissionService->generateForActivation($activation);

        AdminLog::query()->create([
            'admin_id' => $admin->id,
            'action' => 'approve_activation',
            'target_type' => 'activation',
            'target_id' => (string) $activation->id,
            'details' => [
                'user_id' => $activation->user_id,
                'amount' => (float) $activation->amount,
            ],
        ]);

        return response()->json(['message' => 'Activation approved']);
    }

    public function reject(Request $request, Activation $activation): JsonResponse
    {
        $admin = $request->user();
        if (! $admin) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($activation->status !== 'pending') {
            return response()->json(['message' => 'Activation is not pending'], 409);
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $activation->forceFill([
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'note' => $validated['note'] ?? null,
        ])->save();

        AdminLog::query()->create([
            'admin_id' => $admin->id,
            'action' => 'reject_activation',
            'target_type' => 'activation',
            'target_id' => (string) $activation->id,
            'details' => [
                'user_id' => $activation->user_id,
                'amount' => (float) $activation->amount,
                'note' => $validated['note'] ?? null,
            ],
        ]);

        return response()->json(['message' => 'Activation rejected']);
    }
}
