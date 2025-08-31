<?php
/**
 * CPD Management AJAX Handlers for Milestone 2
 */

/**
 * Get CPD dashboard data for member
 */
function iipm_get_cpd_dashboard_data() {
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $user_id = get_current_user_id();
    global $wpdb;
    
    // Get member data
    $member = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d",
        $user_id
    ));
    
    if (!$member) {
        wp_send_json_error('Member record not found');
        return;
    }
    
    // Get CPD progress by category
    $cpd_progress = $wpdb->get_results($wpdb->prepare(
        "SELECT category, SUM(cpd_points) as total_points 
         FROM {$wpdb->prefix}test_iipm_cpd_entries 
         WHERE user_id = %d AND status = 'approved' AND cpd_year = %d
         GROUP BY category",
        $user_id,
        date('Y')
    ));
    
    // Get recent CPD entries
    $recent_entries = $wpdb->get_results($wpdb->prepare(
        "SELECT c.*, co.course_name, co.provider 
         FROM {$wpdb->prefix}test_iipm_cpd_entries c
         LEFT JOIN {$wpdb->prefix}test_iipm_cpd_courses co ON c.course_id = co.id
         WHERE c.user_id = %d AND c.cpd_year = %d
         ORDER BY c.created_at DESC
         LIMIT 10",
        $user_id,
        date('Y')
    ));
    
    // Calculate progress percentages
    $categories = array(
        'pensions' => array('required' => 35, 'earned' => 0),
        'savings_investment' => array('required' => 1, 'earned' => 0),
        'ethics' => array('required' => 1, 'earned' => 0),
        'life_assurance' => array('required' => 1, 'earned' => 0)
    );
    
    foreach ($cpd_progress as $progress) {
        if (isset($categories[$progress->category])) {
            $categories[$progress->category]['earned'] = (int)$progress->total_points;
        }
    }
    
    $total_required = $member->cpd_points_required;
    $total_earned = array_sum(array_column($categories, 'earned'));
    
    wp_send_json_success(array(
        'member' => $member,
        'categories' => $categories,
        'total_required' => $total_required,
        'total_earned' => $total_earned,
        'recent_entries' => $recent_entries,
        'compliance_status' => $total_earned >= $total_required ? 'compliant' : 'non_compliant'
    ));
}
add_action('wp_ajax_iipm_get_cpd_dashboard', 'iipm_get_cpd_dashboard_data');

/**
 * Get pre-approved courses for dropdown
 */
function iipm_get_preapproved_courses() {
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    global $wpdb;
    
    $category = sanitize_text_field($_POST['category'] ?? '');
    $search = sanitize_text_field($_POST['search'] ?? '');
    
    $where_conditions = array("is_active = 1");
    $where_params = array();
    
    if (!empty($category)) {
        $where_conditions[] = "category = %s";
        $where_params[] = $category;
    }
    
    if (!empty($search)) {
        $where_conditions[] = "(course_name LIKE %s OR provider LIKE %s)";
        $where_params[] = '%' . $search . '%';
        $where_params[] = '%' . $search . '%';
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $courses = $wpdb->get_results($wpdb->prepare(
        "SELECT id, course_name, provider, category, cpd_points, course_code
         FROM {$wpdb->prefix}test_iipm_cpd_courses 
         WHERE {$where_clause}
         ORDER BY provider, course_name
         LIMIT 100",
        $where_params
    ));
    
    wp_send_json_success($courses);
}
add_action('wp_ajax_iipm_get_preapproved_courses', 'iipm_get_preapproved_courses');

/**
 * Submit CPD entry
 */
function iipm_submit_cpd_entry() {
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $user_id = get_current_user_id();
    global $wpdb;
    
    // Validate input
    $training_type = sanitize_text_field($_POST['training_type']);
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : null;
    $course_name = sanitize_text_field($_POST['course_name'] ?? '');
    $provider = sanitize_text_field($_POST['provider'] ?? '');
    $category = sanitize_text_field($_POST['category']);
    $completion_date = sanitize_text_field($_POST['completion_date']);
    $duration_hours = floatval($_POST['duration_hours']);
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    
    // Validate required fields
    if (empty($training_type) || empty($category) || empty($completion_date) || $duration_hours <= 0) {
        wp_send_json_error('Missing required fields');
        return;
    }
    
    // Validate completion date
    if (strtotime($completion_date) > time()) {
        wp_send_json_error('Completion date cannot be in the future');
        return;
    }
    
    // Determine CPD points and approval status
    $cpd_points = 0;
    $status = 'pending';
    
    if ($training_type === 'preapproved' && $course_id) {
        // Pre-approved course - auto approve
        $course = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}test_iipm_cpd_courses WHERE id = %d AND is_active = 1",
            $course_id
        ));
        
        if ($course) {
            $cpd_points = $course->cpd_points;
            $status = 'approved';
            $course_name = $course->course_name;
            $provider = $course->provider;
        } else {
            wp_send_json_error('Invalid course selected');
            return;
        }
    } else {
        // External training - requires approval
        $cpd_points = $duration_hours; // Default to hours, admin can adjust
        $status = 'pending';
    }
    
    // Handle certificate upload
    $certificate_url = '';
    if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK) {
        $upload_result = iipm_handle_certificate_upload($_FILES['certificate']);
        if ($upload_result['success']) {
            $certificate_url = $upload_result['url'];
        } else {
            wp_send_json_error('Certificate upload failed: ' . $upload_result['error']);
            return;
        }
    }
    
    // Insert CPD entry
    $result = $wpdb->insert(
        $wpdb->prefix . 'test_iipm_cpd_entries',
        array(
            'user_id' => $user_id,
            'course_id' => $course_id,
            'training_type' => $training_type,
            'course_name' => $course_name,
            'provider' => $provider,
            'category' => $category,
            'completion_date' => $completion_date,
            'duration_hours' => $duration_hours,
            'cpd_points' => $cpd_points,
            'description' => $description,
            'certificate_url' => $certificate_url,
            'status' => $status,
            'cpd_year' => date('Y', strtotime($completion_date)),
            'submitted_at' => current_time('mysql')
        ),
        array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%d', '%s')
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to save CPD entry');
        return;
    }
    
    $entry_id = $wpdb->insert_id;
    
    // Update member's CPD points if auto-approved
    if ($status === 'approved') {
        iipm_update_member_cpd_points($user_id);
    }
    
    // Send notification emails
    if ($status === 'pending') {
        iipm_send_cpd_submission_notification($user_id, $entry_id);
        iipm_send_admin_cpd_review_notification($entry_id);
    } else {
        iipm_send_cpd_approval_notification($user_id, $entry_id);
    }
    
    // Log activity
    iipm_log_user_activity(
        $user_id,
        'cpd_submitted',
        "CPD entry submitted: {$course_name} ({$cpd_points} points)"
    );
    
    wp_send_json_success(array(
        'entry_id' => $entry_id,
        'status' => $status,
        'cpd_points' => $cpd_points,
        'message' => $status === 'approved' ? 'CPD entry approved automatically' : 'CPD entry submitted for review'
    ));
}
add_action('wp_ajax_iipm_submit_cpd_entry', 'iipm_submit_cpd_entry');

