<?php
/**
 * IIPM CPD Certification Functions - Milestone 4
 * 
 * Handles certificate generation, tracking, and management
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate certificate number
 */
function iipm_generate_certificate_number($user_id, $year, $type = 'annual') {
    $prefix = 'IIPM-' . strtoupper($type);
    $suffix = sprintf('%04d-%04d-%05d', $year, $user_id, rand(10000, 99999));
    return $prefix . '-' . $suffix;
}

/**
 * Check if member meets certification requirements
 */
function iipm_check_certification_eligibility($user_id, $year = null) {
    if (!$year) {
        $year = date('Y');
    }
    
    global $wpdb;
    
    // Get member's CPD summary for the year
    $member_data = iipm_get_individual_member_report($user_id, $year);
    
    if (!$member_data) {
        return array(
            'eligible' => false,
            'reason' => 'Member data not found'
        );
    }
    
    $total_points = $member_data['summary']['total_points'];
    $required_points = $member_data['summary']['required_points'];
    $categories = $member_data['summary']['categories'];
    
    // Check overall points requirement
    if ($total_points < $required_points) {
        return array(
            'eligible' => false,
            'reason' => 'Insufficient total CPD points',
            'points_earned' => $total_points,
            'points_required' => $required_points
        );
    }
    
    // Check mandatory category requirements
    $mandatory_categories = array('Pensions', 'Savings & Investment', 'Ethics', 'Life Assurance');
    $failed_categories = array();
    
    foreach ($mandatory_categories as $category) {
        if (!isset($categories[$category]) || $categories[$category]['points'] < 1.0) {
            $failed_categories[] = $category;
        }
    }
    
    if (!empty($failed_categories)) {
        return array(
            'eligible' => false,
            'reason' => 'Missing required category points',
            'failed_categories' => $failed_categories
        );
    }
    
    return array(
        'eligible' => true,
        'total_points' => $total_points,
        'required_points' => $required_points,
        'categories' => $categories
    );
}

/**
 * Generate CPD certificate
 */
