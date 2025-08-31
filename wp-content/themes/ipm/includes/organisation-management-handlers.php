<?php
/**
 * Organisation Management AJAX Handlers
 */

// Save Organisation (Create/Update)
function iipm_save_organisation() {
    // Check permissions
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_org_management_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    global $wpdb;
    
    $org_id = isset($_POST['org_id']) ? intval($_POST['org_id']) : 0;
    $name = sanitize_text_field($_POST['name']);
    $contact_email = sanitize_email($_POST['contact_email']);
    $contact_phone = sanitize_text_field($_POST['contact_phone']);
    $billing_contact = sanitize_text_field($_POST['billing_contact']);
    $address_line1 = sanitize_text_field($_POST['address_line1']);
    $address_line2 = sanitize_text_field($_POST['address_line2']);
    $city = sanitize_text_field($_POST['city']);
    $county = sanitize_text_field($_POST['county']);
    $eircode = sanitize_text_field($_POST['eircode']);
    $admin_email = sanitize_email($_POST['admin_email']);
    $send_invitation = isset($_POST['send_invitation']);
    
    // Validate required fields
    if (empty($name) || empty($contact_email)) {
        wp_send_json_error('Organisation name and contact email are required');
        return;
    }
    
    if (!is_email($contact_email)) {
        wp_send_json_error('Invalid contact email address');
        return;
    }
    
    if ($admin_email && !is_email($admin_email)) {
        wp_send_json_error('Invalid admin email address');
        return;
    }
    
    // Check for duplicate organisation name (case-insensitive)
    $existing_org = $wpdb->get_row($wpdb->prepare(
        "SELECT id, name FROM {$wpdb->prefix}test_iipm_organisations 
         WHERE LOWER(name) = LOWER(%s) AND is_active = 1" . ($org_id ? " AND id != %d" : ""),
        $name,
        $org_id
    ));
    
    if ($existing_org) {
        wp_send_json_error('An organisation with this name already exists: "' . $existing_org->name . '". Please choose a different name.');
        return;
    }
    
    $data = array(
        'name' => $name,
        'contact_email' => $contact_email,
        'contact_phone' => $contact_phone,
        'billing_contact' => $billing_contact,
        'address_line1' => $address_line1,
        'address_line2' => $address_line2,
        'city' => $city,
        'county' => $county,
        'eircode' => $eircode,
        'updated_at' => current_time('mysql')
    );
    
    $formats = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');
    
    if ($org_id > 0) {
        // Update existing organisation
        $result = $wpdb->update(
            $wpdb->prefix . 'test_iipm_organisations',
            $data,
            array('id' => $org_id),
            $formats,
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to update organisation: ' . $wpdb->last_error);
            return;
        }
        
        $action = 'updated';
    } else {
        // Create new organisation
        $data['created_at'] = current_time('mysql');
        $formats[] = '%s';
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'test_iipm_organisations',
            $data,
            $formats
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to create organisation: ' . $wpdb->last_error);
            return;
        }
        
        $org_id = $wpdb->insert_id;
        $action = 'created';
    }
    
    // Send admin invitation if email provided and checkbox checked
    if ($admin_email && $send_invitation) {
        $invitation_result = iipm_send_organisation_admin_invitation($admin_email, $org_id);
        if (!$invitation_result['success']) {
            // Log the error but don't fail the organisation creation
            error_log('IIPM: Failed to send admin invitation: ' . $invitation_result['error']);
        }
    }
    
    // Log activity
    iipm_log_user_activity(
        get_current_user_id(),
        'organisation_' . $action,
        "Organisation '{$name}' {$action}"
    );
    
    wp_send_json_success(array(
        'org_id' => $org_id,
        'action' => $action,
        'invitation_sent' => $admin_email && $send_invitation
    ));
}
add_action('wp_ajax_iipm_save_organisation', 'iipm_save_organisation');

