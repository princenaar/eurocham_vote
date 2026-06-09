<?php

namespace App\Providers;

use RuntimeException;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->assertProductionConfiguration();
    }

    private function assertProductionConfiguration(): void
    {
        if (! $this->app->environment('production')) {
            return;
        }

        $violations = [];

        if (config('database.default') !== 'mysql') {
            $violations[] = 'DB_CONNECTION doit être mysql.';
        }

        if (config('cache.default') !== 'redis') {
            $violations[] = 'CACHE_STORE doit être redis.';
        }

        if (config('cache.vote_lock_store') !== 'redis') {
            $violations[] = 'VOTE_LOCK_STORE doit être redis.';
        }

        if (config('session.driver') !== 'redis') {
            $violations[] = 'SESSION_DRIVER doit être redis.';
        }

        if (config('session.secure') !== true) {
            $violations[] = 'SESSION_SECURE_COOKIE doit être true.';
        }

        if (config('app.debug') !== false) {
            $violations[] = 'APP_DEBUG doit être false.';
        }

        if ($violations !== []) {
            throw new RuntimeException('Configuration production EUROCHAM invalide: '.implode(' ', $violations));
        }
    }
}
