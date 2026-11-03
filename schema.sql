-- SmartGym Operations
-- Fresh database schema based on the approved SRS and Phase 4 ERD.
-- Target: XAMPP MariaDB 10.4+
-- This script creates a NEW database. Back up any existing database first.
-- It does not include deferred RFID hardware tables.

CREATE DATABASE IF NOT EXISTS smartgym_operations
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE smartgym_operations;

-- No existing tables are dropped by this script.
-- Run this against a new/empty database. If tables already exist, stop and review first.

CREATE TABLE users (
    user_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NULL,
    email VARCHAR(190) NOT NULL,
    password VARCHAR(255) NOT NULL COMMENT 'Store only Laravel Hash output',
    role ENUM('Admin', 'Staff', 'Member') NOT NULL,
    account_status ENUM('Active', 'Disabled') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role_status (role, account_status)
) ENGINE=InnoDB;

CREATE TABLE online_applications (
    application_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    date_of_birth DATE NULL,
    email_verification_status ENUM('Unverified', 'Verified') NOT NULL DEFAULT 'Unverified',
    application_status ENUM('Pending', 'Approved', 'Rejected', 'Expired') NOT NULL DEFAULT 'Pending',
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    decision_reason TEXT NULL,
    expires_at DATETIME NOT NULL,
    KEY idx_applications_status_submitted (application_status, submitted_at),
    KEY idx_applications_email (email),
    CONSTRAINT fk_applications_reviewer
      FOREIGN KEY (reviewed_by) REFERENCES users(user_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_application_expiry CHECK (expires_at > submitted_at)
) ENGINE=InnoDB;

CREATE TABLE members (
    member_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    application_id BIGINT UNSIGNED NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    date_of_birth DATE NULL,
    member_status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_members_user (user_id),
    UNIQUE KEY uq_members_application (application_id),
    KEY idx_members_name (last_name, first_name),
    KEY idx_members_status (member_status),
    CONSTRAINT fk_members_user
      FOREIGN KEY (user_id) REFERENCES users(user_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_members_application
      FOREIGN KEY (application_id) REFERENCES online_applications(application_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_members_created_by
      FOREIGN KEY (created_by) REFERENCES users(user_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE membership_plans (
    plan_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    duration_days INT UNSIGNED NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    plan_status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_membership_plans_name (plan_name),
    CONSTRAINT chk_plan_duration CHECK (duration_days > 0),
    CONSTRAINT chk_plan_price CHECK (price > 0)
) ENGINE=InnoDB;

CREATE TABLE memberships (
    membership_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    plan_name_snapshot VARCHAR(120) NOT NULL,
    plan_price_snapshot DECIMAL(10,2) NOT NULL,
    plan_duration_snapshot INT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    membership_status ENUM('Active', 'Expired', 'Suspended') NOT NULL DEFAULT 'Active',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_memberships_member_status (member_id, membership_status),
    KEY idx_memberships_end_date (end_date),
    KEY idx_memberships_plan (plan_id),
    CONSTRAINT fk_memberships_member
      FOREIGN KEY (member_id) REFERENCES members(member_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_memberships_plan
      FOREIGN KEY (plan_id) REFERENCES membership_plans(plan_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_memberships_created_by
      FOREIGN KEY (created_by) REFERENCES users(user_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_membership_dates CHECK (end_date >= start_date),
    CONSTRAINT chk_membership_snapshot_price CHECK (plan_price_snapshot > 0),
    CONSTRAINT chk_membership_snapshot_duration CHECK (plan_duration_snapshot > 0)
) ENGINE=InnoDB;

CREATE TABLE membership_requests (
    request_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NULL,
    plan_id BIGINT UNSIGNED NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    request_type ENUM('Purchase', 'Renewal', 'Suspension', 'Reactivation', 'Cancellation') NOT NULL,
    request_status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    request_reason TEXT NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    decision_reason TEXT NULL,
    KEY idx_membership_requests_member_status (member_id, request_status),
    KEY idx_membership_requests_reviewer (reviewed_by),
    CONSTRAINT fk_membership_requests_member
      FOREIGN KEY (member_id) REFERENCES members(member_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_membership_requests_membership
      FOREIGN KEY (membership_id) REFERENCES memberships(membership_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_membership_requests_plan
      FOREIGN KEY (plan_id) REFERENCES membership_plans(plan_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_membership_requests_requester
      FOREIGN KEY (requested_by) REFERENCES users(user_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_membership_requests_reviewer
      FOREIGN KEY (reviewed_by) REFERENCES users(user_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE payments (
    payment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    membership_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash', 'GCash', 'Card', 'Other') NOT NULL,
    reference_number VARCHAR(150) NULL,
    payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    received_by BIGINT UNSIGNED NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_payments_membership_date (membership_id, payment_date),
    KEY idx_payments_received_by (received_by),
    CONSTRAINT fk_payments_membership
      FOREIGN KEY (membership_id) REFERENCES memberships(membership_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_payments_receiver
      FOREIGN KEY (received_by) REFERENCES users(user_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_payment_amount CHECK (amount > 0)
) ENGINE=InnoDB;

CREATE TABLE refunds (
    refund_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id BIGINT UNSIGNED NOT NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    requested_amount DECIMAL(10,2) NOT NULL,
    reason TEXT NOT NULL,
    refund_status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    approved_amount DECIMAL(10,2) NULL,
    decision_reason TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_refunds_payment_status (payment_id, refund_status),
    CONSTRAINT fk_refunds_payment
      FOREIGN KEY (payment_id) REFERENCES payments(payment_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_refunds_requester
      FOREIGN KEY (requested_by) REFERENCES users(user_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_refunds_reviewer
      FOREIGN KEY (reviewed_by) REFERENCES users(user_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_refund_requested_amount CHECK (requested_amount > 0),
    CONSTRAINT chk_refund_approved_amount CHECK (
      (refund_status = 'Approved' AND approved_amount IS NOT NULL AND approved_amount > 0)
      OR (refund_status <> 'Approved' AND (approved_amount IS NULL OR approved_amount > 0))
    )
) ENGINE=InnoDB;

CREATE TABLE attendance (
    attendance_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id BIGINT UNSIGNED NOT NULL,
    check_in_at DATETIME NOT NULL,
    check_out_at DATETIME NULL,
    attendance_source ENUM('Staff-assisted', 'Member self-service') NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_attendance_member_checkin (member_id, check_in_at),
    KEY idx_attendance_open (check_out_at, member_id),
    CONSTRAINT fk_attendance_member
      FOREIGN KEY (member_id) REFERENCES members(member_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_attendance_checkout CHECK (
      check_out_at IS NULL OR check_out_at > check_in_at
    )
) ENGINE=InnoDB;

CREATE TABLE attendance_corrections (
    correction_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attendance_id BIGINT UNSIGNED NOT NULL,
    corrected_by BIGINT UNSIGNED NOT NULL,
    original_check_in_at DATETIME NOT NULL,
    corrected_check_in_at DATETIME NOT NULL,
    original_check_out_at DATETIME NULL,
    corrected_check_out_at DATETIME NULL,
    correction_reason TEXT NOT NULL,
    corrected_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_attendance_corrections_attendance (attendance_id, corrected_at),
    CONSTRAINT fk_attendance_corrections_attendance
      FOREIGN KEY (attendance_id) REFERENCES attendance(attendance_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_attendance_corrections_user
      FOREIGN KEY (corrected_by) REFERENCES users(user_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_corrected_checkout CHECK (
      corrected_check_out_at IS NULL OR corrected_check_out_at > corrected_check_in_at
    )
) ENGINE=InnoDB;

CREATE TABLE equipment (
    equipment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipment_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    equipment_status ENUM('Available', 'Under Maintenance', 'Unavailable') NOT NULL DEFAULT 'Available',
    usage_threshold INT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_equipment_status (equipment_status)
) ENGINE=InnoDB;

CREATE TABLE maintenance_records (
    maintenance_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipment_id BIGINT UNSIGNED NOT NULL,
    reported_by BIGINT UNSIGNED NOT NULL,
    issue_description TEXT NOT NULL,
    maintenance_status ENUM('Reported', 'In Progress', 'Resolved') NOT NULL DEFAULT 'Reported',
    action_details TEXT NULL,
    reported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_maintenance_equipment_status (equipment_id, maintenance_status),
    CONSTRAINT fk_maintenance_equipment
      FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_maintenance_reporter
      FOREIGN KEY (reported_by) REFERENCES users(user_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_maintenance_updater
      FOREIGN KEY (updated_by) REFERENCES users(user_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE equipment_usage (
    usage_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipment_id BIGINT UNSIGNED NOT NULL,
    recorded_by BIGINT UNSIGNED NOT NULL,
    usage_count INT UNSIGNED NOT NULL,
    usage_date DATE NOT NULL,
    notes TEXT NULL,
    KEY idx_equipment_usage_equipment_date (equipment_id, usage_date),
    CONSTRAINT fk_equipment_usage_equipment
      FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_equipment_usage_recorder
      FOREIGN KEY (recorded_by) REFERENCES users(user_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE notifications (
    notification_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    notification_type VARCHAR(80) NOT NULL,
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notifications_user_read_date (user_id, is_read, created_at),
    CONSTRAINT fk_notifications_user
      FOREIGN KEY (user_id) REFERENCES users(user_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE notification_deliveries (
    delivery_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notification_id BIGINT UNSIGNED NOT NULL,
    delivery_channel ENUM('In-system', 'Email') NOT NULL,
    delivery_status ENUM('Pending', 'Sent', 'Failed') NOT NULL DEFAULT 'Pending',
    sent_at DATETIME NULL,
    error_details TEXT NULL,
    UNIQUE KEY uq_notification_delivery_channel (notification_id, delivery_channel),
    CONSTRAINT fk_notification_deliveries_notification
      FOREIGN KEY (notification_id) REFERENCES notifications(notification_id)
      ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
    activity_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    affected_entity VARCHAR(100) NOT NULL,
    affected_record_id BIGINT UNSIGNED NULL COMMENT 'Polymorphic reference; intentionally no FK',
    old_values LONGTEXT NULL,
    new_values LONGTEXT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_activity_logs_user_date (user_id, created_at),
    KEY idx_activity_logs_entity_record (affected_entity, affected_record_id),
    CONSTRAINT fk_activity_logs_user
      FOREIGN KEY (user_id) REFERENCES users(user_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE system_settings (
    setting_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL,
    setting_value TEXT NULL,
    description TEXT NULL,
    updated_by BIGINT UNSIGNED NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_system_settings_key (setting_key),
    CONSTRAINT fk_system_settings_updater
      FOREIGN KEY (updated_by) REFERENCES users(user_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE backup_records (
    backup_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    backup_type ENUM('Automatic', 'Manual') NOT NULL,
    storage_location VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    backup_status ENUM('Pending', 'Completed', 'Failed') NOT NULL DEFAULT 'Pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    restored_at DATETIME NULL,
    restored_by BIGINT UNSIGNED NULL,
    restoration_status ENUM('Pending', 'Completed', 'Failed') NULL,
    KEY idx_backup_records_created_at (created_at),
    CONSTRAINT fk_backup_records_creator
      FOREIGN KEY (created_by) REFERENCES users(user_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_backup_records_restorer
      FOREIGN KEY (restored_by) REFERENCES users(user_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE operating_schedules (
    schedule_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    day_of_week TINYINT UNSIGNED NOT NULL COMMENT '1=Monday through 7=Sunday',
    opening_time TIME NOT NULL,
    closing_time TIME NOT NULL,
    is_open BOOLEAN NOT NULL DEFAULT TRUE,
    updated_by BIGINT UNSIGNED NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_operating_schedules_day (day_of_week, opening_time),
    CONSTRAINT fk_operating_schedules_updater
      FOREIGN KEY (updated_by) REFERENCES users(user_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_schedule_day CHECK (day_of_week BETWEEN 1 AND 7),
    CONSTRAINT chk_schedule_time CHECK (closing_time > opening_time)
) ENGINE=InnoDB;

CREATE TABLE gym_closures (
    closure_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    reason TEXT NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    closure_status ENUM('Scheduled', 'Active', 'Completed') NOT NULL DEFAULT 'Scheduled',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_gym_closures_period (start_datetime, end_datetime),
    KEY idx_gym_closures_status (closure_status),
    CONSTRAINT fk_gym_closures_creator
      FOREIGN KEY (created_by) REFERENCES users(user_id)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_gym_closure_period CHECK (end_datetime > start_datetime)
) ENGINE=InnoDB;

-- Application-level rules that must also be implemented in Laravel:
-- 1. One open attendance session per member (transaction + row locking).
-- 2. Sum of approved refunds for a payment must never exceed payment amount.
-- 3. Purchase requests require plan_id; other request types follow business rules.
-- 4. Membership activation requires confirmed payment.
-- 5. Member.member_status and linked User.account_status change together in a transaction.
-- 6. Online application expires 30 days after submission.
-- 7. Membership reminders are scheduled 7 and 3 days before expiry with duplicate suppression.
-- 8. Prevent overlapping operating schedule periods for the same day.
-- 9. Enforce role-specific permissions and prevent Staff from approving their own requests.
-- 10. Validate role-specific User names, non-future DOB, and all business transitions in Laravel.
