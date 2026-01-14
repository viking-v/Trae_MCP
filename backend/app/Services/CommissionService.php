<?php

namespace App\Services;

use App\Models\Activation;
use App\Models\Commission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    public function generateForActivation(Activation $activation): void
    {
        $user = $activation->user()->first();
        if (! $user) {
            return;
        }

        $rates = config('mzy.profit_rates', []);
        $maxDepth = (int) config('mzy.max_level_depth', 7);
        $amount = (float) $activation->amount;

        $referralService = app(ReferralService::class);
        $uplines = $referralService->getUpline($user, $maxDepth);

        DB::transaction(function () use ($activation, $uplines, $rates, $amount) {
            foreach ($uplines as $index => $upline) {
                $rate = $rates[$index] ?? null;
                if ($rate === null) {
                    break;
                }

                $commissionAmount = round($amount * (float) $rate, 2);

                Commission::query()->create([
                    'activation_id' => $activation->id,
                    'from_user_id' => $activation->user_id,
                    'to_user_id' => $upline->id,
                    'level' => $index + 1,
                    'rate' => $rate,
                    'amount' => $commissionAmount,
                ]);
            }
        });
    }
}

