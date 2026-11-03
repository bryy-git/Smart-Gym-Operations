<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MemberManagementController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\MembershipPlanController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OperatingScheduleController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RefundController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('login.store');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/forgot-password', [PasswordResetController::class, 'create'])
    ->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'storeLink'])
    ->middleware('throttle:5,1')
    ->name('password.email');
Route::get('/password/reset/{token}', [PasswordResetController::class, 'edit'])
    ->name('password.reset');
Route::put('/password/reset', [PasswordResetController::class, 'update'])
    ->middleware('throttle:6,1')
    ->name('password.update');

Route::middleware(['auth', 'active.account'])->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
        ->name('profile.password.update');
});

Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
    ->middleware(['auth', 'active.account', 'role:Admin'])
    ->name('admin.dashboard');
Route::get('/staff/dashboard', [DashboardController::class, 'staff'])
    ->middleware(['auth', 'active.account', 'role:Staff'])
    ->name('staff.dashboard');
Route::get('/member/dashboard', [DashboardController::class, 'member'])
    ->middleware(['auth', 'active.account', 'role:Member'])
    ->name('member.dashboard');

Route::middleware(['auth', 'active.account', 'role:Admin'])
    ->prefix('admin')
    ->name('admin.')
    ->controller(MemberManagementController::class)
    ->group(function (): void {
        Route::get('/members', 'index')->name('members.index');
        Route::get('/members/create', 'create')->name('members.create');
        Route::post('/members', 'store')->name('members.store');
        Route::get('/members/{member}/status/{targetStatus}', 'confirmStatus')
            ->whereNumber('member')
            ->whereIn('targetStatus', ['Active', 'Inactive'])
            ->name('members.status.confirm');
        Route::patch('/members/{member}/status/{targetStatus}', 'updateStatus')
            ->whereNumber('member')
            ->whereIn('targetStatus', ['Active', 'Inactive'])
            ->name('members.status.update');
        Route::get('/members/{member}/edit', 'edit')
            ->whereNumber('member')
            ->name('members.edit');
        Route::put('/members/{member}', 'update')
            ->whereNumber('member')
            ->name('members.update');
        Route::get('/members/{member}', 'show')
            ->whereNumber('member')
            ->name('members.show');
    });

Route::middleware(['auth', 'active.account', 'role:Staff'])
    ->prefix('staff')
    ->name('staff.')
    ->controller(MemberManagementController::class)
    ->group(function (): void {
        Route::get('/members', 'index')->name('members.index');
        Route::get('/members/create', 'create')->name('members.create');
        Route::post('/members', 'store')->name('members.store');
        Route::get('/members/{member}/status/{targetStatus}', 'confirmStatus')
            ->whereNumber('member')
            ->whereIn('targetStatus', ['Active', 'Inactive'])
            ->name('members.status.confirm');
        Route::patch('/members/{member}/status/{targetStatus}', 'updateStatus')
            ->whereNumber('member')
            ->whereIn('targetStatus', ['Active', 'Inactive'])
            ->name('members.status.update');
        Route::get('/members/{member}/edit', 'edit')
            ->whereNumber('member')
            ->name('members.edit');
        Route::put('/members/{member}', 'update')
            ->whereNumber('member')
            ->name('members.update');
        Route::get('/members/{member}', 'show')
            ->whereNumber('member')
            ->name('members.show');
    });

