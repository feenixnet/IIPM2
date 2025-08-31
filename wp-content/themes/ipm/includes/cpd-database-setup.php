<?php
/**
 * IIPM CPD Database Setup - Milestone 2
 * 
 * Creates and manages CPD-related database tables
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create CPD database tables
 */
function iipm_create_cpd_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // CPD Categories table
    $table_cpd_categories = $wpdb->prefix . 'test_iipm_cpd_categories';
    $sql_cpd_categories = "CREATE TABLE $table_cpd_categories (
        id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text NULL,
        min_points_required decimal(4,2) DEFAULT 0.00,
        max_points_allowed decimal(6,2) DEFAULT 999.99,
        is_mandatory tinyint(1) DEFAULT 0,
        sort_order int(11) DEFAULT 0,
        is_active tinyint(1) DEFAULT 1,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY name (name),
        KEY is_active (is_active),
        KEY sort_order (sort_order)
    ) $charset_collate;";
    
    // CPD Courses table (pre-approved courses)
    $table_cpd_courses = $wpdb->prefix . 'test_iipm_cpd_courses';
    $sql_cpd_courses = "CREATE TABLE $table_cpd_courses (
        id int(11) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        description text NULL,
        provider varchar(255) NOT NULL,
        category_id int(11) NOT NULL,
        cpd_points decimal(4,2) NOT NULL,
        course_type enum('webinar','workshop','conference','online','self_study') NOT NULL DEFAULT 'webinar',
        duration_minutes int(11) NULL,
        lia_code varchar(50) NULL,
        external_url varchar(500) NULL,
        is_active tinyint(1) DEFAULT 1,
        approval_status enum('pending','approved','rejected') DEFAULT 'approved',
        approved_by bigint(20) NULL,
        approved_at timestamp NULL,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY category_id (category_id),
        KEY provider (provider),
        KEY is_active (is_active),
        KEY approval_status (approval_status),
        KEY lia_code (lia_code)
    ) $charset_collate;";
    
    // CPD Records table (user's CPD activities)
    $table_cpd_records = $wpdb->prefix . 'test_iipm_cpd_records';
    $sql_cpd_records = "CREATE TABLE $table_cpd_records (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        course_id int(11) NULL,
        category_id int(11) NOT NULL,
        activity_title varchar(255) NOT NULL,
        description text NULL,
        external_provider varchar(255) NULL,
        cpd_points decimal(4,2) NOT NULL,
        completion_date date NOT NULL,
        cpd_year int(4) NOT NULL,
        status enum('pending','approved','rejected') DEFAULT 'pending',
        certificate_path varchar(500) NULL,
        notes text NULL,
        reviewed_by bigint(20) NULL,
        reviewed_at timestamp NULL,
        review_notes text NULL,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY course_id (course_id),
        KEY category_id (category_id),
        KEY cpd_year (cpd_year),
        KEY status (status),
        KEY completion_date (completion_date)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_cpd_categories);
    dbDelta($sql_cpd_courses);
    dbDelta($sql_cpd_records);
    
    // Insert default CPD categories
    iipm_insert_default_cpd_categories();
    
    // Insert sample CPD courses
    iipm_insert_sample_cpd_courses();
    
    error_log('IIPM: CPD database tables created successfully');
}

/**
 * Insert default CPD categories - Updated to match Figma design
 */
