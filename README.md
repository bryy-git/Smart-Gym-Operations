# SmartGym Operations

A web-based gym operations management system built with Laravel.
SmartGym Operations brings member management, memberships, payments,
attendance, equipment monitoring, operating schedules, and notifications
into one centralized application.

> **Project status:** Active development\
> **Framework:** Laravel 13

------------------------------------------------------------------------

## Overview

SmartGym Operations is designed to support the day-to-day administrative
and operational workflows of a gym. It provides role-based access for
administrators, staff, and members, with features for managing gym
records and monitoring operations.

The system is being developed incrementally. This README documents the
implemented workflows and the current local development setup. Features
and configuration may evolve as development continues.

## User Roles

  -----------------------------------------------------------------------
  Role                                Main responsibilities
  ----------------------------------- -----------------------------------
  **Admin**                           Manage gym operations and records,
                                      review refund requests, configure
                                      operating schedules and capacity,
                                      and access administrative
                                      information.

  **Staff**                           Assist with member services,
                                      memberships, payments, attendance,
                                      equipment, and other assigned
                                      operational tasks.

  **Member**                          View personal membership and
                                      payment information, manage account
                                      details, check in and out, and view
                                      personal notifications.
  -----------------------------------------------------------------------

Access to pages and actions is controlled by role-based authorization.

## Key Features

### Account Access and Dashboards

-   Sign in through `/login` using an active account.
-   Admin, Staff, and Member accounts are directed to their
    role-specific dashboards.
-   Authenticated users can update their profile and password through
    `/profile`.
-   Password-reset links use the configured Laravel mailer and expire
    according to the application password-reset configuration.
-   Admin and Staff dashboards summarize attendance and current
    occupancy.
-   The Member dashboard displays the signed-in member's visits,
    membership information, payments, and notifications.

### Members and Memberships

-   Admin and Staff can manage membership plans.
-   Members can request membership purchases and renewals from active
    plans.
-   Admin and Staff can review requests or assign a paid membership on a
    member's behalf.
-   Membership records retain plan name, price, and duration snapshots.
-   Membership activation is recorded together with the corresponding
    operator-confirmed payment in a database transaction.
-   Membership dates follow the system's payment and renewal rules.

### Payments and Refunds

-   Admin and Staff can record, search, and review payments associated
    with memberships.
-   Payment records capture the operator, payment method, date, optional
    reference, and notes.
-   Members can view their own payment history, payment details, and
    printable receipts.
-   Staff can submit refund requests.
-   Admin can approve or reject refund requests.
-   Approved refunds cannot exceed the requested amount or the remaining
    payment balance.

> Payment and refund records are operational records. The application
> does not currently connect to a payment gateway or automatically
> transfer refunded money.

### Attendance and Occupancy

-   Staff can check members in and out.
-   Members can perform their own check-in and check-out.
-   Check-in requires an active member account and an active membership
    whose dates include the current date.
-   Admin and Staff can search and filter attendance history.
-   Attendance corrections require a reason and preserve the original
    and corrected timestamps.
-   Occupancy is calculated from open attendance sessions.
-   Admin can configure maximum gym capacity. The occupancy monitor
    refreshes every five seconds and displays a warning at or above
    capacity without blocking check-in.
-   Open attendance sessions can be automatically closed at the
    configured closing time when the Laravel scheduler is running.

### Equipment and Maintenance

-   Admin and Staff can add, edit, search, and filter equipment.
-   Equipment statuses include Available, Under Maintenance, and
    Unavailable.
-   Staff and Admin can report equipment issues and update maintenance
    records.
-   Reporting an issue marks equipment as Under Maintenance.
-   Equipment and maintenance history are retained.
-   Admin and Staff can record dated equipment usage, which supports
    usage-threshold alerts.

### Operating Schedules and Gym Closures

-   Admin can configure opening intervals for each weekday.
-   Multiple non-overlapping intervals can be configured for a weekday.
-   Staff and Members can view the weekly operating schedule.
-   Admin can publish and edit temporary gym closure announcements.
-   Relevant upcoming and active closure announcements appear in the
    applicable Staff and Member views.

### Notifications and Membership Reminders

-   Admin, Staff, and Members can review their own notification history.
-   Users can mark notifications as read and manage their notification
    records.
-   The system tracks in-system and email delivery status.
-   Membership reminders are scheduled for seven and three days before
    an active membership expires.
-   Reminder processing suppresses duplicates and skips memberships
    superseded by a renewal.

## Technology Stack

The current project uses the following technologies and tools:

-   **Backend:** PHP 8.3 and Laravel 13
-   **Database:** MySQL / MariaDB
-   **Frontend:** Blade templates, HTML, CSS, and JavaScript
-   **CSS tooling:** Tailwind CSS 4 and project-specific stylesheets
-   **Build tool:** Vite
-   **Local development environment:** XAMPP (Apache and MariaDB/MySQL)
-   **Dependency management:** Composer and npm

The frontend implementation may include project-specific styling and
scripts in addition to the framework tooling listed above.

## Requirements

Before setting up the project, install:

