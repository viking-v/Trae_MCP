<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activation;
use App\Models\AdminLog;
use App\Models\Commission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $totalUsers = User::query()->where('role', 'user')->count();
        $activeUsers = User::query()->where('role', 'user')->where('activation_status', 'active')->count();
        $pendingActivations = Activation::query()->where('status', 'pending')->count();
        $totalCommissions = (float) Commission::query()->sum('amount');

        $recentLogs = AdminLog::query()
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn (AdminLog $log) => [
                'id' => $log->id,
                'adminId' => $log->admin_id,
                'action' => $log->action,
                'targetType' => $log->target_type,
                'targetId' => $log->target_id,
                'details' => $log->details,
                'createdAt' => $log->created_at?->toISOString(),
            ]);

        return response()->json([
            'metrics' => [
                'totalUsers' => $totalUsers,
                'activeUsers' => $activeUsers,
                'pendingActivations' => $pendingActivations,
                'totalCommissions' => $totalCommissions,
            ],
            'recentLogs' => $recentLogs,
        ]);
    }
}
