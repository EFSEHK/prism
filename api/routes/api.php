<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\CheckInactivity;
use App\Http\Middleware\LogAllRequests;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\NavigationController;
use App\Http\Controllers\Api\Prism\AcademicController;
use App\Http\Controllers\Api\Prism\AssessmentController;
use App\Http\Controllers\Api\Prism\AttendanceController;
use App\Http\Controllers\Api\Prism\DeviceTokenController;
use App\Http\Controllers\Api\Prism\FeeVoucherController;
use App\Http\Controllers\Api\Prism\FeedPostController;
use App\Http\Controllers\Api\Prism\HomeworkController;
use App\Http\Controllers\Api\Prism\LeaveRequestController;
use App\Http\Controllers\Api\Prism\MarkSheetController;
use App\Http\Controllers\Api\Prism\NotificationDispatchController;
use App\Http\Controllers\Api\Prism\OnlineClassController;
use App\Http\Controllers\Api\Prism\ParentDashboardController;
use App\Http\Controllers\Api\Prism\StudentController;
use App\Http\Controllers\Api\Prism\TimetableController;
use App\Http\Controllers\Api\Prism\UserNotificationController;

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

    // PRISM school modules (aggregate + domain APIs)
    Route::prefix('prism')->group(function () {
        Route::get('parent/dashboard', [ParentDashboardController::class, 'show']);

        Route::get('academic/classes', [AcademicController::class, 'classesIndex']);
        Route::get('academic/subjects', [AcademicController::class, 'subjectsIndex']);

        Route::get('students', [StudentController::class, 'index']);

        Route::get('attendance/batches', [AttendanceController::class, 'index']);
        Route::post('attendance/batches', [AttendanceController::class, 'store']);
        Route::get('attendance/batches/{attendanceBatch}', [AttendanceController::class, 'show']);
        Route::get('attendance/reports/monthly', [AttendanceController::class, 'reportMonthly']);
        Route::get('attendance/reports/weekly', [AttendanceController::class, 'reportWeekly']);

        Route::get('assessments', [AssessmentController::class, 'index']);
        Route::post('assessments', [AssessmentController::class, 'store']);
        Route::get('mark-sheets', [MarkSheetController::class, 'index']);
        Route::post('mark-sheets', [MarkSheetController::class, 'store']);
        Route::get('mark-sheets/{markSheet}', [MarkSheetController::class, 'show']);
        Route::post('mark-sheets/{markSheet}/entries', [MarkSheetController::class, 'upsertEntries']);
        Route::post('mark-sheets/{markSheet}/notify-parents', [MarkSheetController::class, 'requestParentNotification']);

        Route::get('timetable/slots', [TimetableController::class, 'slotsIndex']);
        Route::post('timetable/slots', [TimetableController::class, 'slotsStore']);
        Route::get('timetable/datesheet', [TimetableController::class, 'datesheetIndex']);
        Route::post('timetable/datesheet', [TimetableController::class, 'datesheetStore']);

        Route::get('homework', [HomeworkController::class, 'index']);
        Route::post('homework', [HomeworkController::class, 'store']);

        Route::get('online-classes', [OnlineClassController::class, 'index']);
        Route::post('online-classes', [OnlineClassController::class, 'store']);

        Route::get('fee-vouchers', [FeeVoucherController::class, 'index']);
        Route::post('fee-vouchers', [FeeVoucherController::class, 'store']);
        Route::patch('fee-vouchers/{feeVoucher}/status', [FeeVoucherController::class, 'updateStatus']);

        Route::get('feed', [FeedPostController::class, 'index']);
        Route::post('feed', [FeedPostController::class, 'store']);

        Route::get('leave-requests', [LeaveRequestController::class, 'index']);
        Route::post('leave-requests', [LeaveRequestController::class, 'store']);
        Route::post('leave-requests/{leaveRequest}/decide', [LeaveRequestController::class, 'decide']);

        Route::get('notification-dispatches/pending', [NotificationDispatchController::class, 'pending']);
        Route::post('notification-dispatches/{notificationDispatchRequest}/approve', [NotificationDispatchController::class, 'approve']);
        Route::post('notification-dispatches/{notificationDispatchRequest}/reject', [NotificationDispatchController::class, 'reject']);

        Route::post('device-tokens', [DeviceTokenController::class, 'store']);
        Route::delete('device-tokens', [DeviceTokenController::class, 'destroy']);

        Route::get('in-app-notifications', [UserNotificationController::class, 'index']);
        Route::post('in-app-notifications/{userNotification}/read', [UserNotificationController::class, 'markRead']);
    });
});
