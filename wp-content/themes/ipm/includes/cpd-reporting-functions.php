<?php
/**
 * CPD Reporting Functions
 * Handle compliance reporting and analytics
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ensure required database columns exist
 */
function iipm_ensure_cpd_reporting_columns() {
    global $wpdb;
    
    $members_table = $wpdb->prefix . 'test_iipm_members';
    
    // Check if cpd_prorata_adjustment column exists
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$members_table} LIKE 'cpd_prorata_adjustment'");
    
    if (empty($column_exists)) {
        // Add the missing column
        $wpdb->query("ALTER TABLE {$members_table} ADD COLUMN cpd_prorata_adjustment DECIMAL(5,2) DEFAULT 0.00 AFTER cpd_points_current");
    }
}

// Ensure columns exist when file is loaded
iipm_ensure_cpd_reporting_columns();

/**
 * Get CPD compliance statistics
 */
function iipm_get_cpd_compliance_stats($year = null) {
    if (!$year) {
        $year = date('Y');
    }
    
    global $wpdb;
    
    // Get all active members
    $members_table = $wpdb->prefix . 'test_iipm_members';
    $cpd_table = $wpdb->prefix . 'test_iipm_cpd_records';
    
    $total_members = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM {$members_table} 
        WHERE membership_status = 'active'
    ");
    
    // Get members with their CPD progress
    $member_progress = $wpdb->get_results($wpdb->prepare("
        SELECT 
            m.user_id,
            m.cpd_points_required,
            m.cpd_prorata_adjustment,
            COALESCE(SUM(CASE WHEN c.status = 'approved' THEN c.cpd_points ELSE 0 END), 0) as earned_points,
            COUNT(CASE WHEN c.status = 'pending' THEN 1 END) as pending_approvals
        FROM {$members_table} m
        LEFT JOIN {$cpd_table} c ON m.user_id = c.user_id AND c.cpd_year = %d
        WHERE m.membership_status = 'active'
        GROUP BY m.user_id
    ", $year));
    
    $compliant_members = 0;
    $at_risk_members = 0;
    $non_compliant_members = 0;
    $total_cpd_logged = 0;
    $total_pending = 0;
    
    foreach ($member_progress as $member) {
        $required = $member->cpd_points_required - $member->cpd_prorata_adjustment;
        $earned = $member->earned_points;
        $total_cpd_logged += $earned;
        $total_pending += $member->pending_approvals;
        
        if ($earned >= $required) {
            $compliant_members++;
        } elseif ($earned >= ($required * 0.75)) { // 75% or more = at risk
            $at_risk_members++;
        } else {
            $non_compliant_members++;
        }
    }
    
    $average_points = $total_members > 0 ? $total_cpd_logged / $total_members : 0;
    
    return array(
        'total_members' => intval($total_members),
        'compliant_members' => $compliant_members,
        'at_risk_members' => $at_risk_members,
        'non_compliant_members' => $non_compliant_members,
        'average_points' => $average_points,
        'total_cpd_logged' => $total_cpd_logged,
        'pending_approvals' => $total_pending
    );
}

/**
 * Get detailed compliance data for reports
 */
function iipm_get_detailed_compliance_data($year = null) {
    if (!$year) {
        $year = date('Y');
    }
    
    global $wpdb;
    
    $members_table = $wpdb->prefix . 'test_iipm_members';
    $cpd_table = $wpdb->prefix . 'test_iipm_cpd_records';
    $users_table = $wpdb->prefix . 'users';
    
    // Get detailed member data
    $member_data = $wpdb->get_results($wpdb->prepare("
        SELECT 
            u.ID as user_id,
            u.display_name as name,
            u.user_email as email,
            m.cpd_points_required,
            m.cpd_prorata_adjustment,
            COALESCE(SUM(CASE WHEN c.status = 'approved' THEN c.cpd_points ELSE 0 END), 0) as earned_points,
            COUNT(CASE WHEN c.status = 'pending' THEN 1 END) as pending_approvals
        FROM {$users_table} u
        LEFT JOIN {$members_table} m ON u.ID = m.user_id
        LEFT JOIN {$cpd_table} c ON u.ID = c.user_id AND c.cpd_year = %d
        WHERE m.membership_status = 'active'
        GROUP BY u.ID
        ORDER BY u.display_name
    ", $year));
    
    $compliant = array();
    $at_risk = array();
    $non_compliant = array();
    
    $year_end = strtotime($year . '-12-31');
    $today = time();
    $days_left = max(0, ceil(($year_end - $today) / (60 * 60 * 24)));
    
    foreach ($member_data as $member) {
        $required = $member->cpd_points_required - $member->cpd_prorata_adjustment;
        $earned = floatval($member->earned_points);
        $shortage = max(0, $required - $earned);
        $progress_percentage = $required > 0 ? min(100, ($earned / $required) * 100) : 0;
        
        $member_info = array(
            'user_id' => $member->user_id,
            'name' => $member->name ?: 'Unknown User',
            'email' => $member->email,
            'earned_points' => $earned,
            'required_points' => $required,
            'shortage' => $shortage,
            'progress_percentage' => round($progress_percentage, 1),
            'days_left' => $days_left,
            'pending_approvals' => $member->pending_approvals
        );
        
        if ($earned >= $required) {
            $compliant[] = $member_info;
        } elseif ($earned >= ($required * 0.75)) {
            $at_risk[] = $member_info;
        } else {
            $non_compliant[] = $member_info;
        }
    }
    
    return array(
        'compliant' => $compliant,
        'at_risk' => $at_risk,
        'non_compliant' => $non_compliant
    );
}

/**
 * Get CPD data by categories
 */
function iipm_get_cpd_by_categories($year = null) {
    if (!$year) {
        $year = date('Y');
    }
    
    global $wpdb;
    
    $categories_table = $wpdb->prefix . 'test_iipm_cpd_categories';
    $cpd_table = $wpdb->prefix . 'test_iipm_cpd_records';
    
    return $wpdb->get_results($wpdb->prepare("
        SELECT 
            cat.name as category_name,
            cat.min_points_required,
            COUNT(c.id) as total_entries,
            SUM(CASE WHEN c.status = 'approved' THEN c.cpd_points ELSE 0 END) as total_points,
            AVG(CASE WHEN c.status = 'approved' THEN c.cpd_points ELSE 0 END) as avg_points,
            COUNT(DISTINCT c.user_id) as unique_members
        FROM {$categories_table} cat
        LEFT JOIN {$cpd_table} c ON cat.id = c.category_id AND c.cpd_year = %d
        WHERE cat.is_active = 1
        GROUP BY cat.id
        ORDER BY cat.sort_order
    ", $year));
}

/**
 * AJAX handler for compliance data
 */
function iipm_handle_get_compliance_data() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_reports_nonce')) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    $year = intval($_POST['year'] ?? date('Y'));
    $data = iipm_get_detailed_compliance_data($year);
    
    wp_send_json_success($data);
}

/**
 * AJAX handler for exporting reports
 */
function iipm_handle_export_report() {
    // Verify nonce
    if (!wp_verify_nonce($_GET['nonce'], 'iipm_reports_nonce')) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('administrator')) {
        wp_die('Insufficient permissions');
    }
    
    $type = sanitize_text_field($_GET['type']);
    $year = intval($_GET['year'] ?? date('Y'));
    
    switch ($type) {
        case 'compliance':
            iipm_export_compliance_report($year);
            break;
        case 'non-compliant':
            iipm_export_non_compliant_report($year);
            break;
        case 'all-members':
            iipm_export_all_members_report($year);
            break;
        case 'categories':
            iipm_export_categories_report($year);
            break;
        default:
            wp_die('Invalid report type');
    }
}

/**
 * Export compliance report as CSV
 */
function iipm_export_compliance_report($year) {
    $data = iipm_get_detailed_compliance_data($year);
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=cpd-compliance-report-' . $year . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Create file handle
    $output = fopen('php://output', 'w');
    
    // Write CSV headers
    fputcsv($output, array(
        'Name',
        'Email',
        'Status',
        'CPD Points Earned',
        'CPD Points Required',
        'Shortage',
        'Progress %',
        'Pending Approvals'
    ));
    
    // Write compliant members
    foreach ($data['compliant'] as $member) {
        fputcsv($output, array(
            $member['name'],
            $member['email'],
            'Compliant',
            $member['earned_points'],
            $member['required_points'],
            0,
            $member['progress_percentage'] . '%',
            $member['pending_approvals']
        ));
    }
    
    // Write at-risk members
    foreach ($data['at_risk'] as $member) {
        fputcsv($output, array(
            $member['name'],
            $member['email'],
            'At Risk',
            $member['earned_points'],
            $member['required_points'],
            $member['shortage'],
            $member['progress_percentage'] . '%',
            $member['pending_approvals']
        ));
    }
    
    // Write non-compliant members
    foreach ($data['non_compliant'] as $member) {
        fputcsv($output, array(
            $member['name'],
            $member['email'],
            'Non-Compliant',
            $member['earned_points'],
            $member['required_points'],
            $member['shortage'],
            $member['progress_percentage'] . '%',
            $member['pending_approvals']
        ));
    }
    
    fclose($output);
    exit;
}

/**
 * Export non-compliant members only
 */
function iipm_export_non_compliant_report($year) {
    $data = iipm_get_detailed_compliance_data($year);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=non-compliant-members-' . $year . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    fputcsv($output, array(
        'Name',
        'Email',
        'CPD Points Earned',
        'CPD Points Required',
        'Shortage',
        'Progress %'
    ));
    
    foreach ($data['non_compliant'] as $member) {
        fputcsv($output, array(
            $member['name'],
            $member['email'],
            $member['earned_points'],
            $member['required_points'],
            $member['shortage'],
            $member['progress_percentage'] . '%'
        ));
    }
    
    fclose($output);
    exit;
}

/**
 * Send bulk reminders to members
 */
function iipm_handle_send_bulk_reminders() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_reports_nonce')) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    $type = sanitize_text_field($_POST['type']);
    $year = intval($_POST['year'] ?? date('Y'));
    
    $data = iipm_get_detailed_compliance_data($year);
    $members_to_remind = array();
    
    switch ($type) {
        case 'non-compliant':
            $members_to_remind = $data['non_compliant'];
            $subject = 'URGENT: CPD Compliance Required';
            break;
        case 'at-risk':
            $members_to_remind = $data['at_risk'];
            $subject = 'CPD Progress Reminder';
            break;
        case 'all':
            $members_to_remind = array_merge($data['non_compliant'], $data['at_risk']);
            $subject = 'CPD Progress Update';
            break;
        default:
            wp_send_json_error(array('message' => 'Invalid reminder type'));
    }
    
    $sent_count = 0;
    
    foreach ($members_to_remind as $member) {
        $email_sent = iipm_send_cpd_reminder_email($member, $subject, $year);
        if ($email_sent) {
            $sent_count++;
        }
    }
    
    wp_send_json_success(array(
        'sent_count' => $sent_count,
        'total_members' => count($members_to_remind)
    ));
}

/**
 * Send individual CPD reminder email
 */
function iipm_send_cpd_reminder_email($member, $subject, $year) {
    $to = $member['email'];
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    $shortage_text = '';
    if ($member['shortage'] > 0) {
        $shortage_text = "You currently need <strong>{$member['shortage']} more CPD points</strong> to meet your annual requirement.";
    }
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #667eea;'>CPD Progress Update - {$year}</h2>
            
            <p>Dear {$member['name']},</p>
            
            <p>This is a reminder about your Continuing Professional Development (CPD) progress for {$year}.</p>
            
            <div style='background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                <h3 style='margin-top: 0; color: #374151;'>Your CPD Progress:</h3>
                <ul style='list-style: none; padding: 0;'>
                    <li><strong>Points Earned:</strong> {$member['earned_points']}</li>
                    <li><strong>Points Required:</strong> {$member['required_points']}</li>
                    <li><strong>Progress:</strong> {$member['progress_percentage']}%</li>
                </ul>
                {$shortage_text}
            </div>
            
            <p>To log additional CPD activities, please visit your member portal:</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='" . home_url('/cpd-portal/') . "' style='background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold;'>
                    Log CPD Activities
                </a>
            </div>
            
            <p>If you have any questions about CPD requirements or need assistance, please contact us.</p>
            
            <p>Best regards,<br>IIPM Administration</p>
            
            <hr style='margin: 30px 0; border: none; border-top: 1px solid #e5e7eb;'>
            <p style='font-size: 12px; color: #6b7280;'>
                This email was sent from the IIPM Member Portal. 
                Days remaining in {$year}: {$member['days_left']}
            </p>
        </div>
    </body>
    </html>
    ";
    
    return wp_mail($to, $subject, $message, $headers);
}

/**
 * Get individual member CPD report data
 */
function iipm_get_individual_member_report($user_id, $year = null) {
    if (!$year) {
        $year = date('Y');
    }
    
    global $wpdb;
    
    // Get member basic info - ensure member record exists and is active
    $member = $wpdb->get_row($wpdb->prepare("
        SELECT u.ID, u.display_name, u.user_email,
               m.cpd_points_required, m.cpd_prorata_adjustment, m.membership_level
        FROM {$wpdb->users} u
        INNER JOIN {$wpdb->prefix}test_iipm_members m ON u.ID = m.user_id
        WHERE u.ID = %d AND m.membership_status = 'active'
    ", $user_id));
    
    // Get first_name and last_name from user meta
    if ($member) {
        $member->first_name = get_user_meta($user_id, 'first_name', true);
        $member->last_name = get_user_meta($user_id, 'last_name', true);
    }
    
    if (!$member) {
        error_log("IIPM: Member not found or not active for user_id: " . $user_id);
        return false;
    }
    
    // Get CPD records by category
    $cpd_records = $wpdb->get_results($wpdb->prepare("
        SELECT c.*, cat.name as category_name, cat.min_points_required,
               course.title as course_title, course.provider
        FROM {$wpdb->prefix}test_iipm_cpd_records c
        LEFT JOIN {$wpdb->prefix}test_iipm_cpd_categories cat ON c.category_id = cat.id
        LEFT JOIN {$wpdb->prefix}test_iipm_cpd_courses course ON c.course_id = course.id
        WHERE c.user_id = %d AND c.cpd_year = %d
        ORDER BY c.created_at DESC
    ", $user_id, $year));
    
    // Initialize categories with proper name mapping
    $categories = array(
        'Pensions' => array('name' => 'Pensions', 'min_required' => 10, 'earned' => 0, 'courses' => array()),
        'Savings & Investment' => array('name' => 'Savings & Investment', 'min_required' => 10, 'earned' => 0, 'courses' => array()),
        'Ethics' => array('name' => 'Ethics', 'min_required' => 10, 'earned' => 0, 'courses' => array()),
        'Life Assurance' => array('name' => 'Life Assurance', 'min_required' => 10, 'earned' => 0, 'courses' => array())
    );
    
    $total_earned = 0;
    $all_courses = array();
    
    foreach ($cpd_records as $record) {
        if ($record->status === 'approved') {
            $total_earned += $record->cpd_points;
            
            // Add to category totals - use the actual category name from database
            if (isset($categories[$record->category_name])) {
                $categories[$record->category_name]['earned'] += $record->cpd_points;
                $categories[$record->category_name]['courses'][] = $record;
            } else {
                // Log unmatched categories for debugging
                error_log("IIPM: Unmatched category: '{$record->category_name}' for record ID {$record->id}");
            }
        }
        
        // Add to all courses list
        $all_courses[] = $record;
    }
    
    $required_points = $member->cpd_points_required - $member->cpd_prorata_adjustment;
    $progress_percentage = $required_points > 0 ? ($total_earned / $required_points) * 100 : 0;
    
    return array(
        'member' => $member,
        'year' => $year,
        'total_earned' => $total_earned,
        'required_points' => $required_points,
        'progress_percentage' => min(100, $progress_percentage),
        'categories' => $categories,
        'all_courses' => $all_courses,
        'compliance_status' => $total_earned >= $required_points ? 'compliant' : ($total_earned >= $required_points * 0.75 ? 'at_risk' : 'non_compliant')
    );
}

/**
 * Get list of members for selection
 */
function iipm_get_members_list($search = '', $limit = 50) {
    global $wpdb;
    
    $where = '';
    $params = array();
    
    if (!empty($search)) {
        $where = "WHERE (u.display_name LIKE %s OR u.user_email LIKE %s OR u.first_name LIKE %s OR u.last_name LIKE %s) AND";
        $search_param = '%' . $wpdb->esc_like($search) . '%';
        $params = array($search_param, $search_param, $search_param, $search_param);
    } else {
        $where = "WHERE";
    }
    
    $sql = "SELECT u.ID, u.display_name, u.user_email, u.first_name, u.last_name, 
                   m.membership_level, m.membership_status
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->prefix}test_iipm_members m ON u.ID = m.user_id
            {$where} m.membership_status = 'active'
            ORDER BY u.display_name ASC
            LIMIT %d";
    
    $params[] = $limit;
    
    if (!empty($params)) {
        $results = $wpdb->get_results($wpdb->prepare($sql, $params));
    } else {
        $results = $wpdb->get_results($wpdb->prepare($sql, $limit));
    }
    
    return $results;
}

/**
 * AJAX handler for individual member report
 */
function iipm_handle_get_individual_report() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_reports_nonce')) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    $user_id = intval($_POST['user_id']);
    $year = intval($_POST['year'] ?? date('Y'));
    
    error_log("IIPM: Generating individual report for user_id: " . $user_id . ", year: " . $year);
    
    $report_data = iipm_get_individual_member_report($user_id, $year);
    
    if ($report_data) {
        error_log("IIPM: Report generated successfully for user_id: " . $user_id);
        wp_send_json_success($report_data);
    } else {
        error_log("IIPM: Failed to generate report for user_id: " . $user_id);
        wp_send_json_error(array('message' => 'Member not found or not active'));
    }
}

/**
 * AJAX handler for members search
 */
function iipm_handle_search_members() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_reports_nonce')) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    $search = sanitize_text_field($_POST['search'] ?? '');
    $members = iipm_get_members_list($search);
    
    wp_send_json_success($members);
}

/**
 * Get all members with their CPD progress for the reports table
 */
function iipm_get_all_members_with_progress($year = null) {
    if (!$year) {
        $year = date('Y');
    }
    
    global $wpdb;
    
    $sql = "SELECT 
                u.ID, 
                u.display_name, 
                u.user_email,
                m.membership_level,
                m.cpd_points_required,
                m.cpd_prorata_adjustment,
                COALESCE(SUM(CASE WHEN c.status = 'approved' THEN c.cpd_points ELSE 0 END), 0) as earned_points
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->prefix}test_iipm_members m ON u.ID = m.user_id
            LEFT JOIN {$wpdb->prefix}test_iipm_cpd_records c ON u.ID = c.user_id AND c.cpd_year = %d
            WHERE m.membership_status = 'active'
            GROUP BY u.ID, u.display_name, u.user_email, m.membership_level, m.cpd_points_required, m.cpd_prorata_adjustment
            ORDER BY u.display_name ASC";
    
    $members = $wpdb->get_results($wpdb->prepare($sql, $year));
    
    // Calculate required points and progress for each member
    foreach ($members as &$member) {
        $required_points = $member->cpd_points_required - $member->cpd_prorata_adjustment;
        $member->required_points = max(0, $required_points); // Ensure it's not negative
        $member->earned_points = (int) $member->earned_points;
        
        // Calculate progress percentage
        if ($member->required_points > 0) {
            $member->progress_percentage = min(100, ($member->earned_points / $member->required_points) * 100);
        } else {
            $member->progress_percentage = 100; // If no points required, they're compliant
        }
        
        // Determine compliance status
        if ($member->earned_points >= $member->required_points) {
            $member->compliance_status = 'compliant';
        } elseif ($member->progress_percentage >= 75) {
            $member->compliance_status = 'at_risk';
        } else {
            $member->compliance_status = 'non_compliant';
        }
    }
    
    return $members;
}

/**
 * AJAX handler for getting all members with progress for reports table
 */
function iipm_handle_get_all_members_for_reports() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_reports_nonce')) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    $year = intval($_POST['year'] ?? date('Y'));
    $members = iipm_get_all_members_with_progress($year);
    
    wp_send_json_success($members);
}

