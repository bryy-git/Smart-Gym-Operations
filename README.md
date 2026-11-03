<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## SmartGym account access

- Sign in at `/login`; active Admin, Staff, and Member accounts are redirected to their role-specific dashboard.
- Authenticated users can update their own account profile and change their password from `/profile`.
- Password-reset links are sent through the configured Laravel mailer, expire after the configured `auth.passwords.users.expire` interval (60 minutes by default), and can only be used once.
- Reset tokens use the `AUTH_PASSWORD_RESET_CACHE_STORE` cache store. The default is `file`, which requires no database schema change and is intended for a single application host. Configure a shared cache store for multi-host deployments.
- Session data encryption is enabled by default. In production, serve the site over HTTPS so the secure session cookie is used.

## Role-based dashboards

- Admin and Staff attendance cards summarize check-ins for today and current occupancy from open attendance sessions.
- The Member dashboard summarizes the signed-in member's visits over the last 30 days, membership record, payments, and user-specific notifications.
- Admin expiration alerts cover active memberships ending within seven days; inactive-member alerts use the stored `member_status`.
- Equipment alerts include non-available equipment, unresolved maintenance, and usage at/above the configured threshold since the most recent resolved maintenance.
- Admin revenue is month-to-date payments less refunds approved during the month. Staff dashboards do not include revenue data.

## Membership plans and memberships

- Admin and Staff can create, edit, activate/deactivate, and list membership plans; existing memberships retain the plan name, price, and duration snapshots captured at assignment.
- Plan name, duration, and price cannot be changed while purchase or renewal requests for that plan are pending, so review cannot silently change the requested terms.
- Members can request purchases or renewals from active plans. Admin and Staff can review requests or assign a paid membership on a member’s behalf.
- Approving a request or assigning a membership requires the operator to confirm that the full current plan price was received. The system records the payment and membership together in one database transaction; this records an operator-confirmed payment and does not contact a payment provider.
- New and expired memberships start on the payment date. Early renewals start the day after the current membership’s expiry. The inclusive end date is calculated from the selected plan duration.

## Payments and refunds

- Admin and Staff can manually record, search, and review payments associated with memberships. Payment records capture the operator, method, date, optional reference, and notes; Members can view only their own payment history, details, and printable receipts.
- Staff can submit refund requests against a payment's remaining balance. Admin alone can approve or reject requests; an approved amount may be lower than requested but cannot exceed either the request or the remaining payment balance.
- Refund decisions are recorded in the system and do not transfer money. Payment entry is also an operator-confirmed record and does not contact a payment provider.
- The existing schema has no configured currency, so amounts are displayed without a currency symbol. Admin month-to-date revenue subtracts refunds approved during the month.

## Attendance and occupancy

- Staff can check members in and out; Members can self-check in and out. Check-in requires an active member account and a membership whose stored status is Active and whose dates include today. Each check-in is recorded as Staff-assisted or Member self-service.
- Admin and Staff can search and filter attendance history. Corrections require a reason and preserve both the original and corrected timestamps; Members have no attendance editing routes.
- Occupancy is the count of open attendance sessions. Admin can configure maximum capacity; the monitoring page refreshes every five seconds and warns at or above capacity without blocking check-in.
- Open sessions are automatically closed at the applicable day's configured closing time in `operating_schedules`. Laravel's scheduler must run every minute (for example, via the platform's cron/task scheduler) for automatic closing to execute.

## Equipment and maintenance

- Admin and Staff can add, edit, search, and filter equipment using the existing Available, Under Maintenance, and Unavailable statuses. Equipment and maintenance history are retained; there are no deletion routes.
- Staff and Admin can report issues and update maintenance through Reported, In Progress, and Resolved. Reporting an issue marks the equipment Under Maintenance; resolving the last open record returns it to Available unless it was explicitly marked Unavailable.
- Admin and Staff can record dated equipment usage. Usage-threshold alerts and dashboard summaries count usage after the most recent resolved maintenance, matching the existing dashboard rule.

## Operating schedules and gym closures

- Admin can maintain multiple non-overlapping opening intervals per weekday and mark a weekday closed without deleting its schedule history. Adjacent intervals are allowed.
- Staff and Members can view the weekly operating schedule. Admin can publish and edit temporary closure announcements; relevant upcoming and active announcements appear on Staff and Member dashboards and the schedule page.
- Closure status is derived from its start and end dates. Schedule and closure management use the existing database tables; no schema migration is required.

## Notifications and membership reminders

- Admin, Staff, and Members can review their own notification history, mark notifications as read, delete them, and see in-system/email delivery status. Deleting a notification does not alter its related business records.
- Membership reminders are created seven and three days before an active membership expires. The daily `notifications:membership-reminders` task suppresses duplicates and skips memberships superseded by a renewal; queued reminder emails are rechecked before delivery.
- Email delivery uses the existing `notification_deliveries` table. The current environment uses the `sync` queue and `log` mailer, so email jobs run inline and messages are logged rather than sent to an external inbox. Configure a production mail transport and, if using an asynchronous queue, run a queue worker. Laravel's scheduler must run every minute for the daily reminder task.
- No notification schema changes or migrations are required.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
