<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\CheckInactivity;
use App\Http\Middleware\LogAllRequests;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\NavigationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware([LogAllRequests::class, 'auth:sanctum', CheckInactivity::class])->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Role Routes
    Route::get('roles/{id}/permissions', [RoleController::class, 'getPermissions']);
    Route::post('roles/{id}/permissions', [RoleController::class, 'assignPermissions']);
    Route::post('roles/assign-to-user', [RoleController::class, 'assignRoleToUser']);
    Route::post('roles/remove-from-user', [RoleController::class, 'removeRoleFromUser']);
    Route::get('users/{userId}/roles', [RoleController::class, 'getUserRoles']);
    Route::apiResource('roles', RoleController::class);
    
    // Permission Routes
    Route::post('permissions/assign-to-user', [PermissionController::class, 'assignPermissionToUser']);
    Route::post('permissions/remove-from-user', [PermissionController::class, 'removePermissionFromUser']);
    Route::get('users/{userId}/permissions', [PermissionController::class, 'getUserPermissions']);
    Route::apiResource('permissions', PermissionController::class);

    // Navigation Routes
    Route::get('navigations/active', [NavigationController::class, 'active']);
    Route::apiResource('navigations', NavigationController::class);
});
