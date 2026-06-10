<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\CheckInactivity;
use App\Http\Middleware\LogAllRequests;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\NavigationController;
use App\Http\Controllers\Api\Efsc\AcademicController;
use App\Http\Controllers\Api\Efsc\AssessmentController;
use App\Http\Controllers\Api\Efsc\AttendanceController;
use App\Http\Controllers\Api\Efsc\DashboardController;
use App\Http\Controllers\Api\Efsc\DeviceTokenController;
use App\Http\Controllers\Api\Efsc\FeeVoucherController;
use App\Http\Controllers\Api\Efsc\HomeworkController;
use App\Http\Controllers\Api\Efsc\LearnerDashboardController;
use App\Http\Controllers\Api\Efsc\LeaveRequestController;
use App\Http\Controllers\Api\Efsc\MarkSheetController;
use App\Http\Controllers\Api\Efsc\NotificationDispatchController;
use App\Http\Controllers\Api\Efsc\OnlineClassController;
use App\Http\Controllers\Api\Efsc\StudentController;
use App\Http\Controllers\Api\Efsc\TimetableController;
use App\Http\Controllers\Api\Efsc\UserBroadcastController;
use App\Http\Controllers\Api\Efsc\UserNotificationController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware([LogAllRequests::class, 'auth:sanctum', CheckInactivity::class])->group(function () {
    Route::get('/user', function (Request $request) {
        $user = $request->user()->load(['roles:id,name']);
        $user->setRelation('permissions', $user->getAllPermissions());

        return response()->json($user);
    });

    Route::get('/users', function (Request $request) {
        abort_unless($request->user()->hasRole('superadmin'), 403);

        return response()->json(
            \App\Models\User::query()->orderBy('name')->get(['id', 'name', 'email'])
        );
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('roles/{id}/permissions', [RoleController::class, 'getPermissions']);
    Route::post('roles/{id}/permissions', [RoleController::class, 'assignPermissions']);
    Route::post('roles/assign-to-user', [RoleController::class, 'assignRoleToUser']);
    Route::post('roles/remove-from-user', [RoleController::class, 'removeRoleFromUser']);
    Route::get('users/{userId}/roles', [RoleController::class, 'getUserRoles']);
    Route::apiResource('roles', RoleController::class);

    Route::post('permissions/assign-to-user', [PermissionController::class, 'assignPermissionToUser']);
    Route::post('permissions/remove-from-user', [PermissionController::class, 'removePermissionFromUser']);
    Route::get('users/{userId}/permissions', [PermissionController::class, 'getUserPermissions']);
    Route::apiResource('permissions', PermissionController::class);

    Route::get('navigations/active', [NavigationController::class, 'active']);
    Route::apiResource('navigations', NavigationController::class);

    Route::prefix('efsc')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'show']);
        Route::get('learner/dashboard', [LearnerDashboardController::class, 'show']);

        Route::get('academic/years', [AcademicController::class, 'yearsIndex']);
        Route::post('academic/years', [AcademicController::class, 'storeYear']);
        Route::put('academic/years/{academicYear}', [AcademicController::class, 'updateYear']);
        Route::delete('academic/years/{academicYear}', [AcademicController::class, 'destroyYear']);
        Route::get('academic/areas', [AcademicController::class, 'areasIndex']);
        Route::post('academic/areas', [AcademicController::class, 'storeArea']);
        Route::put('academic/areas/{area}', [AcademicController::class, 'updateArea']);
        Route::delete('academic/areas/{area}', [AcademicController::class, 'destroyArea']);
        Route::get('academic/section-heads', [AcademicController::class, 'sectionHeadsIndex']);
        Route::get('academic/classes', [AcademicController::class, 'classesIndex']);
        Route::post('academic/classes', [AcademicController::class, 'storeClass']);
        Route::put('academic/classes/{schoolClass}', [AcademicController::class, 'updateClass']);
        Route::delete('academic/classes/{schoolClass}', [AcademicController::class, 'destroyClass']);
        Route::get('academic/sections', [AcademicController::class, 'sectionsIndex']);
        Route::post('academic/sections', [AcademicController::class, 'storeSection']);
        Route::put('academic/sections/{section}', [AcademicController::class, 'updateSection']);
        Route::delete('academic/sections/{section}', [AcademicController::class, 'destroySection']);
        Route::get('academic/study-groups', [AcademicController::class, 'studyGroupsIndex']);
        Route::post('academic/study-groups', [AcademicController::class, 'storeStudyGroup']);
        Route::put('academic/study-groups/{studyGroup}/subjects', [AcademicController::class, 'syncStudyGroupSubjects']);
        Route::get('academic/subjects', [AcademicController::class, 'subjectsIndex']);
        Route::post('academic/subjects', [AcademicController::class, 'storeSubject']);

        Route::get('students', [StudentController::class, 'index']);
        Route::post('students', [StudentController::class, 'store']);

        Route::get('attendance/batches', [AttendanceController::class, 'index']);
        Route::post('attendance/batches', [AttendanceController::class, 'store']);
        Route::post('attendance/batches/{attendanceBatch}/verify', [AttendanceController::class, 'verify']);
        Route::get('attendance/batches/{attendanceBatch}', [AttendanceController::class, 'show']);
        Route::get('attendance/reports/monthly', [AttendanceController::class, 'reportMonthly']);
        Route::get('attendance/reports/weekly', [AttendanceController::class, 'reportWeekly']);

        Route::get('assessments', [AssessmentController::class, 'index']);
        Route::post('assessments', [AssessmentController::class, 'store']);
        Route::get('mark-sheets', [MarkSheetController::class, 'index']);
        Route::post('mark-sheets', [MarkSheetController::class, 'store']);
        Route::get('mark-sheets/{markSheet}', [MarkSheetController::class, 'show']);
        Route::post('mark-sheets/{markSheet}/entries', [MarkSheetController::class, 'upsertEntries']);
        Route::post('mark-sheets/{markSheet}/verify', [MarkSheetController::class, 'verify']);
        Route::post('mark-sheets/{markSheet}/notify-parents', [MarkSheetController::class, 'requestParentNotification']);

        Route::get('timetable/slots', [TimetableController::class, 'slotsIndex']);
        Route::post('timetable/slots', [TimetableController::class, 'slotsStore']);
        Route::get('timetable/datesheet', [TimetableController::class, 'datesheetIndex']);
        Route::post('timetable/datesheet', [TimetableController::class, 'datesheetStore']);

        Route::get('homework', [HomeworkController::class, 'index']);
        Route::post('homework', [HomeworkController::class, 'store']);
        Route::post('homework/{homeworkPost}/approve', [HomeworkController::class, 'approve']);

        Route::get('online-classes', [OnlineClassController::class, 'index']);
        Route::post('online-classes', [OnlineClassController::class, 'store']);
        Route::post('online-classes/{onlineClassLink}/approve', [OnlineClassController::class, 'approve']);

        Route::get('fee-vouchers', [FeeVoucherController::class, 'index']);
        Route::post('fee-vouchers', [FeeVoucherController::class, 'store']);
        Route::patch('fee-vouchers/{feeVoucher}/status', [FeeVoucherController::class, 'updateStatus']);

        Route::get('broadcasts', [UserBroadcastController::class, 'index']);
        Route::post('broadcasts', [UserBroadcastController::class, 'store']);

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
