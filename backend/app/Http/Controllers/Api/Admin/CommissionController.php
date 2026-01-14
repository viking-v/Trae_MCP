<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function index(): JsonResponse
    {
        $items = Commission::query()
            ->with(['fromUser:id,username,name', 'toUser:id,username,name'])
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->map(fn (Commission $c) => [
                'id' => $c->id,
                'fromUserId' => $c->from_user_id,
                'toUserId' => $c->to_user_id,
                'fromUsername' => $c->fromUser?->username ?: $c->fromUser?->name,
                'toUsername' => $c->toUser?->username ?: $c->toUser?->name,
                'level' => (int) $c->level,
                'rate' => (float) $c->rate,
                'amount' => (float) $c->amount,
                'createdAt' => $c->created_at?->toISOString(),
            ]);

        return response()->json(['items' => $items]);
    }
}