// Check Organisation Name for Duplicates
function iipm_check_organisation_name() {
    // Check permissions
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_portal_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    global $wpdb;
    
    $name = sanitize_text_field($_POST['name']);
    $org_id = isset($_POST['org_id']) ? intval($_POST['org_id']) : 0;
    
    if (empty($name)) {
        wp_send_json_success(array('exists' => false));
        return;
    }
    
    // Check for existing organisation with the same name (case-insensitive)
    $existing_org = $wpdb->get_row($wpdb->prepare(
        "SELECT id, name FROM {$wpdb->prefix}test_iipm_organisations 
         WHERE LOWER(name) = LOWER(%s) AND is_active = 1" . ($org_id ? " AND id != %d" : ""),
        $name,
        $org_id
    ));
    
    if ($existing_org) {
        wp_send_json_success(array(
            'exists' => true,
            'existing_name' => $existing_org->name,
            'existing_id' => $existing_org->id
        ));
    } else {
        wp_send_json_success(array('exists' => false));
    }
}
add_action('wp_ajax_iipm_check_organisation_name', 'iipm_check_organisation_name');

// Get Organisation Data
function iipm_get_organisation() {
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_portal_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $org_id = intval($_POST['org_id']);
    
    if (!$org_id) {
        wp_send_json_error('Invalid organisation ID');
        return;
    }
    
    global $wpdb;
    $organisation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_organisations WHERE id = %d",
        $org_id
    ));
    
    if (!$organisation) {
        wp_send_json_error('Organisation not found');
        return;
    }
    
    wp_send_json_success($organisation);
}
add_action('wp_ajax_iipm_get_organisation', 'iipm_get_organisation');

// Setup Organisation Administrator
function iipm_setup_organisation_admin() {
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_setup_admin_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $org_id = intval($_POST['org_id']);
    $admin_email = sanitize_email($_POST['admin_email']);
    $admin_name = sanitize_text_field($_POST['admin_name']);
    $send_invitation = isset($_POST['send_invitation']);
    
    if (!$org_id || !is_email($admin_email)) {
        wp_send_json_error('Organisation ID and valid admin email are required');
        return;
    }
    
    global $wpdb;
    
    // Check if organisation exists
    $organisation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_organisations WHERE id = %d",
        $org_id
    ));
    
    if (!$organisation) {
        wp_send_json_error('Organisation not found');
        return;
    }
    
    // Check if email already exists
    if (email_exists($admin_email)) {
        wp_send_json_error('Email address already exists in the system');
        return;
    }
    
    // Send invitation
    if ($send_invitation) {
        $invitation_result = iipm_send_organisation_admin_invitation($admin_email, $org_id, $admin_name);
        
        if (!$invitation_result['success']) {
            wp_send_json_error('Failed to send invitation: ' . $invitation_result['error']);
            return;
        }
    }
    
    // Log activity
    iipm_log_user_activity(
        get_current_user_id(),
        'admin_setup_initiated',
        "Admin setup initiated for organisation '{$organisation->name}' with email '{$admin_email}'"
    );
    
    wp_send_json_success(array(
        'invitation_sent' => $send_invitation,
        'admin_email' => $admin_email
    ));
}
add_action('wp_ajax_iipm_setup_organisation_admin', 'iipm_setup_organisation_admin');

// Get Organisation Members
function iipm_get_organisation_members() {
    if (!current_user_can('manage_iipm_members') && 
        !current_user_can('administrator') && 
        !current_user_can('manage_organisation_members')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_portal_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $org_id = intval($_POST['org_id']);
    
    if (!$org_id) {
        wp_send_json_error('Invalid organisation ID');
        return;
    }
    
    // Check if user is org admin and restrict to their organisation
    $current_user_id = get_current_user_id();
    $is_org_admin = iipm_is_organisation_admin($current_user_id);
    $is_site_admin = current_user_can('manage_iipm_members') || current_user_can('administrator');
    
    if ($is_org_admin && !$is_site_admin) {
        global $wpdb;
        $user_org = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}test_iipm_organisations WHERE admin_user_id = %d",
            $current_user_id
        ));
        
        if (!$user_org || $user_org->id != $org_id) {
            wp_send_json_error('You can only view members of your own organisation');
            return;
        }
    }
    
    global $wpdb;
    $members = $wpdb->get_results($wpdb->prepare(
        "SELECT u.ID as user_id, u.display_name, u.user_email, 
                m.membership_status, m.created_at, m.last_login
         FROM {$wpdb->prefix}test_iipm_members m
         JOIN {$wpdb->users} u ON m.user_id = u.ID
         WHERE m.organisation_id = %d
         ORDER BY u.display_name",
        $org_id
    ));
    
    wp_send_json_success($members);
}
add_action('wp_ajax_iipm_get_organisation_members', 'iipm_get_organisation_members');

