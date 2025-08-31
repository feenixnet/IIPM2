<?php
/**
 * Leave Request Helper Functions
 * 
 * @package IPM
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get leave requests for a specific user
 */
function iipm_get_user_leave_requests($user_id, $limit = 20, $status = null) {
    global $wpdb;
    
    $where_clause = "WHERE user_id = %d";
    $params = array($user_id);
    
    if ($status) {
        $where_clause .= " AND status = %s";
        $params[] = $status;
    }
    
    $sql = "SELECT * FROM {$wpdb->prefix}test_iipm_leave_requests 
            {$where_clause}
            ORDER BY created_at DESC 
            LIMIT %d";
    
    $params[] = $limit;
    
    return $wpdb->get_results($wpdb->prepare($sql, $params));
}

/**
 * Get all leave requests (for admin)
 */
function iipm_get_all_leave_requests($limit = 50, $status = null) {
    global $wpdb;
    
    $where_clause = "";
    $params = array();
    
    if ($status) {
        $where_clause = "WHERE lr.status = %s";
        $params[] = $status;
    }
    
    $sql = "SELECT lr.*, u.display_name, u.user_email, u.first_name, u.last_name
            FROM {$wpdb->prefix}test_iipm_leave_requests lr
            LEFT JOIN {$wpdb->prefix}users u ON lr.user_id = u.ID
            {$where_clause}
            ORDER BY lr.created_at DESC 
            LIMIT %d";
    
    $params[] = $limit;
    
    if (!empty($params)) {
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    } else {
        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }
}

/**
 * Get leave request by ID
 */
function iipm_get_leave_request($request_id) {
    global $wpdb;
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT lr.*, u.display_name, u.user_email, u.first_name, u.last_name
         FROM {$wpdb->prefix}test_iipm_leave_requests lr
         LEFT JOIN {$wpdb->prefix}users u ON lr.user_id = u.ID
         WHERE lr.id = %d",
        $request_id
    ));
}

/**
 * Check if user can edit leave request
 */
function iipm_can_edit_leave_request($request_id, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    global $wpdb;
    
    $request = $wpdb->get_row($wpdb->prepare(
        "SELECT user_id, status FROM {$wpdb->prefix}test_iipm_leave_requests WHERE id = %d",
        $request_id
    ));
    
    if (!$request) {
        return false;
    }
    
    // Only the owner can edit, and only if it's pending
    return ($request->user_id == $user_id && $request->status === 'pending');
}

/**
 * Check if user can cancel leave request
 */
function iipm_can_cancel_leave_request($request_id, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    global $wpdb;
    
    $request = $wpdb->get_row($wpdb->prepare(
        "SELECT user_id, status FROM {$wpdb->prefix}test_iipm_leave_requests WHERE id = %d",
        $request_id
    ));
    
    if (!$request) {
        return false;
    }
    
    // Only the owner can cancel, and only if it's pending
    return ($request->user_id == $user_id && $request->status === 'pending');
}

/**
 * Format leave request status for display
 */
function iipm_format_leave_status($status) {
    $statuses = array(
        'pending' => array('label' => 'Pending', 'class' => 'status-pending', 'icon' => '⏳'),
        'approved' => array('label' => 'Approved', 'class' => 'status-approved', 'icon' => '✅'),
        'rejected' => array('label' => 'Rejected', 'class' => 'status-rejected', 'icon' => '❌'),
        'cancelled' => array('label' => 'Cancelled', 'class' => 'status-cancelled', 'icon' => '🚫')
    );
    
    return $statuses[$status] ?? array('label' => ucfirst($status), 'class' => 'status-unknown', 'icon' => '❓');
}

/**
 * Format leave request reason for display
 */
function iipm_format_leave_reason($reason) {
    $reasons = array(
        'vacation' => array('label' => 'Vacation', 'icon' => '🏖️'),
        'sick' => array('label' => 'Sick Leave', 'icon' => '🤒'),
        'personal' => array('label' => 'Personal', 'icon' => '👤'),
        'family' => array('label' => 'Family', 'icon' => '👨‍👩‍👧‍👦'),
        'emergency' => array('label' => 'Emergency', 'icon' => '🚨'),
        'bereavement' => array('label' => 'Bereavement', 'icon' => '🕊️'),
        'maternity' => array('label' => 'Maternity', 'icon' => '👶'),
        'paternity' => array('label' => 'Paternity', 'icon' => '👨‍👶'),
        'study' => array('label' => 'Study Leave', 'icon' => '📚'),
        'other' => array('label' => 'Other', 'icon' => '📋')
    );
    
    return $reasons[$reason] ?? array('label' => ucfirst($reason), 'icon' => '📋');
}

