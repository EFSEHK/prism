<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HealthMetricsController;
use App\Http\Controllers\WebAuthController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::redirect('/login', '/')->name('login');

Route::middleware('guest')->group(function () {
    Route::post('/login', [WebAuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/health', [HealthController::class, 'results'])->name('dashboard.health');
    Route::get('/dashboard/health/metrics', HealthMetricsController::class)
        ->name('dashboard.health.metrics');
    Route::get('/dashboard/health/connections', [DashboardController::class, 'activeConnections'])
        ->name('dashboard.health.connections');
    Route::post('/logout', [WebAuthController::class, 'destroy'])->name('logout');
});

Route::get('/telescope-login', function () {
    $user = User::where('email', 'superadmin@lask.com')->first();
    abort_if(! $user, 404, 'Telescope user not found.');

    Auth::login($user);

    return redirect('/telescope');
})->middleware('web')->name('telescope.login');

Route::get('logs', function () {
    if (!app()->environment(['local', 'development'])) {
        abort(403);
    }
    return app()->call('Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
})->middleware('web')->name('logs');