<?php
/**
 * User Management AJAX Handlers
 */

// Get users with filtering and pagination
function iipm_get_users() {
    // Check permissions
    if (!current_user_can('administrator') && 
        !current_user_can('manage_organisation_members') && 
        !in_array('iipm_corporate_admin', wp_get_current_user()->roles)) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Auto-sync missing last_login data for administrators
    if (current_user_can('administrator')) {
        iipm_sync_missing_last_login_data();
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_user_management_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    global $wpdb;
    
    $current_user = wp_get_current_user();
    $is_site_admin = current_user_can('administrator');
    $is_org_admin = in_array('iipm_corporate_admin', $current_user->roles) || current_user_can('manage_organisation_members');
    
    // Get parameters
    $page = intval($_POST['page'] ?? 1);
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    $search = sanitize_text_field($_POST['search'] ?? '');
    $role_filter = sanitize_text_field($_POST['role_filter'] ?? '');
    $status_filter = sanitize_text_field($_POST['status_filter'] ?? '');
    
    // Build WHERE clause
    $where_conditions = array();
    $where_params = array();
    
    // Organization restriction for org admins
    if (!$is_site_admin && $is_org_admin) {
        // Get the organization ID for the current user
        $org_id = $wpdb->get_var($wpdb->prepare(
            "SELECT organisation_id FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d",
            $current_user->ID
        ));
        
        if ($org_id) {
            $where_conditions[] = "m.organisation_id = %d";
            $where_params[] = $org_id;
        } else {
            // If org admin has no organization, show no users
            wp_send_json_success(array(
                'users' => array(),
                'pagination' => array(
                    'current_page' => 1,
                    'total_pages' => 0,
                    'total_users' => 0
                )
            ));
            return;
        }
    }
    
    // Search filter
    if (!empty($search)) {
        $where_conditions[] = "(u.display_name LIKE %s OR u.user_email LIKE %s)";
        $where_params[] = '%' . $search . '%';
        $where_params[] = '%' . $search . '%';
    }
    
    // Status filter
    if (!empty($status_filter)) {
        $where_conditions[] = "m.membership_status = %s";
        $where_params[] = $status_filter;
    }
    
    // Build WHERE clause
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    // Get total count
    $count_sql = "
        SELECT COUNT(DISTINCT u.ID)
        FROM {$wpdb->users} u
        LEFT JOIN {$wpdb->prefix}test_iipm_members m ON u.ID = m.user_id
        LEFT JOIN {$wpdb->prefix}test_iipm_organisations o ON m.organisation_id = o.id
        {$where_clause}
    ";
    
    $total_users = $wpdb->get_var($wpdb->prepare($count_sql, $where_params));
    
    // Get users with pagination
    $users_sql = "
        SELECT u.ID, u.display_name, u.user_email, u.user_registered,
               m.membership_status, m.last_login, m.organisation_id,
               o.name as organisation_name
        FROM {$wpdb->users} u
        LEFT JOIN {$wpdb->prefix}test_iipm_members m ON u.ID = m.user_id
        LEFT JOIN {$wpdb->prefix}test_iipm_organisations o ON m.organisation_id = o.id
        {$where_clause}
        ORDER BY u.display_name ASC
        LIMIT %d OFFSET %d
    ";
    
    $users_params = array_merge($where_params, array($per_page, $offset));
    $users = $wpdb->get_results($wpdb->prepare($users_sql, $users_params));
    
    // Process users data
    $processed_users = array();
    foreach ($users as $user) {
        $wp_user = get_user_by('id', $user->ID);
        $user_roles = $wp_user->roles;
        
        // Role filter
        if (!empty($role_filter) && !in_array($role_filter, $user_roles)) {
            continue;
        }
        
        // Get role display name
        $role_display = 'Member';
        if (in_array('administrator', $user_roles)) {
            $role_display = 'Administrator';
        } elseif (in_array('iipm_corporate_admin', $user_roles)) {
            $role_display = 'Corporate Admin';
        } elseif (in_array('iipm_council_member', $user_roles)) {
            $role_display = 'Council Member';
        } elseif (in_array('iipm_member', $user_roles)) {
            $role_display = 'IIPM Member';
        }
        
        $processed_users[] = array(
            'ID' => $user->ID,
            'display_name' => $user->display_name,
            'user_email' => $user->user_email,
            'membership_status' => $user->membership_status ?: 'pending',
            'organisation_name' => $user->organisation_name,
            'last_login' => $user->last_login ? date('M j, Y g:i A', strtotime($user->last_login)) : null,
            'role_display' => $role_display,
            'roles' => $user_roles
        );
    }
    
    // Calculate pagination
    $total_pages = ceil($total_users / $per_page);
    
    wp_send_json_success(array(
        'users' => $processed_users,
        'pagination' => array(
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_users' => $total_users
        )
    ));
}
add_action('wp_ajax_iipm_get_users', 'iipm_get_users');

// Get user details for editing
function iipm_get_user_details() {
    // Check permissions
    if (!current_user_can('administrator') && 
        !current_user_can('manage_organisation_members') && 
        !in_array('iipm_corporate_admin', wp_get_current_user()->roles)) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_user_management_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $user_id = intval($_POST['user_id']);
    if (!$user_id) {
        wp_send_json_error('Invalid user ID');
        return;
    }
    
    // Check if org admin can edit this user
    $current_user = wp_get_current_user();
    $is_site_admin = current_user_can('administrator');
    
    if (!$is_site_admin) {
        global $wpdb;
        $current_org_id = $wpdb->get_var($wpdb->prepare(
            "SELECT organisation_id FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d",
            $current_user->ID
        ));
        
        $target_org_id = $wpdb->get_var($wpdb->prepare(
            "SELECT organisation_id FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d",
            $user_id
        ));
        
        if ($current_org_id != $target_org_id) {
            wp_send_json_error('You can only edit users from your organization');
            return;
        }
    }
    
    $user = get_user_by('id', $user_id);
    if (!$user) {
        wp_send_json_error('User not found');
        return;
    }
    
    global $wpdb;
    $member_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d",
        $user_id
    ));
    
    wp_send_json_success(array(
        'ID' => $user->ID,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'user_email' => $user->user_email,
        'roles' => $user->roles,
        'membership_status' => $member_data ? $member_data->membership_status : 'pending'
    ));
}
add_action('wp_ajax_iipm_get_user_details', 'iipm_get_user_details');