/**
 * Calculate business days between two dates
 */
function iipm_calculate_business_days($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end->modify('+1 day'); // Include end date
    
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);
    
    $business_days = 0;
    foreach ($period as $date) {
        $day_of_week = $date->format('N');
        if ($day_of_week < 6) { // Monday = 1, Sunday = 7
            $business_days++;
        }
    }
    
    return $business_days;
}

/**
 * Get leave request statistics for user
 */
function iipm_get_user_leave_stats($user_id, $year = null) {
    if (!$year) {
        $year = date('Y');
    }
    
    global $wpdb;
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_requests,
            SUM(CASE WHEN status = 'approved' THEN duration_days ELSE 0 END) as total_approved_days
        FROM {$wpdb->prefix}test_iipm_leave_requests 
        WHERE user_id = %d AND YEAR(leave_start_date) = %d",
        $user_id,
        $year
    ));
}

/**
 * Check for overlapping leave requests
 */
function iipm_check_leave_overlap($user_id, $start_date, $end_date, $exclude_id = null) {
    global $wpdb;
    
    $where_clause = "WHERE user_id = %d 
                     AND status IN ('pending', 'approved')
                     AND (
                         (leave_start_date <= %s AND leave_end_date >= %s) OR
                         (leave_start_date <= %s AND leave_end_date >= %s) OR
                         (leave_start_date >= %s AND leave_end_date <= %s)
                     )";
    
    $params = array($user_id, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date);
    
    if ($exclude_id) {
        $where_clause .= " AND id != %d";
        $params[] = $exclude_id;
    }
    
    $sql = "SELECT * FROM {$wpdb->prefix}test_iipm_leave_requests {$where_clause}";
    
    return $wpdb->get_results($wpdb->prepare($sql, $params));
}

/**
 * Validate leave request dates
 */
function iipm_validate_leave_dates($start_date, $end_date) {
    $errors = array();
    
    // Check if dates are valid
    if (!strtotime($start_date)) {
        $errors[] = 'Invalid start date';
    }
    
    if (!strtotime($end_date)) {
        $errors[] = 'Invalid end date';
    }
    
    // Check if start date is before end date
    if (strtotime($start_date) >= strtotime($end_date)) {
        $errors[] = 'End date must be after start date';
    }
    
    // Check if start date is not in the past (allow today)
    if (strtotime($start_date) < strtotime('today')) {
        $errors[] = 'Start date cannot be in the past';
    }
    
    // Check if the leave period is not too long (e.g., max 30 days)
    $duration = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;
    if ($duration > 30) {
        $errors[] = 'Leave period cannot exceed 30 days';
    }
    
    return $errors;
}

/**
 * Get leave request reasons for dropdown
 */
function iipm_get_leave_reasons() {
    return array(
        'vacation' => 'Vacation',
        'sick' => 'Sick Leave',
        'personal' => 'Personal',
        'family' => 'Family',
        'emergency' => 'Emergency',
        'bereavement' => 'Bereavement',
        'maternity' => 'Maternity',
        'paternity' => 'Paternity',
        'study' => 'Study Leave',
        'other' => 'Other'
    );
}

/**
 * Calculate pro-rata CPD adjustment based on approved leave
 */
function iipm_calculate_prorata_cpd($user_id, $leave_start, $leave_end, $cpd_year) {
    // Calculate leave duration in days
    $start_date = new DateTime($leave_start);
    $end_date = new DateTime($leave_end);
    $interval = $start_date->diff($end_date);
    $leave_days = $interval->days + 1; // Include both start and end date
    
    // Calculate total days in the CPD year
    $year_start = new DateTime($cpd_year . '-01-01');
    $year_end = new DateTime($cpd_year . '-12-31');
    $year_interval = $year_start->diff($year_end);
    $total_days_in_year = $year_interval->days + 1;
    
    // Calculate percentage of year on leave
    $leave_percentage = $leave_days / $total_days_in_year;
    
    // Get current member's CPD requirements
    global $wpdb;
    $member = $wpdb->get_row($wpdb->prepare(
        "SELECT cpd_points_required FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d",
        $user_id
    ));
    
    if (!$member) {
        return 0;
    }
    
    // Calculate pro-rata adjustment
    $adjustment = $member->cpd_points_required * $leave_percentage;
    
    return round($adjustment, 2);
}