function iipm_generate_cpd_certificate($user_id, $year = null, $issue_method = 'automatic') {
    if (!$year) {
        $year = date('Y');
    }
    
    global $wpdb;
    
    // Check if certificate already exists
    $existing_cert = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_cpd_certificates 
         WHERE user_id = %d AND cpd_year = %d AND certification_type = 'annual'",
        $user_id, $year
    ));
    
    if ($existing_cert && $existing_cert->certificate_status === 'issued') {
        return array(
            'success' => false,
            'message' => 'Certificate already exists for this year',
            'certificate_id' => $existing_cert->id
        );
    }
    
    // Check eligibility
    $eligibility = iipm_check_certification_eligibility($user_id, $year);
    
    if (!$eligibility['eligible']) {
        return array(
            'success' => false,
            'message' => 'Member not eligible for certification',
            'reason' => $eligibility['reason'],
            'details' => $eligibility
        );
    }
    
    // Get user details
    $user = get_user_by('id', $user_id);
    if (!$user) {
        return array(
            'success' => false,
            'message' => 'User not found'
        );
    }
    
    // Generate certificate number
    $certificate_number = iipm_generate_certificate_number($user_id, $year, 'annual');
    
    // Determine compliance status
    $compliance_status = 'compliant';
    if (isset($eligibility['failed_categories']) && !empty($eligibility['failed_categories'])) {
        $compliance_status = 'partial';
    }
    
    // Insert or update certificate record
    $certificate_data = array(
        'user_id' => $user_id,
        'cpd_year' => $year,
        'certificate_number' => $certificate_number,
        'certification_type' => 'annual',
        'total_cpd_points' => $eligibility['total_points'],
        'required_points' => $eligibility['required_points'],
        'compliance_status' => $compliance_status,
        'certificate_status' => 'issued',
        'issue_method' => $issue_method,
        'issued_date' => current_time('mysql'),
        'issued_by' => get_current_user_id(),
        'expiry_date' => ($year + 1) . '-12-31'
    );
    
    if ($existing_cert) {
        $result = $wpdb->update(
            $wpdb->prefix . 'test_iipm_cpd_certificates',
            $certificate_data,
            array('id' => $existing_cert->id),
            array('%d', '%d', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%d', '%s'),
            array('%d')
        );
        $certificate_id = $existing_cert->id;
    } else {
        $result = $wpdb->insert(
            $wpdb->prefix . 'test_iipm_cpd_certificates',
            $certificate_data,
            array('%d', '%d', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        $certificate_id = $wpdb->insert_id;
    }
    
    if ($result === false) {
        return array(
            'success' => false,
            'message' => 'Failed to create certificate record'
        );
    }
    
    // Generate PDF certificate
    $pdf_result = iipm_generate_certificate_html($certificate_id);
    
    if ($pdf_result['success']) {
        // Update certificate record with PDF path
        $wpdb->update(
            $wpdb->prefix . 'test_iipm_cpd_certificates',
            array(
                'certificate_file_path' => $pdf_result['file_path'],
                'pdf_generated' => 1
            ),
            array('id' => $certificate_id),
            array('%s', '%d'),
            array('%d')
        );
    }
    
    // Send notification email
    iipm_send_certificate_notification($user_id, $certificate_id);
    
    // Log activity
    iipm_log_user_activity(
        $user_id,
        'certificate_issued',
        "CPD Certificate issued for {$year} - Certificate Number: {$certificate_number}"
    );
    
    return array(
        'success' => true,
        'message' => 'Certificate generated successfully',
        'certificate_id' => $certificate_id,
        'certificate_number' => $certificate_number,
        'pdf_generated' => $pdf_result['success'],
        'pdf_path' => $pdf_result['success'] ? $pdf_result['file_path'] : null
    );
}

/**
 * Generate HTML certificate
 */
function iipm_generate_certificate_html($certificate_id) {
    global $wpdb;
    
    $certificate = $wpdb->get_row($wpdb->prepare(
        "SELECT c.*, u.display_name, u.user_email, 
                COALESCE(um1.meta_value, '') as first_name,
                COALESCE(um2.meta_value, '') as last_name,
                m.membership_level
         FROM {$wpdb->prefix}test_iipm_cpd_certificates c
         JOIN {$wpdb->users} u ON c.user_id = u.ID
         LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
         LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
         LEFT JOIN {$wpdb->prefix}test_iipm_members m ON c.user_id = m.user_id
         WHERE c.id = %d",
        $certificate_id
    ));
    
    if (!$certificate) {
        return array('success' => false, 'error' => 'Certificate not found');
    }
    
    $member_name = trim($certificate->first_name . ' ' . $certificate->last_name);
    if (empty($member_name)) {
        $member_name = $certificate->display_name;
    }
    
    // Create certificates directory
    $upload_dir = wp_upload_dir();
    $cert_dir = $upload_dir['basedir'] . '/cpd-certificates/';
    if (!file_exists($cert_dir)) {
        wp_mkdir_p($cert_dir);
    }
    
    // Generate HTML content
    $html = iipm_get_certificate_html_content($certificate, $member_name, true);
    
    // Generate filename
    $filename = 'certificate_' . $certificate->certificate_number . '.html';
    $file_path = $cert_dir . $filename;
    
    // Save HTML file
    file_put_contents($file_path, $html);
    
    return array(
        'success' => true,
        'file_path' => '/cpd-certificates/' . $filename,
        'full_path' => $file_path,
        'url' => $upload_dir['baseurl'] . '/cpd-certificates/' . $filename
    );
}

/**
 * Get certificate HTML content
 */
function iipm_get_certificate_html_content($certificate, $member_name, $standalone = false) {
    $issue_date = date('F j, Y', strtotime($certificate->issued_date));
    $expiry_date = date('F j, Y', strtotime($certificate->expiry_date));
    
    $styles = $standalone ? '
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 40px; background: #f8f9fa; }
        .certificate { max-width: 800px; margin: 0 auto; background: white; padding: 60px; border: 8px solid #1e3a8a; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .certificate-header { text-align: center; margin-bottom: 40px; }
        .certificate-title { font-size: 36px; font-weight: bold; color: #1e3a8a; margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 2px; }
        .certificate-subtitle { font-size: 18px; color: #6b7280; margin: 0; }
        .certificate-body { text-align: center; margin: 40px 0; }
        .award-text { font-size: 24px; color: #374151; margin: 20px 0; }
        .member-name { font-size: 32px; font-weight: bold; color: #1e3a8a; margin: 20px 0; text-decoration: underline; }
        .achievement-text { font-size: 18px; color: #4b5563; margin: 20px 0; line-height: 1.6; }
        .certificate-details { display: flex; justify-content: space-between; margin-top: 60px; padding-top: 30px; border-top: 2px solid #e5e7eb; }
        .detail-group { text-align: center; }
        .detail-label { font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: bold; }
        .detail-value { font-size: 14px; color: #374151; font-weight: bold; margin-top: 5px; }
        .logo { width: 80px; height: 80px; margin: 0 auto 20px; background: #1e3a8a; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; font-weight: bold; }
    </style>
    ' : '';
    
    $html = ($standalone ? '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>CPD Certificate</title>' . $styles . '</head><body>' : '') . '
    <div class="certificate">
        <div class="certificate-header">
            <div class="logo">IIPM</div>
            <h1 class="certificate-title">Certificate of Completion</h1>
            <p class="certificate-subtitle">Continuing Professional Development</p>
        </div>
        
        <div class="certificate-body">
            <p class="award-text">This is to certify that</p>
            <h2 class="member-name">' . esc_html($member_name) . '</h2>
            <p class="achievement-text">
                has successfully completed the required Continuing Professional Development (CPD) 
                activities for the year ' . esc_html($certificate->cpd_year) . ', earning a total of 
                <strong>' . esc_html($certificate->total_cpd_points) . ' CPD points</strong> out of 
                <strong>' . esc_html($certificate->required_points) . ' required points</strong>, 
                and is hereby recognized for their commitment to professional excellence and 
                ongoing learning in the field of pension management.
            </p>
        </div>
        
        <div class="certificate-details">
            <div class="detail-group">
                <div class="detail-label">Certificate Number</div>
                <div class="detail-value">' . esc_html($certificate->certificate_number) . '</div>
            </div>
            <div class="detail-group">
                <div class="detail-label">Issue Date</div>
                <div class="detail-value">' . esc_html($issue_date) . '</div>
            </div>
            <div class="detail-group">
                <div class="detail-label">Valid Until</div>
                <div class="detail-value">' . esc_html($expiry_date) . '</div>
            </div>
            <div class="detail-group">
                <div class="detail-label">Status</div>
                <div class="detail-value">' . esc_html(ucfirst($certificate->compliance_status)) . '</div>
            </div>
        </div>
    </div>
    ' . ($standalone ? '</body></html>' : '');
    
    return $html;
}

/**
 * Send certificate notification email
 */
function iipm_send_certificate_notification($user_id, $certificate_id) {
    global $wpdb;
    
    $user = get_user_by('id', $user_id);
    if (!$user) return false;
    
    $certificate = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_cpd_certificates WHERE id = %d",
        $certificate_id
    ));
    
    if (!$certificate) return false;
    
    $download_url = home_url('/cpd-reports/?action=download_certificate&cert_id=' . $certificate_id);
    
    $subject = 'CPD Certificate Available - IIPM';
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #1e3a8a;'>CPD Certificate Ready for Download</h2>
            
            <p>Dear {$user->display_name},</p>
            
            <p>Congratulations! Your CPD certificate for {$certificate->cpd_year} is now available for download.</p>
            
            <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                <h3 style='margin: 0 0 15px 0; color: #1e3a8a;'>Certificate Details:</h3>
                <p><strong>Certificate Number:</strong> {$certificate->certificate_number}</p>
                <p><strong>Year:</strong> {$certificate->cpd_year}</p>
                <p><strong>CPD Points Earned:</strong> {$certificate->total_cpd_points}</p>
                <p><strong>Status:</strong> " . ucfirst($certificate->compliance_status) . "</p>
            </div>
            
            <p style='text-align: center; margin: 30px 0;'>
                <a href='{$download_url}' style='background: #1e3a8a; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;'>
                    Download Certificate
                </a>
            </p>
            
            <p>You can also access your certificates anytime through your member portal.</p>
            
            <p>Best regards,<br>IIPM Administration</p>
        </div>
    </body>
    </html>
    ";
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: IIPM Portal <' . get_option('admin_email') . '>'
    );
    
    $email_sent = wp_mail($user->user_email, $subject, $message, $headers);
    
    if ($email_sent) {
        $wpdb->update(
            $wpdb->prefix . 'test_iipm_cpd_certificates',
            array(
                'email_sent' => 1,
                'email_sent_date' => current_time('mysql')
            ),
            array('id' => $certificate_id),
            array('%d', '%s'),
            array('%d')
        );
    }
    
    return $email_sent;
}

/**
 * Get member certificates
 */
function iipm_get_member_certificates($user_id, $limit = 10) {
    global $wpdb;
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_cpd_certificates 
         WHERE user_id = %d 
         ORDER BY cpd_year DESC, issued_date DESC 
         LIMIT %d",
        $user_id, $limit
    ));
}

/**
 * Download certificate
 */
function iipm_download_certificate($certificate_id, $user_id = null) {
    global $wpdb;
    
    // Verify access
    if (!current_user_can('administrator') && !current_user_can('manage_iipm_members')) {
        if (!$user_id || $user_id != get_current_user_id()) {
            return array('success' => false, 'error' => 'Unauthorized access');
        }
    }
    
    $certificate = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_cpd_certificates WHERE id = %d",
        $certificate_id
    ));
    
    if (!$certificate) {
        return array('success' => false, 'error' => 'Certificate not found');
    }
    
    // Check if user owns the certificate (unless admin)
    if (!current_user_can('administrator') && !current_user_can('manage_iipm_members')) {
        if ($certificate->user_id != get_current_user_id()) {
            return array('success' => false, 'error' => 'Unauthorized access');
        }
    }
    
    // Update download count
    $wpdb->update(
        $wpdb->prefix . 'test_iipm_cpd_certificates',
        array(
            'download_count' => $certificate->download_count + 1,
            'last_downloaded' => current_time('mysql')
        ),
        array('id' => $certificate_id),
        array('%d', '%s'),
        array('%d')
    );
    
    // Get file path
    if ($certificate->certificate_file_path) {
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . $certificate->certificate_file_path;
        
        if (file_exists($file_path)) {
            return array(
                'success' => true,
                'file_path' => $file_path,
                'certificate' => $certificate
            );
        }
    }
    
    // If file doesn't exist, regenerate it
    $result = iipm_generate_certificate_html($certificate_id);
    
    if ($result['success']) {
        $wpdb->update(
            $wpdb->prefix . 'test_iipm_cpd_certificates',
            array('certificate_file_path' => $result['file_path']),
            array('id' => $certificate_id),
            array('%s'),
            array('%d')
        );
        
        return array(
            'success' => true,
            'file_path' => $upload_dir['basedir'] . $result['file_path'],
            'certificate' => $certificate
        );
    }
    
    return array('success' => false, 'error' => 'Could not generate certificate file');
}

/**
 * Automatic certificate generation for eligible members
 */
function iipm_auto_generate_certificates($year = null) {
    if (!$year) {
        $year = date('Y');
    }
    
    global $wpdb;
    
    // Get all active members
    $members = $wpdb->get_results(
        "SELECT user_id FROM {$wpdb->prefix}test_iipm_members 
         WHERE membership_status = 'active'"
    );
    
    $results = array(
        'total_members' => count($members),
        'generated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'details' => array()
    );
    
    foreach ($members as $member) {
        $eligibility = iipm_check_certification_eligibility($member->user_id, $year);
        
        if ($eligibility['eligible']) {
            $result = iipm_generate_cpd_certificate($member->user_id, $year, 'automatic');
            
            if ($result['success']) {
                $results['generated']++;
                $results['details'][] = array(
                    'user_id' => $member->user_id,
                    'status' => 'generated',
                    'certificate_number' => $result['certificate_number']
                );
            } else {
                $results['errors']++;
                $results['details'][] = array(
                    'user_id' => $member->user_id,
                    'status' => 'error',
                    'message' => $result['message']
                );
            }
        } else {
            $results['skipped']++;
            $results['details'][] = array(
                'user_id' => $member->user_id,
                'status' => 'skipped',
                'reason' => $eligibility['reason']
            );
        }
    }
    
    return $results;
}

/**
 * Revoke certificate
 */
function iipm_revoke_certificate($certificate_id, $reason = '') {
    global $wpdb;
    
    $result = $wpdb->update(
        $wpdb->prefix . 'test_iipm_cpd_certificates',
        array(
            'certificate_status' => 'revoked',
            'revoked_date' => current_time('mysql'),
            'revoked_by' => get_current_user_id(),
            'revocation_reason' => $reason
        ),
        array('id' => $certificate_id),
        array('%s', '%s', '%d', '%s'),
        array('%d')
    );
    
    if ($result !== false) {
        // Log activity
        $certificate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}test_iipm_cpd_certificates WHERE id = %d",
            $certificate_id
        ));
        
        if ($certificate) {
            iipm_log_user_activity(
                $certificate->user_id,
                'certificate_revoked',
                "Certificate {$certificate->certificate_number} revoked. Reason: {$reason}"
            );
        }
    }
    
    return $result !== false;
} 