// Deactivate Organisation
function iipm_deactivate_organisation() {
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_portal_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $org_id = intval($_POST['org_id']);
    
    if (!$org_id) {
        wp_send_json_error('Invalid organisation ID');
        return;
    }
    
    global $wpdb;
    
    // Get organisation name for logging
    $org_name = $wpdb->get_var($wpdb->prepare(
        "SELECT name FROM {$wpdb->prefix}test_iipm_organisations WHERE id = %d",
        $org_id
    ));
    
    // Deactivate organisation
    $result = $wpdb->update(
        $wpdb->prefix . 'test_iipm_organisations',
        array('is_active' => 0, 'updated_at' => current_time('mysql')),
        array('id' => $org_id),
        array('%d', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to deactivate organisation');
        return;
    }
    
    // Update member statuses to inactive
    $wpdb->update(
        $wpdb->prefix . 'test_iipm_members',
        array('membership_status' => 'inactive'),
        array('organisation_id' => $org_id),
        array('%s'),
        array('%d')
    );
    
    // Log activity
    iipm_log_user_activity(
        get_current_user_id(),
        'organisation_deactivated',
        "Organisation '{$org_name}' deactivated"
    );
    
    wp_send_json_success();
}
add_action('wp_ajax_iipm_deactivate_organisation', 'iipm_deactivate_organisation');

// NEW: Get Organisation Delete Information
function iipm_get_organisation_delete_info() {
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_portal_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $org_id = intval($_POST['org_id']);
    
    if (!$org_id) {
        wp_send_json_error('Invalid organisation ID');
        return;
    }
    
    global $wpdb;
    
    // Get organisation details
    $organisation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_organisations WHERE id = %d",
        $org_id
    ));
    
    if (!$organisation) {
        wp_send_json_error('Organisation not found');
        return;
    }
    
    // Get member count and details
    $members = $wpdb->get_results($wpdb->prepare(
        "SELECT u.ID as user_id, u.display_name, u.user_email, m.membership_status
         FROM {$wpdb->prefix}test_iipm_members m
         JOIN {$wpdb->users} u ON m.user_id = u.ID
         WHERE m.organisation_id = %d
         ORDER BY u.display_name",
        $org_id
    ));
    
    // Get import count
    $import_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_bulk_imports WHERE organisation_id = %d",
        $org_id
    ));
    
    // Get invitation count
    $invitation_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_invitations WHERE organisation_id = %d",
        $org_id
    ));
    
    wp_send_json_success(array(
        'organisation' => $organisation,
        'members' => $members,
        'member_count' => count($members),
        'import_count' => $import_count,
        'invitation_count' => $invitation_count
    ));
}
add_action('wp_ajax_iipm_get_organisation_delete_info', 'iipm_get_organisation_delete_info');

