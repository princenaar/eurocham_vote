<?php

namespace Database\Seeders;

use App\Models\Election;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL');
        $adminPassword = env('ADMIN_PASSWORD');

        if (! app()->environment('local', 'testing')) {
            if (! $adminEmail || ! $adminPassword) {
                throw new RuntimeException('ADMIN_EMAIL et ADMIN_PASSWORD sont obligatoires hors environnement local.');
            }
        }

        $adminEmail ??= 'admin@eurocham.sn';
        $adminPassword ??= 'eurocham2026';

        // Admin back-office account. Production must provide ADMIN_PASSWORD in .env.
        User::query()->updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Administrateur EUROCHAM',
                'password' => Hash::make($adminPassword),
            ],
        );

        // Ensure the single configurable scrutin exists (window closed by default).
        Election::current();

        $this->call(CandidateSeeder::class);
    }
}