foreach (['admin', 'staff'] as $audience) {
    Route::middleware(['auth', 'active.account', 'role:'.ucfirst($audience)])
        ->prefix($audience)
        ->name($audience.'.')
        ->group(function () use ($audience): void {
            if ($audience === 'admin') {
                Route::get('/operating-schedules', [OperatingScheduleController::class, 'adminIndex'])
                    ->name('operating-schedules.index');
            } else {
                Route::get('/operating-schedules', [OperatingScheduleController::class, 'publicIndex'])
                    ->name('operating-schedules.index');
            }

            Route::get('/notifications', [NotificationController::class, 'index'])
                ->name('notifications.index');
            Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
                ->whereNumber('notification')
                ->name('notifications.read');
            Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])
                ->whereNumber('notification')
                ->name('notifications.destroy');

            Route::get('/equipment', [EquipmentController::class, 'index'])
                ->name('equipment.index');
            Route::get('/equipment/create', [EquipmentController::class, 'create'])
                ->name('equipment.create');
            Route::post('/equipment', [EquipmentController::class, 'store'])
                ->name('equipment.store');
            Route::get('/equipment/{equipment}/maintenance-history', [EquipmentController::class, 'maintenanceHistory'])
                ->whereNumber('equipment')
                ->name('equipment.maintenance-history');
            Route::get('/equipment/{equipment}/usage-history', [EquipmentController::class, 'usageHistory'])
                ->whereNumber('equipment')
                ->name('equipment.usage-history');
            Route::post('/equipment/{equipment}/maintenance', [EquipmentController::class, 'reportIssue'])
                ->whereNumber('equipment')
                ->name('equipment.maintenance.store');
            Route::patch('/equipment/{equipment}/maintenance/{maintenanceRecord}', [EquipmentController::class, 'updateMaintenance'])
                ->whereNumber('equipment')
                ->whereNumber('maintenanceRecord')
                ->name('equipment.maintenance.update');
            Route::post('/equipment/{equipment}/usage', [EquipmentController::class, 'recordUsage'])
                ->whereNumber('equipment')
                ->name('equipment.usage.store');
            Route::get('/equipment/{equipment}/edit', [EquipmentController::class, 'edit'])
                ->whereNumber('equipment')
                ->name('equipment.edit');
            Route::put('/equipment/{equipment}', [EquipmentController::class, 'update'])
                ->whereNumber('equipment')
                ->name('equipment.update');
            Route::get('/equipment/{equipment}', [EquipmentController::class, 'show'])
                ->whereNumber('equipment')
                ->name('equipment.show');

            Route::get('/attendance', [AttendanceController::class, 'index'])
                ->name('attendance.index');
            Route::get('/attendance/check-in', [AttendanceController::class, 'create'])
                ->name('attendance.check-in.create');
            Route::post('/attendance/check-in', [AttendanceController::class, 'staffCheckIn'])
                ->name('attendance.check-in');
            Route::post('/attendance/{attendance}/check-out', [AttendanceController::class, 'staffCheckOut'])
                ->whereNumber('attendance')
                ->name('attendance.check-out');
            Route::get('/attendance/occupancy', [AttendanceController::class, 'occupancy'])
                ->name('attendance.occupancy');
            Route::get('/attendance/{attendance}/edit', [AttendanceController::class, 'edit'])
                ->whereNumber('attendance')
                ->name('attendance.edit');
            Route::put('/attendance/{attendance}', [AttendanceController::class, 'update'])
                ->whereNumber('attendance')
                ->name('attendance.update');

            Route::get('/payments', [PaymentController::class, 'index'])
                ->name('payments.index');
            Route::get('/payments/create', [PaymentController::class, 'create'])
                ->name('payments.create');
            Route::post('/payments', [PaymentController::class, 'store'])
                ->name('payments.store');
            Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])
                ->whereNumber('payment')
                ->name('payments.receipt');
            Route::get('/payments/{payment}', [PaymentController::class, 'show'])
                ->whereNumber('payment')
                ->name('payments.show');

            Route::get('/membership-plans', [MembershipPlanController::class, 'index'])
                ->name('membership-plans.index');
            Route::get('/membership-plans/create', [MembershipPlanController::class, 'create'])
                ->name('membership-plans.create');
            Route::post('/membership-plans', [MembershipPlanController::class, 'store'])
                ->name('membership-plans.store');
            Route::get('/membership-plans/{plan}/edit', [MembershipPlanController::class, 'edit'])
                ->whereNumber('plan')
                ->name('membership-plans.edit');
            Route::put('/membership-plans/{plan}', [MembershipPlanController::class, 'update'])
                ->whereNumber('plan')
                ->name('membership-plans.update');
            Route::patch('/membership-plans/{plan}/status', [MembershipPlanController::class, 'updateStatus'])
                ->whereNumber('plan')
                ->name('membership-plans.status.update');
            Route::get('/membership-plans/{plan}', [MembershipPlanController::class, 'show'])
                ->whereNumber('plan')
                ->name('membership-plans.show');

            Route::get('/memberships', [MembershipController::class, 'index'])
                ->name('memberships.index');
            Route::get('/memberships/assign', [MembershipController::class, 'assignForm'])
                ->name('memberships.assign.create');
            Route::post('/memberships/assign', [MembershipController::class, 'assign'])
                ->name('memberships.assign');
            Route::get('/memberships/{membership}', [MembershipController::class, 'show'])
                ->whereNumber('membership')
                ->name('memberships.show');

            Route::get('/membership-requests', [MembershipController::class, 'requests'])
                ->name('membership-requests.index');
            Route::get('/membership-requests/{membershipRequest}/review', [MembershipController::class, 'review'])
                ->whereNumber('membershipRequest')
                ->name('membership-requests.review');
            Route::post('/membership-requests/{membershipRequest}/approve', [MembershipController::class, 'approve'])
                ->whereNumber('membershipRequest')
                ->name('membership-requests.approve');
            Route::post('/membership-requests/{membershipRequest}/reject', [MembershipController::class, 'reject'])
                ->whereNumber('membershipRequest')
                ->name('membership-requests.reject');

            if ($audience === 'admin') {
                Route::post('/operating-schedules', [OperatingScheduleController::class, 'store'])
                    ->name('operating-schedules.store');
                Route::put('/operating-schedules/{schedule}', [OperatingScheduleController::class, 'update'])
                    ->whereNumber('schedule')
                    ->name('operating-schedules.update');
                Route::patch('/operating-schedules/day/{dayOfWeek}/close', [OperatingScheduleController::class, 'closeDay'])
                    ->whereIn('dayOfWeek', ['1', '2', '3', '4', '5', '6', '7'])
                    ->name('operating-schedules.day.close');
                Route::get('/gym-closures', [OperatingScheduleController::class, 'closuresIndex'])
                    ->name('gym-closures.index');
                Route::get('/gym-closures/create', [OperatingScheduleController::class, 'createClosure'])
                    ->name('gym-closures.create');
                Route::post('/gym-closures', [OperatingScheduleController::class, 'storeClosure'])
                    ->name('gym-closures.store');
                Route::get('/gym-closures/{closure}/edit', [OperatingScheduleController::class, 'editClosure'])
                    ->whereNumber('closure')
                    ->name('gym-closures.edit');
                Route::put('/gym-closures/{closure}', [OperatingScheduleController::class, 'updateClosure'])
                    ->whereNumber('closure')
                    ->name('gym-closures.update');
                Route::put('/attendance/capacity', [AttendanceController::class, 'updateCapacity'])
                    ->name('attendance.capacity.update');
                Route::get('/refunds', [RefundController::class, 'adminIndex'])
                    ->name('refunds.index');
                Route::get('/refunds/{refund}/review', [RefundController::class, 'review'])
                    ->whereNumber('refund')
                    ->name('refunds.review');
                Route::post('/refunds/{refund}/approve', [RefundController::class, 'approve'])
                    ->whereNumber('refund')
                    ->name('refunds.approve');
                Route::post('/refunds/{refund}/reject', [RefundController::class, 'reject'])
                    ->whereNumber('refund')
                    ->name('refunds.reject');
                Route::get('/refunds/{refund}', [RefundController::class, 'adminShow'])
                    ->whereNumber('refund')
                    ->name('refunds.show');
            } else {
                Route::get('/refunds', [RefundController::class, 'staffIndex'])
                    ->name('refunds.index');
                Route::get('/payments/{payment}/refunds/create', [RefundController::class, 'create'])
                    ->whereNumber('payment')
                    ->name('payments.refunds.create');
                Route::post('/payments/{payment}/refunds', [RefundController::class, 'store'])
                    ->whereNumber('payment')
                    ->name('payments.refunds.store');
                Route::get('/refunds/{refund}', [RefundController::class, 'staffShow'])
                    ->whereNumber('refund')
                    ->name('refunds.show');
            }
        });
}