/**
 * Get CPD entries for member
 */
function iipm_get_member_cpd_entries() {
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $user_id = get_current_user_id();
    $year = intval($_POST['year'] ?? date('Y'));
    $page = intval($_POST['page'] ?? 1);
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    
    global $wpdb;
    
    $entries = $wpdb->get_results($wpdb->prepare(
        "SELECT c.*, co.course_code
         FROM {$wpdb->prefix}test_iipm_cpd_entries c
         LEFT JOIN {$wpdb->prefix}test_iipm_cpd_courses co ON c.course_id = co.id
         WHERE c.user_id = %d AND c.cpd_year = %d
         ORDER BY c.completion_date DESC, c.created_at DESC
         LIMIT %d OFFSET %d",
        $user_id,
        $year,
        $per_page,
        $offset
    ));
    
    $total_entries = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_cpd_entries 
         WHERE user_id = %d AND cpd_year = %d",
        $user_id,
        $year
    ));
    
    wp_send_json_success(array(
        'entries' => $entries,
        'pagination' => array(
            'current_page' => $page,
            'total_pages' => ceil($total_entries / $per_page),
            'total_entries' => $total_entries
        )
    ));
}
add_action('wp_ajax_iipm_get_member_cpd_entries', 'iipm_get_member_cpd_entries');

/**
 * Admin: Get CPD submissions for review
 */
function iipm_get_cpd_submissions_for_review() {
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_admin_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    global $wpdb;
    
    $status = sanitize_text_field($_POST['status'] ?? 'pending');
    $page = intval($_POST['page'] ?? 1);
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    
    $submissions = $wpdb->get_results($wpdb->prepare(
        "SELECT c.*, u.display_name, u.user_email, m.membership_level, cat.name as category_name
         FROM {$wpdb->prefix}test_iipm_cpd_records c
         JOIN {$wpdb->users} u ON c.user_id = u.ID
         LEFT JOIN {$wpdb->prefix}test_iipm_members m ON c.user_id = m.user_id
         LEFT JOIN {$wpdb->prefix}test_iipm_cpd_categories cat ON c.category_id = cat.id
         WHERE c.status = %s
         ORDER BY c.created_at ASC
         LIMIT %d OFFSET %d",
        $status,
        $per_page,
        $offset
    ));
    
    $total_submissions = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_cpd_records WHERE status = %s",
        $status
    ));
    
    wp_send_json_success(array(
        'submissions' => $submissions,
        'pagination' => array(
            'current_page' => $page,
            'total_pages' => ceil($total_submissions / $per_page),
            'total_submissions' => $total_submissions
        )
    ));
}
add_action('wp_ajax_iipm_get_cpd_submissions', 'iipm_get_cpd_submissions_for_review');

/**
 * Admin: Review CPD submission
 */
function iipm_review_cpd_submission() {
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_admin_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $entry_id = intval($_POST['entry_id']);
    $action = sanitize_text_field($_POST['action_type']); // 'approve' or 'reject'
    $cpd_points = floatval($_POST['cpd_points'] ?? 0);
    $admin_comments = sanitize_textarea_field($_POST['admin_comments'] ?? '');
    
    if (!$entry_id || !in_array($action, array('approve', 'reject'))) {
        wp_send_json_error('Invalid parameters');
        return;
    }
    
    global $wpdb;
    
    // Get the CPD entry
    $entry = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_cpd_records WHERE id = %d",
        $entry_id
    ));
    
    if (!$entry) {
        wp_send_json_error('CPD entry not found');
        return;
    }
    
    // Update the entry
    $update_data = array(
        'status' => $action === 'approve' ? 'approved' : 'rejected',
        'updated_at' => current_time('mysql')
    );
    
    if ($action === 'approve' && $cpd_points > 0) {
        $update_data['cpd_points'] = $cpd_points;
    }
    
    $result = $wpdb->update(
        $wpdb->prefix . 'test_iipm_cpd_records',
        $update_data,
        array('id' => $entry_id),
        array('%s', '%s', '%f'),
        array('%d')
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to update CPD entry');
        return;
    }
    
    // Update member's total CPD points if approved
    if ($action === 'approve') {
        iipm_update_member_cpd_points($entry->user_id);
    }
    
    // Send notification to member
    if ($action === 'approve') {
        iipm_send_cpd_approval_notification($entry->user_id, $entry_id);
    } else {
        iipm_send_cpd_rejection_notification($entry->user_id, $entry_id, $admin_comments);
    }
    
    // Log activity
    iipm_log_user_activity(
        get_current_user_id(),
        'cpd_reviewed',
        "CPD entry {$action}d for user {$entry->user_id}: {$entry->activity_title}"
    );
    
    wp_send_json_success(array(
        'message' => "CPD entry {$action}d successfully",
        'new_status' => $update_data['status']
    ));
}
add_action('wp_ajax_iipm_review_cpd_submission', 'iipm_review_cpd_submission');

/**
 * Admin: Get CPD submission details for review
 */
function iipm_get_cpd_submission_details() {
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_admin_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $entry_id = intval($_POST['entry_id']);
    
    if (!$entry_id) {
        wp_send_json_error('Invalid entry ID');
        return;
    }
    
    global $wpdb;
    
    $submission = $wpdb->get_row($wpdb->prepare(
        "SELECT c.*, u.display_name, u.user_email, m.membership_level, cat.name as category_name
         FROM {$wpdb->prefix}test_iipm_cpd_records c
         JOIN {$wpdb->users} u ON c.user_id = u.ID
         LEFT JOIN {$wpdb->prefix}test_iipm_members m ON c.user_id = m.user_id
         LEFT JOIN {$wpdb->prefix}test_iipm_cpd_categories cat ON c.category_id = cat.id
         WHERE c.id = %d",
        $entry_id
    ));
    
    if (!$submission) {
        wp_send_json_error('CPD submission not found');
        return;
    }
    
    // Add certificate URL if exists
    if ($submission->certificate_path) {
        $upload_dir = wp_upload_dir();
        $submission->certificate_path = $upload_dir['baseurl'] . $submission->certificate_path;
    }
    
    wp_send_json_success($submission);
}
add_action('wp_ajax_iipm_get_cpd_submission_details', 'iipm_get_cpd_submission_details');

