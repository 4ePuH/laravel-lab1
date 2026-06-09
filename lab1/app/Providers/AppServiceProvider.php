<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Token\TokenService;
use App\Services\Token\TokenServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Привязываем абстракцию к конкретной реализации (DIP).
        // Параметры берём из config/tokens.php (а тот — из .env).
        $this->app->singleton(TokenServiceInterface::class, static function (): TokenService {
            return new TokenService(
                secret: (string) config('tokens.secret'),
                accessTtl: (int) config('tokens.access_ttl'),
                refreshTtl: (int) config('tokens.refresh_ttl'),
                maxActive: (int) config('tokens.max_active'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
