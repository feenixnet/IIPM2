<?php
/**
 * Enhanced Member Registration System
 * Handles different registration types including organisation admins
 */

/**
 * Enhanced registration processing that handles different invitation types
 */
function iipm_process_enhanced_member_registration($data, $token = null) {
    global $wpdb;
    
    $invitation = null;
    if ($token) {
        $invitation = iipm_validate_invitation_token($token);
        if (!$invitation) {
            return array('success' => false, 'error' => 'Invalid or expired invitation');
        }
    }
    
    // Determine registration type and process accordingly
    if ($invitation && $invitation->invitation_type === 'organisation_admin') {
        return iipm_process_organisation_admin_registration($data, $invitation);
    } else {
        return iipm_process_member_registration($data, $token);
    }
}

/**
 * Enhanced invitation validation with type checking
 */
function iipm_validate_enhanced_invitation_token($token) {
    global $wpdb;
    
    $invitation = $wpdb->get_row($wpdb->prepare(
        "SELECT i.*, o.name as organisation_name 
         FROM {$wpdb->prefix}test_iipm_invitations i
         LEFT JOIN {$wpdb->prefix}test_iipm_organisations o ON i.organisation_id = o.id
         WHERE i.token = %s AND i.used_at IS NULL AND i.expires_at > NOW()",
        $token
    ));
    
    return $invitation;
}

/**
 * Get invitation context for registration form
 */
function iipm_get_invitation_context($invitation) {
    if (!$invitation) {
        return array(
            'type' => 'public',
            'title' => 'IIPM Member Registration',
            'subtitle' => 'Join the Irish Institute of Pensions Management'
        );
    }
    
    switch ($invitation->invitation_type) {
        case 'organisation_admin':
            return array(
                'type' => 'admin',
                'title' => 'Organisation Administrator Setup',
                'subtitle' => "Complete your administrator account for {$invitation->organisation_name}",
                'organisation' => $invitation->organisation_name,
                'special_instructions' => 'You are being set up as an organisation administrator with special privileges.'
            );
            
        case 'bulk':
            return array(
                'type' => 'bulk',
                'title' => 'Organisation Member Registration',
                'subtitle' => "Join {$invitation->organisation_name} on IIPM",
                'organisation' => $invitation->organisation_name,
                'special_instructions' => 'You have been invited by your organisation to join IIPM.'
            );
            
        default:
            return array(
                'type' => 'individual',
                'title' => 'IIPM Member Registration',
                'subtitle' => 'Complete your IIPM membership registration'
            );
    }
}

/**
 * Enhanced role assignment based on invitation type
 */
function iipm_assign_user_role($user_id, $invitation = null, $organisation_id = null) {
    $user = new WP_User($user_id);
    
    if ($invitation && $invitation->invitation_type === 'organisation_admin') {
        $user->set_role('iipm_corporate_admin');
        return 'iipm_corporate_admin';
    } elseif ($organisation_id) {
        $user->set_role('iipm_member');
        return 'iipm_member';
    } else {
        $user->set_role('iipm_member');
        return 'iipm_member';
    }
}

/**
 * Enhanced membership status assignment
 */
function iipm_get_initial_membership_status($invitation = null) {
    if ($invitation && $invitation->invitation_type === 'organisation_admin') {
        return 'active'; // Admins are automatically active
    }
    
    return 'pending'; // Regular members start as pending
}

/**
 * Enhanced membership level assignment
 */
function iipm_get_initial_membership_level($invitation = null) {
    if ($invitation && $invitation->invitation_type === 'organisation_admin') {
        return 'member'; // Admins get full member status
    }
    
    return 'free'; // Regular members start as free
}

/**
 * Check if user should have admin privileges for organisation
 */
function iipm_should_be_organisation_admin($email, $organisation_id, $invitation = null) {
    // If invitation type is organisation_admin, definitely yes
    if ($invitation && $invitation->invitation_type === 'organisation_admin') {
        return true;
    }
    
    // Check if email matches organisation contact email
    global $wpdb;
    $org = $wpdb->get_row($wpdb->prepare(
        "SELECT contact_email FROM {$wpdb->prefix}test_iipm_organisations WHERE id = %d",
        $organisation_id
    ));
    
    return $org && $org->contact_email === $email;
}

/**
 * Enhanced welcome email based on user type
 */
function iipm_send_enhanced_welcome_email($user_id, $email, $first_name, $role, $organisation_id = null) {
    if ($role === 'iipm_corporate_admin' && $organisation_id) {
        iipm_send_admin_welcome_email($user_id, $email, $first_name, $organisation_id);
    } else {
        iipm_send_welcome_email($user_id, $email, $first_name);
    }
}

/**
 * Get registration success message based on user type
 */
function iipm_get_registration_success_message($role, $organisation_name = null) {
    switch ($role) {
        case 'iipm_corporate_admin':
            return array(
                'title' => '🎉 Administrator Account Created!',
                'subtitle' => "You're now the administrator for {$organisation_name}",
                'next_steps' => array(
                    'Access your admin dashboard',
                    'Set up your organisation profile',
                    'Start importing team members',
                    'Configure billing and payments'
                )
            );
            
        default:
            return array(
                'title' => '🎉 Welcome to IIPM!',
                'subtitle' => 'Your membership account has been created successfully',
                'next_steps' => array(
                    'Verify your email address',
                    'Complete your profile',
                    'Explore member resources',
                    'Register for events'
                )
            );
    }
}
?>
