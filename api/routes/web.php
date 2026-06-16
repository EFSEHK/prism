<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DevPortalController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HealthMetricsController;
use App\Http\Controllers\WelcomeController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

$devPortal = trim(config('releases.dev_portal_path', 'sys/portal-access'), '/');

Route::get('/', WelcomeController::class)->name('welcome');

Route::redirect('/login', '/')->name('login');

Route::prefix($devPortal)->name('dev-portal.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/', [DevPortalController::class, 'showLogin'])->name('login');
        Route::post('/', [DevPortalController::class, 'login'])->name('login.store');
    });

    Route::middleware(['auth', 'dev.portal'])->group(function () {
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
    $user = User::where('email', 'superadmin@efsc-ya.test')->first()
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