/**
 * Update CPD requirements when leave is approved
 */
function iipm_update_cpd_requirements_on_leave_approval($leave_request_id) {
    global $wpdb;
    
    // Get leave request details
    $leave_request = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_leave_requests WHERE id = %d",
        $leave_request_id
    ));
    
    if (!$leave_request || $leave_request->status !== 'approved') {
        return false;
    }
    
    // Only apply pro-rata for certain leave types
    $prorata_applicable_types = array('maternity', 'paternity', 'sick', 'study');
    if (!in_array($leave_request->leave_reason, $prorata_applicable_types)) {
        return false;
    }
    
    // Calculate the CPD year from the leave dates
    $leave_year = date('Y', strtotime($leave_request->leave_start_date));
    
    // Calculate pro-rata adjustment
    $adjustment = iipm_calculate_prorata_cpd(
        $leave_request->user_id,
        $leave_request->leave_start_date,
        $leave_request->leave_end_date,
        $leave_year
    );
    
    // Update member's CPD pro-rata adjustment
    $result = $wpdb->update(
        $wpdb->prefix . 'test_iipm_members',
        array('cpd_prorata_adjustment' => $adjustment),
        array('user_id' => $leave_request->user_id),
        array('%f'),
        array('%d')
    );
    
    if ($result !== false) {
        // Log the CPD adjustment
        iipm_log_user_activity(
            $leave_request->user_id,
            'cpd_prorata_adjustment',
            "CPD requirement adjusted by {$adjustment} points due to approved leave",
            array(
                'leave_request_id' => $leave_request_id,
                'adjustment_amount' => $adjustment,
                'leave_year' => $leave_year
            )
        );
        
        // Send notification to member about CPD adjustment
        iipm_send_cpd_adjustment_notification($leave_request->user_id, $adjustment, $leave_year);
        
        return true;
    }
    
    return false;
}

/**
 * Send notification about CPD adjustment
 */
function iipm_send_cpd_adjustment_notification($user_id, $adjustment, $year) {
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        return false;
    }
    
    global $wpdb;
    $member = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d",
        $user_id
    ));
    
    $original_requirement = $member->cpd_points_required;
    $adjusted_requirement = $original_requirement - $adjustment;
    
    $subject = 'CPD Requirement Adjusted Due to Approved Leave';
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #667eea;'>CPD Requirement Adjustment - {$year}</h2>
            
            <p>Dear {$user->display_name},</p>
            
            <p>Your approved leave request has resulted in an adjustment to your CPD requirements for {$year}.</p>
            
            <div style='background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #0ea5e9;'>
                <h3 style='margin-top: 0; color: #0369a1;'>CPD Requirement Update:</h3>
                <ul style='list-style: none; padding: 0;'>
                    <li><strong>Original Requirement:</strong> {$original_requirement} CPD points</li>
                    <li><strong>Pro-rata Adjustment:</strong> -{$adjustment} CPD points</li>
                    <li><strong>New Requirement:</strong> <span style='color: #059669; font-size: 1.2em;'>{$adjusted_requirement} CPD points</span></li>
                </ul>
            </div>
            
            <p>This adjustment reflects the period you were on approved leave and ensures fair CPD requirements for the remainder of the year.</p>
            
            <p>You can view your updated CPD progress in your member portal:</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='" . home_url('/member-portal/') . "' style='background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold;'>
                    View CPD Progress
                </a>
            </div>
            
            <p>If you have any questions about this adjustment, please contact us.</p>
            
            <p>Best regards,<br>IIPM Administration</p>
        </div>
    </body>
    </html>
    ";
    
    return wp_mail($user->user_email, $subject, $message, $headers);
}

/**
 * Get member's effective CPD requirement (after pro-rata adjustment)
 */
function iipm_get_effective_cpd_requirement($user_id) {
    global $wpdb;
    
    $member = $wpdb->get_row($wpdb->prepare(
        "SELECT cpd_points_required, cpd_prorata_adjustment FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d",
        $user_id
    ));
    
    if (!$member) {
        return 4; // Default requirement
    }
    
    return max(0, $member->cpd_points_required - $member->cpd_prorata_adjustment);
}
?>