// Register AJAX handlers (for logged-in users only, since these require admin access)
add_action('wp_ajax_iipm_get_compliance_data', 'iipm_handle_get_compliance_data');
add_action('wp_ajax_iipm_export_report', 'iipm_handle_export_report');
add_action('wp_ajax_iipm_send_bulk_reminders', 'iipm_handle_send_bulk_reminders');
add_action('wp_ajax_iipm_get_individual_report', 'iipm_handle_get_individual_report');
add_action('wp_ajax_iipm_search_members', 'iipm_handle_search_members');
add_action('wp_ajax_iipm_get_all_members_for_reports', 'iipm_handle_get_all_members_for_reports');

// Add this new function at the end of the file
function iipm_handle_get_training_history() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    // Check user permissions
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    $user_id = intval($_POST['user_id']);
    $current_user = wp_get_current_user();
    
    // Verify user can access this data
    if ($current_user->ID != $user_id && !current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    global $wpdb;
    
    try {
        // Get training records with category information
        $training_records = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                cr.*,
                cc.name as category_name,
                c.title as course_title,
                c.provider,
                c.lia_code
            FROM {$wpdb->prefix}test_iipm_cpd_records cr
            LEFT JOIN {$wpdb->prefix}test_iipm_cpd_categories cc ON cr.category_id = cc.id
            LEFT JOIN {$wpdb->prefix}test_iipm_cpd_courses c ON cr.course_id = c.id
            WHERE cr.user_id = %d
            ORDER BY cr.completion_date DESC, cr.created_at DESC",
            $user_id
        ));
        
        // Get category-based progress summary
        $current_year = date('Y');
        $category_summary = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                cc.name as category_name,
                cc.min_points_required as required_points,
                COALESCE(SUM(CASE WHEN cr.status = 'approved' AND cr.cpd_year = %d THEN cr.cpd_points ELSE 0 END), 0) as earned_points
            FROM {$wpdb->prefix}test_iipm_cpd_categories cc
            LEFT JOIN {$wpdb->prefix}test_iipm_cpd_records cr ON cc.id = cr.category_id AND cr.user_id = %d
            WHERE cc.is_active = 1 AND cc.is_mandatory = 1
            GROUP BY cc.id, cc.name, cc.min_points_required
            ORDER BY cc.sort_order ASC",
            $current_year,
            $user_id
        ), ARRAY_A);
        
        // Format category summary for frontend
        $categories = array();
        foreach ($category_summary as $category) {
            $categories[$category['category_name']] = array(
                'points' => intval($category['earned_points']),
                'required' => intval($category['required_points'])
            );
        }
        
        // Calculate total progress
        $total_points = array_sum(array_column($categories, 'points'));
        $total_required = array_sum(array_column($categories, 'required'));
        
        $response_data = array(
            'records' => $training_records,
            'summary' => array(
                'categories' => $categories,
                'total_points' => $total_points,
                'total_required' => $total_required,
                'total_records' => count($training_records)
            )
        );
        
        wp_send_json_success($response_data);
        
    } catch (Exception $e) {
        error_log('IIPM Training History Error: ' . $e->getMessage());
        wp_send_json_error('Failed to load training history');
    }
}

// Register the AJAX handler
add_action('wp_ajax_iipm_get_training_history', 'iipm_handle_get_training_history');
add_action('wp_ajax_nopriv_iipm_get_training_history', 'iipm_handle_get_training_history'); 