// Update user
function iipm_update_user() {
    // Check permissions
    if (!current_user_can('administrator') && 
        !current_user_can('manage_organisation_members') && 
        !in_array('iipm_corporate_admin', wp_get_current_user()->roles)) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_user_management_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $user_id = intval($_POST['user_id']);
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $email = sanitize_email($_POST['email']);
    $role = sanitize_text_field($_POST['role']);
    $status = sanitize_text_field($_POST['status']);
    
    if (!$user_id || !$first_name || !$last_name || !$email) {
        wp_send_json_error('Missing required fields');
        return;
    }
    
    // Check if org admin can edit this user
    $current_user = wp_get_current_user();
    $is_site_admin = current_user_can('administrator');
    
    if (!$is_site_admin) {
        global $wpdb;
        $current_org_id = $wpdb->get_var($wpdb->prepare(
            "SELECT organisation_id FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d",
            $current_user->ID
        ));
        
        $target_org_id = $wpdb->get_var($wpdb->prepare(
            "SELECT organisation_id FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d",
            $user_id
        ));
        
        if ($current_org_id != $target_org_id) {
            wp_send_json_error('You can only edit users from your organization');
            return;
        }
        
        // Org admins cannot assign administrator role
        if ($role === 'administrator') {
            wp_send_json_error('You cannot assign administrator role');
            return;
        }
    }
    
    // Check if email already exists for another user
    $existing_user = get_user_by('email', $email);
    if ($existing_user && $existing_user->ID != $user_id) {
        wp_send_json_error('Email already exists for another user');
        return;
    }
    
    // Update user data
    $user_data = array(
        'ID' => $user_id,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'display_name' => $first_name . ' ' . $last_name,
        'user_email' => $email
    );

    // Add password to user data if provided
    if (!empty($_POST['password'])) {
        $user_data['user_pass'] = $_POST['password'];
    }
    
    $result = wp_update_user($user_data);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
        return;
    }
    
    // Update user role
    $user = new WP_User($user_id);
    $user->set_role($role);
    
    // Update membership status
    global $wpdb;
    $wpdb->update(
        $wpdb->prefix . 'test_iipm_members',
        array('membership_status' => $status),
        array('user_id' => $user_id),
        array('%s'),
        array('%d')
    );
    
    // Log activity
    iipm_log_user_activity(
        get_current_user_id(),
        'user_updated',
        "Updated user: {$first_name} {$last_name} ({$email})"
    );
    
    wp_send_json_success('User updated successfully');
}
add_action('wp_ajax_iipm_update_user', 'iipm_update_user');