function iipm_insert_default_cpd_categories() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'test_iipm_cpd_categories';
    
    // Check if categories already exist
    $existing_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    if ($existing_count > 0) {
        // Update existing categories to match Figma design
        $wpdb->query("UPDATE $table_name SET min_points_required = 1.0 WHERE name IN ('Pensions', 'Savings & Investment', 'Ethics', 'Life Assurance')");
        return;
    }
    
    // Categories matching Figma design - each requires 1 point
    $categories = array(
        array(
            'name' => 'Pensions',
            'description' => 'Pension scheme administration, regulation, and best practices',
            'min_points_required' => 1.0,
            'max_points_allowed' => 999.99,
            'is_mandatory' => 1,
            'sort_order' => 1
        ),
        array(
            'name' => 'Savings & Investment',
            'description' => 'Investment strategies, savings products, and financial planning',
            'min_points_required' => 1.0,
            'max_points_allowed' => 999.99,
            'is_mandatory' => 1,
            'sort_order' => 2
        ),
        array(
            'name' => 'Ethics',
            'description' => 'Professional ethics, conduct, and regulatory compliance',
            'min_points_required' => 1.0,
            'max_points_allowed' => 999.99,
            'is_mandatory' => 1,
            'sort_order' => 3
        ),
        array(
            'name' => 'Life Assurance',
            'description' => 'Life insurance products, underwriting, and claims management',
            'min_points_required' => 1.0,
            'max_points_allowed' => 999.99,
            'is_mandatory' => 1,
            'sort_order' => 4
        ),

    );
    
    foreach ($categories as $category) {
        $wpdb->insert($table_name, $category);
    }
    
    error_log('IIPM: Default CPD categories inserted successfully');
}

/**
 * Insert sample CPD courses - Expanded with more realistic data
 */