// NEW: Delete Organisation
function iipm_delete_organisation() {
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_portal_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $org_id = intval($_POST['org_id']);
    
    if (!$org_id) {
        wp_send_json_error('Invalid organisation ID');
        return;
    }
    
    global $wpdb;
    
    // Get organisation name for logging
    $org_name = $wpdb->get_var($wpdb->prepare(
        "SELECT name FROM {$wpdb->prefix}test_iipm_organisations WHERE id = %d",
        $org_id
    ));
    
    if (!$org_name) {
        wp_send_json_error('Organisation not found');
        return;
    }
    
    // Start transaction
    $wpdb->query('START TRANSACTION');
    
    try {
        // Get all members of this organisation
        $member_user_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}test_iipm_members WHERE organisation_id = %d",
            $org_id
        ));
        
        // Delete member profiles
        if (!empty($member_user_ids)) {
            $placeholders = implode(',', array_fill(0, count($member_user_ids), '%d'));
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}test_iipm_member_profiles WHERE user_id IN ($placeholders)",
                ...$member_user_ids
            ));
        }
        
        // Delete members
        $wpdb->delete(
            $wpdb->prefix . 'test_iipm_members',
            array('organisation_id' => $org_id),
            array('%d')
        );
        
        // Delete WordPress users (optional - you might want to keep them)
        if (!empty($member_user_ids)) {
            foreach ($member_user_ids as $user_id) {
                // Only delete users that are not administrators
                $user = get_user_by('id', $user_id);
                if ($user && !user_can($user, 'administrator')) {
                    wp_delete_user($user_id);
                }
            }
        }
        
        // Delete bulk imports
        $wpdb->delete(
            $wpdb->prefix . 'test_iipm_bulk_imports',
            array('organisation_id' => $org_id),
            array('%d')
        );
        
        // Delete invitations
        $wpdb->delete(
            $wpdb->prefix . 'test_iipm_invitations',
            array('organisation_id' => $org_id),
            array('%d')
        );
        
        // Delete user activity logs for organisation members
        if (!empty($member_user_ids)) {
            $placeholders = implode(',', array_fill(0, count($member_user_ids), '%d'));
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}test_iipm_user_activity WHERE user_id IN ($placeholders)",
                ...$member_user_ids
            ));
        }
        
        // Finally, delete the organisation
        $result = $wpdb->delete(
            $wpdb->prefix . 'test_iipm_organisations',
            array('id' => $org_id),
            array('%d')
        );
        
        if ($result === false) {
            throw new Exception('Failed to delete organisation');
        }
        
        // Commit transaction
        $wpdb->query('COMMIT');
        
        // Log activity
        iipm_log_user_activity(
            get_current_user_id(),
            'organisation_deleted',
            "Organisation '{$org_name}' permanently deleted with all associated data"
        );
        
        wp_send_json_success(array(
            'message' => "Organisation '{$org_name}' has been permanently deleted.",
            'deleted_members' => count($member_user_ids)
        ));
        
    } catch (Exception $e) {
        // Rollback transaction
        $wpdb->query('ROLLBACK');
        
        error_log('IIPM: Error deleting organisation: ' . $e->getMessage());
        wp_send_json_error('Failed to delete organisation: ' . $e->getMessage());
    }
}
add_action('wp_ajax_iipm_delete_organisation', 'iipm_delete_organisation');

// Export Organisations
function iipm_export_organisations() {
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        wp_die('Insufficient permissions');
        return;
    }
    
    if (!wp_verify_nonce($_GET['nonce'], 'iipm_portal_nonce')) {
        wp_die('Security check failed');
        return;
    }
    
    global $wpdb;
    $organisations = $wpdb->get_results("
        SELECT o.*, 
               u.display_name as admin_name,
               u.user_email as admin_email,
               COUNT(m.id) as member_count
        FROM {$wpdb->prefix}test_iipm_organisations o
        LEFT JOIN {$wpdb->users} u ON o.admin_user_id = u.ID
        LEFT JOIN {$wpdb->prefix}test_iipm_members m ON o.id = m.organisation_id
        WHERE o.is_active = 1
        GROUP BY o.id
        ORDER BY o.name
    ");
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="iipm_organisations_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, array(
        'ID', 'Name', 'Contact Email', 'Contact Phone', 'City', 'County',
        'Admin Name', 'Admin Email', 'Member Count', 'Created Date'
    ));
    
    // CSV data
    foreach ($organisations as $org) {
        fputcsv($output, array(
            $org->id,
            $org->name,
            $org->contact_email,
            $org->contact_phone,
            $org->city,
            $org->county,
            $org->admin_name,
            $org->admin_email,
            $org->member_count,
            $org->created_at
        ));
    }
    
    fclose($output);
    exit;
}
add_action('wp_ajax_iipm_export_organisations', 'iipm_export_organisations');

/**
 * NEW: Clear Organisation Invitations (for testing)
 */
