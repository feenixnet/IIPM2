<?php
/**
 * Leave Request Admin Handlers
 * 
 * @package IPM
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create leave request tables
 */
function iipm_create_leave_request_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Leave requests table
    $table_leave_requests = $wpdb->prefix . 'test_iipm_leave_requests';
    $sql_leave_requests = "CREATE TABLE $table_leave_requests (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        title varchar(255) NOT NULL,
        reason enum('annual', 'sick', 'personal', 'maternity', 'paternity', 'bereavement', 'other') NOT NULL,
        leave_start_date date NOT NULL,
        leave_end_date date NOT NULL,
        duration_days int(11) NOT NULL,
        description text NULL,
        status enum('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
        approved_by bigint(20) NULL,
        approved_at timestamp NULL,
        rejection_reason text NULL,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY status (status),
        KEY leave_start_date (leave_start_date),
        KEY approved_by (approved_by)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_leave_requests);
    
    error_log('IIPM: Leave request tables created successfully');
}

/**
 * Wrapper function to create leave request tables if needed
 */
function iipm_create_leave_request_tables_if_needed() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'test_iipm_leave_requests';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    
    if (!$table_exists) {
        iipm_create_leave_request_tables();
        error_log('IIPM: Leave request tables created via wrapper function');
    } else {
        error_log('IIPM: Leave request tables already exist');
    }
}

/**
 * AJAX handler for approving leave requests
 */
function iipm_handle_approve_leave_request() {
    // Check permissions
    if (!current_user_can('administrator') && !current_user_can('iipm_admin')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_portal_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $request_id = intval($_POST['request_id']);
    
    if (!$request_id) {
        wp_send_json_error('Invalid request ID');
        return;
    }
    
    global $wpdb;
    
    // Get the request details
    $request = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_leave_requests WHERE id = %d",
        $request_id
    ));
    
    if (!$request) {
        wp_send_json_error('Request not found');
        return;
    }
    
    // Update the request status
    $result = $wpdb->update(
        $wpdb->prefix . 'test_iipm_leave_requests',
        array(
            'status' => 'approved',
            'approved_by' => get_current_user_id(),
            'approved_at' => current_time('mysql')
        ),
        array('id' => $request_id),
        array('%s', '%d', '%s'),
        array('%d')
    );
    
    if ($result !== false) {
        // Log activity
        iipm_log_user_activity(
            get_current_user_id(), 
            'leave_request_approved', 
            "Approved leave request: {$request->title} for user ID {$request->user_id}"
        );
        
        // Send notification email to user
        iipm_send_leave_approval_email($request);
        
        wp_send_json_success('Leave request approved successfully');
    } else {
        wp_send_json_error('Failed to approve leave request');
    }
}
add_action('wp_ajax_iipm_approve_leave_request', 'iipm_handle_approve_leave_request');

/**
 * AJAX handler for rejecting leave requests
 */
function iipm_handle_reject_leave_request() {
    // Check permissions
    if (!current_user_can('administrator') && !current_user_can('iipm_admin')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_portal_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $request_id = intval($_POST['request_id']);
    $rejection_reason = sanitize_textarea_field($_POST['rejection_reason']);
    
    if (!$request_id) {
        wp_send_json_error('Invalid request ID');
        return;
    }
    
    if (!$rejection_reason) {
        wp_send_json_error('Rejection reason is required');
        return;
    }
    
    global $wpdb;
    
    // Get the request details
    $request = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_leave_requests WHERE id = %d",
        $request_id
    ));
    
    if (!$request) {
        wp_send_json_error('Request not found');
        return;
    }
    
    // Update the request status
    $result = $wpdb->update(
        $wpdb->prefix . 'test_iipm_leave_requests',
        array(
            'status' => 'rejected',
            'approved_by' => get_current_user_id(),
            'approved_at' => current_time('mysql'),
            'rejection_reason' => $rejection_reason
        ),
        array('id' => $request_id),
        array('%s', '%d', '%s', '%s'),
        array('%d')
    );
    
    if ($result !== false) {
        // Log activity
        iipm_log_user_activity(
            get_current_user_id(), 
            'leave_request_rejected', 
            "Rejected leave request: {$request->title} for user ID {$request->user_id}. Reason: {$rejection_reason}"
        );
        
        // Send notification email to user
        iipm_send_leave_rejection_email($request, $rejection_reason);
        
        wp_send_json_success('Leave request rejected successfully');
    } else {
        wp_send_json_error('Failed to reject leave request');
    }
}
add_action('wp_ajax_iipm_reject_leave_request', 'iipm_handle_reject_leave_request');

/**
 * Send leave approval email to user
 */
function iipm_send_leave_approval_email($request) {
    $user = get_user_by('id', $request->user_id);
    if (!$user) return false;
    
    $subject = 'IIPM - Leave Request Approved';
    $message = "
Dear {$user->first_name},

Your leave request has been APPROVED.

Request Details:
- Title: {$request->title}
- Reason: " . ucfirst($request->reason) . "
- Leave Period: " . date('F j, Y', strtotime($request->leave_start_date)) . " to " . date('F j, Y', strtotime($request->leave_end_date)) . "
- Duration: {$request->duration_days} days

Your leave has been officially approved and will be processed accordingly.

If you have any questions, please contact us at info@iipm.ie

Best regards,
IIPM Administration Team
    ";
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: IIPM Portal <' . (defined('SMTP_FROM') ? SMTP_FROM : get_option('admin_email')) . '>'
    );
    
    return wp_mail($user->user_email, $subject, $message, $headers);
}

/**
 * Send leave rejection email to user
 */
