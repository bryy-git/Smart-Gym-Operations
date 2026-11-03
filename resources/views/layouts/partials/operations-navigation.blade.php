<nav aria-label="{{ $audience }} navigation">
    <p class="sidebar-label">Workspace</p>
    <a href="{{ route(strtolower($audience).'.dashboard') }}" @if (request()->routeIs(strtolower($audience).'.dashboard')) aria-current="page" @endif>Dashboard</a>

    <details class="sidebar-group" data-sidebar-group @if (request()->routeIs(strtolower($audience).'.members.*', strtolower($audience).'.membership-plans.*', strtolower($audience).'.memberships.*', strtolower($audience).'.membership-requests.*')) open @endif>
        <summary>People & memberships</summary>
        <div class="sidebar-group-links">
            <a href="{{ route(strtolower($audience).'.members.index') }}" @if (request()->routeIs(strtolower($audience).'.members.*')) aria-current="page" @endif>Members</a>
            <a href="{{ route(strtolower($audience).'.membership-plans.index') }}" @if (request()->routeIs(strtolower($audience).'.membership-plans.*')) aria-current="page" @endif>Membership plans</a>
            <a href="{{ route(strtolower($audience).'.memberships.index') }}" @if (request()->routeIs(strtolower($audience).'.memberships.*')) aria-current="page" @endif>Memberships</a>
            <a href="{{ route(strtolower($audience).'.membership-requests.index') }}" @if (request()->routeIs(strtolower($audience).'.membership-requests.*')) aria-current="page" @endif>Requests</a>
        </div>
    </details>

    <details class="sidebar-group" data-sidebar-group @if (request()->routeIs(strtolower($audience).'.attendance.*', strtolower($audience).'.equipment.*', strtolower($audience).'.operating-schedules.*') || ($audience === 'Admin' && request()->routeIs('admin.gym-closures.*'))) open @endif>
        <summary>Gym operations</summary>
        <div class="sidebar-group-links">
            <a href="{{ route(strtolower($audience).'.attendance.index') }}" @if (request()->routeIs(strtolower($audience).'.attendance.*')) aria-current="page" @endif>Attendance</a>
            <a href="{{ route(strtolower($audience).'.equipment.index') }}" @if (request()->routeIs(strtolower($audience).'.equipment.*')) aria-current="page" @endif>Equipment</a>
            <a href="{{ route(strtolower($audience).'.operating-schedules.index') }}" @if (request()->routeIs(strtolower($audience).'.operating-schedules.*')) aria-current="page" @endif>Operating schedule</a>
        @if ($audience === 'Admin')
            <a href="{{ route('admin.gym-closures.index') }}" @if (request()->routeIs('admin.gym-closures.*')) aria-current="page" @endif>Gym closures</a>
        @endif
        </div>
    </details>

    <details class="sidebar-group" data-sidebar-group @if (request()->routeIs(strtolower($audience).'.payments.*', strtolower($audience).'.refunds.*', strtolower($audience).'.notifications.*')) open @endif>
        <summary>Finance & updates</summary>
        <div class="sidebar-group-links">
            <a href="{{ route(strtolower($audience).'.payments.index') }}" @if (request()->routeIs(strtolower($audience).'.payments.*')) aria-current="page" @endif>Payments</a>
            <a href="{{ route(strtolower($audience).'.refunds.index') }}" @if (request()->routeIs(strtolower($audience).'.refunds.*')) aria-current="page" @endif>Refunds</a>
            <a href="{{ route(strtolower($audience).'.notifications.index') }}" @if (request()->routeIs(strtolower($audience).'.notifications.*')) aria-current="page" @endif>Notifications</a>
        </div>
    </details>
</nav>