function iipm_insert_sample_cpd_courses() {
    global $wpdb;
    
    $courses_table = $wpdb->prefix . 'test_iipm_cpd_courses';
    $categories_table = $wpdb->prefix . 'test_iipm_cpd_categories';
    
    // Check if courses already exist
    $existing_count = $wpdb->get_var("SELECT COUNT(*) FROM $courses_table");
    if ($existing_count > 0) {
        return; // Courses already exist
    }
    
    // Get category IDs
    $pensions_cat = $wpdb->get_var("SELECT id FROM $categories_table WHERE name = 'Pensions'");
    $ethics_cat = $wpdb->get_var("SELECT id FROM $categories_table WHERE name = 'Ethics'");
    $savings_cat = $wpdb->get_var("SELECT id FROM $categories_table WHERE name = 'Savings & Investment'");
    $life_cat = $wpdb->get_var("SELECT id FROM $categories_table WHERE name = 'Life Assurance'");
    
    $sample_courses = array(
        // Life Assurance courses (matching Figma design)
        array(
            'title' => 'SME week Day Five Building a solid defence – group protection for SMEs',
            'description' => 'Comprehensive overview of group protection schemes for small and medium enterprises',
            'provider' => 'Irish Life',
            'category_id' => $life_cat,
            'cpd_points' => 1.0,
            'course_type' => 'webinar',
            'duration_minutes' => 60,
            'lia_code' => 'LIA18419_2025',
            'created_at' => '2025-02-14 10:00:00'
        ),
        array(
            'title' => 'SME week Day Five Building a solid defence – group protection for SMEs',
            'description' => 'Comprehensive overview of group protection schemes for small and medium enterprises',
            'provider' => 'Irish Life',
            'category_id' => $life_cat,
            'cpd_points' => 1.0,
            'course_type' => 'webinar',
            'duration_minutes' => 60,
            'lia_code' => 'LIA18420_2025',
            'created_at' => '2025-02-15 10:00:00'
        ),
        array(
            'title' => 'SME week Day Five Building a solid defence – group protection for SMEs',
            'description' => 'Comprehensive overview of group protection schemes for small and medium enterprises',
            'provider' => 'Irish Life',
            'category_id' => $life_cat,
            'cpd_points' => 1.0,
            'course_type' => 'webinar',
            'duration_minutes' => 60,
            'lia_code' => 'LIA18421_2025',
            'created_at' => '2025-02-16 10:00:00'
        ),
        array(
            'title' => 'SME week Day Five Building a solid defence – group protection for SMEs',
            'description' => 'Comprehensive overview of group protection schemes for small and medium enterprises',
            'provider' => 'Irish Life',
            'category_id' => $life_cat,
            'cpd_points' => 1.0,
            'course_type' => 'webinar',
            'duration_minutes' => 60,
            'lia_code' => 'LIA18422_2025',
            'created_at' => '2025-02-17 10:00:00'
        ),
        array(
            'title' => 'SME week Day Five Building a solid defence – group protection for SMEs',
            'description' => 'Comprehensive overview of group protection schemes for small and medium enterprises',
            'provider' => 'Irish Life',
            'category_id' => $life_cat,
            'cpd_points' => 1.0,
            'course_type' => 'webinar',
            'duration_minutes' => 60,
            'lia_code' => 'LIA18423_2025',
            'created_at' => '2025-02-18 10:00:00'
        ),
        array(
            'title' => 'SME week Day Five Building a solid defence – group protection for SMEs',
            'description' => 'Comprehensive overview of group protection schemes for small and medium enterprises',
            'provider' => 'Irish Life',
            'category_id' => $life_cat,
            'cpd_points' => 1.0,
            'course_type' => 'webinar',
            'duration_minutes' => 60,
            'lia_code' => 'LIA18424_2025',
            'created_at' => '2025-02-19 10:00:00'
        ),
        // Pensions courses
        array(
            'title' => 'Sustainability in Pensions',
            'description' => 'ESG considerations and sustainable investing in pension schemes',
            'provider' => 'IIPM',
            'category_id' => $pensions_cat,
            'cpd_points' => 1.0,
            'course_type' => 'workshop',
            'duration_minutes' => 180,
            'lia_code' => 'IIPM2025_002',
            'created_at' => '2025-01-15 09:00:00'
        ),
        array(
            'title' => 'Pension Auto-Enrolment Updates',
            'description' => 'Latest developments in automatic enrolment legislation and implementation',
            'provider' => 'IIPM',
            'category_id' => $pensions_cat,
            'cpd_points' => 1.0,
            'course_type' => 'webinar',
            'duration_minutes' => 150,
            'lia_code' => 'IIPM2025_003',
            'created_at' => '2025-01-20 14:00:00'
        ),
        array(
            'title' => 'Defined Benefit Scheme Management',
            'description' => 'Best practices for managing defined benefit pension schemes',
            'provider' => 'Pension Authority',
            'category_id' => $pensions_cat,
            'cpd_points' => 1.0,
            'course_type' => 'conference',
            'duration_minutes' => 240,
            'lia_code' => 'PA2025_001',
            'created_at' => '2025-01-25 10:00:00'
        ),
        // Ethics courses
        array(
            'title' => 'Professional Ethics in Financial Services',
            'description' => 'Core ethical principles and practical applications in financial services',
            'provider' => 'IIPM',
            'category_id' => $ethics_cat,
            'cpd_points' => 1.0,
            'course_type' => 'online',
            'duration_minutes' => 120,
            'lia_code' => 'IIPM2025_004',
            'created_at' => '2025-01-10 11:00:00'
        ),
        array(
            'title' => 'Conflicts of Interest Management',
            'description' => 'Identifying and managing conflicts of interest in pension management',
            'provider' => 'Ethics Institute',
            'category_id' => $ethics_cat,
            'cpd_points' => 1.0,
            'course_type' => 'webinar',
            'duration_minutes' => 90,
            'lia_code' => 'EI2025_001',
            'created_at' => '2025-01-12 15:00:00'
        ),
        // Savings & Investment courses
        array(
            'title' => 'Investment Strategy for Pension Schemes',
            'description' => 'Modern portfolio theory and investment strategies for pension funds',
            'provider' => 'Investment Institute',
            'category_id' => $savings_cat,
            'cpd_points' => 1.0,
            'course_type' => 'conference',
            'duration_minutes' => 240,
            'lia_code' => 'II2025_001',
            'created_at' => '2025-01-08 09:00:00'
        ),
        array(
            'title' => 'ESG Investment Principles',
            'description' => 'Environmental, Social, and Governance factors in investment decisions',
            'provider' => 'Sustainable Finance Ireland',
            'category_id' => $savings_cat,
            'cpd_points' => 1.0,
            'course_type' => 'workshop',
            'duration_minutes' => 180,
            'lia_code' => 'SFI2025_001',
            'created_at' => '2025-01-18 13:00:00'
        ),

    );
    
    foreach ($sample_courses as $course) {
        $wpdb->insert($courses_table, $course);
    }
    
    error_log('IIPM: Sample CPD courses inserted successfully');
}

