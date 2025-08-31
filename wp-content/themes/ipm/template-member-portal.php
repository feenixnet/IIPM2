<?php
/*
Template Name: Enhanced Member Portal
*/

// Check if user is logged in and has proper role
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;

// Check if user has IIPM member role
if (!in_array('iipm_member', $user_roles) && 
    !in_array('iipm_council_member', $user_roles) && 
    !in_array('iipm_corporate_admin', $user_roles) &&
    !in_array('administrator', $user_roles)) {
    wp_redirect(home_url());
    exit;
}

$user_id = $current_user->ID;

// Get member data
global $wpdb;
$member = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d", 
    $user_id
));

$profile = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}test_iipm_member_profiles WHERE user_id = %d",
    $user_id
));

$organisation = null;
if ($member && $member->organisation_id) {
    $organisation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_organisations WHERE id = %d",
        $member->organisation_id
    ));
}

// If no member record exists, create one
if (!$member) {
    $wpdb->insert(
        $wpdb->prefix . 'test_iipm_members',
        array(
            'user_id' => $user_id,
            'member_type' => 'individual',
            'membership_status' => 'active',
            'membership_level' => 'member',
            'cpd_points_required' => 4, // Updated to match Figma design (4 categories × 1 point each)
            'cpd_points_current' => 0
        ), 
        array('%d', '%s', '%s', '%s', '%d', '%d')
    );

    $member = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d",
        $user_id
    ));
}

// Get CPD data for Milestone 2 functionality
$cpd_summary = $wpdb->get_row($wpdb->prepare(
    "SELECT 
        SUM(CASE WHEN status = 'approved' THEN cpd_points ELSE 0 END) as total_points,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as completed_activities,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_activities
    FROM {$wpdb->prefix}test_iipm_cpd_records 
    WHERE user_id = %d AND cpd_year = %d",
    $user_id,
    date('Y')
));

// Get category-based CPD progress - Fixed query to match database schema and Figma design
$cpd_categories = $wpdb->get_results($wpdb->prepare(
    "SELECT 
        cc.id,
        cc.name,
        cc.min_points_required as required_points,
        COALESCE(SUM(CASE WHEN cr.status = 'approved' THEN cr.cpd_points ELSE 0 END), 0) as earned_points
    FROM {$wpdb->prefix}test_iipm_cpd_categories cc
    LEFT JOIN {$wpdb->prefix}test_iipm_cpd_records cr ON cc.id = cr.category_id 
        AND cr.user_id = %d AND cr.cpd_year = %d
    WHERE cc.is_active = 1 AND cc.is_mandatory = 1
    GROUP BY cc.id, cc.name, cc.min_points_required
    ORDER BY cc.sort_order ASC",
    $user_id,
    date('Y')
));

// Get recent CPD records for training history
$recent_cpd_records = $wpdb->get_results($wpdb->prepare(
    "SELECT cr.*, cc.name as category_name, c.title as course_title
    FROM {$wpdb->prefix}test_iipm_cpd_records cr
    LEFT JOIN {$wpdb->prefix}test_iipm_cpd_categories cc ON cr.category_id = cc.id
    LEFT JOIN {$wpdb->prefix}test_iipm_cpd_courses c ON cr.course_id = c.id
    WHERE cr.user_id = %d 
    ORDER BY cr.completion_date DESC
    LIMIT 4",
    $user_id
));

// Check CPD return status
$cpd_return_status = function_exists('iipm_get_cpd_return_status') ? 
    iipm_get_cpd_return_status($user_id, date('Y')) : 
    array('can_submit' => false, 'status' => 'not_submitted');

// Get upcoming courses
$upcoming_courses = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}test_iipm_cpd_courses 
    WHERE is_active = 1
    ORDER BY created_at DESC
    LIMIT 4"
);

// Check if profile is complete
$profile_completion = function_exists('iipm_calculate_profile_completion') ? iipm_calculate_profile_completion($user_id) : 100;
$is_organisation_admin = function_exists('iipm_is_organisation_admin') ? iipm_is_organisation_admin($user_id) : false;

get_header();
?>

<style>
/* Member Portal Styles */
.member-portal {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: #f8fafc;
    min-height: 100vh;
    margin-bottom:30px;
}

/* CPD Selection Modal Styles */
.cpd-option-card:hover {
    border-color: #ff6b35 !important;
    box-shadow: 0 12px 32px rgba(255, 107, 53, 0.2) !important;
    transform: translateY(-4px) scale(1.02) !important;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%) !important;
}

.cpd-option-card:active {
    transform: translateY(-2px) scale(1.01) !important;
}

.portal-hero {
    background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('<?php echo get_template_directory_uri(); ?>/assets/images/portal-hero-bg.jpg');
    background-size: cover;
    background-position: center;
    color: white;
    padding-top: 160px;
    padding-bottom: 60px;
    position: relative;
}

.portal-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('https://hebbkx1anhila5yf.public.blob.vercel-storage.com/image-LLiKAQcMG6rEd1ylgFtkt7INLh13Ii.png') center/cover;
    opacity: 0.8;
    z-index: -1;
}

.hero-content {
    position: relative;
    z-index: 2;
}

.portal-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.announcement-banner {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 16px;
    margin: -30px 0 40px 0;
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
    z-index: 10;
}

.announcement-icon {
    width: 24px;
    height: 24px;
    background: #000;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    flex-shrink: 0;
    font-size: 14px;
}

.portal-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 24px;
    margin-bottom: 40px;
    align-items: start;
}

.right-column-container {
    display: flex;
    flex-direction: column;
    height: 100%;
    gap: 24px;
}

.top-cards-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    align-items: stretch;
}

.cpd-section {
    background: #e5e7eb;
                <div class="training-history">
    border-radius: 12px;
    padding: 24px;
    box-shadow: none;
    border: none;
    height: fit-content;
}

.all-courses-card {
    background:rgb(179, 219, 219);
    /* opacity: 0.3; */
    border-radius: 12px;
    padding: 24px;
    box-shadow: none;
    border: none;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
}

.all-courses-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 20px;
}

.all-courses-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.courses-icon {
    width: 40px;
    height: 40px;
    background: #6b7280;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.browse-courses-link {
    color: #6b7280;
    text-decoration: underline;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: auto;
    cursor: pointer;
}

.leave-request-card {
    background:rgb(179, 219, 219);
    border-radius: 12px;
    padding: 24px;
    box-shadow: none;
    border: none;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
}

.leave-request-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 10px;
}

.leave-icon {
    width: 40px;
    height: 40px;
    background: #6b7280;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.leave-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.leave-subtitle {
    color: #6b7280;
    margin: 8px 0 20px 0;
    font-size: 14px;
}

.submit-leave-link {
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    text-decoration: underline;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: auto;
}

.cpd-header {
    /* display: flex; */
    /* justify-content: space-between; */
    align-items: center;
    margin-bottom: 24px;
}

.cpd-title {
    font-size: 1.8rem;
    font-weight: 900;
    color: #333;
    margin: 0;
    margin-bottom: 10px;
}

.training-btn-div {
    display: flex;
    justify-content: center;
}

.log-training-btn {
    background: #ff6b35;
    color: white;
    border: none;
    width: 300px;
    padding: 15px 20px;
    border-radius: 10px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.log-training-btn:hover {
    background: #e55a2b;
}

.cpd-progress {
    margin-bottom: 24px;
}

.progress-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #d1d5db;
}

.progress-item:last-child {
    border-bottom: none;
}

.progress-label {
    font-weight: 500;
    color: #333;
    flex: 1;
}

