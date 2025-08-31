<?php
/**
 * Direct Admin Assignment - For Local Development
 * 
 * This file provides functionality to directly assign admin users to organisations
 * without requiring email invitations, useful for local development environments.
 */

/**
 * AJAX handler for direct admin assignment
 */
function iipm_direct_admin_assignment() {
    error_log('IIPM: iipm_direct_admin_assignment called');
    error_log('IIPM: POST data: ' . print_r($_POST, true));
    
    // Check permissions
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        error_log('IIPM: Permission denied for user: ' . get_current_user_id());
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_portal_nonce')) {
        error_log('IIPM: Nonce verification failed');
        wp_send_json_error('Security check failed');
        return;
    }
    
    $org_id = intval($_POST['org_id']);
    $user_id = intval($_POST['user_id']);
    
    error_log('IIPM: Processing assignment - Org ID: ' . $org_id . ', User ID: ' . $user_id);
    
    if (!$org_id || !$user_id) {
        error_log('IIPM: Missing required IDs');
        wp_send_json_error('Organisation ID and User ID are required');
        return;
    }
    
    global $wpdb;
    
    // Check if organisation exists
    $org = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_organisations WHERE id = %d",
        $org_id
    ));
    
    if (!$org) {
        error_log('IIPM: Organisation not found: ' . $org_id);
        wp_send_json_error('Organisation not found');
        return;
    }
    
    // Check if user exists
    $user = get_user_by('id', $user_id);
    if (!$user) {
        error_log('IIPM: User not found: ' . $user_id);
        wp_send_json_error('User not found');
        return;
    }
    
    // Update organisation with admin user ID
    $result = $wpdb->update(
        $wpdb->prefix . 'test_iipm_organisations',
        array('admin_user_id' => $user_id, 'updated_at' => current_time('mysql')),
        array('id' => $org_id),
        array('%d', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        error_log('IIPM: Failed to update organisation: ' . $wpdb->last_error);
        wp_send_json_error('Failed to update organisation: ' . $wpdb->last_error);
        return;
    }
    
    // Set user role to corporate admin
    $user->set_role('iipm_corporate_admin');
    
    // Check if user is already a member of this organisation
    $member = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d",
        $user_id
    ));
    
    if (!$member) {
        // Create member record
        $member_result = $wpdb->insert(
            $wpdb->prefix . 'test_iipm_members',
            array(
                'user_id' => $user_id,
                'member_type' => 'organisation',
                'organisation_id' => $org_id,
                'membership_status' => 'active',
                'gdpr_consent' => 1,
                'email_verified' => 1,
                'profile_completed' => 1
            ),
            array('%d', '%s', '%d', '%s', '%d', '%d', '%d')
        );
        
        if ($member_result === false) {
            error_log('IIPM: Failed to create member record: ' . $wpdb->last_error);
        }
    } else {
        // Update existing member record
        $member_result = $wpdb->update(
            $wpdb->prefix . 'test_iipm_members',
            array(
                'organisation_id' => $org_id,
                'membership_status' => 'active',
                'updated_at' => current_time('mysql')
            ),
            array('user_id' => $user_id),
            array('%d', '%s', '%s'),
            array('%d')
        );
        
        if ($member_result === false) {
            error_log('IIPM: Failed to update member record: ' . $wpdb->last_error);
        }
    }
    
    // Log activity
    iipm_log_user_activity(
        get_current_user_id(),
        'direct_admin_assignment',
        "User ID {$user_id} assigned as admin for organisation ID {$org_id}"
    );
    
    error_log('IIPM: Admin assignment successful');
    
    wp_send_json_success(array(
        'message' => 'Admin assigned successfully',
        'admin_name' => $user->display_name,
        'admin_email' => $user->user_email
    ));
}
add_action('wp_ajax_iipm_direct_admin_assignment', 'iipm_direct_admin_assignment');

/**
 * Get all users for admin assignment dropdown
 */
function iipm_get_all_users_for_assignment() {
    error_log('IIPM: iipm_get_all_users_for_assignment called');
    
    // Check permissions
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        error_log('IIPM: Permission denied for get users');
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_portal_nonce')) {
        error_log('IIPM: Nonce verification failed for get users');
        wp_send_json_error('Security check failed');
        return;
    }
    
    $users = get_users(array(
        'orderby' => 'display_name',
        'order' => 'ASC',
        'number' => 100 // Limit to 100 users for performance
    ));
    
    $user_options = array();
    foreach ($users as $user) {
        $user_options[] = array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'text' => $user->display_name . ' (' . $user->user_email . ')'
        );
    }
    
    error_log('IIPM: Found ' . count($user_options) . ' users');
    
    wp_send_json_success($user_options);
}
add_action('wp_ajax_iipm_get_all_users_for_assignment', 'iipm_get_all_users_for_assignment');
?>
