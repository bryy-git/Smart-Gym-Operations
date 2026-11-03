<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipRequest;
use App\Models\Notification;
use App\Models\OnlineApplication;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\ClosureAnnouncementService;
use App\Services\EquipmentAlertService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function admin(EquipmentAlertService $equipmentAlertService): View
    {
        $today = today();
        $now = now();
        $attendance = $this->attendanceSummary($today, $now);
        $equipmentAlertCount = $equipmentAlertService->alerts()->count();
        $equipmentAlerts = $equipmentAlertService->alerts()
            ->with(['maintenanceRecords' => function (HasMany $query): void {
                $query->whereIn('maintenance_status', ['Reported', 'In Progress'])
                    ->orderByDesc('reported_at');
            }])
            ->orderBy('equipment_name')
            ->limit(8)
            ->get();

        $nextWeek = $today->copy()->addDays(7);
        $expiringMembershipCount = $this->expiringMembershipsQuery($today, $nextWeek)->count();
        $expiringMemberships = $this->expiringMembershipsQuery($today, $nextWeek)
            ->with('member')
            ->orderBy('end_date')
            ->limit(8)
            ->get();

        $inactiveMemberCount = Member::query()->where('member_status', 'Inactive')->count();
        $inactiveMembers = Member::query()
            ->where('member_status', 'Inactive')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(8)
            ->get();

        $revenueChartStart = $now->copy()->startOfMonth()->subMonths(5);
        $paymentsByDay = Payment::query()
            ->selectRaw('DATE(payment_date) as payment_day, SUM(amount) as total')
            ->where('payment_date', '>=', $revenueChartStart)
            ->where('payment_date', '<=', $now)
            ->groupByRaw('DATE(payment_date)')
            ->get();
        $paymentsByMonth = [];
        foreach ($paymentsByDay as $paymentDay) {
            $monthKey = substr($paymentDay->payment_day, 0, 7);
            $paymentsByMonth[$monthKey] = ($paymentsByMonth[$monthKey] ?? 0) + (float) $paymentDay->total;
        }

        $grossRevenue = $paymentsByMonth[$now->format('Y-m')] ?? 0;
        $approvedRefunds = Refund::query()
            ->where('refund_status', 'Approved')
            ->whereBetween('reviewed_at', [$now->copy()->startOfMonth(), $now])
            ->sum('approved_amount');

        $attendanceChartStart = $today->copy()->subDays(6)->startOfDay();
        $attendanceCountsByDate = Attendance::query()
            ->selectRaw('DATE(check_in_at) as chart_date, COUNT(*) as total')
            ->where('check_in_at', '>=', $attendanceChartStart)
            ->where('check_in_at', '<=', $now)
            ->groupByRaw('DATE(check_in_at)')
            ->pluck('total', 'chart_date');
        $attendanceChart = collect(range(6, 0))
            ->map(function (int $daysAgo) use ($today, $attendanceCountsByDate): array {
                $date = $today->copy()->subDays($daysAgo);

                return [
                    'label' => $date->format('D'),
                    'period' => $date->format('M j'),
                    'value' => (int) $attendanceCountsByDate->get($date->toDateString(), 0),
                ];
            })
            ->all();

        $revenueChart = collect(range(0, 5))
            ->map(function (int $monthsAgo) use ($revenueChartStart, $paymentsByMonth): array {
                $monthStart = $revenueChartStart->copy()->addMonths($monthsAgo);
                $monthKey = $monthStart->format('Y-m');

                return [
                    'label' => $monthStart->format('M'),
                    'period' => $monthStart->format('F Y'),
                    'value' => (float) ($paymentsByMonth[$monthKey] ?? 0),
                ];
            })
            ->all();

        return view('dashboards.admin', [
            'totalMembers' => Member::query()->count(),
            'activeMembers' => Member::query()->where('member_status', 'Active')->count(),
            'inactiveMemberCount' => $inactiveMemberCount,
            'activeMemberships' => $this->activeMembershipsQuery($today)->count(),
            'attendance' => $attendance,
            'equipmentAlertCount' => $equipmentAlertCount,
            'equipmentAlerts' => $equipmentAlerts,
            'expiringMembershipCount' => $expiringMembershipCount,
            'expiringMemberships' => $expiringMemberships,
            'inactiveMembers' => $inactiveMembers,
            'pendingApplicationCount' => OnlineApplication::query()->where('application_status', 'Pending')->count(),
            'pendingPayments' => null,
            'activities' => ActivityLog::query()
                ->with('user')
                ->orderByDesc('created_at')
                ->orderByDesc('activity_id')
                ->limit(8)
                ->get(),
            'unreadNotificationCount' => Notification::query()
                ->where('user_id', request()->user()->user_id)
                ->where('is_read', false)
                ->count(),
            'grossRevenue' => $grossRevenue,
            'approvedRefunds' => $approvedRefunds,
            'netRevenue' => round((float) $grossRevenue - (float) $approvedRefunds, 2),
            'attendanceChart' => $attendanceChart,
            'revenueChart' => $revenueChart,
        ]);
    }

    public function staff(
        EquipmentAlertService $equipmentAlertService,
        ClosureAnnouncementService $closureAnnouncementService,
    ): View {
        $today = today();
        $now = now();
        $attendance = $this->attendanceSummary($today, $now);
        $equipmentAlertCount = $equipmentAlertService->alerts()->count();
        $equipmentAlerts = $equipmentAlertService->alerts()
            ->with(['maintenanceRecords' => function (HasMany $query): void {
                $query->whereIn('maintenance_status', ['Reported', 'In Progress'])
                    ->orderByDesc('reported_at');
            }])
            ->orderBy('equipment_name')
            ->limit(8)
            ->get();

        return view('dashboards.staff', [
            'attendance' => $attendance,
            'activeMembers' => Member::query()->where('member_status', 'Active')->count(),
            'inactiveMembers' => Member::query()->where('member_status', 'Inactive')->count(),
            'recentMembers' => Member::query()
                ->with('user')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),
            'pendingRequestCount' => MembershipRequest::query()
                ->where('request_status', 'Pending')
                ->count(),
            'pendingRequests' => MembershipRequest::query()
                ->where('request_status', 'Pending')
                ->with('member')
                ->orderByDesc('requested_at')
                ->limit(8)
                ->get(),
            'equipmentAlertCount' => $equipmentAlertCount,
            'equipmentAlerts' => $equipmentAlerts,
            'relevantClosures' => $closureAnnouncementService->relevant()->limit(5)->get(),
            'recentAttendance' => Attendance::query()
                ->with('member')
                ->where('check_in_at', '>=', $today->copy()->startOfDay())
                ->where('check_in_at', '<', $today->copy()->addDay()->startOfDay())
                ->orderByDesc('check_in_at')
                ->limit(8)
                ->get(),
        ]);
    }

    public function member(Request $request, ClosureAnnouncementService $closureAnnouncementService): View
    {
        $user = $request->user();
        $member = Member::query()->where('user_id', $user->user_id)->first();
        $today = today();
        $now = now();
        $attendanceStart = $today->copy()->subDays(29)->startOfDay();
        $membership = null;
        $membershipStatus = 'No membership';
        $attendanceCount = 0;
        $recentAttendance = collect();
        $paymentTotal = 0;
        $recentPayments = collect();

        if ($member !== null) {
            $membership = $member->memberships()
                ->where('membership_status', 'Active')
                ->whereDate('start_date', '<=', $today->toDateString())
                ->whereDate('end_date', '>=', $today->toDateString())
                ->orderByDesc('end_date')
                ->first();

            if ($membership === null) {
                $membership = $member->memberships()
                    ->orderByDesc('start_date')
                    ->orderByDesc('membership_id')
                    ->first();
            }

            if ($membership !== null) {
                $membershipStatus = $membership->membership_status;

                if ($membershipStatus === 'Active' && $membership->start_date->isAfter($today)) {
                    $membershipStatus = 'Upcoming';
                } elseif ($membershipStatus === 'Active' && $membership->end_date->isBefore($today)) {
                    $membershipStatus = 'Expired';
                }
            }

            $attendanceQuery = $member->attendances()
                ->where('check_in_at', '>=', $attendanceStart)
                ->where('check_in_at', '<=', $now);
            $attendanceCount = (clone $attendanceQuery)->count();
            $recentAttendance = $attendanceQuery
                ->orderByDesc('check_in_at')
                ->limit(5)
                ->get();

            $paymentQuery = Payment::query()
                ->whereHas('membership', fn (Builder $query): Builder => $query->where('member_id', $member->member_id));
            $paymentTotal = (clone $paymentQuery)->sum('amount');
            $recentPayments = $paymentQuery
                ->orderByDesc('payment_date')
                ->limit(5)
                ->get();
        }

        $notifications = Notification::query()
            ->where('user_id', $user->user_id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
        $announcements = Notification::query()
            ->where('user_id', $user->user_id)
            ->where('notification_type', 'Announcement')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboards.member', [
            'member' => $member,
            'membership' => $membership,
            'membershipStatus' => $membershipStatus,
            'attendanceCount' => $attendanceCount,
            'recentAttendance' => $recentAttendance,
            'paymentTotal' => $paymentTotal,
            'recentPayments' => $recentPayments,
            'notifications' => $notifications,
            'announcements' => $announcements,
            'relevantClosures' => $closureAnnouncementService->relevant()->limit(5)->get(),
            'unreadNotificationCount' => Notification::query()
                ->where('user_id', $user->user_id)
                ->where('is_read', false)
                ->count(),
        ]);
    }

    private function attendanceSummary(Carbon $today, Carbon $now): array
    {
        return [
            'checkInsToday' => Attendance::query()
                ->where('check_in_at', '>=', $today->copy()->startOfDay())
                ->where('check_in_at', '<=', $now)
                ->count(),
            'currentlyInGym' => Attendance::query()
                ->whereNotNull('check_in_at')
                ->where('check_in_at', '<=', $now)
                ->whereNull('check_out_at')
                ->count(),
            'asOf' => $now,
        ];
    }

    private function activeMembershipsQuery(Carbon $today): Builder
    {
        return Membership::query()
            ->where('membership_status', 'Active')
            ->whereDate('start_date', '<=', $today->toDateString())
            ->whereDate('end_date', '>=', $today->toDateString());
    }

    private function expiringMembershipsQuery(Carbon $today, Carbon $limit): Builder
    {
        return Membership::query()
            ->where('membership_status', 'Active')
            ->whereDate('start_date', '<=', $today->toDateString())
            ->whereBetween('end_date', [$today->toDateString(), $limit->toDateString()]);
    }
}
