<?php

use Database\Seeders\EurochamE2ESeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('e2e:prepare', function () {
    $connection = config('database.default');
    $database = config('database.connections.sqlite.database');
    $expectedRoot = realpath(storage_path('framework/testing')) ?: storage_path('framework/testing');

    if ($connection !== 'sqlite') {
        $this->error("E2E refusé : DB_CONNECTION doit être sqlite, valeur observée : {$connection}.");

        return 1;
    }

    if ($database === ':memory:' || ! is_string($database) || trim($database) === '') {
        $this->error('E2E refusé : DB_DATABASE doit être un fichier SQLite jetable sous storage/framework/testing.');

        return 1;
    }

    $isAbsolutePath = fn (string $path): bool => preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\\/]{2}|[\\\\\/])/', $path) === 1;
    $databasePath = $isAbsolutePath($database) ? $database : base_path($database);
    $databaseDir = dirname($databasePath);

    File::ensureDirectoryExists($expectedRoot);
    File::ensureDirectoryExists($databaseDir);

    $resolvedRoot = realpath($expectedRoot);
    $resolvedDir = realpath($databaseDir);
    $normalizedRoot = $resolvedRoot ? rtrim($resolvedRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR : null;
    $normalizedDir = $resolvedDir ? rtrim($resolvedDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR : null;

    if (! $normalizedRoot || ! $normalizedDir || ($normalizedDir !== $normalizedRoot && ! str_starts_with($normalizedDir, $normalizedRoot))) {
        $this->error("E2E refusé : DB_DATABASE doit rester sous {$expectedRoot}. Cible observée : {$databasePath}.");

        return 1;
    }

    if (! file_exists($databasePath)) {
        File::put($databasePath, '');
    }

    $this->call('migrate:fresh', [
        '--seed' => true,
        '--seeder' => EurochamE2ESeeder::class,
        '--force' => true,
    ]);

    if (! File::exists(public_path('storage'))) {
        $this->call('storage:link');
    }

    return 0;
})->purpose('Prepare the disposable SQLite database used by browser E2E tests');