/**
 * Admin: Add pre-approved course
 */
function iipm_add_preapproved_course() {
    // Debug: Log user capabilities and request
    error_log('IIPM Add Course - User ID: ' . get_current_user_id());
    error_log('IIPM Add Course - User can manage_iipm_members: ' . (current_user_can('manage_iipm_members') ? 'Yes' : 'No'));
    error_log('IIPM Add Course - User is administrator: ' . (current_user_can('administrator') ? 'Yes' : 'No'));
    error_log('IIPM Add Course - POST data: ' . print_r($_POST, true));
    
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        error_log('IIPM Add Course - Permission denied for user ID: ' . get_current_user_id());
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_admin_nonce')) {
        error_log('IIPM Add Course - Nonce verification failed');
        wp_send_json_error('Security check failed');
        return;
    }
    
    global $wpdb;
    
    // Map category names to IDs (only mandatory categories)
    $category_name = sanitize_text_field($_POST['category']);
    $category_mapping = array(
        'pensions' => 1,
        'savings_investment' => 2,
        'ethics' => 3,
        'life_assurance' => 4
    );
    
    $category_id = isset($category_mapping[$category_name]) ? $category_mapping[$category_name] : null;
    
    if (!$category_id) {
        error_log('IIPM Add Course - Invalid category: ' . $category_name);
        wp_send_json_error('Invalid category selected: ' . $category_name);
        return;
    }
    
    $course_data = array(
        'title' => sanitize_text_field($_POST['course_name']),
        'provider' => sanitize_text_field($_POST['provider']),
        'category_id' => $category_id,
        'cpd_points' => floatval($_POST['cpd_points']),
        'description' => sanitize_textarea_field($_POST['description'] ?? ''),
        'course_type' => sanitize_text_field($_POST['course_type'] ?? 'online'),
        'is_active' => 1,
        'approval_status' => 'approved', // Auto-approve admin-added courses
        'approved_by' => get_current_user_id(),
        'approved_at' => current_time('mysql')
    );
    
    // Validate required fields
    if (empty($course_data['title']) || empty($course_data['provider']) || 
        empty($course_data['category_id']) || $course_data['cpd_points'] <= 0) {
        wp_send_json_error('Missing required fields');
        return;
    }
    
    // Debug: Log the data being inserted
    error_log('IIPM Add Course - Attempting to insert: ' . print_r($course_data, true));
    
    $result = $wpdb->insert(
        $wpdb->prefix . 'test_iipm_cpd_courses',
        $course_data,
        array('%s', '%s', '%d', '%f', '%s', '%s', '%d', '%s', '%d', '%s')
    );
    
    if ($result === false) {
        // Log the actual database error
        $db_error = $wpdb->last_error;
        error_log('IIPM Add Course - Database error: ' . $db_error);
        error_log('IIPM Add Course - Last query: ' . $wpdb->last_query);
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}test_iipm_cpd_courses'");
        if (!$table_exists) {
            error_log('IIPM Add Course - Table does not exist: ' . $wpdb->prefix . 'test_iipm_cpd_courses');
            wp_send_json_error('Database table not found. Please contact administrator.');
            return;
        }
        
        wp_send_json_error('Database error: ' . ($db_error ?: 'Unknown database error'));
        return;
    }
    
    $course_id = $wpdb->insert_id;
    
    // Log activity
    iipm_log_user_activity(
        get_current_user_id(),
        'course_added',
        "Added pre-approved course: {$course_data['title']}"
    );
    
    wp_send_json_success(array(
        'course_id' => $course_id,
        'message' => 'Course added successfully'
    ));
}
add_action('wp_ajax_iipm_add_preapproved_course', 'iipm_add_preapproved_course');

/**
 * Admin: Bulk import LIA courses
 */
function iipm_bulk_import_lia_courses() {
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_admin_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    // Check file upload
    if (!isset($_FILES['lia_courses_file']) || $_FILES['lia_courses_file']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error('File upload failed');
        return;
    }
    
    $file = $_FILES['lia_courses_file'];
    
    // Validate file type
    if (!in_array($file['type'], array('text/csv', 'application/csv', 'text/plain'))) {
        wp_send_json_error('Invalid file type. Please upload a CSV file.');
        return;
    }
    
    // Process CSV file
    $result = iipm_process_lia_courses_csv($file['tmp_name']);
    
    if ($result['success']) {
        wp_send_json_success($result['data']);
    } else {
        wp_send_json_error($result['error']);
    }
}
add_action('wp_ajax_iipm_bulk_import_lia_courses', 'iipm_bulk_import_lia_courses');

/**
 * Helper Functions
 */

/**
 * Handle certificate file upload
 */
function iipm_handle_certificate_upload($file) {
    $allowed_types = array('pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx');
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        return array('success' => false, 'error' => 'Invalid file type');
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        return array('success' => false, 'error' => 'File too large');
    }
    
    $upload_dir = wp_upload_dir();
    $cpd_dir = $upload_dir['basedir'] . '/cpd-certificates';
    
    if (!file_exists($cpd_dir)) {
        wp_mkdir_p($cpd_dir);
    }
    
    $filename = uniqid('cpd_cert_') . '.' . $file_extension;
    $file_path = $cpd_dir . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        return array(
            'success' => true,
            'url' => $upload_dir['baseurl'] . '/cpd-certificates/' . $filename,
            'path' => $file_path
        );
    } else {
        return array('success' => false, 'error' => 'Failed to save file');
    }
}

/**
 * Update member's total CPD points
 */
function iipm_update_member_cpd_points($user_id) {
    global $wpdb;
    
    $current_year = date('Y');
    
    $total_points = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(cpd_points) FROM {$wpdb->prefix}test_iipm_cpd_entries 
         WHERE user_id = %d AND status = 'approved' AND cpd_year = %d",
        $user_id,
        $current_year
    ));
    
    $wpdb->update(
        $wpdb->prefix . 'test_iipm_members',
        array('cpd_points_current' => $total_points ?: 0),
        array('user_id' => $user_id),
        array('%d'),
        array('%d')
    );
}

