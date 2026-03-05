<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/home';

    public function boot(): void
    {
        $this->routes(function () {
            // Web 路由前綴
            Route::prefix('TCNR_CLASS_API')
                ->middleware('web')
                ->group(base_path('routes/web.php'));

            // API 路由前綴
            Route::prefix('TCNR_CLASS_API/api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));
        });
    }
}