function iipm_clear_organisation_invitations() {
    // Check permissions - Allow both site admins and org admins
    if (!current_user_can('manage_iipm_members') && 
        !current_user_can('administrator') && 
        !current_user_can('manage_organisation_members')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_portal_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $org_id = intval($_POST['org_id']);
    
    if (!$org_id) {
        wp_send_json_error('Invalid organisation ID');
        return;
    }
    
    // Check if user is org admin and restrict to their organisation
    $current_user_id = get_current_user_id();
    $is_org_admin = iipm_is_organisation_admin($current_user_id);
    $is_site_admin = current_user_can('manage_iipm_members') || current_user_can('administrator');
    
    if ($is_org_admin && !$is_site_admin) {
        global $wpdb;
        $user_org = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}test_iipm_organisations WHERE admin_user_id = %d",
            $current_user_id
        ));
        
        if (!$user_org || $user_org->id != $org_id) {
            wp_send_json_error('You can only clear invitations for your own organisation');
            return;
        }
    }
    
    global $wpdb;
    
    // Get organisation name for logging
    $org_name = $wpdb->get_var($wpdb->prepare(
        "SELECT name FROM {$wpdb->prefix}test_iipm_organisations WHERE id = %d",
        $org_id
    ));
    
    if (!$org_name) {
        wp_send_json_error('Organisation not found');
        return;
    }
    
    // Get count of invitations to be deleted
    $invitation_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_invitations WHERE organisation_id = %d",
        $org_id
    ));
    
    // Delete all invitations for this organisation
    $result = $wpdb->delete(
        $wpdb->prefix . 'test_iipm_invitations',
        array('organisation_id' => $org_id),
        array('%d')
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to clear invitations: ' . $wpdb->last_error);
        return;
    }
    
    // Log activity
    iipm_log_user_activity(
        get_current_user_id(),
        'invitations_cleared',
        "Cleared {$invitation_count} invitations for organisation '{$org_name}'"
    );
    
    wp_send_json_success(array(
        'message' => "Cleared {$invitation_count} invitations for {$org_name}",
        'cleared_count' => $invitation_count
    ));
}
add_action('wp_ajax_iipm_clear_organisation_invitations', 'iipm_clear_organisation_invitations');

/**
 * NEW: Get Organisation Invitations
 */
function iipm_get_organisation_invitations() {
    // Check permissions
    if (!current_user_can('manage_iipm_members') && 
        !current_user_can('administrator') && 
        !current_user_can('manage_organisation_members')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_portal_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $org_id = intval($_POST['org_id']);
    
    if (!$org_id) {
        wp_send_json_error('Invalid organisation ID');
        return;
    }
    
    // Check if user is org admin and restrict to their organisation
    $current_user_id = get_current_user_id();
    $is_org_admin = iipm_is_organisation_admin($current_user_id);
    $is_site_admin = current_user_can('manage_iipm_members') || current_user_can('administrator');
    
    if ($is_org_admin && !$is_site_admin) {
        global $wpdb;
        $user_org = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}test_iipm_organisations WHERE admin_user_id = %d",
            $current_user_id
        ));
        
        if (!$user_org || $user_org->id != $org_id) {
            wp_send_json_error('You can only view invitations for your own organisation');
            return;
        }
    }
    
    global $wpdb;
    
    // Get all invitations for this organisation
    $invitations = $wpdb->get_results($wpdb->prepare(
        "SELECT i.*, u.display_name as invited_by_name
         FROM {$wpdb->prefix}test_iipm_invitations i
         LEFT JOIN {$wpdb->users} u ON i.invited_by = u.ID
         WHERE i.organisation_id = %d
         ORDER BY i.created_at DESC",
        $org_id
    ));
    
    // Separate by status
    $pending = array();
    $used = array();
    $expired = array();
    
    foreach ($invitations as $invitation) {
        if ($invitation->used_at) {
            $used[] = $invitation;
        } elseif (strtotime($invitation->expires_at) < time()) {
            $expired[] = $invitation;
        } else {
            $pending[] = $invitation;
        }
    }
    
    wp_send_json_success(array(
        'pending' => $pending,
        'used' => $used,
        'expired' => $expired,
        'total' => count($invitations),
        'pending_count' => count($pending),
        'used_count' => count($used),
        'expired_count' => count($expired)
    ));
}
add_action('wp_ajax_iipm_get_organisation_invitations', 'iipm_get_organisation_invitations');

