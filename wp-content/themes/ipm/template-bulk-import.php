<?php
/*
Template Name: Bulk Import Members
*/

// Check if user has permission - Updated to include corporate admins
if (!current_user_can('manage_iipm_members') && 
    !current_user_can('administrator') && 
    !current_user_can('bulk_import_members') &&
    !current_user_can('manage_organisation_members')) {
    wp_redirect(home_url('/login/'));
    exit;
}

// Check if user is organisation admin
$current_user = wp_get_current_user();
$is_org_admin = iipm_is_organisation_admin($current_user->ID);
$user_organisation = null;
$is_site_admin = current_user_can('manage_iipm_members') || current_user_can('administrator');

if ($is_org_admin) {
    global $wpdb;
    $user_organisation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_organisations WHERE admin_user_id = %d",
        $current_user->ID
    ));
}

// Enqueue required styles and scripts
wp_enqueue_style('iipm-portal-css', get_template_directory_uri() . '/css/iipm-portal.css', array(), '2.1.0');
wp_enqueue_style('iipm-enhanced-portal-css', get_template_directory_uri() . '/css/enhanced-iipm-portal.css', array(), '2.1.0');
wp_enqueue_script('jquery');
wp_enqueue_script('iipm-portal-js', get_template_directory_uri() . '/js/iipm-portal.js', array('jquery'), '2.1.0', true);

// Localize script with AJAX data
wp_localize_script('iipm-portal-js', 'iipm_ajax', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('iipm_portal_nonce'),
    'debug' => defined('WP_DEBUG') && WP_DEBUG
));

get_header();
?>