/**
 * Check if CPD tables exist
 */
function iipm_cpd_tables_exist() {
    global $wpdb;
    
    $tables = array(
        'test_iipm_cpd_categories',
        'test_iipm_cpd_courses',
        'test_iipm_cpd_records'
    );
    
    foreach ($tables as $table) {
        $full_table_name = $wpdb->prefix . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
        if (!$exists) {
            return false;
        }
    }
    
    return true;
}

/**
 * Create CPD certificates table for Milestone 4
 */
function iipm_create_cpd_certificates_table() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // CPD Certificates table
    $table_cpd_certificates = $wpdb->prefix . 'test_iipm_cpd_certificates';
    $sql_cpd_certificates = "CREATE TABLE $table_cpd_certificates (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        cpd_year int(4) NOT NULL,
        certificate_number varchar(50) NOT NULL UNIQUE,
        certification_type enum('annual','category','completion') DEFAULT 'annual',
        category_id int(11) NULL,
        total_cpd_points decimal(6,2) NOT NULL,
        required_points decimal(6,2) NOT NULL,
        compliance_status enum('compliant','non_compliant','partial') DEFAULT 'compliant',
        certificate_status enum('pending','issued','revoked') DEFAULT 'pending',
        issue_method enum('automatic','manual') DEFAULT 'automatic',
        certificate_file_path varchar(500) NULL,
        pdf_generated tinyint(1) DEFAULT 0,
        issued_date timestamp NULL,
        issued_by bigint(20) NULL,
        expiry_date date NULL,
        revoked_date timestamp NULL,
        revoked_by bigint(20) NULL,
        revocation_reason text NULL,
        email_sent tinyint(1) DEFAULT 0,
        email_sent_date timestamp NULL,
        download_count int(11) DEFAULT 0,
        last_downloaded timestamp NULL,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY certificate_number (certificate_number),
        KEY user_id (user_id),
        KEY cpd_year (cpd_year),
        KEY category_id (category_id),
        KEY certificate_status (certificate_status),
        KEY compliance_status (compliance_status),
        KEY issued_date (issued_date)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_cpd_certificates);
    
    error_log('IIPM: CPD certificates table created successfully');
}

/**
 * Create CPD returns table for tracking annual CPD submissions
 */
function iipm_create_cpd_returns_table() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // CPD Returns table
    $table_cpd_returns = $wpdb->prefix . 'test_iipm_cpd_returns';
    $sql_cpd_returns = "CREATE TABLE $table_cpd_returns (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        cpd_year int(4) NOT NULL,
        submission_date timestamp DEFAULT CURRENT_TIMESTAMP,
        total_cpd_points decimal(6,2) NOT NULL,
        required_points decimal(6,2) NOT NULL,
        category_breakdown text NULL,
        compliance_status enum('compliant','non_compliant','partial') DEFAULT 'compliant',
        submission_status enum('submitted','processed','certificate_issued') DEFAULT 'submitted',
        notes text NULL,
        processed_by bigint(20) NULL,
        processed_date timestamp NULL,
        certificate_issued tinyint(1) DEFAULT 0,
        certificate_issued_date timestamp NULL,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_year (user_id, cpd_year),
        KEY user_id (user_id),
        KEY cpd_year (cpd_year),
        KEY submission_status (submission_status),
        KEY compliance_status (compliance_status),
        KEY submission_date (submission_date)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_cpd_returns);
    
    error_log('IIPM: CPD returns table created successfully');
}

// Hook to create tables on theme activation
add_action('after_switch_theme', 'iipm_create_cpd_tables');
?>
