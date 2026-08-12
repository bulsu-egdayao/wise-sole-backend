<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (!$email || !$password) {
            return;
        }

        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Wise Sole Admin',
                'password' => bcrypt($password),
                'role' => 'admin',
            ]
        );
    }
}