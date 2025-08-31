<?php
/**
 * IIPM CPD Certification AJAX Handlers - Milestone 4
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX: Generate certificate for member
 */
function iimp_ajax_generate_certificate() {
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $user_id = intval($_POST['user_id']);
    $year = intval($_POST['year']) ?: date('Y');
    
    // Check permissions
    if (!current_user_can('administrator') && !current_user_can('manage_iipm_members')) {
        if ($user_id != get_current_user_id()) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
    }
    
    $result = iipm_generate_cpd_certificate($user_id, $year, 'manual');
    
    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result['message']);
    }
}
add_action('wp_ajax_iipm_generate_certificate', 'iimp_ajax_generate_certificate');

/**
 * AJAX: Download certificate
 */
function iipm_ajax_download_certificate() {
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $certificate_id = intval($_POST['certificate_id']);
    $user_id = intval($_POST['user_id']) ?: null;
    
    $result = iipm_download_certificate($certificate_id, $user_id);
    
    if ($result['success']) {
        // Return download URL instead of file contents
        $upload_dir = wp_upload_dir();
        $file_url = $upload_dir['baseurl'] . $result['certificate']->certificate_file_path;
        
        wp_send_json_success(array(
            'download_url' => $file_url,
            'certificate_number' => $result['certificate']->certificate_number
        ));
    } else {
        wp_send_json_error($result['error']);
    }
}
add_action('wp_ajax_iipm_download_certificate', 'iipm_ajax_download_certificate');

/**
 * AJAX: Get member certificates
 */
function iipm_ajax_get_member_certificates() {
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $user_id = intval($_POST['user_id']);
    
    // Check permissions
    if (!current_user_can('administrator') && !current_user_can('manage_iipm_members')) {
        if ($user_id != get_current_user_id()) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
    }
    
    $certificates = iipm_get_member_certificates($user_id);
    
    wp_send_json_success(array(
        'certificates' => $certificates
    ));
}
add_action('wp_ajax_iipm_get_member_certificates', 'iipm_ajax_get_member_certificates');

/**
 * AJAX: Check certificate eligibility
 */
function iipm_ajax_check_certificate_eligibility() {
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $user_id = intval($_POST['user_id']);
    $year = intval($_POST['year']) ?: date('Y');
    
    // Check permissions
    if (!current_user_can('administrator') && !current_user_can('manage_iipm_members')) {
        if ($user_id != get_current_user_id()) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
    }
    
    $eligibility = iipm_check_certification_eligibility($user_id, $year);
    
    wp_send_json_success($eligibility);
}
add_action('wp_ajax_iipm_check_certificate_eligibility', 'iipm_ajax_check_certificate_eligibility');

/**
 * AJAX: Auto-generate certificates for all eligible members
 */
function iipm_ajax_auto_generate_certificates() {
    if (!current_user_can('administrator') && !current_user_can('manage_iipm_members')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_admin_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $year = intval($_POST['year']) ?: date('Y');
    
    $result = iipm_auto_generate_certificates($year);
    
    wp_send_json_success($result);
}
add_action('wp_ajax_iipm_auto_generate_certificates', 'iipm_ajax_auto_generate_certificates');

/**
 * AJAX: Revoke certificate
 */
function iipm_ajax_revoke_certificate() {
    if (!current_user_can('administrator') && !current_user_can('manage_iipm_members')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_admin_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $certificate_id = intval($_POST['certificate_id']);
    $reason = sanitize_textarea_field($_POST['reason']);
    
    $result = iipm_revoke_certificate($certificate_id, $reason);
    
    if ($result) {
        wp_send_json_success(array('message' => 'Certificate revoked successfully'));
    } else {
        wp_send_json_error('Failed to revoke certificate');
    }
}
add_action('wp_ajax_iipm_revoke_certificate', 'iipm_ajax_revoke_certificate');

/**
 * Handle certificate download requests via URL
 */
function iipm_handle_certificate_download_request() {
    if (isset($_GET['action']) && $_GET['action'] === 'download_certificate' && isset($_GET['cert_id'])) {
        $certificate_id = intval($_GET['cert_id']);
        
        // Verify user access
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(home_url($_SERVER['REQUEST_URI'])));
            exit;
        }
        
        $result = iipm_download_certificate($certificate_id);
        
        if ($result['success']) {
            $file_path = $result['file_path'];
            $certificate = $result['certificate'];
            
            if (file_exists($file_path)) {
                // Determine file type
                $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
                $content_type = $file_extension === 'pdf' ? 'application/pdf' : 'text/html';
                
                // Set headers for download
                header('Content-Type: ' . $content_type);
                header('Content-Disposition: attachment; filename="IIPM_Certificate_' . $certificate->certificate_number . '.' . $file_extension . '"');
                header('Content-Length: ' . filesize($file_path));
                
                // Output file
                readfile($file_path);
                exit;
            }
        }
        
        // If download fails, redirect with error
        wp_redirect(add_query_arg('cert_error', '1', home_url('/cpd-reports/')));
        exit;
    }
}
add_action('init', 'iipm_handle_certificate_download_request');

/**
 * Display certificate download status on dashboard
 */
function iipm_get_certificate_status_display($user_id, $year = null) {
    if (!$year) {
        $year = date('Y');
    }
    
    global $wpdb;
    
    // Get existing certificate
    $certificate = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_cpd_certificates 
         WHERE user_id = %d AND cpd_year = %d AND certification_type = 'annual'",
        $user_id, $year
    ));
    
    // Check eligibility
    $eligibility = iipm_check_certification_eligibility($user_id, $year);
    
    $status = array(
        'certificate_exists' => !empty($certificate),
        'certificate_status' => $certificate ? $certificate->certificate_status : null,
        'eligible' => $eligibility['eligible'],
        'eligibility_reason' => $eligibility['reason'] ?? null,
        'certificate_number' => $certificate ? $certificate->certificate_number : null,
        'issue_date' => $certificate ? $certificate->issued_date : null,
        'download_count' => $certificate ? $certificate->download_count : 0,
        'year' => $year
    );
    
    return $status;
} 