/**
 * Process LIA courses CSV import
 */
function iipm_process_lia_courses_csv($file_path) {
    global $wpdb;
    
    $results = array(
        'total' => 0,
        'successful' => 0,
        'failed' => 0,
        'errors' => array()
    );
    
    if (($handle = fopen($file_path, 'r')) !== FALSE) {
        $header = fgetcsv($handle);
        if (!$header) {
            return array('success' => false, 'error' => 'Invalid CSV file');
        }
        
        // Expected columns: course_name, provider, category, cpd_points, course_code, description
        $required_columns = array('course_name', 'provider', 'category', 'cpd_points');
        $missing_columns = array_diff($required_columns, $header);
        if (!empty($missing_columns)) {
            return array('success' => false, 'error' => 'Missing required columns: ' . implode(', ', $missing_columns));
        }
        
        $row_number = 1;
        while (($row = fgetcsv($handle)) !== FALSE) {
            $row_number++;
            if (count($row) === count($header)) {
                $course_data = array_combine($header, $row);
                
                // Validate and insert course
                if (!empty($course_data['course_name']) && !empty($course_data['provider']) && 
                    !empty($course_data['category']) && is_numeric($course_data['cpd_points'])) {
                    
                    $insert_result = $wpdb->insert(
                        $wpdb->prefix . 'test_iipm_cpd_courses',
                        array(
                            'course_name' => sanitize_text_field($course_data['course_name']),
                            'provider' => sanitize_text_field($course_data['provider']),
                            'category' => sanitize_text_field($course_data['category']),
                            'cpd_points' => floatval($course_data['cpd_points']),
                            'course_code' => sanitize_text_field($course_data['course_code'] ?? ''),
                            'description' => sanitize_textarea_field($course_data['description'] ?? ''),
                            'is_active' => 1,
                            'created_by' => get_current_user_id(),
                            'created_at' => current_time('mysql')
                        ),
                        array('%s', '%s', '%s', '%f', '%s', '%s', '%d', '%d', '%s')
                    );
                    
                    if ($insert_result !== false) {
                        $results['successful']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Row {$row_number}: Database error";
                    }
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Row {$row_number}: Missing required data";
                }
                
                $results['total']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Row {$row_number}: Invalid number of columns";
            }
        }
        fclose($handle);
    } else {
        return array('success' => false, 'error' => 'Could not read CSV file');
    }
    
    return array('success' => true, 'data' => $results);
}

/**
 * Email notification functions
 */
function iipm_send_cpd_submission_notification($user_id, $entry_id) {
    $user = get_user_by('id', $user_id);
    if (!$user) return false;
    
    global $wpdb;
    $entry = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_cpd_entries WHERE id = %d",
        $entry_id
    ));
    
    if (!$entry) return false;
    
    $subject = 'CPD Submission Received - IIPM';
    $message = "
    Dear {$user->first_name},
    
    Your CPD submission has been received and is under review.
    
    Course: {$entry->course_name}
    Provider: {$entry->provider}
    CPD Points: {$entry->cpd_points}
    Completion Date: {$entry->completion_date}
    
    You will be notified once the review is complete.
    
    Best regards,
    IIPM Team
    ";
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: IIPM Portal <' . (defined('SMTP_FROM') ? SMTP_FROM : get_option('admin_email')) . '>'
    );
    
    return wp_mail($user->user_email, $subject, $message, $headers);
}

function iipm_send_cpd_approval_notification($user_id, $entry_id) {
    $user = get_user_by('id', $user_id);
    if (!$user) return false;
    
    global $wpdb;
    $entry = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_cpd_entries WHERE id = %d",
        $entry_id
    ));
    
    if (!$entry) return false;
    
    $subject = 'CPD Entry Approved - IIPM';
    $message = "
    Dear {$user->first_name},
    
    Your CPD submission has been approved!
    
    Course: {$entry->course_name}
    Provider: {$entry->provider}
    CPD Points Awarded: {$entry->cpd_points}
    
    These points have been added to your CPD record for {$entry->cpd_year}.
    
    You can view your updated CPD progress in your member portal.
    
    Best regards,
    IIPM Team
    ";
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: IIPM Portal <' . (defined('SMTP_FROM') ? SMTP_FROM : get_option('admin_email')) . '>'
    );
    
    return wp_mail($user->user_email, $subject, $message, $headers);
}

function iipm_send_cpd_rejection_notification($user_id, $entry_id, $comments) {
    $user = get_user_by('id', $user_id);
    if (!$user) return false;
    
    global $wpdb;
    $entry = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_cpd_entries WHERE id = %d",
        $entry_id
    ));
    
    if (!$entry) return false;
    
    $subject = 'CPD Entry Requires Attention - IIPM';
    $message = "
    Dear {$user->first_name},
    
    Your CPD submission requires attention and has not been approved at this time.
    
    Course: {$entry->course_name}
    Provider: {$entry->provider}
    
    Admin Comments: {$comments}
    
    Please review the feedback and resubmit if necessary.
    
    If you have questions, please contact us at info@iipm.ie
    
    Best regards,
    IIPM Team
    ";
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: IIPM Portal <' . (defined('SMTP_FROM') ? SMTP_FROM : get_option('admin_email')) . '>'
    );
    
    return wp_mail($user->user_email, $subject, $message, $headers);
}

function iipm_send_admin_cpd_review_notification($entry_id) {
    global $wpdb;
    
    $entry = $wpdb->get_row($wpdb->prepare(
        "SELECT c.*, u.display_name, u.user_email 
         FROM {$wpdb->prefix}test_iipm_cpd_entries c
         JOIN {$wpdb->users} u ON c.user_id = u.ID
         WHERE c.id = %d",
        $entry_id
    ));
    
    if (!$entry) return false;
    
    $admin_email = get_option('admin_email');
    $subject = 'New CPD Submission for Review - IIPM';
    $message = "
    A new CPD submission requires review:
    
    Member: {$entry->display_name} ({$entry->user_email})
    Course: {$entry->course_name}
    Provider: {$entry->provider}
    Category: {$entry->category}
    Duration: {$entry->duration_hours} hours
    Completion Date: {$entry->completion_date}
    
    Please log in to the admin portal to review this submission.
    
    Admin Portal: " . home_url('/admin-cpd-review/') . "
    ";
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: IIPM Portal <' . (defined('SMTP_FROM') ? SMTP_FROM : get_option('admin_email')) . '>'
    );
    
    return wp_mail($admin_email, $subject, $message, $headers);
}