<main id="primary" class="site-main">
    <!-- Hero Section -->
    <section class="bulk-import-hero">
        <div class="container">
            <div class="hero-content">
                <h1>📊 Bulk Import Members</h1>
                <p>
                    <?php if ($is_org_admin && !$is_site_admin): ?>
                        Upload a CSV file to create multiple member accounts for <?php echo esc_html($user_organisation->name); ?>
                    <?php else: ?>
                        Upload a CSV file to create multiple member accounts at once
                    <?php endif; ?>
                </p>
                <?php if ($user_organisation): ?>
                    <div class="organisation-info">
                        <span class="org-badge">🏢 <?php echo esc_html($user_organisation->name); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="iipm-bulk-import-page">
        <div class="container">
            
            <!-- Import Instructions & Form -->
            <div class="import-content">
                <div class="import-instructions">
                    <h3>📋 Import Instructions</h3>
                    <div class="instruction-steps">
                        <div class="step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Prepare Your CSV File</h4>
                                <p>Your CSV file must include the following columns:</p>
                                <ul class="required-columns">
                                    <li><strong>first_name</strong> - Employee's first name</li>
                                    <li><strong>last_name</strong> - Employee's last name</li>
                                    <li><strong>email</strong> - Employee's email address</li>
                                    <li><strong>member_type</strong> - Set to "organisation"</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Optional Columns</h4>
                                <ul class="optional-columns">
                                    <li>personal_phone</li>
                                    <li>work_email</li>
                                    <li>work_phone</li>
                                    <li>employer_name</li>
                                    <li>professional_designation</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>Upload & Process</h4>
                                <p>Upload your CSV file and we'll:</p>
                                <ul class="process-steps">
                                    <li>✅ Validate all data</li>
                                    <li>📧 Send invitation emails</li>
                                    <li>👤 Users complete registration</li>
                                    <li>📊 Provide detailed results</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sample Download -->
                    <div class="sample-download">
                        <h4>📥 Download Sample CSV</h4>
                        <p>Use this template to format your data correctly:</p>
                        <a href="<?php echo get_template_directory_uri(); ?>/assets/sample-bulk-import.csv" 
                           class="btn btn-outline" download>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7,10 12,15 17,10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Download Sample CSV
                        </a>
                    </div>
                </div>
                
                <!-- Upload Form -->
                <div class="import-form-container">
                    <form id="iipm-bulk-import-form" class="bulk-import-form" enctype="multipart/form-data">
                        <?php wp_nonce_field('iipm_bulk_import_nonce', 'nonce'); ?>
                        
                        <?php if ($is_org_admin && !$is_site_admin): ?>
                            <!-- Corporate admin - auto-set organisation -->
                            <input type="hidden" name="organisation_id" value="<?php echo $user_organisation->id; ?>">
                            <div class="form-group">
                                <label>Organisation</label>
                                <div class="readonly-field">
                                    <span class="organisation-badge">
                                        🏢 <?php echo esc_html($user_organisation->name); ?>
                                    </span>
                                    <small class="field-note">Members will be imported to your organisation</small>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Site admin - can select organisation -->
                            <div class="form-group">
                                <label for="organisation_id">Select Organisation *</label>
                                <select name="organisation_id" id="organisation_id" required>
                                    <option value="">Choose organisation</option>
                                    <?php
                                    $organisations = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}test_iipm_organisations WHERE is_active = 1 ORDER BY name");
                                    foreach ($organisations as $org) {
                                        echo "<option value='{$org->id}'>" . esc_html($org->name) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="csv_file">CSV File *</label>
                            <div class="file-upload-area">
                                <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                                <div class="file-upload-display">
                                    <div class="upload-icon">📁</div>
                                    <div class="upload-text">
                                        <span class="file-text">Choose CSV file or drag and drop</span>
                                        <small>Maximum file size: 5MB</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Options -->
                        <div class="import-options" style="margin-top: 20px;">
                            <div class="option-group">
                                <label>
                                    <input type="checkbox" name="send_invitations" value="1">
                                    Send invitation emails to imported users
                                </label>
                            </div>
                            <div class="option-group">
                                <label>
                                    <input type="checkbox" name="skip_existing" value="1">
                                    Skip users with existing email addresses
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-large">
                                <span class="btn-text">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                        <polyline points="17,8 12,3 7,8"/>
                                        <line x1="12" y1="3" x2="12" y2="15"/>
                                    </svg>
                                    Start Import
                                </span>
                                <span class="btn-loading" style="display:none;">
                                    <svg class="spinner" width="20" height="20" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                                            <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                                            <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                                        </circle>
                                    </svg>
                                    Processing...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Progress Section -->
            <div id="import-progress" class="import-progress" style="display:none;">
                <div class="progress-container">
                    <h3>🔄 Processing Import</h3>
                    <div class="progress-bar">
                        <div id="progress-fill" class="progress-fill"></div>
                    </div>
                    <div id="progress-text" class="progress-text">Preparing...</div>
                    <div class="progress-details">
                        <div class="detail-item">
                            <span class="detail-label">Total Records:</span>
                            <span id="total-records" class="detail-value">-</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Processed:</span>
                            <span id="processed-records" class="detail-value">0</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Successful:</span>
                            <span id="successful-records" class="detail-value">0</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Failed:</span>
                            <span id="failed-records" class="detail-value">0</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Results Section -->
            <div id="import-results" class="import-results" style="display:none;">
                <div class="results-container">
                    <div class="results-header">
                        <h3>📊 Import Results</h3>
                        <div class="results-summary" id="results-summary">
                            <!-- Results will be populated here -->
                        </div>
                    </div>
                    
                    <div class="results-tabs">
                        <button class="tab-button active" data-tab="successful">
                            ✅ Successful (<span id="successful-count">0</span>)
                        </button>
                        <button class="tab-button" data-tab="failed">
                            ❌ Failed (<span id="failed-count">0</span>)
                        </button>
                        <button class="tab-button" data-tab="invitations">
                            📧 Invitations (<span id="invitations-count">0</span>)
                        </button>
                    </div>
                    
                    <div class="tab-content">
                        <div id="successful-tab" class="tab-pane active">
                            <div class="results-table-container">
                                <table class="results-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>User ID</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="successful-results">
                                        <!-- Successful results will be populated here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div id="failed-tab" class="tab-pane">
                            <div class="results-table-container">
                                <table class="results-table">
                                    <thead>
                                        <tr>
                                            <th>Row</th>
                                            <th>Email</th>
                                            <th>Error</th>
                                        </tr>
                                    </thead>
                                    <tbody id="failed-results">
                                        <!-- Failed results will be populated here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div id="invitations-tab" class="tab-pane">
                            <div class="results-table-container">
                                <table class="results-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Invitation Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="invitations-results">
                                        <!-- Invitation results will be populated here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="results-actions">
                        <button id="download-report" class="btn btn-outline">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7,10 12,15 17,10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Download Report
                        </button>
                        <button id="import-another" class="btn btn-secondary">
                            📁 Import Another File
                        </button>
                        <a href="<?php echo home_url('/member-portal/'); ?>" class="btn btn-primary">
                            🏠 Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Recent Imports -->
            <div class="recent-imports">
                <h3>📈 Recent Imports</h3>
                <div class="imports-table-container">
                    <table class="imports-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Filename</th>
                                <?php if ($is_site_admin): ?>
                                    <th>Organisation</th>
                                <?php endif; ?>
                                <th>Total</th>
                                <th>Successful</th>
                                <th>Failed</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Build query based on user permissions
                            if ($is_org_admin && !$is_site_admin) {
                                // Corporate admin - only their organisation's imports
                                $recent_imports = iipm_get_recent_imports($current_user->ID, $user_organisation->id);
                            } else {
                                // Site admin - all imports
                                $recent_imports = iipm_get_recent_imports($current_user->ID, null);
                            }
                            
                            if (empty($recent_imports)) {
                                $colspan = $is_site_admin ? 8 : 7;
                                echo "<tr><td colspan='{$colspan}' class='no-data'>No recent imports found</td></tr>";
                            } else {
                                foreach ($recent_imports as $import) {
                                    $status_class = strtolower($import->status);
                                    echo "<tr>";
                                    echo "<td>" . date('M j, Y g:i A', strtotime($import->created_at)) . "</td>";
                                    echo "<td>" . esc_html($import->filename) . "</td>";
                                    if ($is_site_admin) {
                                        echo "<td>" . esc_html($import->organisation_name ?? 'N/A') . "</td>";
                                    }
                                    echo "<td>" . $import->total_records . "</td>";
                                    echo "<td class='success-count'>" . $import->successful_imports . "</td>";
                                    echo "<td class='error-count'>" . $import->failed_imports . "</td>";
                                    echo "<td><span class='status-{$status_class}'>" . ucfirst($import->status) . "</span></td>";
                                    echo "<td>";
                                    if ($import->status === 'completed') {
                                        echo "<button class='btn-small view-details' data-import-id='{$import->id}'>View Details</button>";
                                    }
                                    if ($import->error_log) {
                                        echo " <button class='btn-small view-errors' data-errors='" . esc_attr($import->error_log) . "'>View Errors</button>";
                                    }
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Error Modal -->
<div id="error-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Import Errors</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <pre id="error-content"></pre>
        </div>
    </div>
</div>

<!-- Invitations Modal -->
<div id="invitations-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Organisation Invitations</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="invitations-content">
                <!-- Invitations will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
/* Testing Tools Styles */
.testing-tools {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.testing-tools h3 {
    margin: 0 0 15px 0;
    color: #856404;
    font-size: 1.2rem;
}

.testing-actions {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.testing-actions .form-group {
    margin-bottom: 0;
    min-width: 200px;
}

.btn-warning {
    background: #f39c12;
    color: white;
    border: none;
}

.btn-warning:hover {
    background: #e67e22;
}

.btn-warning:disabled {
    background: #bdc3c7;
    cursor: not-allowed;
}

.invitation-status {
    margin-top: 15px;
    padding: 15px;
    border-radius: 6px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
}

.invitation-status.success {
    background: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.invitation-status.error {
    background: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

/* Bulk Import Styles */
.bulk-import-hero {
    background: linear-gradient(135deg, #8b5a96 0%, #6b4c93 100%);
    color: white;
    padding: 60px 0;
    margin-bottom: 40px;
    padding-top: 120px;
    position: relative;
    overflow: hidden;
}

.bulk-import-hero::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("/placeholder.svg?height=400&width=1200") center / cover;
    opacity: 0.1;
    z-index: 1;
}

.hero-content {
    position: relative;
    z-index: 2;
    text-align: center;
}

.hero-content h1 {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 15px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.hero-content p {
    font-size: 1.2rem;
    opacity: 0.9;
    margin-bottom: 20px;
}

.organisation-info {
    margin-top: 20px;
}

.org-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 500;
    display: inline-block;
}

/* Import Content */
.import-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-bottom: 50px;
}

.import-instructions {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    border: 1px solid #e5e7eb;
}

.import-instructions h3 {
    margin: 0 0 25px 0;
    font-size: 1.4rem;
    font-weight: 700;
    color: #1f2937;
}

/* Instruction Steps */
.instruction-steps {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.step {
    display: flex;
    gap: 15px;
    align-items: flex-start;
}

.step-number {
    width: 30px;
    height: 30px;
    background: linear-gradient(135deg, #8b5a96, #6b4c93);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    flex-shrink: 0;
}

.step-content h4 {
    margin: 0 0 8px 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
}

.step-content p {
    margin: 0 0 10px 0;
    color: #6b7280;
    line-height: 1.5;
}

.required-columns,
.optional-columns,
.process-steps {
    list-style: none;
    padding: 0;
    margin: 0;
}

.required-columns li,
.optional-columns li,
.process-steps li {
    padding: 6px 0;
    color: #374151;
    font-size: 0.9rem;
}

.required-columns li strong {
    color: #8b5a96;
    font-family: monospace;
    background: #f3f4f6;
    padding: 2px 6px;
    border-radius: 4px;
}

.optional-columns li {
    font-family: monospace;
    background: #f8fafc;
    padding: 4px 8px;
    border-radius: 4px;
    margin-bottom: 4px;
    border-left: 3px solid #d1d5db;
}

/* Sample Download */
.sample-download {
    margin-top: 30px;
    padding-top: 25px;
    border-top: 1px solid #e5e7eb;
}

.sample-download h4 {
    margin: 0 0 10px 0;
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
}

.sample-download p {
    margin: 0 0 15px 0;
    color: #6b7280;
    font-size: 0.9rem;
}

/* Import Form */
.import-form-container {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    border: 1px solid #e5e7eb;
    height: fit-content;
}

.bulk-import-form .form-group {
    margin-bottom: 25px;
}

.bulk-import-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
    font-size: 0.95rem;
}

.bulk-import-form select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #d1d5db;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.bulk-import-form select:focus {
    outline: none;
    border-color: #8b5a96;
    box-shadow: 0 0 0 3px rgba(139, 90, 150, 0.1);
}

/* File Upload */
.file-upload-area {
    position: relative;
    border: 2px dashed #d1d5db;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    transition: all 0.3s ease;
    background: #f8fafc;
}

.file-upload-area:hover {
    border-color: #8b5a96;
    background: #f3f4f6;
}

.file-upload-area input[type="file"] {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.file-upload-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.upload-icon {
    font-size: 3rem;
    opacity: 0.6;
}

.upload-text {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.file-text {
    font-weight: 500;
    color: #374151;
}

.upload-text small {
    color: #6b7280;
    font-size: 0.85rem;
}

/* Checkbox Styles */
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    padding: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    transition: all 0.3s ease;
    margin-bottom: 15px;
}

.checkbox-label:hover {
    border-color: #8b5a96;
    background: #f8fafc;
}

.checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #d1d5db;
    border-radius: 4px;
    position: relative;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.checkbox-label input[type="checkbox"] {
    display: none;
}

.checkbox-label input[type="checkbox"]:checked + .checkbox-custom {
    background: #8b5a96;
    border-color: #8b5a96;
}

.checkbox-label input[type="checkbox"]:checked + .checkbox-custom::after {
    content: "✓";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 12px;
    font-weight: bold;
}

/* Progress Section */
.import-progress {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    border: 1px solid #e5e7eb;
    margin-bottom: 40px;
}

.progress-container h3 {
    margin: 0 0 20px 0;
    font-size: 1.4rem;
    font-weight: 700;
    color: #1f2937;
    text-align: center;
}

.progress-bar {
    width: 100%;
    height: 12px;
    background: #e5e7eb;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 15px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #059669);
    transition: width 0.3s ease;
    border-radius: 6px;
}

.progress-text {
    text-align: center;
    font-weight: 500;
    color: #374151;
    margin-bottom: 20px;
}

.progress-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 15px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.detail-label {
    font-weight: 500;
    color: #6b7280;
}

.detail-value {
    font-weight: 600;
    color: #1f2937;
}

/* Results Section */
.import-results {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    border: 1px solid #e5e7eb;
    margin-bottom: 40px;
}

.results-header h3 {
    margin: 0 0 20px 0;
    font-size: 1.4rem;
    font-weight: 700;
    color: #1f2937;
}

.results-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    border: 1px solid #e5e7eb;
}

.summary-card.success {
    background: #dcfce7;
    border-color: #bbf7d0;
}

.summary-card.error {
    background: #fee2e2;
    border-color: #fecaca;
}

.summary-card.info {
    background: #dbeafe;
    border-color: #bfdbfe;
}

.summary-number {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 5px;
}

.summary-label {
    font-size: 0.9rem;
    font-weight: 500;
    opacity: 0.8;
}

/* Tabs */
.results-tabs {
    display: flex;
    gap: 5px;
    margin-bottom: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.tab-button {
    padding: 12px 20px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-weight: 500;
    color: #6b7280;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
}

.tab-button.active {
    color: #8b5a96;
    border-bottom-color: #8b5a96;
}

.tab-button:hover {
    color: #8b5a96;
}

.tab-content {
    min-height: 300px;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

/* Tables */
.results-table-container,
.imports-table-container {
    overflow-x: auto;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.results-table,
.imports-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.results-table th,
.results-table td,
.imports-table th,
.imports-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #f3f4f6;
}

.results-table th,
.imports-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #374151;
}

.results-table tbody tr:hover,
.imports-table tbody tr:hover {
    background: #f8fafc;
}

.no-data {
    text-align: center;
    color: #6b7280;
    font-style: italic;
    padding: 40px;
}

.success-count {
    color: #059669;
    font-weight: 600;
}

.error-count {
    color: #dc2626;
    font-weight: 600;
}

/* Status badges */
.status-processing {
    background: #fef3c7;
    color: #92400e;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-completed {
    background: #dcfce7;
    color: #166534;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-failed {
    background: #fee2e2;
    color: #dc2626;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
}

.btn-primary {
    background: linear-gradient(135deg, #8b5a96 0%, #6b4c93 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(139, 90, 150, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(139, 90, 150, 0.4);
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
    transform: translateY(-2px);
}

.btn-outline {
    background: transparent;
    border: 2px solid #d1d5db;
    color: #374151;
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #8b5a96;
    color: #8b5a96;
}

.btn-large {
    padding: 16px 32px;
    font-size: 1.1rem;
}

.btn-small {
    padding: 6px 12px;
    font-size: 0.75rem;
    background: #8b5a96;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 5px;
}

.btn-small:hover {
    background: #6b4c93;
}

.btn.loading .btn-text {
    display: none;
}

.btn.loading .btn-loading {
    display: inline-flex !important;
}

/* Spinner Animation */
.spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Results Actions */
.results-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

/* Recent Imports */
.recent-imports {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    border: 1px solid #e5e7eb;
}

.recent-imports h3 {
    margin: 0 0 20px 0;
    font-size: 1.4rem;
    font-weight: 700;
    color: #1f2937;
}

/* Modal */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 12px;
    padding: 30px;
    max-width: 800px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6b7280;
}

.modal-close:hover {
    color: #374151;
}

#error-content {
    background: #f8fafc;
    padding: 15px;
    border-radius: 6px;
    overflow-x: auto;
    font-size: 12px;
    white-space: pre-wrap;
}

/* New styles for organisation admin features */
.readonly-field {
    padding: 12px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
}

.organisation-badge {
    display: inline-block;
    background: linear-gradient(135deg, #8b5a96, #6b4c93);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 14px;
}

.field-note {
    display: block;
    margin-top: 8px;
    color: #6c757d;
    font-size: 12px;
    font-style: italic;
}

/* Responsive Design */
@media (max-width: 768px) {
    .import-content {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .hero-content h1 {
        font-size: 2rem;
    }
    
    .progress-details {
        grid-template-columns: 1fr;
    }
    
    .results-summary {
        grid-template-columns: 1fr;
    }
    
    .results-actions {
        flex-direction: column;
    }
    
    .results-tabs {
        flex-wrap: wrap;
    }
    
    .tab-button {
        flex: 1;
        min-width: 120px;
    }
    
    .testing-actions {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    let importResults = null;
    var isOrgAdmin = <?php echo json_encode($is_org_admin && !$is_site_admin); ?>;
    var userOrganisationId = <?php echo json_encode($user_organisation ? $user_organisation->id : null); ?>;
    
    // File input change handler
    $('#csv_file').change(function() {
        const fileName = this.files[0] ? this.files[0].name : 'Choose CSV file or drag and drop';
        $('.file-text').text(fileName);
        
        // Validate file
        if (this.files[0]) {
            const file = this.files[0];
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            if (file.size > maxSize) {
                alert('File size too large. Maximum 5MB allowed.');
                this.value = '';
                $('.file-text').text('Choose CSV file or drag and drop');
                return;
            }
            
            if (!file.name.toLowerCase().endsWith('.csv')) {
                alert('Please select a CSV file.');
                this.value = '';
                $('.file-text').text('Choose CSV file or drag and drop');
                return;
            }
        }
    });
    
    // Form submission
    $('#iipm-bulk-import-form').submit(function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const formData = new FormData(this);
        formData.append('action', 'iipm_bulk_import');
        
        // For org admins, ensure organisation_id is set
        if (isOrgAdmin && userOrganisationId) {
            formData.set('organisation_id', userOrganisationId);
        }
        
        // Validate file
        const fileInput = $form.find('input[type="file"]')[0];
        if (!fileInput.files.length) {
            alert('Please select a CSV file');
            return;
        }
        
        // Show loading state
        $submitBtn.addClass('loading');
        $form.hide();
        $('#import-progress').show();
        
        // Start progress simulation
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += Math.random() * 10;
            if (progress > 90) progress = 90;
            $('#progress-fill').css('width', progress + '%');
            $('#progress-text').text('Processing... ' + Math.round(progress) + '%');
        }, 500);
        
        $.ajax({
            url: iipm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                clearInterval(progressInterval);
                $('#progress-fill').css('width', '100%');
                $('#progress-text').text('Complete!');
                
                setTimeout(() => {
                    $('#import-progress').hide();
                    
                    if (response.success) {
                        importResults = response.data;
                        displayResults(response.data);
                        $('#import-results').show();
                    } else {
                        alert('Error: ' + response.data);
                        $form.show();
                    }
                }, 1000);
            },
            error: function(xhr, status, error) {
                clearInterval(progressInterval);
                console.error('Import error:', error);
                alert('An error occurred during import. Please try again.');
                $('#import-progress').hide();
                $form.show();
            },
            complete: function() {
                $submitBtn.removeClass('loading');
            }
        });
    });
    
    // Display results function
    function displayResults(results) {
        // Update summary
        const summaryHtml = `
            <div class="summary-card success">
                <div class="summary-number">${results.successful}</div>
                <div class="summary-label">Successful</div>
            </div>
            <div class="summary-card error">
                <div class="summary-number">${results.failed}</div>
                <div class="summary-label">Failed</div>
            </div>
            <div class="summary-card info">
                <div class="summary-number">${results.total}</div>
                <div class="summary-label">Total Records</div>
            </div>
        `;
        $('#results-summary').html(summaryHtml);
        
        // Update tab counts
        $('#successful-count').text(results.successful);
        $('#failed-count').text(results.failed);
        $('#invitations-count').text(results.invitations_sent ? results.invitations_sent.length : 0);
        
        // Populate successful results
        if (results.successful_records && results.successful_records.length > 0) {
            let successfulHtml = '';
            results.successful_records.forEach(record => {
                successfulHtml += `
                    <tr>
                        <td>${record.name}</td>
                        <td>${record.email}</td>
                        <td>Invitation Sent</td>
                        <td><span class="status-completed">Pending Registration</span></td>
                    </tr>
                `;
            });
            $('#successful-results').html(successfulHtml);
        } else {
            $('#successful-results').html('<tr><td colspan="4" class="no-data">No successful records</td></tr>');
        }
        
        // Populate failed results
        if (results.errors && results.errors.length > 0) {
            let failedHtml = '';
            results.errors.forEach((error, index) => {
                failedHtml += `
                    <tr>
                        <td>${error.row || (index + 1)}</td>
                        <td>${error.email || 'N/A'}</td>
                        <td>${error.message}</td>
                    </tr>
                `;
            });
            $('#failed-results').html(failedHtml);
        } else {
            $('#failed-results').html('<tr><td colspan="3" class="no-data">No failed records</td></tr>');
        }
        
        // Populate invitations
        if (results.invitations_sent && results.invitations_sent.length > 0) {
            let invitationsHtml = '';
            results.invitations_sent.forEach(invitation => {
                invitationsHtml += `
                    <tr>
                        <td>${invitation.name}</td>
                        <td>${invitation.email}</td>
                        <td><span class="status-completed">Sent</span></td>
                        <td><button class="btn-small resend-invitation" data-email="${invitation.email}">Resend</button></td>
                    </tr>
                `;
            });
            $('#invitations-results').html(invitationsHtml);
        } else {
            $('#invitations-results').html('<tr><td colspan="4" class="no-data">No invitations sent</td></tr>');
        }
    }
    
    // Tab switching
    $('.tab-button').click(function() {
        const tab = $(this).data('tab');
        
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
        
        $('.tab-pane').removeClass('active');
        $('#' + tab + '-tab').addClass('active');
    });
    
    // Import another file
    $('#import-another').click(function() {
        $('#import-results').hide();
        $('#iipm-bulk-import-form').show();
        $('#iipm-bulk-import-form')[0].reset();
        $('.file-text').text('Choose CSV file or drag and drop');
    });
    
    // Download report
    $('#download-report').click(function() {
        if (!importResults) return;
        
        // Create CSV content
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "Type,Name,Email,Status,Error\n";
        
        // Add successful records
        if (importResults.successful_records) {
            importResults.successful_records.forEach(record => {
                csvContent += `Successful,"${record.name}","${record.email}",Invitation Sent,\n`;
            });
        }
        
        // Add failed records
        if (importResults.errors) {
            importResults.errors.forEach(error => {
                csvContent += `Failed,"${error.name || ''}","${error.email || ''}",Failed,"${error.message}"\n`;
            });
        }
        
        // Download file
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "import_report_" + new Date().toISOString().slice(0,10) + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
    
    // View errors modal
    $('.view-errors').click(function() {
        const errors = $(this).data('errors');
        $('#error-content').text(errors);
        $('#error-modal').show();
    });
    
    // Close modal
    $('.modal-close').click(function() {
        $(this).closest('.modal').hide();
    });
    
    // Close modal when clicking outside
    $('.modal').click(function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Resend invitation
    $(document).on('click', '.resend-invitation', function() {
        const email = $(this).data('email');
        const $btn = $(this);
        
        if (confirm('Resend invitation to ' + email + '?')) {
            $btn.text('Sending...').prop('disabled', true);
            
            $.ajax({
                url: iipm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'iipm_resend_invitation',
                    email: email,
                    nonce: iipm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Invitation resent successfully!');
                        $btn.text('Sent').addClass('status-completed');
                    } else {
                        alert('Error: ' + response.data);
                        $btn.text('Resend').prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Error sending invitation');
                    $btn.text('Resend').prop('disabled', false);
                }
            });
        }
    });
});
</script>

<?php get_footer(); ?>
