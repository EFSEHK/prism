<?php

namespace App\Providers;

use App\Checks\ActiveDatabaseConnectionsCheck;
use App\Checks\ApplicationEnvironmentCheck;
use App\Checks\CacheStoreCheck;
use App\Checks\DatabaseSizeCheck;
use App\Checks\OptimizationCheck;
use App\Checks\TableSizesCheck;
use App\Http\Middleware\EnsureDeveloperOrSuperadmin;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;
use Spatie\Health\Facades\Health;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;

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
        // Laravel 12 does not use App\Http\Kernel; register the alias here so
        // cached routes that still reference "dev.portal" keep resolving.
        $this->app['router']->aliasMiddleware(
            'dev.portal',
            EnsureDeveloperOrSuperadmin::class
        );

        Carbon::serializeUsing(function ($date) {
            return $date->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        });

        Health::checks([
            DatabaseCheck::new()->label('MySQL Database'),
            DatabaseSizeCheck::new()->label('MySQL Database Size'),
            TableSizesCheck::new()->label('MySQL Table Sizes'),
            ActiveDatabaseConnectionsCheck::new()->label('Currently Active Connections'),
            CacheStoreCheck::new()->label('Laravel Cache'),
            ApplicationEnvironmentCheck::new()->label('Laravel Environment'),
            DebugModeCheck::new()
                ->label('Laravel Debug Mode')
                ->expectedToBe(app()->environment(['local', 'development'])),
            OptimizationCheck::new()->label('Laravel Optimization'),
        ]);
    }
}