/**
 * Send CPD course completion confirmation email to member
 */
function iipm_send_cpd_course_confirmation_email($user_id, $entry_id, $course) {
    $user = get_user_by('id', $user_id);
    if (!$user) return false;
    
    $subject = 'CPD Course Completion Confirmed - IIPM';
    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; color: white;'>
            <h1 style='margin: 0; font-size: 24px;'>CPD Course Completion Confirmed</h1>
        </div>
        
        <div style='padding: 30px; background: #f8fafc;'>
            <p>Dear {$user->display_name},</p>
            
            <p>We're pleased to confirm that your CPD course completion has been logged successfully:</p>
            
            <div style='background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #10b981; margin: 20px 0;'>
                <h3 style='margin: 0 0 10px 0; color: #1f2937;'>{$course->title}</h3>
                <p style='margin: 5px 0; color: #6b7280;'><strong>Provider:</strong> {$course->provider}</p>
                <p style='margin: 5px 0; color: #6b7280;'><strong>CPD Points Awarded:</strong> {$course->cpd_points}</p>
                <p style='margin: 5px 0; color: #6b7280;'><strong>Status:</strong> <span style='color: #10b981; font-weight: bold;'>Approved</span></p>
            </div>
            
            <p>This course completion has been automatically approved as it's from our pre-approved course library. The CPD points have been added to your annual total.</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='" . home_url('/member-portal/') . "' style='background: #ff6b35; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: bold;'>View My CPD Progress</a>
            </div>
            
            <p style='font-size: 14px; color: #6b7280;'>
                If you have any questions about your CPD requirements or this submission, please contact us at <a href='mailto:" . get_option('admin_email') . "'>" . get_option('admin_email') . "</a>
            </p>
        </div>
        
        <div style='background: #374151; padding: 20px; text-align: center; color: #9ca3af; font-size: 12px;'>
            <p style='margin: 0;'>© " . date('Y') . " Irish Institute of Pension Management. All rights reserved.</p>
        </div>
    </div>
    ";
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: IIPM Portal <' . (defined('SMTP_FROM') ? SMTP_FROM : get_option('admin_email')) . '>'
    );
    
    return wp_mail($user->user_email, $subject, $message, $headers);
}

/**
 * Additional AJAX Handlers
 */

/**
 * Get CPD courses function
 */
function iipm_get_cpd_courses($category_id = null, $active_only = true) {
    global $wpdb;
    
    $where_conditions = array();
    $params = array();
    
    if ($active_only) {
        $where_conditions[] = 'c.is_active = 1';
    }
    
    if ($category_id) {
        $where_conditions[] = 'c.category_id = %d';
        $params[] = $category_id;
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    $sql = "SELECT c.*, cat.name as category_name 
            FROM {$wpdb->prefix}test_iipm_cpd_courses c
            LEFT JOIN {$wpdb->prefix}test_iipm_cpd_categories cat ON c.category_id = cat.id
            {$where_clause}
            ORDER BY c.title ASC";
    
    if (!empty($params)) {
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    } else {
        return $wpdb->get_results($sql);
    }
}

/**
 * Get CPD courses for AJAX
 */
function iipm_ajax_get_cpd_courses() {
    // Accept both regular and admin nonces
    $nonce_valid = wp_verify_nonce($_POST['nonce'], 'iipm_cpd_nonce') || 
                   wp_verify_nonce($_POST['nonce'], 'iipm_cpd_admin_nonce');
    
    if (!$nonce_valid) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!current_user_can('view_cpd_records') && !current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $courses = iipm_get_cpd_courses($category_id, true);
    
    // For admin panel, return courses with additional details
    if (wp_verify_nonce($_POST['nonce'], 'iipm_cpd_admin_nonce')) {
        global $wpdb;
        $detailed_courses = $wpdb->get_results(
            "SELECT c.*, cat.name as category_name 
             FROM {$wpdb->prefix}test_iipm_cpd_courses c
             LEFT JOIN {$wpdb->prefix}test_iipm_cpd_categories cat ON c.category_id = cat.id
             ORDER BY c.title ASC"
        );
        wp_send_json_success(array('courses' => $detailed_courses));
    } else {
        wp_send_json_success($courses);
    }
}
add_action('wp_ajax_iipm_get_cpd_courses', 'iipm_ajax_get_cpd_courses');

/**
 * Get course details for editing
 */
function iipm_get_course_details() {
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_admin_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    $course_id = intval($_POST['course_id']);
    
    global $wpdb;
    $course = $wpdb->get_row($wpdb->prepare(
        "SELECT c.*, cat.name as category_name 
         FROM {$wpdb->prefix}test_iipm_cpd_courses c
         LEFT JOIN {$wpdb->prefix}test_iipm_cpd_categories cat ON c.category_id = cat.id
         WHERE c.id = %d",
        $course_id
    ));
    
    if (!$course) {
        wp_send_json_error('Course not found');
        return;
    }
    
    wp_send_json_success($course);
}
add_action('wp_ajax_iipm_get_course_details', 'iipm_get_course_details');

/**
 * Update course
 */
function iipm_update_course() {
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_admin_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    $course_id = intval($_POST['course_id']);
    $course_name = sanitize_text_field($_POST['course_name']);
    $provider = sanitize_text_field($_POST['provider']);
    $category = sanitize_text_field($_POST['category']);
    $cpd_points = floatval($_POST['cpd_points']);
    $course_code = sanitize_text_field($_POST['course_code']);
    
    // Validate inputs
    if (empty($course_name) || empty($provider) || empty($category) || $cpd_points <= 0) {
        wp_send_json_error('All required fields must be filled');
        return;
    }
    
    // Get category ID
    global $wpdb;
    $category_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}test_iipm_cpd_categories WHERE name = %s",
        ucfirst(str_replace('_', ' & ', $category))
    ));
    
    if (!$category_id) {
        wp_send_json_error('Invalid category');
        return;
    }
    
    // Update course
    $result = $wpdb->update(
        $wpdb->prefix . 'test_iipm_cpd_courses',
        array(
            'title' => $course_name,
            'provider' => $provider,
            'category_id' => $category_id,
            'cpd_points' => $cpd_points,
            'lia_code' => $course_code
        ),
        array('id' => $course_id),
        array('%s', '%s', '%d', '%f', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to update course');
        return;
    }
    
    wp_send_json_success(array('message' => 'Course updated successfully'));
}
add_action('wp_ajax_iipm_update_course', 'iipm_update_course');