/**
 * Enhanced Organisation Admin Invitation System
 */
function iipm_send_organisation_admin_invitation($email, $organisation_id, $admin_name = '') {
    global $wpdb;
    
    try {
        // Get organisation details
        $organisation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}test_iipm_organisations WHERE id = %d",
            $organisation_id
        ));
        
        if (!$organisation) {
            return array('success' => false, 'error' => 'Organisation not found');
        }
        
        // Generate unique token
        $token = wp_generate_password(32, false);
        $expires_at = date('Y-m-d H:i:s', strtotime('+14 days')); // 14 days for admin invitations
        
        // Insert invitation record
        $result = $wpdb->insert(
            $wpdb->prefix . 'test_iipm_invitations',
            array(
                'email' => $email,
                'token' => $token,
                'invitation_type' => 'organisation_admin',
                'invited_by' => get_current_user_id(),
                'organisation_id' => $organisation_id,
                'expires_at' => $expires_at
            ),
            array('%s', '%s', '%s', '%d', '%d', '%s')
        );
        
        if ($result === false) {
            error_log('IIPM: Failed to insert admin invitation record: ' . $wpdb->last_error);
            return array('success' => false, 'error' => 'Database error: ' . $wpdb->last_error);
        }
        
        // Send email
        $registration_url = home_url('/member-registration/?token=' . $token);
        $subject = 'IIPM Organisation Administrator Invitation - ' . $organisation->name;
        
        $greeting = $admin_name ? "Dear {$admin_name}," : "Dear Colleague,";
        
        $message = "
{$greeting}

You have been invited to become the administrator for {$organisation->name} on the Irish Institute of Pensions Management (IIPM) platform.

As an organisation administrator, you will be able to:
• Manage your organisation's member accounts
• Bulk import employees as IIPM members
• View organisation-wide reports and statistics
• Process group payments and invoicing

To accept this invitation and set up your administrator account, please click the link below:
{$registration_url}

This invitation will expire in 14 days.

Organisation Details:
- Name: {$organisation->name}
- Contact: {$organisation->contact_email}
- Location: {$organisation->city}, {$organisation->county}

If you have any questions about this invitation or the IIPM platform, please contact us at info@iipm.ie or +353 (0)1 613 0874.

Best regards,
IIPM Administration Team

---
Irish Institute of Pensions Management
www.iipm.ie
        ";
        
        // Add headers for better email delivery
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: IIPM Portal <' . (defined('SMTP_FROM') ? SMTP_FROM : get_option('admin_email')) . '>'
        );
        
        // Try to send email
        $email_sent = wp_mail($email, $subject, $message, $headers);
        
        if (!$email_sent) {
            error_log('IIPM: Failed to send admin invitation email to: ' . $email);
            
            // For debugging in local environment
            if (defined('WP_DEBUG') && WP_DEBUG) {
                return array('success' => true, 'token' => $token, 'note' => 'Email logged locally instead of sending');
            }
            
            return array('success' => false, 'error' => 'Failed to send email. Please check email configuration.');
        }
        
        return array('success' => true, 'token' => $token);
        
    } catch (Exception $e) {
        error_log('IIPM: Exception in iipm_send_organisation_admin_invitation: ' . $e->getMessage());
        return array('success' => false, 'error' => 'System error: ' . $e->getMessage());
    }
}

/**
 * Enhanced Registration Processing for Organisation Admins
 */
