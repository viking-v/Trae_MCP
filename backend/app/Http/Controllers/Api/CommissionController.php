<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $items = Commission::query()
            ->with(['fromUser:id,username,name', 'toUser:id,username,name'])
            ->where('to_user_id', $user->id)
            ->orderByDesc('id')
            ->get()
            ->map(function (Commission $c) {
                return [
                    'id' => $c->id,
                    'fromUserId' => $c->from_user_id,
                    'toUserId' => $c->to_user_id,
                    'fromUsername' => $c->fromUser?->username ?: $c->fromUser?->name,
                    'toUsername' => $c->toUser?->username ?: $c->toUser?->name,
                    'level' => (int) $c->level,
                    'rate' => (float) $c->rate,
                    'amount' => (float) $c->amount,
                    'createdAt' => $c->created_at?->toISOString(),
                ];
            });

        $total = (float) Commission::query()->where('to_user_id', $user->id)->sum('amount');

        return response()->json([
            'total' => $total,
            'items' => $items,
        ]);
    }
}