/**
 * Delete course
 */
function iipm_delete_course() {
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_admin_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    $course_id = intval($_POST['course_id']);
    
    global $wpdb;
    
    // Check if course is being used in any CPD records
    $usage_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_cpd_records WHERE course_id = %d",
        $course_id
    ));
    
    if ($usage_count > 0) {
        // Don't delete, just deactivate
        $result = $wpdb->update(
            $wpdb->prefix . 'test_iipm_cpd_courses',
            array('is_active' => 0),
            array('id' => $course_id),
            array('%d'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to deactivate course');
            return;
        }
        
        wp_send_json_success(array('message' => 'Course deactivated (was in use by members)'));
    } else {
        // Safe to delete
        $result = $wpdb->delete(
            $wpdb->prefix . 'test_iipm_cpd_courses',
            array('id' => $course_id),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to delete course');
            return;
        }
        
        wp_send_json_success(array('message' => 'Course deleted successfully'));
    }
}
add_action('wp_ajax_iipm_delete_course', 'iipm_delete_course');

/**
 * Get CPD categories
 */
function iipm_get_cpd_categories($active_only = true) {
    global $wpdb;
    
    $where_clause = '';
    if ($active_only) {
        $where_clause = 'WHERE is_active = 1';
    }
    
    $sql = "SELECT id, name, description, min_points_required, max_points_allowed, is_mandatory, sort_order 
            FROM {$wpdb->prefix}test_iipm_cpd_categories 
            {$where_clause}
            ORDER BY sort_order ASC, name ASC";
    
    return $wpdb->get_results($sql);
}

/**
 * Get CPD categories for AJAX
 */
function iipm_ajax_get_cpd_categories() {
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!current_user_can('view_cpd_records')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    $categories = iipm_get_cpd_categories(true);
    
    wp_send_json_success($categories);
}
add_action('wp_ajax_iipm_get_cpd_categories', 'iipm_ajax_get_cpd_categories');

/**
 * Log CPD course completion
 */
function iipm_ajax_log_cpd_course() {
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!current_user_can('submit_cpd_entries')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    $user_id = get_current_user_id();
    $course_id = intval($_POST['course_id']);
    $completion_date = sanitize_text_field($_POST['completion_date']);
    $notes = sanitize_textarea_field($_POST['notes'] ?? '');
    
    // Handle optional certificate upload
    $certificate_path = null;
    if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK) {
        $file_upload = iipm_handle_cpd_file_upload($_FILES['certificate']);
        if ($file_upload['success']) {
            $certificate_path = str_replace(wp_upload_dir()['basedir'], '', $file_upload['file']);
        } else {
            wp_send_json_error('File upload failed: ' . $file_upload['error']);
            return;
        }
    }
    
    // Validate inputs
    if (!$course_id || !$completion_date) {
        wp_send_json_error('Course and completion date are required');
        return;
    }
    
    // Validate date
    if (!strtotime($completion_date)) {
        wp_send_json_error('Invalid completion date');
        return;
    }
    
    // Get course details
    global $wpdb;
    $course = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_cpd_courses WHERE id = %d AND is_active = 1",
        $course_id
    ));
    
    if (!$course) {
        wp_send_json_error('Course not found');
        return;
    }
    
    // Check if already logged
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}test_iipm_cpd_records 
         WHERE user_id = %d AND course_id = %d AND completion_date = %s",
        $user_id,
        $course_id,
        $completion_date
    ));
    
    if ($existing) {
        wp_send_json_error('This course completion has already been logged for this date');
        return;
    }
    
    // Insert CPD record
    $cpd_year = date('Y', strtotime($completion_date));
    $result = $wpdb->insert(
        $wpdb->prefix . 'test_iipm_cpd_records',
        array(
            'user_id' => $user_id,
            'course_id' => $course_id,
            'category_id' => $course->category_id,
            'activity_title' => $course->title,
            'description' => $course->description,
            'external_provider' => $course->provider,
            'cpd_points' => $course->cpd_points,
            'completion_date' => $completion_date,
            'cpd_year' => $cpd_year,
            'status' => 'approved', // Pre-approved courses are automatically approved
            'notes' => $notes,
            'certificate_path' => $certificate_path
        ),
        array('%d', '%d', '%d', '%s', '%s', '%s', '%f', '%s', '%d', '%s', '%s', '%s')
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to log CPD course');
        return;
    }
    
    // Update member's total CPD points
    iipm_update_member_cpd_points($user_id);
    
    // Log activity
    iipm_log_user_activity(
        $user_id,
        'cpd_course_logged',
        "Logged CPD course: {$course->title} ({$course->cpd_points} points)"
    );
    
    // Send confirmation email to member
    $entry_id = $wpdb->insert_id;
    iipm_send_cpd_course_confirmation_email($user_id, $entry_id, $course);
    
    wp_send_json_success(array(
        'message' => 'CPD course logged successfully',
        'points' => $course->cpd_points
    ));
}
add_action('wp_ajax_iipm_log_cpd_course', 'iipm_ajax_log_cpd_course');

/**
 * Submit external CPD training
 */
