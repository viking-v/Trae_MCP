<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InviteCode;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function inviteCodes(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $codes = InviteCode::query()
            ->where('owner_user_id', $user->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn (InviteCode $c) => [
                'code' => $c->code,
                'usedByUserId' => $c->used_by_user_id,
                'usedAt' => $c->used_at?->toISOString(),
            ]);

        return response()->json(['items' => $codes]);
    }

    public function teamSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $maxDepth = (int) config('mzy.max_level_depth', 7);
        $directCount = Referral::query()->where('inviter_id', $user->id)->count();

        [$teamSize, $maxFoundDepth] = $this->computeTeamStats($user->id, $maxDepth);

        return response()->json([
            'directCount' => $directCount,
            'teamSize' => $teamSize,
            'maxDepth' => $maxFoundDepth,
        ]);
    }

    public function teamTree(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $maxDepth = (int) config('mzy.max_level_depth', 7);
        $tree = $this->buildTree($user->id, $maxDepth);

        return response()->json(['tree' => $tree]);
    }

    private function computeTeamStats(int $rootUserId, int $maxDepth): array
    {
        $visited = [];
        $queue = [[$rootUserId, 0]];
        $teamSize = 0;
        $maxFoundDepth = 0;

        while ($queue !== []) {
            [$userId, $depth] = array_shift($queue);
            if (isset($visited[$userId])) {
                continue;
            }
            $visited[$userId] = true;

            if ($depth >= $maxDepth) {
                continue;
            }

            $children = Referral::query()
                ->where('inviter_id', $userId)
                ->pluck('invitee_id')
                ->all();

            foreach ($children as $childId) {
                if (! isset($visited[$childId])) {
                    $teamSize++;
                    $maxFoundDepth = max($maxFoundDepth, $depth + 1);
                    $queue[] = [$childId, $depth + 1];
                }
            }
        }

        return [$teamSize, $maxFoundDepth];
    }

    private function buildTree(int $userId, int $remainingDepth): array
    {
        $user = User::query()->find($userId);
        if (! $user) {
            return [];
        }

        $node = [
            'id' => $user->id,
            'username' => $user->username ?: $user->name,
            'activationStatus' => $user->activation_status,
            'createdAt' => $user->created_at?->toISOString(),
            'children' => [],
        ];

        if ($remainingDepth <= 0) {
            return $node;
        }

        $childrenIds = Referral::query()
            ->where('inviter_id', $user->id)
            ->orderBy('id')
            ->pluck('invitee_id')
            ->all();

        foreach ($childrenIds as $childId) {
            $node['children'][] = $this->buildTree((int) $childId, $remainingDepth - 1);
        }

        return $node;
    }
}