function iipm_process_organisation_admin_registration($data, $invitation) {
    global $wpdb;
    
    $email = sanitize_email($data['email']);
    $first_name = sanitize_text_field($data['first_name']);
    $last_name = sanitize_text_field($data['last_name']);
    $password = $data['password'];
    $organisation_id = $invitation->organisation_id;
    
    // Create user account
    $user_id = wp_create_user($email, $password, $email);
    
    if (is_wp_error($user_id)) {
        return array('success' => false, 'error' => $user_id->get_error_message());
    }
    
    // Update user profile
    wp_update_user(array(
        'ID' => $user_id,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'display_name' => $first_name . ' ' . $last_name,
    ));
    
    // Set user role to corporate admin
    $user = new WP_User($user_id);
    $user->set_role('iipm_corporate_admin');
    
    // Update organisation with admin user ID
    $wpdb->update(
        $wpdb->prefix . 'test_iipm_organisations',
        array('admin_user_id' => $user_id, 'updated_at' => current_time('mysql')),
        array('id' => $organisation_id),
        array('%d', '%s'),
        array('%d')
    );
    
    // Create member record
    $wpdb->insert(
        $wpdb->prefix . 'test_iipm_members',
        array(
            'user_id' => $user_id,
            'member_type' => 'organisation',
            'organisation_id' => $organisation_id,
            'membership_status' => 'active', // Admins are automatically active
            'membership_level' => 'member',
            'gdpr_consent' => 1,
            'marketing_consent' => isset($data['marketing_consent']) ? 1 : 0,
            'email_verified' => 1, // Auto-verify admin emails
            'profile_completed' => 0
        ),
        array('%d', '%s', '%d', '%s', '%s', '%d', '%d', '%d', '%d')
    );
    
    // Create profile record
    $wpdb->insert(
        $wpdb->prefix . 'test_iipm_member_profiles',
        array(
            'user_id' => $user_id,
            'user_phone' => sanitize_text_field($data['user_phone'] ?? ''),
            'email_address' => sanitize_email($data['work_email'] ?? ''),
            'user_mobile' => sanitize_text_field($data['user_mobile'] ?? ''),
            'employer_name' => sanitize_text_field($data['employer_name'] ?? ''),
            'is_admin' => 1,
            'dateOfUpdatePers' => current_time('mysql'),
            'dateOfUpdateGen' => current_time('mysql'),
            'employerDetailsUpdated' => current_time('mysql'),
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );
    
    // Mark invitation as used
    $wpdb->update(
        $wpdb->prefix . 'test_iipm_invitations',
        array('used_at' => current_time('mysql')),
        array('id' => $invitation->id),
        array('%s'),
        array('%d')
    );
    
    // Log activity
    iipm_log_user_activity($user_id, 'admin_registration', 'Organisation administrator registered successfully');
    
    // Send welcome email
    iipm_send_admin_welcome_email($user_id, $email, $first_name, $organisation_id);
    
    return array('success' => true, 'user_id' => $user_id, 'role' => 'corporate_admin');
}

/**
 * Send Welcome Email to New Organisation Admin
 */
function iipm_send_admin_welcome_email($user_id, $email, $first_name, $organisation_id) {
    global $wpdb;
    
    $organisation = $wpdb->get_row($wpdb->prepare(
        "SELECT name FROM {$wpdb->prefix}test_iipm_organisations WHERE id = %d",
        $organisation_id
    ));
    
    $portal_url = home_url('/member-portal/');
    $bulk_import_url = home_url('/bulk-import/');
    $subject = 'Welcome to IIPM - Organisation Administrator Access';
    
    $message = "
Dear {$first_name},

Welcome to the Irish Institute of Pensions Management! Your administrator account for {$organisation->name} has been successfully created.

As an organisation administrator, you now have access to:

🏢 Organisation Dashboard: {$portal_url}
📊 Bulk Member Import: {$bulk_import_url}
👥 Member Management Tools
📈 Reporting and Analytics
💳 Payment and Billing Management

Getting Started:
1. Log in to your dashboard: {$portal_url}
2. Complete your profile information
3. Review your organisation settings
4. Start importing your team members

Need help getting started? Our support team is here to assist:
📧 Email: info@iipm.ie
📞 Phone: +353 (0)1 613 0874

Best regards,
IIPM Administration Team

---
Irish Institute of Pensions Management
www.iipm.ie
    ";
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: IIPM Portal <' . (defined('SMTP_FROM') ? SMTP_FROM : get_option('admin_email')) . '>'
    );
    
    wp_mail($email, $subject, $message, $headers);
}

/**
 * UPDATED: Enhanced bulk import handler with organisation restrictions
 */
