<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('THUNDERPOINT_ADMIN_EMAIL');
        $password = env('THUNDERPOINT_ADMIN_PASSWORD');

        if (blank($email) || blank($password)) {
            return;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => env('THUNDERPOINT_ADMIN_NAME', 'Thunderpoint Admin'),
                'password' => Hash::make($password),
                'site_role' => 'admin',
                'approved_at' => now(),
                'email_verified_at' => now(),
            ]
        );
    }
}