<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DevPortalController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HealthMetricsController;
use App\Http\Middleware\EnsureDeveloperOrSuperadmin;
use App\Models\AppReleaseSetting;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

$devPortal = trim(config('releases.dev_portal_path', 'sys/portal-access'), '/');

$webAppUrl = static function (): string {
    // Local/dev: never send browsers to the production SPA host.
    if (! app()->environment('production')) {
        $local = config('releases.default_web_app_url');
        if (is_string($local) && $local !== '' && ! str_contains($local, 'innovisiq.com')) {
            return rtrim($local, '/');
        }

        return 'http://localhost:5173';
    }

    $url = AppReleaseSetting::current()->web_app_url
        ?: config('releases.default_web_app_url');

    return rtrim((string) $url, '/');
};

Route::get('/', function () use ($webAppUrl) {
    // Production: send users to the live web app.
    if (app()->environment('production')) {
        return redirect()->away($webAppUrl());
    }

    $settings = AppReleaseSetting::current();

    return view('welcome', [
        'webAppUrl' => $webAppUrl(),
        'settings' => $settings,
        'apkUrl' => $settings->androidApkUrl(),
    ]);
})->name('welcome');

Route::get('/login', function () use ($webAppUrl) {
    return redirect()->away($webAppUrl().'/login');
})->name('login');

Route::prefix($devPortal)->name('dev-portal.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/', [DevPortalController::class, 'showLogin'])->name('login');
        Route::post('/', [DevPortalController::class, 'login'])->name('login.store');
    });

    Route::middleware(['auth', EnsureDeveloperOrSuperadmin::class])->group(function () {
        Route::get('/releases', [DevPortalController::class, 'showSettings'])->name('settings');
        Route::post('/releases', [DevPortalController::class, 'updateSettings'])->name('settings.update');
        Route::post('/logout', [DevPortalController::class, 'logout'])->name('logout');
    });
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [DevPortalController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/health', [HealthController::class, 'results'])->name('dashboard.health');
    Route::get('/dashboard/health/metrics', HealthMetricsController::class)
        ->name('dashboard.health.metrics');
    Route::get('/dashboard/health/connections', [DashboardController::class, 'activeConnections'])
        ->name('dashboard.health.connections');
});

Route::get('/telescope-login', function () {
    $user = User::where('email', 'superadmin@efsc-ya.com')->first()
        ?? User::where('email', 'superadmin@lask.com')->first();
    abort_if(! $user, 404, 'Telescope user not found.');

    Auth::login($user);

    return redirect('/telescope');
})->middleware('web')->name('telescope.login');

Route::get('logs', function () {
    if (! app()->environment(['local', 'development'])) {
        abort(403);
    }

    return app()->call('Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
})->middleware('web')->name('logs');
