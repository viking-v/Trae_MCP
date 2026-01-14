<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Services\InviteCodeService;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'username' => 'admin',
                'password' => Hash::make('admin123456'),
                'role' => 'admin',
                'status' => true,
                'activation_status' => 'active',
                'wallet_address' => null,
            ]
        );

        app(InviteCodeService::class)->ensureCodesForUser($admin, (int) config('mzy.invite_codes_per_user', 5));
    }
}
