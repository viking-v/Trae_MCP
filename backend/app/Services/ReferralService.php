<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\User;

class ReferralService
{
    public function link(User $inviter, User $invitee): void
    {
        Referral::query()->firstOrCreate(
            ['invitee_id' => $invitee->id],
            ['inviter_id' => $inviter->id, 'invitee_id' => $invitee->id],
        );
    }

    public function getUpline(User $user, int $maxDepth): array
    {
        $uplines = [];
        $current = $user;

        for ($i = 0; $i < $maxDepth; $i++) {
            $referral = Referral::query()->where('invitee_id', $current->id)->first();
            if (! $referral) {
                break;
            }

            $inviter = User::query()->find($referral->inviter_id);
            if (! $inviter) {
                break;
            }

            $uplines[] = $inviter;
            $current = $inviter;
        }

        return $uplines;
    }
}

