<?php
/**
 * Enhanced AJAX Handlers for Organisation Management
 */

/**
 * Enhanced member registration handler with organisation admin support
 */
function iipm_handle_enhanced_member_registration() {
    error_log('IIPM: Enhanced member registration called');
    error_log('IIPM: POST data: ' . print_r($_POST, true));
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_registration_nonce')) {
        error_log('IIPM: Nonce verification failed');
        wp_send_json_error('Security check failed');
    }
    
    $token = sanitize_text_field($_POST['token'] ?? '');
    $invitation = null;
    
    if ($token) {
        $invitation = iipm_validate_enhanced_invitation_token($token);
        if (!$invitation) {
            wp_send_json_error('Invalid or expired invitation');
        }
    }
    
    $result = iipm_process_enhanced_member_registration($_POST, $token);
    
    error_log('IIPM: Enhanced registration result: ' . print_r($result, true));
    
    if ($result['success']) {
        // Add additional context for frontend
        $result['data']['invitation_type'] = $invitation ? $invitation->invitation_type : 'public';
        $result['data']['organisation_name'] = $invitation ? $invitation->organisation_name : null;
        
        wp_send_json_success($result['data']);
    } else {
        wp_send_json_error($result['error']);
    }
}

// Replace the existing handler
remove_action('wp_ajax_iipm_register_member', 'iipm_handle_member_registration');
remove_action('wp_ajax_nopriv_iipm_register_member', 'iipm_handle_member_registration');

add_action('wp_ajax_iipm_register_member', 'iipm_handle_enhanced_member_registration');
add_action('wp_ajax_nopriv_iipm_register_member', 'iipm_handle_enhanced_member_registration');

/**
 * Get organisation admin dashboard data
 */
function iipm_get_admin_dashboard_data() {
    if (!current_user_can('manage_organisation_members')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    $user_id = get_current_user_id();
    
    global $wpdb;
    $organisation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_organisations WHERE admin_user_id = %d",
        $user_id
    ));
    
    if (!$organisation) {
        wp_send_json_error('Organisation not found');
        return;
    }
    
    // Get organisation statistics
    $stats = array(
        'total_members' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_members WHERE organisation_id = %d",
            $organisation->id
        )),
        'active_members' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_members 
             WHERE organisation_id = %d AND membership_status = 'active'",
            $organisation->id
        )),
        'pending_members' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_members 
             WHERE organisation_id = %d AND membership_status = 'pending'",
            $organisation->id
        )),
        'recent_imports' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_bulk_imports 
             WHERE organisation_id = %d AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)",
            $organisation->id
        ))
    );
    
    wp_send_json_success(array(
        'organisation' => $organisation,
        'stats' => $stats
    ));
}
add_action('wp_ajax_iipm_get_admin_dashboard_data', 'iipm_get_admin_dashboard_data');

/**
 * Quick organisation setup for existing users
 */
function iipm_quick_organisation_setup() {
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_portal_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $user_email = sanitize_email($_POST['user_email']);
    $org_id = intval($_POST['org_id']);
    
    if (!is_email($user_email) || !$org_id) {
        wp_send_json_error('Valid email and organisation ID required');
        return;
    }
    
    // Check if user exists
    $user = get_user_by('email', $user_email);
    if (!$user) {
        wp_send_json_error('User not found with that email address');
        return;
    }
    
    global $wpdb;
    
    // Update organisation with admin user ID
    $result = $wpdb->update(
        $wpdb->prefix . 'test_iipm_organisations',
        array('admin_user_id' => $user->ID, 'updated_at' => current_time('mysql')),
        array('id' => $org_id),
        array('%d', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to update organisation');
        return;
    }
    
    // Update user role
    $user->set_role('iipm_corporate_admin');
    
    // Update member record if exists
    $member = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d",
        $user->ID
    ));
    
    if ($member) {
        $wpdb->update(
            $wpdb->prefix . 'test_iipm_members',
            array(
                'organisation_id' => $org_id,
                'membership_status' => 'active',
                'membership_level' => 'member'
            ),
            array('user_id' => $user->ID),
            array('%d', '%s', '%s'),
            array('%d')
        );
    } else {
        // Create member record
        $wpdb->insert(
            $wpdb->prefix . 'test_iipm_members',
            array(
                'user_id' => $user->ID,
                'member_type' => 'organisation',
                'organisation_id' => $org_id,
                'membership_status' => 'active',
                'membership_level' => 'member',
                'gdpr_consent' => 1,
                'email_verified' => 1
            ),
            array('%d', '%s', '%d', '%s', '%s', '%d', '%d')
        );
    }
    
    // Log activity
    iipm_log_user_activity(
        get_current_user_id(),
        'quick_admin_setup',
        "User {$user_email} assigned as organisation admin for org ID {$org_id}"
    );
    
    wp_send_json_success(array(
        'user_id' => $user->ID,
        'user_name' => $user->display_name
    ));
}
add_action('wp_ajax_iipm_quick_organisation_setup', 'iipm_quick_organisation_setup');
?>
