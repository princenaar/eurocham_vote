<?php

namespace Database\Seeders;

use App\Models\Election;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Admin back-office account. Override the dev password via ADMIN_PASSWORD in .env.
        User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@eurocham.sn')],
            [
                'name' => 'Administrateur EUROCHAM',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'eurocham2026')),
            ],
        );

        // Ensure the single configurable scrutin exists (window closed by default).
        Election::current();
    }
}