function iipm_send_leave_rejection_email($request, $rejection_reason) {
    $user = get_user_by('id', $request->user_id);
    if (!$user) return false;
    
    $subject = 'IIPM - Leave Request Rejected';
    $message = "
Dear {$user->first_name},

Unfortunately, your leave request has been REJECTED.

Request Details:
- Title: {$request->title}
- Reason: " . ucfirst($request->reason) . "
- Leave Period: " . date('F j, Y', strtotime($request->leave_start_date)) . " to " . date('F j, Y', strtotime($request->leave_end_date)) . "
- Duration: {$request->duration_days} days

Reason for Rejection:
{$rejection_reason}

If you have any questions about this decision or would like to discuss alternative arrangements, please contact us at info@iipm.ie

Best regards,
IIPM Administration Team
    ";
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: IIPM Portal <' . (defined('SMTP_FROM') ? SMTP_FROM : get_option('admin_email')) . '>'
    );
    
    return wp_mail($user->user_email, $subject, $message, $headers);
}

/**
 * Get leave request statistics for dashboard
 */
function iipm_get_leave_request_stats() {
    global $wpdb;
    
    $stats = $wpdb->get_row(
        "SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_requests
        FROM {$wpdb->prefix}test_iipm_leave_requests"
    );
    
    return $stats;
}

/**
 * Get recent leave requests for dashboard
 */
function iipm_get_recent_leave_requests($limit = 10) {
    global $wpdb;
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT lr.*, u.display_name, u.user_email 
         FROM {$wpdb->prefix}test_iipm_leave_requests lr
         LEFT JOIN {$wpdb->prefix}users u ON lr.user_id = u.ID
         ORDER BY lr.created_at DESC
         LIMIT %d",
        $limit
    ));
}

// Note: iipm_get_user_leave_requests() function moved to leave-request-functions.php to avoid duplication

// Note: iipm_get_leave_request() function moved to leave-request-functions.php to avoid duplication

/**
 * Calculate leave balance for user (placeholder function)
 */
function iipm_get_user_leave_balance($user_id, $leave_type = 'annual') {
    // This is a placeholder function - you would implement actual leave balance calculation
    // based on your business rules (e.g., days allocated per year, days used, etc.)
    
    global $wpdb;
    
    // Get total approved leave days for current year
    $current_year = date('Y');
    $used_days = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(duration_days) 
         FROM {$wpdb->prefix}test_iipm_leave_requests 
         WHERE user_id = %d 
         AND status = 'approved' 
         AND reason = %s
         AND YEAR(leave_start_date) = %d",
        $user_id,
        $leave_type,
        $current_year
    ));
    
    // Default allocation (you would get this from user profile or company policy)
    $annual_allocation = 25; // 25 days per year
    
    return array(
        'allocated' => $annual_allocation,
        'used' => intval($used_days),
        'remaining' => $annual_allocation - intval($used_days)
    );
}

/**
 * Check if user can submit leave request
 */
function iipm_can_user_submit_leave($user_id, $leave_type, $duration_days) {
    $balance = iipm_get_user_leave_balance($user_id, $leave_type);
    
    if ($leave_type === 'annual' && $duration_days > $balance['remaining']) {
        return array(
            'can_submit' => false,
            'message' => "Insufficient leave balance. You have {$balance['remaining']} days remaining."
        );
    }
    
    return array(
        'can_submit' => true,
        'message' => 'Leave request can be submitted.'
    );
}

/**
 * Get leave requests for admin dashboard with filters
 */
function iipm_get_filtered_leave_requests($filters = array()) {
    global $wpdb;
    
    $where_clauses = array();
    $params = array();
    
    // Status filter
    if (!empty($filters['status'])) {
        $where_clauses[] = "lr.status = %s";
        $params[] = $filters['status'];
    }
    
    // Date range filter
    if (!empty($filters['start_date'])) {
        $where_clauses[] = "lr.leave_start_date >= %s";
        $params[] = $filters['start_date'];
    }
    
    if (!empty($filters['end_date'])) {
        $where_clauses[] = "lr.leave_end_date <= %s";
        $params[] = $filters['end_date'];
    }
    
    // User filter
    if (!empty($filters['user_id'])) {
        $where_clauses[] = "lr.user_id = %d";
        $params[] = $filters['user_id'];
    }
    
    // Leave type filter
    if (!empty($filters['reason'])) {
        $where_clauses[] = "lr.reason = %s";
        $params[] = $filters['reason'];
    }
    
    $where_sql = '';
    if (!empty($where_clauses)) {
        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
    }
    
    $limit = isset($filters['limit']) ? intval($filters['limit']) : 50;
    
    $sql = "SELECT lr.*, u.display_name, u.user_email, u.first_name, u.last_name
            FROM {$wpdb->prefix}test_iipm_leave_requests lr
            LEFT JOIN {$wpdb->prefix}users u ON lr.user_id = u.ID
            {$where_sql}
            ORDER BY lr.created_at DESC
            LIMIT %d";
    
    $params[] = $limit;
    
    if (!empty($params)) {
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    } else {
        return $wpdb->get_results($sql);
    }
}

/**
 * Initialize leave request system
 */
function iipm_init_leave_request_system() {
    // Create tables if they don't exist
    iipm_create_leave_request_tables_if_needed();
    
    // Add AJAX handlers
    add_action('wp_ajax_iipm_submit_leave_request', 'iipm_handle_submit_leave_request');
    add_action('wp_ajax_iipm_cancel_leave_request', 'iipm_handle_cancel_leave_request');
    
    error_log('IIPM: Leave request system initialized');
}

// Initialize the leave request system
add_action('init', 'iipm_init_leave_request_system', 10);

?>