// Delete user
function iipm_delete_user() {
    // Check permissions
    if (!current_user_can('administrator') && 
        !current_user_can('manage_organisation_members') && 
        !in_array('iipm_corporate_admin', wp_get_current_user()->roles)) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_user_management_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $user_id = intval($_POST['user_id']);
    if (!$user_id) {
        wp_send_json_error('Invalid user ID');
        return;
    }
    
    // Check if org admin can delete this user
    $current_user = wp_get_current_user();
    $is_site_admin = current_user_can('administrator');
    
    if (!$is_site_admin) {
        global $wpdb;
        $current_org_id = $wpdb->get_var($wpdb->prepare(
            "SELECT organisation_id FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d",
            $current_user->ID
        ));
        
        $target_org_id = $wpdb->get_var($wpdb->prepare(
            "SELECT organisation_id FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d",
            $user_id
        ));
        
        if ($current_org_id != $target_org_id) {
            wp_send_json_error('You can only delete users from your organization');
            return;
        }
    }
    
    // Don't allow deleting administrators unless you're an admin
    $user_to_delete = get_user_by('id', $user_id);
    if ($user_to_delete && in_array('administrator', $user_to_delete->roles) && !$is_site_admin) {
        wp_send_json_error('You cannot delete administrators');
        return;
    }
    
    // Don't allow deleting yourself
    if ($user_id == get_current_user_id()) {
        wp_send_json_error('You cannot delete yourself');
        return;
    }
    
    // Get user info for logging
    $user_info = get_user_by('id', $user_id);
    $user_name = $user_info ? $user_info->display_name : 'Unknown';
    $user_email = $user_info ? $user_info->user_email : 'Unknown';
    
    // Delete related data first
    global $wpdb;
    
    // Delete member profile
    $wpdb->delete(
        $wpdb->prefix . 'test_iipm_member_profiles',
        array('user_id' => $user_id),
        array('%d')
    );
    
    // Delete member record
    $wpdb->delete(
        $wpdb->prefix . 'test_iipm_members',
        array('user_id' => $user_id),
        array('%d')
    );
    
    // Delete user activity logs
    $wpdb->delete(
        $wpdb->prefix . 'test_iipm_user_activity',
        array('user_id' => $user_id),
        array('%d')
    );
    
    // Delete the WordPress user
    $result = wp_delete_user($user_id);
    
    if (!$result) {
        wp_send_json_error('Failed to delete user');
        return;
    }
    
    // Log activity
    iipm_log_user_activity(
        get_current_user_id(),
        'user_deleted',
        "Deleted user: {$user_name} ({$user_email})"
    );
    
    wp_send_json_success('User deleted successfully');
}
add_action('wp_ajax_iipm_delete_user', 'iipm_delete_user');

// Sync missing last_login data
function iipm_sync_last_login_data() {
    // Check permissions
    if (!current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_user_management_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $updated_count = iipm_sync_missing_last_login_data();
    
    wp_send_json_success(array(
        'message' => "Successfully synced {$updated_count} user login timestamps",
        'updated_count' => $updated_count
    ));
}
add_action('wp_ajax_iipm_sync_last_login_data', 'iipm_sync_last_login_data');
?>