Route::middleware(['auth', 'active.account', 'role:Member'])
    ->prefix('member')
    ->name('member.')
    ->group(function (): void {
        Route::get('/operating-schedules', [OperatingScheduleController::class, 'publicIndex'])
            ->name('operating-schedules.index');
        Route::get('/notifications', [NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
            ->whereNumber('notification')
            ->name('notifications.read');
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])
            ->whereNumber('notification')
            ->name('notifications.destroy');

        Route::get('/attendance', [AttendanceController::class, 'memberIndex'])
            ->name('attendance.index');
        Route::post('/attendance/check-in', [AttendanceController::class, 'memberCheckIn'])
            ->name('attendance.check-in');
        Route::post('/attendance/{attendance}/check-out', [AttendanceController::class, 'memberCheckOut'])
            ->whereNumber('attendance')
            ->name('attendance.check-out');

        Route::get('/plans', [MembershipPlanController::class, 'catalog'])
            ->name('plans.index');
        Route::post('/plans/{plan}/purchase', [MembershipController::class, 'storePurchaseRequest'])
            ->whereNumber('plan')
            ->name('plans.purchase');

        Route::get('/memberships', [MembershipController::class, 'memberIndex'])
            ->name('memberships.index');
        Route::post('/memberships/{membership}/renew', [MembershipController::class, 'storeRenewalRequest'])
            ->whereNumber('membership')
            ->name('memberships.renew');
        Route::get('/membership-requests', [MembershipController::class, 'memberRequests'])
            ->name('membership-requests.index');

        Route::get('/payments', [PaymentController::class, 'memberIndex'])
            ->name('payments.index');
        Route::get('/payments/{payment}/receipt', [PaymentController::class, 'memberReceipt'])
            ->whereNumber('payment')
            ->name('payments.receipt');
        Route::get('/payments/{payment}', [PaymentController::class, 'memberShow'])
            ->whereNumber('payment')
            ->name('payments.show');
    });
