<?php

namespace App\Services;

use App\Models\InviteCode;
use App\Models\User;
use Illuminate\Support\Str;

class InviteCodeService
{
    public function generateCode(): string
    {
        return 'INV' . strtoupper(Str::random(8));
    }

    public function ensureCodesForUser(User $user, int $count): void
    {
        $existingCount = InviteCode::query()->where('owner_user_id', $user->id)->count();
        $missing = max(0, $count - $existingCount);

        for ($i = 0; $i < $missing; $i++) {
            $this->createUniqueCodeForUser($user);
        }
    }

    public function createUniqueCodeForUser(User $user): InviteCode
    {
        do {
            $code = $this->generateCode();
        } while (InviteCode::query()->where('code', $code)->exists());

        return InviteCode::query()->create([
            'code' => $code,
            'owner_user_id' => $user->id,
        ]);
    }

    public function claim(string $code, User $invitee): ?InviteCode
    {
        $inviteCode = InviteCode::query()
            ->where('code', $code)
            ->whereNull('used_by_user_id')
            ->first();

        if (! $inviteCode) {
            return null;
        }

        if ($inviteCode->owner_user_id === $invitee->id) {
            return null;
        }

        $inviteCode->forceFill([
            'used_by_user_id' => $invitee->id,
            'used_at' => now(),
        ])->save();

        return $inviteCode;
    }
}