-   PHP 8.3 or a compatible version required by the project dependencies
-   Composer
-   Node.js and npm
-   MySQL or MariaDB
-   Git
-   A local PHP development environment such as XAMPP, or equivalent
    services

Enable the PHP extensions required by Laravel and the packages in
`composer.json`.

## Installation and Setup

The following instructions describe a local development setup. They
assume that the repository contains the Laravel application and the
database schema file `schema.sql`.

### 1. Clone the repository

``` bash
git clone https://github.com/bryy-git/Smart-Gym-Operations.git
cd Smart-Gym-Operations
```

If the Laravel application is stored in a subdirectory in the
repository, change into that directory before running the following
commands.

### 2. Install PHP dependencies

``` bash
composer install
```

### 3. Install frontend dependencies

``` bash
npm install
```

### 4. Configure the environment

Create your local environment file:

**Windows Command Prompt**

``` cmd
copy .env.example .env
```

**macOS / Linux**

``` bash
cp .env.example .env
```

Open `.env` and configure the database connection for your local MySQL
or MariaDB server. For example:

``` env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smartgym_operations
DB_USERNAME=root
DB_PASSWORD=
```

Use the username and password configured on your own machine. Do not
copy production credentials into your local environment.

### 5. Create the database

Create a database named `smartgym_operations` in MySQL/MariaDB, or use
another database name and update `DB_DATABASE` in `.env` to match.

### 6. Import the database schema

Import the provided `schema.sql` into the database you created.

You can do this through phpMyAdmin:

1.  Open phpMyAdmin.
2.  Select the `smartgym_operations` database.
3.  Open the **Import** tab.
4.  Choose the project's `schema.sql` file.
5.  Start the import and confirm that the tables were created.

You can also import the SQL file using your MySQL/MariaDB command-line
client.

**Important:** The project currently uses a baseline migration for the
existing database schema. Import `schema.sql` before running Laravel
migrations on a new local database. Do not use
`php artisan migrate:fresh`, `php artisan migrate:refresh`, or
`php artisan db:wipe` against a database containing records you want to
keep.

### 7. Generate the application key

``` bash
php artisan key:generate
```

### 8. Clear cached configuration

``` bash
php artisan config:clear
```

### 9. Build frontend assets

``` bash
npm run build
```

### 10. Run the application

``` bash
php artisan serve
```

Open the local URL displayed by Artisan, usually:

``` text
http://127.0.0.1:8000
```

## Scheduled Tasks and Email Configuration

Some automated workflows depend on Laravel's scheduler. In a local
development environment, run the scheduler in a separate terminal:

``` bash
php artisan schedule:work
```

The scheduler is used for tasks such as automatic closure of open
attendance sessions and membership-expiration reminders. In production,
configure the host's scheduler to execute Laravel's schedule runner
every minute.

The current development configuration uses:

-   `QUEUE_CONNECTION=sync`
-   `MAIL_MAILER=log`

With the `sync` queue, queued jobs run inline. With the `log` mailer,
outgoing email content is written to the application log instead of
being delivered to an external inbox. Configure a real mail transport
and an appropriate queue worker before relying on external email
delivery in production.

## Running Tests

Run the automated test suite with:

``` bash
php artisan test
```

The test environment is configured separately from the local MySQL
database. Review `phpunit.xml` before changing test database settings.

## Project Structure

The Laravel application follows the standard Laravel directory
structure. Important project directories include:

  -----------------------------------------------------------------------
  Directory / File                    Purpose
  ----------------------------------- -----------------------------------
  `app/`                              Application models, controllers,
                                      middleware, and other backend code

  `bootstrap/`                        Laravel application bootstrapping

  `config/`                           Application and service
                                      configuration

  `database/`                         Database migrations and related
                                      database resources

  `public/`                           Public entry point and publicly
                                      served assets

  `resources/`                        Blade views, CSS, and JavaScript
                                      source files

  `routes/`                           Web and API route definitions

  `storage/`                          Logs, cache, and generated
                                      application files

  `tests/`                            Automated tests

  `schema.sql`                        SQL schema used to initialize the
                                      project's database
  -----------------------------------------------------------------------

## Security Notes

-   Never commit `.env` or other files containing passwords, API keys,
    application keys, or private credentials.
-   Keep `.env.example` updated with placeholder values only.
-   Use a unique `APP_KEY` for each environment.
-   Use HTTPS in production.
-   Do not use test accounts or development credentials in a production
    deployment.
-   Back up the database before applying schema changes.
-   Do not expose application logs or private storage files publicly.

## Current Scope and Limitations

The current project scope is a responsive web application for gym
operations.

The following integrations are not part of the current implementation
scope: - RFID / ESP32 hardware-based access control - Payment gateway
integration - SMS notification delivery - Native mobile application

Payment records currently represent payments confirmed and entered by an
authorized operator; they are not proof of a transaction processed by an
online payment provider.

## License

No project-specific license has been specified yet. Unless a license is
added to this repository, all rights remain with the project owner.
Laravel and other third-party dependencies retain their respective
licenses.
#   S m a r t - G y m - O p e r a t i o n s  
 