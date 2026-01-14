<?php

namespace Tests\Feature;

use App\Models\Activation;
use App\Models\Commission;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivationCommissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_approval_generates_commission_for_inviter(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'activation_status' => 'active',
            'status' => true,
        ]);

        $inviter = User::factory()->create([
            'role' => 'user',
            'activation_status' => 'active',
            'status' => true,
        ]);

        $invitee = User::factory()->create([
            'role' => 'user',
            'activation_status' => 'pending',
            'status' => true,
        ]);

        Referral::query()->create([
            'inviter_id' => $inviter->id,
            'invitee_id' => $invitee->id,
        ]);

        $activation = Activation::query()->create([
            'user_id' => $invitee->id,
            'amount' => 300,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($admin);

        $resp = $this->postJson("/api/admin/activations/{$activation->id}/approve");
        $resp->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $invitee->id,
            'activation_status' => 'active',
        ]);

        $commission = Commission::query()->where('activation_id', $activation->id)->first();
        $this->assertNotNull($commission);
        $this->assertSame($invitee->id, $commission->from_user_id);
        $this->assertSame($inviter->id, $commission->to_user_id);
        $this->assertSame(1, (int) $commission->level);
        $this->assertSame('0.2000', (string) $commission->rate);
        $this->assertSame('60.00', (string) $commission->amount);
    }
}