.progress-bar {
    width: 120px;
    height: 8px;
    background: #f3f4f6;
    border-radius: 4px;
    overflow: hidden;
    margin: 0 16px;
}

.progress-fill {
    height: 100%;
    background: #ff6b35;
    transition: width 0.3s ease;
}

.progress-value {
    font-weight: 600;
    color: #333;
    min-width: 40px;
    text-align: right;
}

.total-progress {
    background: #f9fafb;
    padding: 16px;
    border-radius: 8px;
    margin-top: 16px;
    border-top: 2px solid #d1d5db;
}

.total-progress .progress-item {
    border-bottom: none;
    padding: 0;
}

.total-progress .progress-fill {
    background: #ff6b35;
}

.submit-return-btn {
    background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    width: 100%;
    margin-top: 16px;
    transition: all 0.3s ease;
}

.submit-return-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #6d28d9 0%, #9333ea 100%);
    transform: translateY(-2px);
}

.submit-return-btn:disabled {
    background: #d1d5db;
    color: #9ca3af;
    cursor: not-allowed;
    transform: none;
}

/* CPD Submitted State */
.cpd-submitted-state {
    text-align: center;
    padding: 40px 20px;
}

.check-icon {
    width: 80px;
    height: 80px;
    background: #7c3aed;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px auto;
    font-size: 40px;
    color: white;
    font-weight: bold;
}

.submitted-title {
    font-size: 24px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 15px;
}

.submitted-message {
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: 30px;
    font-size: 16px;
}

.see-cpd-record-btn {
    background: none;
    border: none;
    color: #7c3aed;
    font-weight: 600;
    text-decoration: underline;
    cursor: pointer;
    font-size: 16px;
    transition: color 0.3s ease;
    margin-bottom: 100px;
}

.see-cpd-record-btn:hover {
    color: #6d28d9;
}

/* CPD Return Confirmation Modal */
.cpd-return-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.cpd-return-modal.show {
    display: flex;
}

.cpd-return-modal-content {
    background: white;
    border-radius: 12px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}

.cpd-return-modal-body {
    padding: 40px;
    text-align: center;
}

.cpd-return-icon {
    width: 80px;
    height: 80px;
    background: #10b981;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px auto;
    font-size: 40px;
    color: white;
    font-weight: bold;
}

.cpd-return-modal h3 {
    font-size: 24px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 15px;
}

.cpd-return-modal p {
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: 30px;
}

.cpd-return-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.cpd-return-btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    font-size: 16px;
}

.cpd-return-btn-cancel {
    background: #f3f4f6;
    color: #374151;
}

.cpd-return-btn-cancel:hover {
    background: #e5e7eb;
}

.cpd-return-btn-confirm {
    background: #7c3aed;
    color: white;
}

.cpd-return-btn-confirm:hover {
    background: #6d28d9;
}

.cpd-return-btn-confirm:disabled {
    background: #d1d5db;
    color: #9ca3af;
    cursor: not-allowed;
}

.warning-message {
    background: #dc2626;
    color: white;
    padding: 12px 16px;
    border-radius: 6px;
    margin-top: 12px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.training-history {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    flex: 1;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #8b5a96;
    margin-bottom: 24px;
}

.training-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 16px 0;
    border-bottom: 1px solid #f0f0f0;
}

.training-item:last-child {
    border-bottom: none;
}

.training-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    background: #ff6b35;
    color: white;
    flex-shrink: 0;
}

.training-status.approved {
    background: #10b981;
}

.training-status.pending {
    background: #f59e0b;
}

.training-status.rejected {
    background: #ef4444;
}

.training-details {
    flex: 1;
}

.training-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    font-size: 16px;
}

.training-meta {
    font-size: 14px;
    color: #666;
    line-height: 1.4;
}

.see-history-link {
    color: #8b5a96;
    text-decoration: none;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 16px;
    float: right;
    cursor: pointer;
}

.course-registration {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 32px;
}

.courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.course-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    position: relative;
}

.course-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.badge-webinar {
    background: #fff3cd;
    color: #856404;
}

.badge-workshop {
    background: #e3f2fd;
    color: #1565c0;
}

.badge-conference {
    background: #f3e5f5;
    color: #7b1fa2;
}

.course-title {
    font-weight: 600;
    color: #333;
    margin: 24px 0 8px;
}

.course-details {
    font-size: 14px;
    color: #666;
    margin-bottom: 16px;
}

.see-details-btn {
    background: transparent;
    color: #8b5a96;
    border: 1px solid #8b5a96;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.see-details-btn:hover {
    background: #8b5a96;
    color: white;
}

.see-all-courses {
    text-align: center;
}

.see-all-btn {
    background: #f0f0f0;
    color: #666;
    border: none;
    padding: 12px 32px;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
}

.cpd-record {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 32px;
}

.record-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.record-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    position: relative;
}

.record-status {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-complete {
    background: #d4edda;
    color: #155724;
}

.record-year {
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.record-points {
    color: #666;
    margin-bottom: 16px;
}

.download-btn {
    background: transparent;
    color: #8b5a96;
    border: 1px solid #8b5a96;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    margin-right: 8px;
}

.footer-sections {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-top: 40px;
}

.footer-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 12px;
}

.footer-links a {
    color: #8b5a96;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
}

.footer-links a:hover {
    text-decoration: underline;
}

/* Enhanced Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    animation: fadeIn 0.3s ease-out;
}

.modal.show {
    display: flex !important;
    align-items: center;
    justify-content: center;
}

.modal-dialog {
    background: white;
    border-radius: 16px;
    max-width: 650px;
    width: 95%;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    transform: translateY(20px);
    animation: slideUp 0.3s ease-out forwards;
}

.modal-header {
    padding: 24px 30px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 16px 16px 0 0;
}

.modal-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
}

.modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

.modal-body {
    padding: 30px;
    overflow-y: auto;
    max-height: calc(90vh - 160px);
}

.modal-footer {
    padding: 20px 30px 30px;
    border-top: 1px solid #f1f5f9;
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    background: #f8fafc;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { 
        transform: translateY(30px);
        opacity: 0;
    }
    to { 
        transform: translateY(0);
        opacity: 1;
    }
}

.form-group {
    margin-bottom: 24px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-control {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 16px;
    background: #f8fafc;
    transition: all 0.3s ease;
    font-family: inherit;
}

.form-control:focus {
    outline: none;
    border-color: #ff6b35;
    box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.1);
    background: white;
    transform: translateY(-1px);
}

.form-control:hover {
    border-color: #d1d5db;
    background: white;
}

.btn {
    padding: 14px 24px;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    font-size: 16px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    min-width: 120px;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.btn:hover::before {
    left: 100%;
}

.btn-primary {
    background: linear-gradient(135deg, #ff6b35 0%, #e55a2b 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(135deg, #e55a2b 0%, #d14d26 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 107, 53, 0.4);
}

.btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.btn-success:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
}

.btn-secondary {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(107, 114, 128, 0.4);
}

.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 12px;
}

.alert::before {
    content: '';
    width: 20px;
    height: 20px;
    border-radius: 50%;
    flex-shrink: 0;
}

.alert-success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
    border: 2px solid #10b981;
}

.alert-success::before {
    background: #10b981;
    content: '✓';
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.alert-danger {
    background: linear-gradient(135deg, #fee2e2 0%, #fca5a5 100%);
    color: #991b1b;
    border: 2px solid #ef4444;
}

.alert-danger::before {
    background: #ef4444;
    content: '✕';
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.alert-warning {
    background: linear-gradient(135deg, #fef3c7 0%, #fcd34d 100%);
    color: #92400e;
    border: 2px solid #f59e0b;
}

.alert-warning::before {
    background: #f59e0b;
    content: '⚠';
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
    font-weight: bold;
}

/* No Training History State */
.no-training-history {
    text-align: center;
    padding: 20px 40px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 16px;
    border: 2px dashed #cbd5e1;
    transition: all 0.3s ease;
}

.no-training-history:hover {
    border-color: #ff6b35;
    background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 107, 53, 0.1);
}