function iipm_ajax_submit_external_cpd() {
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!current_user_can('submit_cpd_entries')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    $user_id = get_current_user_id();
    
    // Validate required fields
    $required_fields = array('activity_title', 'external_provider', 'category_id', 'cpd_points', 'completion_date', 'description');
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            wp_send_json_error("Field '$field' is required");
            return;
        }
    }
    
    $activity_title = sanitize_text_field($_POST['activity_title']);
    $external_provider = sanitize_text_field($_POST['external_provider']);
    $category_id = intval($_POST['category_id']);
    $cpd_points = floatval($_POST['cpd_points']);
    $completion_date = sanitize_text_field($_POST['completion_date']);
    $description = sanitize_textarea_field($_POST['description']);
    
    // Validate inputs
    if ($cpd_points <= 0 || $cpd_points > 50) {
        wp_send_json_error('CPD points must be between 0.5 and 50');
        return;
    }
    
    if (!strtotime($completion_date)) {
        wp_send_json_error('Invalid completion date');
        return;
    }
    
    // Validate category exists
    global $wpdb;
    $category = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}test_iipm_cpd_categories WHERE id = %d AND is_active = 1",
        $category_id
    ));
    
    if (!$category) {
        wp_send_json_error('Invalid category selected');
        return;
    }
    
    // Handle file upload if present
    $certificate_path = null;
    if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK) {
        $file_upload = iipm_handle_cpd_file_upload($_FILES['certificate']);
        if ($file_upload['success']) {
            $certificate_path = str_replace(wp_upload_dir()['basedir'], '', $file_upload['file']);
        } else {
            wp_send_json_error('File upload failed: ' . $file_upload['error']);
            return;
        }
    }
    
    // Insert CPD record
    $cpd_year = date('Y', strtotime($completion_date));
    $result = $wpdb->insert(
        $wpdb->prefix . 'test_iipm_cpd_records',
        array(
            'user_id' => $user_id,
            'course_id' => null,
            'category_id' => $category_id,
            'activity_title' => $activity_title,
            'description' => $description,
            'external_provider' => $external_provider,
            'cpd_points' => $cpd_points,
            'completion_date' => $completion_date,
            'cpd_year' => $cpd_year,
            'status' => 'pending', // External training requires approval
            'certificate_path' => $certificate_path
        ),
        array('%d', '%d', '%d', '%s', '%s', '%s', '%f', '%s', '%d', '%s', '%s')
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to submit external CPD training');
        return;
    }
    
    // Log activity
    iipm_log_user_activity(
        $user_id,
        'external_cpd_submitted',
        "Submitted external CPD: {$activity_title} ({$cpd_points} points) for approval"
    );
    
    wp_send_json_success(array(
        'message' => 'External CPD training submitted for approval',
        'points' => $cpd_points
    ));
}
add_action('wp_ajax_iipm_submit_external_cpd', 'iipm_ajax_submit_external_cpd');

/**
 * Get CPD library for browsing
 */
function iipm_ajax_get_cpd_library() {
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!current_user_can('access_cpd_library')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    
    global $wpdb;
    
    $where_conditions = array('c.is_active = 1');
    $params = array();
    
    if ($category_id) {
        $where_conditions[] = 'c.category_id = %d';
        $params[] = $category_id;
    }
    
    if ($search) {
        $where_conditions[] = '(c.title LIKE %s OR c.description LIKE %s OR c.provider LIKE %s)';
        $search_term = '%' . $wpdb->esc_like($search) . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    $sql = "SELECT c.*, cat.name as category_name 
            FROM {$wpdb->prefix}test_iipm_cpd_courses c
            LEFT JOIN {$wpdb->prefix}test_iipm_cpd_categories cat ON c.category_id = cat.id
            $where_clause
            ORDER BY c.title ASC
            LIMIT 50";
    
    if (!empty($params)) {
        $courses = $wpdb->get_results($wpdb->prepare($sql, $params));
    } else {
        $courses = $wpdb->get_results($sql);
    }
    
    // Generate HTML for the library
    ob_start();
    ?>
    <div class="cpd-library-filters mb-3">
        <div class="row">
            <div class="col-md-6">
                <select class="form-select" id="libraryCategory">
                    <option value="">All Categories</option>
                    <?php
                    $categories = iipm_get_cpd_categories(true);
                    foreach ($categories as $cat) {
                        $selected = ($cat->id == $category_id) ? 'selected' : '';
                        echo "<option value='{$cat->id}' {$selected}>{$cat->name}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control" id="librarySearch" placeholder="Search courses..." value="<?php echo esc_attr($search); ?>">
            </div>
        </div>
    </div>
    
    <div class="cpd-library-results">
        <?php if (empty($courses)): ?>
            <div class="alert alert-info">No courses found matching your criteria.</div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($courses as $course): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0"><?php echo esc_html($course->title); ?></h6>
                                    <span class="badge bg-primary"><?php echo $course->cpd_points; ?> pts</span>
                                </div>
                                <p class="card-text small text-muted mb-2">
                                    <strong>Provider:</strong> <?php echo esc_html($course->provider); ?><br>
                                    <strong>Category:</strong> <?php echo esc_html($course->category_name); ?><br>
                                    <strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $course->course_type)); ?>
                                    <?php if ($course->duration_minutes): ?>
                                        <br><strong>Duration:</strong> <?php echo $course->duration_minutes; ?> minutes
                                    <?php endif; ?>
                                </p>
                                <?php if ($course->description): ?>
                                    <p class="card-text small"><?php echo esc_html(substr($course->description, 0, 100)) . (strlen($course->description) > 100 ? '...' : ''); ?></p>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-primary" onclick="logCourseFromLibrary(<?php echo $course->id; ?>, '<?php echo esc_js($course->title); ?>')">
                                    Log This Course
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
    function logCourseFromLibrary(courseId, courseTitle) {
        $('#cpdLibraryModal').modal('hide');
        $('#cpdCourseModal select[name="course_id"]').val(courseId);
        $('#cpdCourseModal').modal('show');
    }
    
    $('#libraryCategory, #librarySearch').on('change keyup', function() {
        // Reload library with new filters
        var category = $('#libraryCategory').val();
        var search = $('#librarySearch').val();
        
        $.ajax({
            url: iipm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'iipm_get_cpd_library',
                nonce: iimp_ajax.cpd_nonce,
                category_id: category,
                search: search
            },
            success: function(response) {
                if (response.success) {
                    $('#cpdLibraryContent').html(response.data);
                }
            }
        });
    });
    </script>
    <?php
    $html = ob_get_clean();
    
    wp_send_json_success($html);
}
add_action('wp_ajax_iipm_get_cpd_library', 'iipm_ajax_get_cpd_library');

/**
 * Admin: Review CPD submission
 */
function iipm_ajax_review_cpd_submission() {
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_admin_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!current_user_can('review_cpd_submissions')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    $record_id = intval($_POST['record_id']);
    $action = sanitize_text_field($_POST['review_action']); // 'approve' or 'reject'
    $review_notes = sanitize_textarea_field($_POST['review_notes'] ?? '');
    
    if (!in_array($action, array('approve', 'reject'))) {
        wp_send_json_error('Invalid review action');
        return;
    }
    
    global $wpdb;
    
    // Get the record
    $record = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_cpd_records WHERE id = %d",
        $record_id
    ));
    
    if (!$record) {
        wp_send_json_error('CPD record not found');
        return;
    }
    
    // Update the record
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    $result = $wpdb->update(
        $wpdb->prefix . 'test_iipm_cpd_records',
        array(
            'status' => $status,
            'reviewed_by' => get_current_user_id(),
            'reviewed_at' => current_time('mysql'),
            'review_notes' => $review_notes
        ),
        array('id' => $record_id),
        array('%s', '%d', '%s', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to update CPD record');
        return;
    }
    
    // Update member's total CPD points if approved
    if ($status === 'approved') {
        iipm_update_member_cpd_points($record->user_id);
    }
    
    // Log activity
    iipm_log_user_activity(
        get_current_user_id(),
        'cpd_review_' . $action,
        "CPD submission {$action}d: {$record->activity_title} for user {$record->user_id}"
    );
    
    // Send notification to user (optional)
    // iipm_send_cpd_review_notification($record->user_id, $record, $status, $review_notes);
    
    wp_send_json_success(array(
        'message' => "CPD submission {$action}d successfully",
        'status' => $status
    ));
}
add_action('wp_ajax_iipm_review_cpd_submission', 'iipm_ajax_review_cpd_submission');