function iipm_handle_bulk_import_enhanced() {
    // Check permissions - Updated to include corporate admins
    if (!current_user_can('manage_iipm_members') && 
        !current_user_can('administrator') && 
        !current_user_can('bulk_import_members') &&
        !current_user_can('manage_organisation_members')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_bulk_import_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    // Validate organisation ID
    $organisation_id = intval($_POST['organisation_id']);
    if (!$organisation_id) {
        wp_send_json_error('Organisation ID is required');
        return;
    }
    
    // Check if user is org admin and restrict to their organisation
    $current_user_id = get_current_user_id();
    $is_org_admin = iipm_is_organisation_admin($current_user_id);
    $is_site_admin = current_user_can('manage_iipm_members') || current_user_can('administrator');
    
    if ($is_org_admin && !$is_site_admin) {
        global $wpdb;
        $user_org = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}test_iipm_organisations WHERE admin_user_id = %d",
            $current_user_id
        ));
        
        if (!$user_org || $user_org->id != $organisation_id) {
            wp_send_json_error('You can only import members to your own organisation');
            return;
        }
    }
    
    // Check file upload
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error('File upload failed');
        return;
    }
    
    $file = $_FILES['csv_file'];
    
    // Validate file type
    if (!in_array($file['type'], array('text/csv', 'application/csv', 'text/plain'))) {
        wp_send_json_error('Invalid file type. Please upload a CSV file.');
        return;
    }
    
    // Validate file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        wp_send_json_error('File too large. Maximum size is 5MB.');
        return;
    }
    
    // Move uploaded file to temp location
    $upload_dir = wp_upload_dir();
    $temp_file = $upload_dir['path'] . '/' . uniqid('bulk_import_') . '.csv';
    
    if (!move_uploaded_file($file['tmp_name'], $temp_file)) {
        wp_send_json_error('Failed to process uploaded file');
        return;
    }
    
    // Process import options
    $options = array(
        'send_invitations' => isset($_POST['send_invitations']),
        'skip_existing' => isset($_POST['skip_existing'])
    );
    
    // Log import start
    global $wpdb;
    $import_id = $wpdb->insert(
        $wpdb->prefix . 'test_iipm_bulk_imports',
        array(
            'filename' => $file['name'],
            'total_records' => 0,
            'import_type' => 'members',
            'imported_by' => get_current_user_id(),
            'organisation_id' => $organisation_id,
            'status' => 'processing'
        ),
        array('%s', '%d', '%s', '%d', '%d', '%s')
    );
    
    if (!$import_id) {
        unlink($temp_file);
        wp_send_json_error('Failed to create import record');
        return;
    }
    
    // Process the import
    $result = iipm_process_bulk_import($temp_file, $organisation_id, $options);
    
    // Update import record
    if ($result['success']) {
        $data = $result['data'];
        $wpdb->update(
            $wpdb->prefix . 'test_iipm_bulk_imports',
            array(
                'total_records' => $data['total'],
                'successful_imports' => $data['successful'],
                'failed_imports' => $data['failed'],
                'status' => 'completed',
                'error_log' => !empty($data['errors']) ? json_encode($data['errors']) : null
            ),
            array('id' => $import_id),
            array('%d', '%d', '%d', '%s', '%s'),
            array('%d')
        );
        
        // Log activity
        iipm_log_user_activity(
            get_current_user_id(),
            'bulk_import',
            "Bulk import completed for organisation ID {$organisation_id}: {$data['successful']} successful, {$data['failed']} failed"
        );
        
        wp_send_json_success($data);
    } else {
        $wpdb->update(
            $wpdb->prefix . 'test_iipm_bulk_imports',
            array(
                'status' => 'failed',
                'error_log' => $result['error']
            ),
            array('id' => $import_id),
            array('%s', '%s'),
            array('%d')
        );
        
        wp_send_json_error($result['error']);
    }
    
    // Clean up temp file
    unlink($temp_file);
}

// Replace the existing handler
remove_action('wp_ajax_iipm_bulk_import', 'iipm_handle_bulk_import');
add_action('wp_ajax_iipm_bulk_import', 'iipm_handle_bulk_import_enhanced');
?>