.no-training-icon {
    margin-bottom: 24px;
    opacity: 0.6;
    transition: all 0.3s ease;
}

.no-training-history:hover .no-training-icon {
    opacity: 0.8;
    transform: scale(1.1);
}

.no-training-icon svg {
    color: #64748b;
    transition: color 0.3s ease;
}

.no-training-history:hover .no-training-icon svg {
    color: #ff6b35;
}

.no-training-content {
    max-width: 400px;
    margin: 0 auto;
}

.no-training-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #374151;
    margin: 0 0 12px 0;
    transition: color 0.3s ease;
}

.no-training-history:hover .no-training-title {
    color: #1f2937;
}

.no-training-message {
    color: #6b7280;
    font-size: 1rem;
    line-height: 1.6;
    margin: 0 0 32px 0;
    transition: color 0.3s ease;
}

.no-training-history:hover .no-training-message {
    color: #374151;
}

.start-training-btn {
    background: linear-gradient(135deg, #ff6b35 0%, #e55a2b 100%);
    color: white;
    border: none;
    padding: 14px 28px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    font-size: 1rem;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(255, 107, 53, 0.2);
}

.start-training-btn:hover {
    background: linear-gradient(135deg, #e55a2b 0%, #dc2626 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
}

.start-training-btn:active {
    transform: translateY(0);
    box-shadow: 0 4px 15px rgba(255, 107, 53, 0.2);
}

.start-training-btn span {
    font-size: 1.2rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .portal-grid {
        grid-template-columns: 1fr;
    }
    
    .top-cards-row {
        grid-template-columns: 1fr;
    }
    
    .courses-grid {
        grid-template-columns: 1fr;
    }
    
    .record-grid {
        grid-template-columns: 1fr;
    }
    
    .footer-sections {
        grid-template-columns: 1fr;
    }
    
    .portal-title {
        font-size: 2rem;
    }
    
    .modal-dialog {
        width: 95%;
        margin: 10px;
    }
    
    .no-training-history {
        padding: 40px 20px;
    }
    
    .no-training-title {
        font-size: 1.25rem;
    }
    
    .no-training-message {
        font-size: 0.9rem;
    }
    
    .start-training-btn {
        padding: 12px 24px;
        font-size: 0.9rem;
    }
}
</style>

<div class="member-portal">
    <!-- Hero Section -->
    <section class="portal-hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="portal-title"><?php echo esc_html($current_user->display_name); ?>'s Portal</h1>
            </div>
        </div>
    </section>

    <div class="container">
        <!-- Announcement Banner -->
        <div class="announcement-banner">
            <div class="announcement-icon">!</div>
            <div>
                <strong>ANNOUNCEMENT:</strong> 2025 IIPM Gala Dinner registration is now open. Registration is open until 31 December, 2025
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="portal-grid">
            <!-- CPD Course Section (Left Column) -->
            <div class="cpd-section" id="cpd-section">
                <?php if ($cpd_return_status['status'] === 'submitted'): ?>
                    <!-- Return Submitted State -->
                    <h2 class="cpd-title">My CPD Course</h2>
                    <div class="cpd-submitted-state">
                        <div class="check-icon">
                            <span>✓</span>
                        </div>
                        <h2 class="submitted-title">Return submitted</h2>
                        <p class="submitted-message">
                            You have submitted your CPD return.<br>
                            You will be notified when certificate<br>
                            has been issued.
                        </p>
                        <button class="see-cpd-record-btn" onclick="window.location.href='<?php echo home_url('/training-history/'); ?>'">See CPD Record →</button>
                    </div>
                <?php else: ?>
                    <!-- Normal State -->
                    <div class="cpd-header">
                        <h2 class="cpd-title">My CPD Course</h2>
                        <div class="training-btn-div" class="cpd-header-right">
                            <button class="log-training-btn" onclick="showCPDSelectionModal()">Log Training</button>
                        </div>
                        
                    </div>

                    <div class="cpd-progress">
                        <?php if ($cpd_categories): ?>
                            <?php foreach ($cpd_categories as $category): ?>
                                <?php 
                                $progress_percentage = $category->required_points > 0 ? 
                                    min(100, ($category->earned_points / $category->required_points) * 100) : 0;
                                ?>
                                <div class="progress-item">
                                    <span class="progress-label"><?php echo esc_html($category->name); ?></span>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
                                    </div>
                                    <span class="progress-value"><?php echo intval($category->earned_points); ?>/<?php echo intval($category->required_points); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Fallback if no categories found -->
                            <div class="progress-item">
                                <span class="progress-label">Pensions</span>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 0%"></div>
                                </div>
                                <span class="progress-value">0/1</span>
                            </div>
                            <div class="progress-item">
                                <span class="progress-label">Savings & Investment</span>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 0%"></div>
                                </div>
                                <span class="progress-value">0/1</span>
                            </div>
                            <div class="progress-item">
                                <span class="progress-label">Ethics</span>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 0%"></div>
                                </div>
                                <span class="progress-value">0/1</span>
                            </div>
                            <div class="progress-item">
                                <span class="progress-label">Life Assurance</span>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 0%"></div>
                                </div>
                                <span class="progress-value">0/1</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="total-progress">
                        <div class="progress-item">
                            <span class="progress-label"><strong>Total</strong></span>
                            <div class="progress-bar">
                                <?php 
                                $total_earned = $cpd_summary ? $cpd_summary->total_points : 0;
                                $total_required = 4; // Fixed to match Figma design
                                $total_percentage = min(100, ($total_earned / max(1, $total_required)) * 100);
                                ?>
                                <div class="progress-fill" style="width: <?php echo $total_percentage; ?>%"></div>
                            </div>
                            <span class="progress-value"><strong><?php echo intval($total_earned); ?>/<?php echo $total_required; ?></strong></span>
                        </div>
                    </div>

                    <button class="submit-return-btn" 
                            id="submit-return-btn"
                            onclick="handleSubmitReturn()"
                            <?php echo !$cpd_return_status['can_submit'] ? 'disabled' : ''; ?>>
                        Submit my return
                    </button>
                    <div class="warning-message">
                        ⚠️ Log your hours before June 15, 2025
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column Container -->
            <div class="right-column-container">
                <!-- Top Cards Row (All Courses and Submit Leave Request side by side) -->
                <div class="top-cards-row">
                    <!-- All Courses Card -->
                    <div class="all-courses-card">
                        <div class="all-courses-header">
                            <h2 class="all-courses-title">All Courses</h2>
                            <div class="courses-icon">📚</div>
                        </div>
                        <div class="course-actions">
                            <button class="browse-courses-link" onclick="window.location.href='<?php echo home_url('/cpd-courses/'); ?>'" style="background: none; border: none; color: #6b7280; cursor: pointer; text-decoration: underline; font-weight: 500; display: flex; align-items: center; gap: 8px; margin-top: auto;">Browse courses →</button>
                        </div>
                    </div>

                    <!-- Submit Leave Request -->
                    <div class="leave-request-card">
                        <div class="leave-request-header">
                            <h3 class="leave-title">Submit<br>Leave Request</h3>
                            <div class="leave-icon">📅</div>
                        </div>
                        <div class="leave-actions">
                            <button class="submit-leave-link" onclick="window.location.href='#'" style="background: none; border: none; color: #6b7280; cursor: pointer; text-decoration: underline; font-weight: 500; display: flex; align-items: center; gap: 8px; margin-top: auto;">Submit →</button>
                        </div>
                    </div>
                </div>

                <!-- Recently Logged Training (Full width below the cards) -->
                <div class="training-history">
                    <h2 class="section-title">Recently Logged Training</h2>
                    
                    <?php if ($recent_cpd_records): ?>
                        <?php foreach ($recent_cpd_records as $record): ?>
                            <div class="training-item">
                                <span class="training-status <?php echo $record->status; ?>">
                                    <?php echo $record->category_name ?: 'Pensions'; ?>
                                </span>
                                <div class="training-details">
                                    <div class="training-title">
                                        <?php echo $record->course_title ?: $record->activity_title; ?>
                                    </div>
                                    <div class="training-meta">
                                        <?php if ($record->external_provider): ?>
                                            Provided by <?php echo esc_html($record->external_provider); ?> • 
                                        <?php endif; ?>
                                        LIA Code: LIA18419_2025 • 
                                        CPD minutes: <?php echo intval($record->cpd_points * 60); ?> minutes • 
                                        Date: <?php echo date('F j, Y', strtotime($record->completion_date)); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Beautiful No Training History State -->
                        <div class="no-training-history">
                            <div class="no-training-icon">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>
                                    <circle cx="12" cy="12" r="2"/>
                                    <path d="M12 6v2m0 8v2m-6-6h2m8 0h2"/>
                                </svg>
                            </div>
                            <div class="no-training-content">
                                <h3 class="no-training-title">No training history yet</h3>
                                <p class="no-training-message">Start your CPD journey by logging your first training session</p>
                                <button class="start-training-btn" onclick="showCPDSelectionModal()">
                                    <span>📚</span>
                                    Log your first training
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <a href="<?php echo home_url('/training-history/'); ?>" class="see-history-link">See training history →</a>
                </div>
            </div>
        </div>

        <!-- Register to CPD Courses -->
        <div class="course-registration">
            <h2 class="section-title">Register to CPD Courses</h2>
            
            <div class="courses-grid">
                <?php if ($upcoming_courses): ?>
                    <?php foreach ($upcoming_courses as $course): ?>
                        <div class="course-card">
                            <span class="course-badge badge-<?php echo strtolower($course->course_type ?: 'webinar'); ?>">
                                <?php echo strtoupper($course->course_type ?: 'WEBINAR'); ?>
                            </span>
                            <h3 class="course-title"><?php echo esc_html($course->title); ?></h3>
                            <div class="course-details">
                                <?php if ($course->duration_minutes): ?>
                                    Duration: <?php echo $course->duration_minutes; ?> minutes<br>
                                <?php endif; ?>
                                CPD Points: <?php echo $course->cpd_points; ?><br>
                                Provider: <?php echo esc_html($course->provider); ?>
                            </div>
                            <button class="see-details-btn" onclick="viewCourseDetails(<?php echo $course->id; ?>)">See details</button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Default static courses if no database courses -->
                    <div class="course-card">
                        <span class="course-badge badge-webinar">WEBINAR</span>
                        <h3 class="course-title">March 2025 - DORA</h3>
                        <div class="course-details">
                            Date: March 15, 2025<br>
                            Time: 1:00PM - 5:00PM<br>
                            Location: Online
                        </div>
                        <button class="see-details-btn">See details</button>
                    </div>
                    
                    <div class="course-card">
                        <span class="course-badge badge-workshop">WORKSHOP</span>
                        <h3 class="course-title">April 2025 - Sustainability</h3>
                        <div class="course-details">
                            Date: April 20, 2025<br>
                            Time: 1:00PM - 5:00PM<br>
                            Location: Dublin
                        </div>
                        <button class="see-details-btn">See details</button>
                    </div>
                    
                    <div class="course-card">
                        <span class="course-badge badge-webinar">WEBINAR</span>
                        <h3 class="course-title">May 2025 - Pensions</h3>
                        <div class="course-details">
                            Date: May 10, 2025<br>
                            Time: 1:00PM - 5:00PM<br>
                            Location: Online
                        </div>
                        <button class="see-details-btn">See details</button>
                    </div>
                    
                    <div class="course-card">
                        <span class="course-badge badge-conference">CONFERENCE</span>
                        <h3 class="course-title">June 2025 - Pensions</h3>
                        <div class="course-details">
                            Date: June 15, 2025<br>
                            Time: 9:00AM - 6:00PM<br>
                            Location: Cork
                        </div>
                        <button class="see-details-btn">See details</button>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="see-all-courses">
                <a href="<?php echo home_url('/cpd-courses/'); ?>" class="see-all-btn">See all courses</a>
            </div>
        </div>

        <!-- Your CPD Record -->
        <div class="cpd-record">
            <h2 class="section-title">Your CPD Record</h2>
            
            <div class="record-grid">
                <?php
                // Get CPD records by year
                $cpd_years = $wpdb->get_results($wpdb->prepare(
                    "SELECT 
                        cpd_year,
                        SUM(CASE WHEN status = 'approved' THEN cpd_points ELSE 0 END) as total_points,
                        COUNT(*) as total_activities
                    FROM {$wpdb->prefix}test_iipm_cpd_records 
                    WHERE user_id = %d 
                    GROUP BY cpd_year 
                    ORDER BY cpd_year DESC 
                    LIMIT 3",
                    $user_id
                ));
                
                if ($cpd_years):
                    foreach ($cpd_years as $year_record):
                        // Get certificate status for this year
                        $cert_status = iipm_get_certificate_status_display($user_id, $year_record->cpd_year);
                        $is_eligible = $cert_status['eligible'];
                        $has_certificate = $cert_status['certificate_exists'];
                ?>
                    <div class="record-card">
                        <span class="record-status <?php echo $year_record->total_points >= 4 ? 'status-complete' : 'status-incomplete'; ?>">
                            <?php echo $year_record->total_points >= 4 ? 'COMPLETE' : 'IN PROGRESS'; ?>
                        </span>
                        <h3 class="record-year">CPD <?php echo $year_record->cpd_year; ?></h3>
                        <p class="record-points"><?php echo $year_record->total_points; ?> CPD Points</p>
                        
                        <?php if ($has_certificate && $cert_status['certificate_status'] === 'issued'): ?>
                            <button class="download-btn" onclick="downloadCPDCertificate(<?php echo $year_record->cpd_year; ?>)">
                                📄 Download Certificate
                            </button>
                            <small style="color: #10b981; font-size: 12px; display: block; margin-top: 5px;">
                                Certificate Available
                            </small>
                        <?php elseif ($is_eligible): ?>
                            <button class="download-btn generate-btn" onclick="generateCPDCertificate(<?php echo $year_record->cpd_year; ?>)">
                                🎓 Generate Certificate
                            </button>
                            <small style="color: #f59e0b; font-size: 12px; display: block; margin-top: 5px;">
                                Eligible for Certificate
                            </small>
                        <?php else: ?>
                            <button class="download-btn disabled" disabled title="<?php echo esc_attr($cert_status['eligibility_reason']); ?>">
                                📄 Certificate Not Available
                            </button>
                            <small style="color: #ef4444; font-size: 12px; display: block; margin-top: 5px;">
                                <?php echo esc_html($cert_status['eligibility_reason']); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                <?php 
                    endforeach;
                else:
                    // Default static records if no data
                ?>
                    <div class="record-card">
                        <span class="record-status status-complete">COMPLETE</span>
                        <h3 class="record-year">CPD 2024</h3>
                        <p class="record-points">2024 CPD Record</p>
                        <button class="download-btn">Download CPD Certificate</button>
                    </div>
                    
                    <div class="record-card">
                        <span class="record-status status-complete">COMPLETE</span>
                        <h3 class="record-year">CPD 2023</h3>
                        <p class="record-points">2023 CPD Record</p>
                        <button class="download-btn">Download CPD Certificate</button>
                    </div>
                    
                    <div class="record-card">
                        <span class="record-status status-complete">COMPLETE</span>
                        <h3 class="record-year">CPD 2022</h3>
                        <p class="record-points">2022 CPD Record</p>
                        <button class="download-btn">Download CPD Certificate</button>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="see-all-courses">
                <button class="see-all-btn" onclick="viewAllCPDRecords()">See all CPD records</button>
            </div>
        </div>

        <!-- Footer Sections -->
        <div class="footer-sections">
            <div class="footer-section">
                <h3>Helpful Links</h3>
                <ul class="footer-links">
                    <li><a href="#">Manage your account →</a></li>
                    <li><a href="#">Reset your password →</a></li>
                    <li><a href="#">View payment history →</a></li>
                    <li><a href="<?php echo home_url('/cpd-courses/'); ?>">Register to courses →</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Need help?</h3>
                <ul class="footer-links">
                    <li><a href="#" onclick="showCPDForm('external')">How to log my courses →</a></li>
                    <li><a href="#">How to submit leave request →</a></li>
                    <li><a href="#">How to reset my password →</a></li>
                    <li><a href="<?php echo home_url('/cpd-courses/'); ?>">Register to courses →</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- CPD Training Type Selection Modal -->
<div class="modal" id="cpdSelectionModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h5 class="modal-title">Log CPD Training</h5>
            <button type="button" class="modal-close" onclick="closeCPDModal('cpdSelectionModal')">&times;</button>
        </div>
        <div class="modal-body" style="padding: 30px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <p style="font-size: 18px; color: #64748b; margin-bottom: 40px; line-height: 1.6;">Choose how you'd like to log your CPD training:</p>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <!-- Pre-approved Course Option -->
                <div class="cpd-option-card" onclick="selectCPDType('course')" style="border: 3px solid #e2e8f0; border-radius: 16px; padding: 32px 24px; cursor: pointer; text-align: center; transition: all 0.3s ease; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); position: relative; overflow: hidden;">
                    <div style="position: absolute; top: -50%; right: -50%; width: 100%; height: 100%; background: radial-gradient(circle, rgba(255, 107, 53, 0.05) 0%, transparent 70%); z-index: 1;"></div>
                    <div style="position: relative; z-index: 2;">
                        <div style="font-size: 3.5rem; margin-bottom: 20px; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));">📚</div>
                        <h3 style="margin: 0 0 12px 0; color: #1e293b; font-size: 20px; font-weight: 700;">Pre-approved Course</h3>
                        <p style="margin: 0; color: #64748b; font-size: 15px; line-height: 1.6;">Select from our library of approved courses. Automatically approved upon submission.</p>
                    </div>
                </div>
                
                <!-- External Training Option -->
                <div class="cpd-option-card" onclick="selectCPDType('external')" style="border: 3px solid #e2e8f0; border-radius: 16px; padding: 32px 24px; cursor: pointer; text-align: center; transition: all 0.3s ease; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); position: relative; overflow: hidden;">
                    <div style="position: absolute; top: -50%; right: -50%; width: 100%; height: 100%; background: radial-gradient(circle, rgba(16, 185, 129, 0.05) 0%, transparent 70%); z-index: 1;"></div>
                    <div style="position: relative; z-index: 2;">
                        <div style="font-size: 3.5rem; margin-bottom: 20px; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));">📝</div>
                        <h3 style="margin: 0 0 12px 0; color: #1e293b; font-size: 20px; font-weight: 700;">External Training</h3>
                        <p style="margin: 0; color: #64748b; font-size: 15px; line-height: 1.6;">Submit training from external providers. Requires approval from admin.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CPD Course Form Modal -->
<div class="modal" id="cpdCourseModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h5 class="modal-title">Log CPD Course</h5>
            <button type="button" class="modal-close" onclick="closeCPDModal('cpdCourseModal')">&times;</button>
        </div>
        <form id="cpdCourseForm" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Course *</label>
                    <select class="form-control" name="course_id" required>
                        <option value="">Select a course...</option>
                        <!-- Courses will be loaded via AJAX -->
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Completion Date *</label>
                    <input type="date" class="form-control" name="completion_date" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Certificate/Evidence (Optional)</label>
                    <input type="file" class="form-control" name="certificate" accept=".pdf,.jpg,.jpeg,.png">
                    <small style="color: #666; font-size: 12px;">Upload certificate or evidence (PDF, JPG, PNG - Max 5MB)</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="notes" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCPDModal('cpdCourseModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Log Course</button>
            </div>
        </form>
    </div>
</div>

<!-- CPD External Training Form Modal -->
<div class="modal" id="cpdExternalModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h5 class="modal-title">Submit External Training</h5>
            <button type="button" class="modal-close" onclick="closeCPDModal('cpdExternalModal')">&times;</button>
        </div>
        <form id="cpdExternalForm" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Activity Title *</label>
                    <input type="text" class="form-control" name="activity_title" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Provider *</label>
                    <input type="text" class="form-control" name="external_provider" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <select class="form-control" name="category_id" required>
                        <option value="">Select category...</option>
                        <!-- Categories will be loaded via AJAX -->
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">CPD Points *</label>
                    <input type="number" class="form-control" name="cpd_points" min="0.5" step="0.5" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Completion Date *</label>
                    <input type="date" class="form-control" name="completion_date" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description *</label>
                    <textarea class="form-control" name="description" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Certificate/Evidence</label>
                    <input type="file" class="form-control" name="certificate" accept=".pdf,.jpg,.jpeg,.png">
                    <small style="color: #666; font-size: 12px;">Upload certificate or evidence (PDF, JPG, PNG - Max 5MB)</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCPDModal('cpdExternalModal')">Cancel</button>
                <button type="submit" class="btn btn-success">Submit for Approval</button>
            </div>
        </form>
    </div>
</div>

<!-- CPD Library Modal -->
<div class="modal" id="cpdLibraryModal">
    <div class="modal-dialog" style="max-width: 1200px;">
        <div class="modal-header">
            <h5 class="modal-title">CPD Course Library</h5>
            <button type="button" class="modal-close" onclick="closeCPDModal('cpdLibraryModal')">&times;</button>
        </div>
        <div class="modal-body" style="max-height: 80vh; overflow-y: auto;">
            <!-- Enhanced Course Search Interface -->
            <div class="course-search-interface">
                <!-- Search Input -->
                <div class="search-input-container" style="margin-bottom: 20px;">
                    <input type="text" 
                           id="enhanced-course-search-input" 
                           placeholder="Search courses by title, provider, or code..." 
                           style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 16px;">
                </div>
                
                <!-- Advanced Filters -->
                <div class="search-filters" style="margin-bottom: 20px; display: none;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                        <select id="enhanced-category-filter" style="padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                            <option value="">All Categories</option>
                        </select>
                        
                        <select id="enhanced-provider-filter" style="padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                            <option value="">All Providers</option>
                        </select>
                        
                        <select id="enhanced-type-filter" style="padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                            <option value="">All Types</option>
                        </select>
                        
                        <div style="display: flex; gap: 5px;">
                            <input type="number" id="enhanced-points-min" placeholder="Min Points" step="0.5" min="0" 
                                   style="padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; width: 100%;">
                            <input type="number" id="enhanced-points-max" placeholder="Max Points" step="0.5" min="0" 
                                   style="padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; width: 100%;">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <button id="enhanced-search-btn" type="button" 
                                style="background: #ff6b35; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                            🔍 Search Courses
                        </button>
                        <button id="enhanced-clear-filters-btn" type="button" 
                                style="background: #6b7280; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                            🗑️ Clear Filters
                        </button>
                    </div>
                </div>
                
                <!-- Toggle Advanced Filters -->
                <div style="text-align: center; margin-bottom: 20px;">
                    <button id="toggle-enhanced-filters" type="button" 
                            style="background: #6b7280; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px;">
                        ⚙️ Advanced Filters
                    </button>
                </div>
                
                <!-- Search Results -->
                <div id="enhanced-search-results" style="margin-top: 20px;">
                    <div class="loading-message" style="text-align: center; padding: 40px; color: #6b7280;">
                        <div style="font-size: 2rem; margin-bottom: 10px;">📚</div>
                        <p>Start typing to search through our course library...</p>
                    </div>
                </div>
                
                <!-- Pagination -->
                <div id="enhanced-search-pagination" style="margin-top: 20px; text-align: center; display: none;">
                    <!-- Pagination will be inserted here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CPD Return Confirmation Modal -->
<div class="cpd-return-modal" id="cpdReturnConfirmationModal">
    <div class="cpd-return-modal-content">
        <div class="cpd-return-modal-body">
            <div class="cpd-return-icon">
                <span>✓</span>
            </div>
            <h3>CPD return submitted</h3>
            <p>Your CPD return has been submitted.<br>You will be notified when the certificate is issued.</p>
            <div class="cpd-return-actions">
                <button type="button" class="cpd-return-btn cpd-return-btn-confirm" onclick="closeCPDReturnModal('success')">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- CPD Return Submit Confirmation Modal -->
<div class="cpd-return-modal" id="cpdReturnSubmitModal">
    <div class="cpd-return-modal-content">
        <div class="cpd-return-modal-body">
            <div class="cpd-return-icon" style="background: #f59e0b;">
                <span>?</span>
            </div>
            <h3>Submit CPD return?</h3>
            <p>Are you sure you want to submit your CPD return for <?php echo date('Y'); ?>? This action cannot be undone.</p>
            <div class="cpd-return-actions">
                <button type="button" class="cpd-return-btn cpd-return-btn-cancel" onclick="closeCPDReturnModal('cancel')">Cancel</button>
                <button type="button" class="cpd-return-btn cpd-return-btn-confirm" onclick="confirmCPDReturnSubmission()" id="confirmSubmitBtn">Submit</button>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container -->
<div id="alertContainer" style="position: fixed; top: 20px; right: 20px; z-index: 1100; max-width: 400px;"></div>

<script>
// Global variables
let iipm_ajax = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    cpd_nonce: '<?php echo wp_create_nonce('iipm_cpd_nonce'); ?>'
};

// Alert function
function showAlert(type, message) {
    const alertContainer = document.getElementById('alertContainer');
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;" onclick="this.parentElement.remove()">&times;</button>
    `;
    alertContainer.appendChild(alertDiv);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentElement) {
            alertDiv.remove();
        }
    }, 5000);
}

// Modal functions
function showCPDSelectionModal() {
    // Ensure any previously opened modals are closed and reset
    const modals = ['cpdCourseModal', 'cpdExternalModal', 'cpdLibraryModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal && modal.classList.contains('show')) {
            closeCPDModal(modalId);
        }
    });
    
    // Show the selection modal
    document.getElementById('cpdSelectionModal').classList.add('show');
}

function selectCPDType(type) {
    closeCPDModal('cpdSelectionModal');
    showCPDForm(type);
}

function showCPDForm(type) {
    if (type === 'course') {
        // Always reload course options to ensure fresh data
        loadCourseOptions();
        document.getElementById('cpdCourseModal').classList.add('show');
        
        // Set today's date as default completion date
        const dateInput = document.querySelector('#cpdCourseModal input[name="completion_date"]');
        if (dateInput && !dateInput.value) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }
    } else if (type === 'external') {
        // Always reload category options to ensure fresh data
        loadCategoryOptions();
        document.getElementById('cpdExternalModal').classList.add('show');
        
        // Set today's date as default completion date
        const dateInput = document.querySelector('#cpdExternalModal input[name="completion_date"]');
        if (dateInput && !dateInput.value) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }
    }
}

function closeCPDModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        
        // Reset forms when closing modals
        const forms = modal.querySelectorAll('form');
        forms.forEach(form => {
            form.reset();
        });
        
        // Clear any dynamic content
        if (modalId === 'cpdCourseModal') {
            const courseSelect = modal.querySelector('select[name="course_id"]');
            if (courseSelect) {
                courseSelect.innerHTML = '<option value="">Select a course...</option>';
            }
        }
        
        if (modalId === 'cpdExternalModal') {
            const categorySelect = modal.querySelector('select[name="category_id"]');
            if (categorySelect) {
                categorySelect.innerHTML = '<option value="">Select category...</option>';
            }
        }
        
        if (modalId === 'cpdLibraryModal') {
            // Reset search interface
            const searchInput = document.getElementById('enhanced-course-search-input');
            const resultsContainer = document.getElementById('enhanced-search-results');
            const paginationContainer = document.getElementById('enhanced-search-pagination');
            
            if (searchInput) searchInput.value = '';
            if (resultsContainer) {
                resultsContainer.innerHTML = '<div class="loading-message" style="text-align: center; padding: 40px; color: #6b7280;"><div style="font-size: 2rem; margin-bottom: 10px;">📚</div><p>Start typing to search through our course library...</p></div>';
            }
            if (paginationContainer) paginationContainer.style.display = 'none';
            
            // Reset filters
            const filters = ['enhanced-category-filter', 'enhanced-provider-filter', 'enhanced-type-filter', 'enhanced-points-min', 'enhanced-points-max'];
            filters.forEach(filterId => {
                const element = document.getElementById(filterId);
                if (element) {
                    if (element.tagName === 'SELECT') {
                        element.selectedIndex = 0;
                    } else {
                        element.value = '';
                    }
                }
            });
            
            // Hide advanced filters
            const filtersDiv = document.querySelector('.search-filters');
            const toggleBtn = document.getElementById('toggle-enhanced-filters');
            if (filtersDiv) filtersDiv.style.display = 'none';
            if (toggleBtn) toggleBtn.textContent = '⚙️ Advanced Filters';
        }
    }
}

function loadCourseOptions() {
    fetch(iipm_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'iipm_get_cpd_courses',
            nonce: iipm_ajax.cpd_nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let options = '<option value="">Select a course...</option>';
            data.data.forEach(course => {
                options += `<option value="${course.id}" data-points="${course.cpd_points}">${course.title} (${course.cpd_points} points)</option>`;
            });
            document.querySelector('#cpdCourseModal select[name="course_id"]').innerHTML = options;
        }
    })
    .catch(error => {
        console.error('Error loading courses:', error);
        showAlert('danger', 'Failed to load courses');
    });
}

function loadCategoryOptions() {
    fetch(iipm_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'iipm_get_cpd_categories',
            nonce: iipm_ajax.cpd_nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let options = '<option value="">Select category...</option>';
            data.data.forEach(category => {
                options += `<option value="${category.id}">${category.name}</option>`;
            });
            document.querySelector('#cpdExternalModal select[name="category_id"]').innerHTML = options;
        }
    })
    .catch(error => {
        console.error('Error loading categories:', error);
        showAlert('danger', 'Failed to load categories');
    });
}

function viewCPDLibrary() {
    window.location.href = '<?php echo home_url('/cpd-courses/'); ?>';
}

function redirectToCPDCourses() {
    window.location.href = '<?php echo home_url('/cpd-courses/'); ?>';
}

// Enhanced Course Search Functions
function showEnhancedCourseSearch() {
    document.getElementById('cpdLibraryModal').classList.add('show');
    initializeEnhancedSearch();
}

let enhancedSearchTimeout;
let enhancedCurrentPage = 1;

function initializeEnhancedSearch() {
    // Load filter options
    loadEnhancedFilterOptions();
    
    // Search input handler with 500ms delay
    const searchInput = document.getElementById('enhanced-course-search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(enhancedSearchTimeout);
            enhancedSearchTimeout = setTimeout(function() {
                enhancedCurrentPage = 1;
                performEnhancedSearch();
            }, 500);
        });
    }
    
    // Filter change handlers
    const filters = ['enhanced-category-filter', 'enhanced-provider-filter', 'enhanced-type-filter', 'enhanced-points-min', 'enhanced-points-max'];
    filters.forEach(filterId => {
        const element = document.getElementById(filterId);
        if (element) {
            element.addEventListener('change', function() {
                enhancedCurrentPage = 1;
                performEnhancedSearch();
            });
        }
    });
    
    // Search button
    const searchBtn = document.getElementById('enhanced-search-btn');
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            enhancedCurrentPage = 1;
            performEnhancedSearch();
        });
    }
    
    // Clear filters button
    const clearBtn = document.getElementById('enhanced-clear-filters-btn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            document.getElementById('enhanced-course-search-input').value = '';
            document.getElementById('enhanced-category-filter').value = '';
            document.getElementById('enhanced-provider-filter').value = '';
            document.getElementById('enhanced-type-filter').value = '';
            document.getElementById('enhanced-points-min').value = '';
            document.getElementById('enhanced-points-max').value = '';
            enhancedCurrentPage = 1;
            document.getElementById('enhanced-search-results').innerHTML = '<div class="loading-message" style="text-align: center; padding: 40px; color: #6b7280;"><div style="font-size: 2rem; margin-bottom: 10px;">📚</div><p>Start typing to search through our course library...</p></div>';
            document.getElementById('enhanced-search-pagination').style.display = 'none';
        });
    }
    
    // Toggle filters
    const toggleBtn = document.getElementById('toggle-enhanced-filters');
    const filtersDiv = document.querySelector('.search-filters');
    if (toggleBtn && filtersDiv) {
        toggleBtn.addEventListener('click', function() {
            const isVisible = filtersDiv.style.display !== 'none';
            filtersDiv.style.display = isVisible ? 'none' : 'block';
            toggleBtn.textContent = isVisible ? '⚙️ Advanced Filters' : '🔼 Hide Filters';
        });
    }
}

function loadEnhancedFilterOptions() {
    fetch(iipm_ajax.ajax_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'iipm_get_filter_options',
            nonce: iipm_ajax.cpd_nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Populate categories
            const categorySelect = document.getElementById('enhanced-category-filter');
            if (categorySelect) {
                data.data.categories.forEach(function(category) {
                    categorySelect.innerHTML += `<option value="${category.id}">${category.name}</option>`;
                });
            }
            
            // Populate providers
            const providerSelect = document.getElementById('enhanced-provider-filter');
            if (providerSelect) {
                data.data.providers.forEach(function(provider) {
                    providerSelect.innerHTML += `<option value="${provider}">${provider}</option>`;
                });
            }
            
            // Populate course types
            const typeSelect = document.getElementById('enhanced-type-filter');
            if (typeSelect) {
                Object.entries(data.data.course_types).forEach(function([key, value]) {
                    typeSelect.innerHTML += `<option value="${key}">${value}</option>`;
                });
            }
        }
    })
    .catch(error => {
        console.error('Error loading filter options:', error);
    });
}

function performEnhancedSearch() {
    const searchTerm = document.getElementById('enhanced-course-search-input').value;
    const categoryFilter = document.getElementById('enhanced-category-filter').value;
    const providerFilter = document.getElementById('enhanced-provider-filter').value;
    const typeFilter = document.getElementById('enhanced-type-filter').value;
    const pointsMin = document.getElementById('enhanced-points-min').value;
    const pointsMax = document.getElementById('enhanced-points-max').value;
    
    // Show loading
    document.getElementById('enhanced-search-results').innerHTML = '<div style="text-align: center; padding: 40px; color: #6b7280;"><div class="loading-spinner" style="display: inline-block; width: 30px; height: 30px; border: 3px solid #e5e7eb; border-top: 3px solid #ff6b35; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 15px;">Searching courses...</p></div>';
    
    fetch(iipm_ajax.ajax_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'iipm_enhanced_course_search',
            nonce: iipm_ajax.cpd_nonce,
            search_term: searchTerm,
            category_filter: categoryFilter,
            provider_filter: providerFilter,
            course_type: typeFilter,
            points_min: pointsMin || 0,
            points_max: pointsMax || 999,
            page: enhancedCurrentPage
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayEnhancedSearchResults(data.data);
        } else {
            document.getElementById('enhanced-search-results').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;"><p>Error searching courses. Please try again.</p></div>';
        }
    })
    .catch(error => {
        console.error('Error searching courses:', error);
        document.getElementById('enhanced-search-results').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;"><p>Error searching courses. Please try again.</p></div>';
    });
}

function displayEnhancedSearchResults(data) {
    const resultsContainer = document.getElementById('enhanced-search-results');
    const paginationContainer = document.getElementById('enhanced-search-pagination');
    
    if (data.courses.length === 0) {
        resultsContainer.innerHTML = '<div style="text-align: center; padding: 40px; color: #6b7280;"><div style="font-size: 2rem; margin-bottom: 10px;">🔍</div><p>No courses found matching your search criteria.</p></div>';
        paginationContainer.style.display = 'none';
        return;
    }
    
    // Display results summary
    let html = `<div style="margin-bottom: 20px; padding: 15px; background: #f8fafc; border-radius: 8px; text-align: center; border: 1px solid #e5e7eb;">
        <strong>${data.total_found}</strong> courses found (Page ${data.page} of ${data.total_pages})
    </div>`;
    
    html += '<div style="display: grid; gap: 15px;">';
    
    data.courses.forEach(function(course) {
        html += `
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                <h4 style="margin: 0; color: #374151; flex: 1; font-size: 16px; font-weight: 600;">${course.title}</h4>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <span style="background: #ff6b35; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">
                        ${course.cpd_points} CPD Points
                    </span>
                    <span style="background: #f3f4f6; color: #374151; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                        ${course.provider}
                    </span>
                </div>
            </div>
            
            ${course.description ? `<p style="margin: 10px 0; color: #6b7280; font-size: 14px;">${course.description}</p>` : ''}
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                <div style="display: flex; gap: 15px; align-items: center; font-size: 13px; color: #6b7280;">
                    <span>📚 ${course.category_name}</span>
                    <span>🎯 ${course.course_type}</span>
                    ${course.duration_minutes ? `<span>⏱️ ${Math.round(course.duration_minutes/60)}h</span>` : ''}
                    ${course.lia_code ? `<span>🔖 ${course.lia_code}</span>` : ''}
                </div>
                
                <button onclick="selectCourseFromLibrary(${course.id}, '${course.title.replace(/'/g, "\\'")}', ${course.cpd_points})" 
                        style="background: #10b981; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">
                    ✅ Select Course
                </button>
            </div>
        </div>`;
    });
    
    html += '</div>';
    resultsContainer.innerHTML = html;
    
    // Show pagination if needed
    if (data.total_pages > 1) {
        displayEnhancedPagination(data);
        paginationContainer.style.display = 'block';
    } else {
        paginationContainer.style.display = 'none';
    }
}

function displayEnhancedPagination(data) {
    let paginationHtml = '<div style="display: flex; justify-content: center; align-items: center; gap: 10px;">';
    
    // Previous button
    if (data.page > 1) {
        paginationHtml += `<button onclick="changeEnhancedPage(${data.page - 1})" style="background: #ff6b35; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer;">‹ Previous</button>`;
    }
    
    // Page numbers
    for (let i = Math.max(1, data.page - 2); i <= Math.min(data.total_pages, data.page + 2); i++) {
        const isActive = i === data.page;
        paginationHtml += `<button onclick="changeEnhancedPage(${i})" style="background: ${isActive ? '#374151' : '#f3f4f6'}; color: ${isActive ? 'white' : '#374151'}; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-weight: ${isActive ? 'bold' : 'normal'};">${i}</button>`;
    }
    
    // Next button
    if (data.page < data.total_pages) {
        paginationHtml += `<button onclick="changeEnhancedPage(${data.page + 1})" style="background: #ff6b35; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer;">Next ›</button>`;
    }
    
    paginationHtml += '</div>';
    document.getElementById('enhanced-search-pagination').innerHTML = paginationHtml;
}

function changeEnhancedPage(page) {
    enhancedCurrentPage = page;
    performEnhancedSearch();
}

function selectCourseFromLibrary(courseId, courseTitle, cpdPoints) {
    // Close the search modal
    closeCPDModal('cpdLibraryModal');
    
    // Show the CPD form with the selected course
    showCPDForm('course');
    
    // Pre-populate the course selection
    setTimeout(() => {
        const courseSelect = document.querySelector('#cpdCourseModal select[name="course_id"]');
        if (courseSelect) {
            // Add the selected course as an option if it doesn't exist
            const option = document.createElement('option');
            option.value = courseId;
            option.textContent = `${courseTitle} (${cpdPoints} points)`;
            option.selected = true;
            courseSelect.innerHTML = '<option value="">Select a course...</option>';
            courseSelect.appendChild(option);
        }
    }, 100);
    
    showAlert('success', `Selected: ${courseTitle}`);
}

function viewAllCPDRecords() {
    // Implementation for viewing all CPD records
    showAlert('info', 'CPD Records view coming soon!');
}

// CPD Return Functions
function handleSubmitReturn() {
    // First check if user can submit
    fetch(iipm_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'iipm_check_cpd_return_status',
            nonce: iipm_ajax.cpd_nonce,
            year: new Date().getFullYear()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.data.can_submit) {
                // Show confirmation modal
                document.getElementById('cpdReturnSubmitModal').classList.add('show');
            } else {
                showAlert('warning', 'CPD requirements not met or already submitted');
            }
        } else {
            showAlert('danger', 'Failed to check CPD status: ' + (data.data || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Failed to check CPD status');
    });
}

function confirmCPDReturnSubmission() {
    const confirmBtn = document.getElementById('confirmSubmitBtn');
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Submitting...';
    
    fetch(iipm_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'iipm_submit_cpd_return',
            nonce: iipm_ajax.cpd_nonce,
            year: new Date().getFullYear()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close submit modal
            closeCPDReturnModal('cancel');
            
            // Show success modal
            document.getElementById('cpdReturnConfirmationModal').classList.add('show');
        } else {
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Submit';
            showAlert('danger', 'Failed to submit CPD return: ' + (data.data || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Submit';
        showAlert('danger', 'Failed to submit CPD return');
    });
}

function closeCPDReturnModal(type) {
    const submitModal = document.getElementById('cpdReturnSubmitModal');
    const confirmationModal = document.getElementById('cpdReturnConfirmationModal');
    
    if (submitModal) {
        submitModal.classList.remove('show');
    }
    
    if (confirmationModal) {
        confirmationModal.classList.remove('show');
    }
    
    // Reset submit button
    const confirmBtn = document.getElementById('confirmSubmitBtn');
    if (confirmBtn) {
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Submit';
    }
    
    // If successful submission, reload page to show submitted state
    if (type === 'success') {
        setTimeout(() => {
            location.reload();
        }, 1000);
    }
}

function generateCPDReport() {
    // Redirect to training history page for now
    window.location.href = '<?php echo home_url('/training-history/'); ?>';
}

function downloadCPDCertificate(year) {
    // Get existing certificate and download
    const formData = new FormData();
    formData.append('action', 'iipm_get_member_certificates');
    formData.append('nonce', iipm_ajax.cpd_nonce);
    formData.append('user_id', <?php echo get_current_user_id(); ?>);
    
    fetch(iimp_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const certificate = data.data.certificates.find(cert => cert.cpd_year == year);
            if (certificate) {
                // Trigger download
                window.open(`${window.location.origin}/cpd-reports/?action=download_certificate&cert_id=${certificate.id}`, '_blank');
                showAlert('success', 'Certificate download started!');
            } else {
                showAlert('danger', 'Certificate not found for this year');
            }
        } else {
            showAlert('danger', 'Failed to retrieve certificates');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Failed to download certificate');
    });
}

function generateCPDCertificate(year) {
    // Generate new certificate
    const formData = new FormData();
    formData.append('action', 'iipm_generate_certificate');
    formData.append('nonce', iipm_ajax.cpd_nonce);
    formData.append('user_id', <?php echo get_current_user_id(); ?>);
    formData.append('year', year);
    
    // Show loading state
    showAlert('info', 'Generating your CPD certificate...');
    
    fetch(iipm_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Certificate generated successfully! Check your email for notification.');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert('danger', data.data || 'Failed to generate certificate');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Failed to generate certificate');
    });
}

function viewCourseDetails(courseId) {
    // Implementation for viewing course details
    showAlert('info', 'Course details view coming soon!');
}

// Form submissions
document.getElementById('cpdCourseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'iipm_log_cpd_course');
    formData.append('nonce', iipm_ajax.cpd_nonce);
    
    fetch(iipm_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeCPDModal('cpdCourseModal');
            showAlert('success', 'CPD course logged successfully!');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('danger', data.data || 'Failed to log CPD course');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Failed to log CPD course');
    });
});

document.getElementById('cpdExternalForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'iipm_submit_external_cpd');
    formData.append('nonce', iipm_ajax.cpd_nonce);
    
    fetch(iipm_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeCPDModal('cpdExternalModal');
            showAlert('success', 'External training submitted for approval!');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('danger', data.data || 'Failed to submit external training');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Failed to submit external training');
    });
});

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        const modalId = e.target.id;
        closeCPDModal(modalId);
    }
    
    // Close CPD return modals when clicking outside
    if (e.target.classList.contains('cpd-return-modal')) {
        closeCPDReturnModal('cancel');
    }
});


</script>

<?php get_footer(); ?>