/**
 * Get CPD records for admin review
 */
function iipm_ajax_get_cpd_pending_reviews() {
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_admin_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!current_user_can('review_cpd_submissions')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    global $wpdb;
    
    $pending_records = $wpdb->get_results(
        "SELECT cr.*, u.display_name, u.user_email, cc.name as category_name
         FROM {$wpdb->prefix}test_iipm_cpd_records cr
         LEFT JOIN {$wpdb->prefix}users u ON cr.user_id = u.ID
         LEFT JOIN {$wpdb->prefix}test_iipm_cpd_categories cc ON cr.category_id = cc.id
         WHERE cr.status = 'pending'
         ORDER BY cr.created_at ASC"
    );
    
    wp_send_json_success($pending_records);
}
add_action('wp_ajax_iipm_get_cpd_pending_reviews', 'iipm_ajax_get_cpd_pending_reviews');

/**
 * Generate CPD report
 */
function iipm_ajax_generate_cpd_report() {
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_admin_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!current_user_can('generate_cpd_reports')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    $report_type = sanitize_text_field($_POST['report_type'] ?? 'summary');
    $year = intval($_POST['year'] ?? date('Y'));
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
    
    global $wpdb;
    
    switch ($report_type) {
        case 'summary':
            $data = iipm_generate_cpd_summary_report($year);
            break;
        case 'individual':
            if (!$user_id) {
                wp_send_json_error('User ID required for individual report');
                return;
            }
            $data = iipm_generate_individual_cpd_report($user_id, $year);
            break;
        case 'compliance':
            $data = iipm_generate_cpd_compliance_report($year);
            break;
        default:
            wp_send_json_error('Invalid report type');
            return;
    }
    
    wp_send_json_success($data);
}
add_action('wp_ajax_iipm_generate_cpd_report', 'iipm_ajax_generate_cpd_report');

/**
 * Generate CPD summary report
 */
function iipm_generate_cpd_summary_report($year) {
    global $wpdb;
    
    $summary = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            cc.name as category,
            COUNT(cr.id) as total_submissions,
            COUNT(CASE WHEN cr.status = 'approved' THEN 1 END) as approved_submissions,
            COUNT(CASE WHEN cr.status = 'pending' THEN 1 END) as pending_submissions,
            COUNT(CASE WHEN cr.status = 'rejected' THEN 1 END) as rejected_submissions,
            SUM(CASE WHEN cr.status = 'approved' THEN cr.cpd_points ELSE 0 END) as total_points
         FROM {$wpdb->prefix}test_iipm_cpd_categories cc
         LEFT JOIN {$wpdb->prefix}test_iipm_cpd_records cr ON cc.id = cr.category_id AND cr.cpd_year = %d
         WHERE cc.is_active = 1
         GROUP BY cc.id, cc.name
         ORDER BY cc.sort_order ASC",
        $year
    ));
    
    return array(
        'year' => $year,
        'summary' => $summary,
        'generated_at' => current_time('mysql')
    );
}

/**
 * Generate individual CPD report
 */
function iipm_generate_individual_cpd_report($user_id, $year) {
    global $wpdb;
    
    $user = get_user_by('id', $user_id);
    if (!$user) {
        return array('error' => 'User not found');
    }
    
    $records = $wpdb->get_results($wpdb->prepare(
        "SELECT cr.*, cc.name as category_name, c.title as course_title
         FROM {$wpdb->prefix}test_iipm_cpd_records cr
         LEFT JOIN {$wpdb->prefix}test_iipm_cpd_categories cc ON cr.category_id = cc.id
         LEFT JOIN {$wpdb->prefix}test_iipm_cpd_courses c ON cr.course_id = c.id
         WHERE cr.user_id = %d AND cr.cpd_year = %d
         ORDER BY cr.completion_date DESC",
        $user_id,
        $year
    ));
    
    $summary = iipm_get_user_cpd_summary($user_id, $year);
    
    return array(
        'user' => array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email
        ),
        'year' => $year,
        'summary' => $summary,
        'records' => $records,
        'generated_at' => current_time('mysql')
    );
}

/**
 * Generate CPD compliance report
 */
function iipm_generate_cpd_compliance_report($year) {
    global $wpdb;
    
    $compliance_data = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            u.ID as user_id,
            u.display_name,
            u.user_email,
            m.cpd_points_required,
            COALESCE(SUM(CASE WHEN cr.status = 'approved' THEN cr.cpd_points ELSE 0 END), 0) as points_earned,
            CASE 
                WHEN COALESCE(SUM(CASE WHEN cr.status = 'approved' THEN cr.cpd_points ELSE 0 END), 0) >= m.cpd_points_required THEN 'Compliant'
                ELSE 'Non-Compliant'
            END as compliance_status
         FROM {$wpdb->prefix}users u
         INNER JOIN {$wpdb->prefix}test_iipm_members m ON u.ID = m.user_id
         LEFT JOIN {$wpdb->prefix}test_iipm_cpd_records cr ON u.ID = cr.user_id AND cr.cpd_year = %d
         WHERE m.membership_status = 'active'
         GROUP BY u.ID, u.display_name, u.user_email, m.cpd_points_required
         ORDER BY compliance_status DESC, u.display_name ASC",
        $year
    ));
    
    $total_members = count($compliance_data);
    $compliant_members = count(array_filter($compliance_data, function($member) {
        return $member->compliance_status === 'Compliant';
    }));
    
    return array(
        'year' => $year,
        'total_members' => $total_members,
        'compliant_members' => $compliant_members,
        'compliance_rate' => $total_members > 0 ? round(($compliant_members / $total_members) * 100, 2) : 0,
        'members' => $compliance_data,
        'generated_at' => current_time('mysql')
    );
}
?>
