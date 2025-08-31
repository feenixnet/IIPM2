<?php
/*
Template Name: Organisation Management
*/

// Check if user has permission (IIPM Admin or Administrator only)
if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
    wp_redirect(home_url('/login/'));
    exit;
}

get_header();

// Enqueue and localize script for AJAX
wp_enqueue_script('jquery');
wp_add_inline_script('jquery', 'var iipm_ajax = ' . json_encode(array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('iipm_portal_nonce')
)) . ';', 'before');
?>

<main id="primary" class="iipm-organisation-manage-page">
    <!-- Hero Section -->
    <section class="org-management-hero" style=" padding-top: 160px; position: relative; z-index: 1;">
        <div class="hero-background">
            <div class="hero-overlay"></div>
        </div>
        <div class="hero-content">
            <div class="container">
                <h1 class="hero-title">🏢 Organisation Management</h1>
                <p class="hero-subtitle">Manage member organisations and their administrators</p>
                <div class="admin-nav">
                    <a href="<?php echo home_url('/member-portal/'); ?>" class="btn btn-outline">Dashboard</a>
                    <a href="<?php echo home_url('/admin-invitations/'); ?>" class="btn btn-outline">Invitations</a>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-outline">Logout</a>
                </div>
            </div>
        </div>
    </section>

    <div class="org-management-page">
        <div class="container">
            
            <!-- Quick Stats -->
            <div class="stats-grid">
                <?php
                global $wpdb;
                $total_orgs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_organisations WHERE is_active = 1");
                $orgs_with_admins = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_organisations WHERE admin_user_id IS NOT NULL AND is_active = 1");
                $total_org_members = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_members WHERE member_type = 'organisation'");
                $pending_setups = $total_orgs - $orgs_with_admins;
                ?>
                
                <div class="stat-card">
                    <div class="stat-icon">🏢</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $total_orgs; ?></div>
                        <div class="stat-label">Total Organisations</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $orgs_with_admins; ?></div>
                        <div class="stat-label">With Admins</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $total_org_members; ?></div>
                        <div class="stat-label">Organisation Members</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $pending_setups; ?></div>
                        <div class="stat-label">Pending Setup</div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <button id="add-organisation-btn" class="btn btn-primary btn-large">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="16"/>
                        <line x1="8" y1="12" x2="16" y2="12"/>
                    </svg>
                    Add New Organisation
                </button>
                
                <button id="bulk-setup-btn" class="btn btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-15"/>
                        <polyline points="7,10 12,15 17,10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Bulk Setup
                </button>
                
                <button id="export-orgs-btn" class="btn btn-outline">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7,10 12,15 17,10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Export Data
                </button>
            </div>
            
            <!-- Organisations Table -->
            <div class="organisations-section">
                <div class="section-header">
                    <h2>📋 Organisations</h2>
                    <div class="search-filter">
                        <input type="text" id="org-search" placeholder="Search organisations..." class="search-input">
                        <select id="status-filter" class="filter-select">
                            <option value="">All Status</option>
                            <option value="setup">Setup Complete</option>
                            <option value="pending">Pending Setup</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="organisations-table" id="organisations-table">
                        <thead>
                            <tr>
                                <th>Organisation</th>
                                <th>Contact Info</th>
                                <th>Admin Status</th>
                                <th>Members</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
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
                                ORDER BY o.created_at DESC
                            ");
                            
                            if (empty($organisations)) {
                                echo "<tr><td colspan='6' class='no-data'>No organisations found</td></tr>";
                            } else {
                                foreach ($organisations as $org) {
                                    $admin_status = $org->admin_user_id ? 'setup' : 'pending';
                                    $admin_status_text = $org->admin_user_id ? 'Setup Complete' : 'Pending Setup';
                                    $admin_status_class = $org->admin_user_id ? 'status-complete' : 'status-pending';
                                    
                                    echo "<tr data-org-id='{$org->id}' data-status='{$admin_status}'>";
                                    echo "<td>";
                                    echo "<div class='org-info'>";
                                    echo "<div class='org-name'>" . esc_html($org->name) . "</div>";
                                    echo "<div class='org-location'>" . esc_html($org->city . ', ' . $org->county) . "</div>";
                                    echo "</div>";
                                    echo "</td>";
                                    
                                    echo "<td>";
                                    echo "<div class='contact-info'>";
                                    echo "<div class='contact-email'>" . esc_html($org->contact_email) . "</div>";
                                    if ($org->contact_phone) {
                                        echo "<div class='contact-phone'>" . esc_html($org->contact_phone) . "</div>";
                                    }
                                    echo "</div>";
                                    echo "</td>";
                                    
                                    echo "<td>";
                                    echo "<span class='admin-status {$admin_status_class}'>{$admin_status_text}</span>";
                                    if ($org->admin_user_id) {
                                        echo "<div class='admin-details'>";
                                        echo "<small>" . esc_html($org->admin_name) . "</small><br>";
                                        echo "<small>" . esc_html($org->admin_email) . "</small>";
                                        echo "</div>";
                                    }
                                    echo "</td>";
                                    
                                    echo "<td class='member-count'>" . $org->member_count . "</td>";
                                    echo "<td>" . date('M j, Y', strtotime($org->created_at)) . "</td>";
                                    
                                    echo "<td>";
                                    echo "<div class='action-buttons-cell'>";
                                    echo "<button class='btn-small edit-org' data-org-id='{$org->id}'>Edit</button>";
                                    
                                    if (!$org->admin_user_id) {
                                        echo "<button class='btn-small setup-admin' data-org-id='{$org->id}'>Setup Admin</button>";
                                        echo "<button class='btn-small direct-assign-admin' data-org-id='{$org->id}' data-org-name='" . esc_attr($org->name) . "'>Direct Assign</button>";
                                    } else {
                                        echo "<button class='btn-small view-members' data-org-id='{$org->id}'>View Members</button>";
                                    }
                                    
                                    echo "<button class='btn-small btn-danger deactivate-org' data-org-id='{$org->id}'>Deactivate</button>";
                                    echo "<button class='btn-small btn-delete delete-org' data-org-id='{$org->id}' data-org-name='" . esc_attr($org->name) . "'>Delete</button>";
                                    echo "</div>";
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

<!-- Add/Edit Organisation Modal -->
<div id="organisation-modal" class="modal" style="display:none;">
    <div class="modal-content large">
        <div class="modal-header">
            <h3 id="modal-title">Add New Organisation</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="organisation-form">
                <input type="hidden" id="org-id" name="org_id">
                <?php wp_nonce_field('iipm_org_management_nonce', 'nonce'); ?>
                
                <div class="form-section">
                    <h4>🏢 Organisation Details</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="org-name">Organisation Name *</label>
                            <input type="text" id="org-name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="contact-email">Contact Email *</label>
                            <input type="email" id="contact-email" name="contact_email" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact-phone">Contact Phone</label>
                            <input type="tel" id="contact-phone" name="contact_phone">
                        </div>
                        
                        <div class="form-group">
                            <label for="billing-contact">Billing Contact</label>
                            <input type="text" id="billing-contact" name="billing_contact">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h4>📍 Address Information</h4>
                    <div class="form-group">
                        <label for="address-line1">Address Line 1</label>
                        <input type="text" id="address-line1" name="address_line1">
                    </div>
                    
                    <div class="form-group">
                        <label for="address-line2">Address Line 2</label>
                        <input type="text" id="address-line2" name="address_line2">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city">
                        </div>
                        
                        <div class="form-group">
                            <label for="county">County</label>
                            <input type="text" id="county" name="county">
                        </div>
                        
                        <div class="form-group">
                            <label for="eircode">Eircode</label>
                            <input type="text" id="eircode" name="eircode">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h4>👤 Administrator Setup</h4>
                    <div class="form-group">
                        <label for="admin-email">Admin Email Address</label>
                        <input type="email" id="admin-email" name="admin_email">
                        <small>If provided, an invitation will be sent to this email address</small>
                    </div>
                    
                    
                </div>
                
                <!-- Organization Options -->
                <div class="org-options">
                    <label>
                        <input type="checkbox" name="auto_approve" value="1">
                        Auto-approve member registrations
                    </label>
                    <label>
                        <input type="checkbox" name="allow_member_invite" value="1">
                        Allow members to invite others
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-text">Save Organisation</span>
                        <span class="btn-loading" style="display:none;">Saving...</span>
                    </button>
                    <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Setup Admin Modal -->
<div id="setup-admin-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Setup Organisation Administrator</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="setup-admin-form">
                <input type="hidden" id="setup-org-id" name="org_id">
                <?php wp_nonce_field('iipm_setup_admin_nonce', 'nonce'); ?>
                
                <div class="org-info-display">
                    <h4 id="setup-org-name">Organisation Name</h4>
                    <p id="setup-org-details">Organisation details will appear here</p>
                </div>
                
                <div class="form-group">
                    <label for="setup-admin-email">Administrator Email Address *</label>
                    <input type="email" id="setup-admin-email" name="admin_email" required>
                    <small>This person will become the organisation administrator</small>
                </div>
                
                <div class="form-group">
                    <label for="setup-admin-name">Administrator Name</label>
                    <input type="text" id="setup-admin-name" name="admin_name" placeholder="Optional: Admin's full name">
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="setup-send-invitation" name="send_invitation" checked>
                        <span class="checkbox-custom"></span>
                        Send invitation email to administrator
                    </label>
                </div>
                
                <div class="invitation-preview">
                    <h5>📧 Invitation Preview</h5>
                    <div class="preview-content">
                        <p><strong>Subject:</strong> IIPM Organisation Administrator Invitation</p>
                        <p><strong>Message:</strong> You have been invited to become the administrator for <span id="preview-org-name">[Organisation]</span> on the IIPM platform...</p>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-text">Setup Administrator</span>
                        <span class="btn-loading" style="display:none;">Setting up...</span>
                    </button>
                    <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Direct Admin Assignment Modal -->
<div id="direct-assign-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Direct Admin Assignment</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="local-dev-notice">
                <div class="notice-icon">⚠️</div>
                <div class="notice-content">
                    <strong>Local Development Mode</strong>
                    <p>This feature allows you to directly assign an existing user as an organisation admin without sending emails. Use this for testing in local environments.</p>
                </div>
            </div>
            
            <form id="direct-assign-form">
                <input type="hidden" id="direct-assign-org-id" name="org_id">
                <?php wp_nonce_field('iipm_portal_nonce', 'nonce'); ?>
                
                <div class="org-info-display">
                    <h4 id="direct-assign-org-name">Organisation Name</h4>
                </div>
                
                <div class="form-group">
                    <label for="direct-assign-user">Select Existing User *</label>
                    <select id="direct-assign-user" name="user_id" class="user-select" required>
                        <option value="">Loading users...</option>
                    </select>
                    <small>This user will be assigned as the organisation administrator</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-text">Assign Administrator</span>
                        <span class="btn-loading" style="display:none;">Assigning...</span>
                    </button>
                    <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Members Modal -->
<div id="view-members-modal" class="modal" style="display:none;">
    <div class="modal-content large">
        <div class="modal-header">
            <h3 id="members-modal-title">Organisation Members</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="members-actions">
                <button id="export-members-btn" class="btn btn-outline btn-small">Export Members</button>
                <button id="bulk-import-members-btn" class="btn btn-secondary btn-small">Bulk Import</button>
            </div>
            
            <div class="table-container">
                <table class="members-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="members-table-body">
                        <!-- Members will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
/* Organisation Management Styles */
.org-management-hero {
    background: linear-gradient(135deg, #8b5a96 0%, #6b4c93 100%);
    color: white;
    padding: 60px 0;
    margin-bottom: 40px;
    position: relative;
    overflow: hidden;
}

.org-management-hero::before {
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

.hero-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 15px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.hero-subtitle {
    font-size: 1.2rem;
    opacity: 0.9;
    margin-bottom: 30px;
}

.admin-nav {
    display: flex;
    gap: 15px;
    justify-content: center;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-icon {
    font-size: 2.5rem;
    opacity: 0.8;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.stat-label {
    color: #6b7280;
    font-size: 0.875rem;
    font-weight: 500;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 15px;
    margin-bottom: 40px;
    flex-wrap: wrap;
}

/* Organisations Section */
.organisations-section {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    margin-bottom:30px
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 20px;
}

.section-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}

.search-filter {
    display: flex;
    gap: 12px;
    align-items: center;
}

.search-input {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    min-width: 200px;
}

.filter-select {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    background: white;
}

/* Table Styles */
.table-container {
    overflow-x: auto;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.organisations-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.organisations-table th,
.organisations-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #f3f4f6;
}

.organisations-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.organisations-table tbody tr:hover {
    background: #f8fafc;
}

.org-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.org-name {
    font-weight: 600;
    color: #1f2937;
}

.org-location {
    font-size: 0.875rem;
    color: #6b7280;
}

.contact-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.contact-email {
    font-weight: 500;
    color: #1f2937;
}

.contact-phone {
    font-size: 0.875rem;
    color: #6b7280;
}

.admin-status {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-complete {
    background: #dcfce7;
    color: #166534;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.admin-details {
    margin-top: 8px;
}

.admin-details small {
    color: #6b7280;
    font-size: 0.75rem;
}

.member-count {
    font-weight: 600;
    color: #1f2937;
    text-align: center;
}

.action-buttons-cell {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.btn-small {
    padding: 4px 8px;
    font-size: 0.75rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-small:not(.btn-danger) {
    background: #8b5a96;
    color: white;
}

.btn-small:not(.btn-danger):hover {
    background: #6b4c93;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

/* Enhanced Modal Styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: none !important; /* Force it to be hidden initially */
    align-items: center;
    justify-content: center;
    z-index: 10000;
    backdrop-filter: blur(4px);
    animation: modalFadeIn 0.3s ease;
}

.modal.show {
    display: flex !important; /* Force it to be visible when .show is added */
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.modal-content {
    background: white;
    border-radius: 20px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    max-width: 600px;
    width: 95%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    animation: modalSlideIn 0.3s ease;
}

.modal-content.large {
    max-width: 900px;
    width: 95%;
}

@keyframes modalSlideIn {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    background: linear-gradient(135deg, #8b5a96 0%, #6b4c93 100%);
    color: white;
    padding: 25px 30px;
    border-radius: 20px 20px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 1;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
}

.modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.modal-body {
    padding: 40px 30px;
}

/* Enhanced Form Styles */
.form-section {
    margin-bottom: 35px;
    padding: 25px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    position: relative;
}

.form-section:last-child {
    margin-bottom: 0;
}

.form-section h4 {
    margin: 0 0 25px 0;
    font-size: 1.2rem;
    font-weight: 700;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 10px;
    border-bottom: 2px solid #8b5a96;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
    font-size: 0.95rem;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: white;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #8b5a96;
    box-shadow: 0 0 0 4px rgba(139, 90, 150, 0.1);
    transform: translateY(-1px);
}

.form-group small {
    display: block;
    margin-top: 6px;
    font-size: 0.85rem;
    color: #6b7280;
    font-style: italic;
}

/* Enhanced Checkbox Styles */
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    font-size: 0.95rem;
    padding: 15px;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    transition: all 0.3s ease;
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
    background: white;
}

.checkbox-label input[type="checkbox"] {
    display: none;
}

.checkbox-label input[type="checkbox"]:checked + .checkbox-custom {
    background: #8b5a96;
    border-color: #8b5a96;
    transform: scale(1.1);
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

/* Form Actions */
.form-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 25px;
    border-top: 1px solid #e5e7eb;
}

.btn {
    padding: 14px 28px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    transition: all 0.3s ease;
    font-size: 1rem;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.btn:hover::before {
    left: 100%;
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

.btn-large {
    padding: 16px 32px;
    font-size: 1.1rem;
}

.btn.loading {
    pointer-events: none;
    opacity: 0.7;
}

.btn.loading .btn-text {
    display: none;
}

.btn.loading .btn-loading {
    display: inline;
}

.btn-loading {
    display: none;
}

/* Organisation Info Display */
.org-info-display {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    border: 1px solid #e5e7eb;
}

.org-info-display h4 {
    margin: 0 0 10px 0;
    color: #1f2937;
    font-size: 1.2rem;
}

.org-info-display p {
    margin: 0;
    color: #6b7280;
    font-size: 0.95rem;
}

/* Invitation Preview */
.invitation-preview {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    padding: 20px;
    border-radius: 12px;
    border: 1px solid #bae6fd;
    margin-top: 25px;
}

.invitation-preview h5 {
    margin: 0 0 15px 0;
    color: #0c4a6e;
    font-size: 1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.preview-content p {
    margin: 0 0 10px 0;
    font-size: 0.85rem;
    color: #0c4a6e;
}

.preview-content p:last-child {
    margin-bottom: 0;
}

/* Members Actions */
.members-actions {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    justify-content: flex-end;
}

/* No Data State */
.no-data {
    text-align: center;
    color: #6b7280;
    font-style: italic;
    padding: 40px;
}

/* Local Development Notice */
.local-dev-notice {
    background: #fffbeb;
    border: 1px solid #fef3c7;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 25px;
    display: flex;
    gap: 16px;
    align-items: flex-start;
}

.notice-icon {
    font-size: 24px;
    line-height: 1;
}

.notice-content strong {
    display: block;
    margin-bottom: 6px;
    color: #92400e;
}

.notice-content p {
    margin: 0;
    font-size: 0.9rem;
    color: #78350f;
}

/* User Select Dropdown */
.user-select {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .admin-nav {
        flex-direction: column;
        align-items: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .section-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-filter {
        flex-direction: column;
    }
    
    .search-input {
        min-width: auto;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .action-buttons-cell {
        flex-direction: column;
    }
    
    .members-actions {
        flex-direction: column;
    }
    
    .modal-content {
        margin: 20px;
        width: calc(100% - 40px);
    }
    
    .modal-body {
        padding: 25px 20px;
    }
    
    .form-actions {
        flex-direction: column;
    }
}

.btn-delete {
    background: #dc2626 !important;
    color: white !important;
    margin-left: 4px;
}

.btn-delete:hover {
    background: #b91c1c !important;
}

/* Delete Confirmation Modal */
.delete-confirmation-modal .modal-header {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
}

.delete-warning {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 16px;
    margin: 20px 0;
}

.delete-warning-icon {
    color: #dc2626;
    font-size: 24px;
    margin-right: 12px;
}

.delete-details {
    background: #f9fafb;
    border-radius: 8px;
    padding: 16px;
    margin: 16px 0;
}

.delete-details h5 {
    margin: 0 0 12px 0;
    color: #374151;
    font-weight: 600;
}

.delete-details ul {
    margin: 0;
    padding-left: 20px;
}

.delete-details li {
    color: #6b7280;
    margin-bottom: 4px;
}

/* Organisation Name Validation Styles */
.form-group input.duplicate-name {
    border-color: #f59e0b !important;
    background-color: #fffbeb !important;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1) !important;
}

.form-group input.valid-name {
    border-color: #10b981 !important;
    background-color: #f0fdf4 !important;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1) !important;
}

.validation-message {
    margin-top: 8px;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.validation-message.error {
    background: #fef3c7;
    border: 1px solid #fbbf24;
    color: #92400e;
}

.validation-message.success {
    background: #dcfce7;
    border: 1px solid #10b981;
    color: #166534;
}

/* Enhanced form validation states */
.form-group input:focus.duplicate-name {
    transform: none;
}

.form-group input:focus.valid-name {
    transform: translateY(-1px);
}
</style>

<script>
jQuery(document).ready(function($) {
    let currentOrgId = null;
    
    // Add Organisation
    $('#add-organisation-btn').click(function() {
        $('#modal-title').text('Add New Organisation');
        $('#organisation-form')[0].reset();
        $('#org-id').val('');
        $('#organisation-modal').addClass('show');
        
        // Clear any existing validation states
        $('#org-name').removeClass('error duplicate-name');
        $('#org-name').next('.validation-message').remove();
    });
    
    // Edit Organisation
    $(document).on('click', '.edit-org', function() {
        const orgId = $(this).data('org-id');
        loadOrganisationData(orgId);
    });
    
    // Setup Admin
    $(document).on('click', '.setup-admin', function() {
        const orgId = $(this).data('org-id');
        const orgName = $(this).closest('tr').find('.org-name').text();
        const orgDetails = $(this).closest('tr').find('.org-location').text();
        
        $('#setup-org-id').val(orgId);
        $('#setup-org-name').text(orgName);
        $('#setup-org-details').text(orgDetails);
        $('#preview-org-name').text(orgName);
        $('#setup-admin-modal').addClass('show');
    });
    
    // Direct Assign Admin
    $(document).on('click', '.direct-assign-admin', function() {
        console.log('Direct assign clicked');
        const orgId = $(this).data('org-id');
        const orgName = $(this).data('org-name');
        
        $('#direct-assign-org-id').val(orgId);
        $('#direct-assign-org-name').text(orgName);
        $('#direct-assign-modal').addClass('show');
        
        // Load users for the dropdown
        loadUsersForAssignment();
    });

    // Function to load users for assignment
    function loadUsersForAssignment() {
        console.log('Loading users for assignment...');
        $.ajax({
            url: iipm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'iipm_get_all_users_for_assignment',
                nonce: iipm_ajax.nonce
            },
            success: function(response) {
                console.log('Users response:', response);
                if (response.success) {
                    const users = response.data;
                    let options = '<option value="">Select a user...</option>';
                    
                    users.forEach(function(user) {
                        options += `<option value="${user.id}">${user.text}</option>`;
                    });
                    
                    $('#direct-assign-user').html(options);
                } else {
                    $('#direct-assign-user').html('<option value="">Error loading users</option>');
                    console.error('Error loading users:', response.data);
                }
            },
            error: function(xhr, status, error) {
                $('#direct-assign-user').html('<option value="">Error loading users</option>');
                console.error('AJAX error:', error);
                console.error('Response:', xhr.responseText);
            }
        });
    }
    
    // View Members
    $(document).on('click', '.view-members', function() {
        const orgId = $(this).data('org-id');
        const orgName = $(this).closest('tr').find('.org-name').text();
        
        $('#members-modal-title').text(orgName + ' - Members');
        loadOrganisationMembers(orgId);
        $('#view-members-modal').addClass('show');
    });
    
    // Organisation Form Submit
    $('#organisation-form').submit(function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const formData = new FormData(this);
        formData.append('action', 'iipm_save_organisation');
        
        $submitBtn.addClass('loading');
        
        $.ajax({
            url: iipm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showSuccessModal('Organisation saved successfully!');
                    $('#organisation-modal').removeClass('show');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    // Check if it's a duplicate name error
                    if (response.data && response.data.includes('organisation with this name already exists')) {
                        showDuplicateNameErrorModal(response.data);
                    } else {
                        showErrorModal('Save Failed', response.data || 'Unknown error occurred');
                    }
                }
            },
            error: function(xhr, status, error) {
                showErrorModal('Connection Error', 'Unable to save organisation. Please check your connection and try again.');
            },
            complete: function() {
                $submitBtn.removeClass('loading');
            }
        });
    });
    
    // Real-time organisation name validation
    let nameCheckTimeout;
    $('#org-name').on('input', function() {
        const $input = $(this);
        const name = $input.val().trim();
        const orgId = $('#org-id').val();
        
        // Clear existing timeout
        clearTimeout(nameCheckTimeout);
        
        // Remove existing validation messages
        $input.removeClass('error duplicate-name valid-name');
        $input.next('.validation-message').remove();
        
        if (name.length < 2) {
            return; // Don't validate very short names
        }
        
        // Debounce the validation check
        nameCheckTimeout = setTimeout(function() {
            $.ajax({
                url: iipm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'iipm_check_organisation_name',
                    name: name,
                    org_id: orgId,
                    nonce: iipm_ajax.nonce
                },
                success: function(response) {
                    if (response.data && response.data.exists) {
                        $input.addClass('duplicate-name');
                        $input.after('<div class="validation-message error">⚠️ Organisation name already exists: "' + response.data.existing_name + '"</div>');
                    } else {
                        $input.addClass('valid-name');
                        $input.after('<div class="validation-message success">✅ Organisation name is available</div>');
                    }
                },
                error: function() {
                    // Silently fail for real-time validation
                }
            });
        }, 500); // 500ms delay
    });
    
    // Setup Admin Form Submit
    $('#setup-admin-form').submit(function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const formData = $form.serialize() + '&action=iipm_setup_organisation_admin';
        
        $submitBtn.addClass('loading');
        
        $.ajax({
            url: iipm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('Administrator setup successfully! Invitation sent.');
                    $('#setup-admin-modal').removeClass('show');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                $submitBtn.removeClass('loading');
            }
        });
    });
    
    // Direct Admin Assignment Form Submit - FIXED TYPO HERE
    $('#direct-assign-form').submit(function(e) {
        e.preventDefault();
        
        console.log('Direct assign form submitted');
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const formData = $form.serialize() + '&action=iipm_direct_admin_assignment'; // FIXED: was 'iimp_direct_admin_assignment'
        
        console.log('Form data:', formData);
        
        $submitBtn.addClass('loading');
        
        $.ajax({
            url: iipm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Assignment response:', response);
                if (response.success) {
                    alert('Administrator assigned successfully!');
                    $('#direct-assign-modal').removeClass('show');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Assignment error:', error);
                console.error('Response:', xhr.responseText);
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                $submitBtn.removeClass('loading');
            }
        });
    });
    
    // Search and Filter
    $('#org-search').on('input', function() {
        filterOrganisations();
    });
    
    $('#status-filter').change(function() {
        filterOrganisations();
    });
    
    function filterOrganisations() {
        const searchTerm = $('#org-search').val().toLowerCase();
        const statusFilter = $('#status-filter').val();
        
        $('#organisations-table tbody tr').each(function() {
            const $row = $(this);
            const orgName = $row.find('.org-name').text().toLowerCase();
            const orgLocation = $row.find('.org-location').text().toLowerCase();
            const contactEmail = $row.find('.contact-email').text().toLowerCase();
            const status = $row.data('status');
            
            const matchesSearch = orgName.includes(searchTerm) || 
                                orgLocation.includes(searchTerm) || 
                                contactEmail.includes(searchTerm);
            
            const matchesStatus = !statusFilter || status === statusFilter;
            
            if (matchesSearch && matchesStatus) {
                $row.show();
            } else {
                $row.hide();
            }
        });
    }
    
    // Load Organisation Data for Editing
    function loadOrganisationData(orgId) {
        $.ajax({
            url: iipm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'iipm_get_organisation',
                org_id: orgId,
                nonce: iipm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const org = response.data;
                    
                    $('#modal-title').text('Edit Organisation');
                    $('#org-id').val(org.id);
                    $('#org-name').val(org.name);
                    $('#contact-email').val(org.contact_email);
                    $('#contact-phone').val(org.contact_phone);
                    $('#billing-contact').val(org.billing_contact);
                    $('#address-line1').val(org.address_line1);
                    $('#address-line2').val(org.address_line2);
                    $('#city').val(org.city);
                    $('#county').val(org.county);
                    $('#eircode').val(org.eircode);
                    
                    $('#organisation-modal').addClass('show');
                } else {
                    alert('Error loading organisation data: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred while loading organisation data.');
            }
        });
    }
    
    // Load Organisation Members
    function loadOrganisationMembers(orgId) {
        $.ajax({
            url: iipm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'iipm_get_organisation_members',
                org_id: orgId,
                nonce: iipm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const members = response.data;
                    let html = '';
                    
                    if (members.length === 0) {
                        html = '<tr><td colspan="6" class="no-data">No members found</td></tr>';
                    } else {
                        members.forEach(function(member) {
                            html += '<tr>';
                            html += '<td>' + member.display_name + '</td>';
                            html += '<td>' + member.user_email + '</td>';
                            html += '<td><span class="status-' + member.membership_status + '">' + member.membership_status + '</span></td>';
                            html += '<td>' + (member.created_at ? new Date(member.created_at).toLocaleDateString() : 'N/A') + '</td>';
                            html += '<td>' + (member.last_login ? new Date(member.last_login).toLocaleDateString() : 'Never') + '</td>';
                            html += '<td><button class="btn-small edit-member" data-user-id="' + member.user_id + '">Edit</button></td>';
                            html += '</tr>';
                        });
                    }
                    
                    $('#members-table-body').html(html);
                } else {
                    alert('Error loading members: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred while loading members.');
            }
        });
    }
    
    // Close modals
    $('.modal-close').click(function() {
        $(this).closest('.modal').removeClass('show');
    });
    
    $('.modal').click(function(e) {
        if (e.target === this) {
            $(this).removeClass('show');
        }
    });
    
    // Deactivate Organisation
    $(document).on('click', '.deactivate-org', function() {
        const orgId = $(this).data('org-id');
        const orgName = $(this).closest('tr').find('.org-name').text();
        
        if (confirm('Are you sure you want to deactivate "' + orgName + '"? This will affect all associated members.')) {
            $.ajax({
                url: iipm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'iipm_deactivate_organisation',
                    org_id: orgId,
                    nonce: iipm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Organisation deactivated successfully.');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred.');
                }
            });
        }
    });
    
    // Export functionality
    $('#export-orgs-btn').click(function() {
        window.location.href = iipm_ajax.ajax_url + '?action=iipm_export_organisations&nonce=' + iipm_ajax.nonce;
    });
    
    // Modal helper functions
    function showSuccessModal(message) {
        const modalHtml = `
            <div id="success-modal" class="modal show">
                <div class="modal-content">
                    <div class="modal-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <h3>✅ Success</h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div style="text-align: center; padding: 20px;">
                            <div style="font-size: 3rem; margin-bottom: 16px;">🎉</div>
                            <p style="font-size: 1.1rem; margin: 0;">${message}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#success-modal').remove();
        $('body').append(modalHtml);
        
        // Auto-close after 3 seconds
        setTimeout(function() {
            $('#success-modal').remove();
        }, 3000);
    }
    
    function showErrorModal(title, message) {
        const modalHtml = `
            <div id="error-modal" class="modal show">
                <div class="modal-content">
                    <div class="modal-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                        <h3>❌ ${title}</h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div style="padding: 20px;">
                            <div style="display: flex; align-items: flex-start; gap: 16px;">
                                <div style="font-size: 2rem; color: #ef4444;">⚠️</div>
                                <div>
                                    <p style="margin: 0; font-size: 1rem; line-height: 1.5;">${message}</p>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-primary modal-close">OK</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#error-modal').remove();
        $('body').append(modalHtml);
    }
    
    function showDuplicateNameErrorModal(message) {
        const modalHtml = `
            <div id="duplicate-error-modal" class="modal show">
                <div class="modal-content">
                    <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <h3>⚠️ Duplicate Organisation Name</h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div style="padding: 20px;">
                            <div style="display: flex; align-items: flex-start; gap: 16px; margin-bottom: 20px;">
                                <div style="font-size: 2rem; color: #f59e0b;">🏢</div>
                                <div>
                                    <p style="margin: 0 0 12px 0; font-size: 1rem; line-height: 1.5; font-weight: 500;">${message}</p>
                                    <p style="margin: 0; font-size: 0.9rem; color: #6b7280; line-height: 1.4;">
                                        Organisation names must be unique to avoid confusion. Please choose a different name for this organisation.
                                    </p>
                                </div>
                            </div>
                            
                            <div style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                                <h5 style="margin: 0 0 8px 0; color: #92400e; font-size: 0.9rem; font-weight: 600;">💡 Suggestions:</h5>
                                <ul style="margin: 0; padding-left: 16px; color: #92400e; font-size: 0.85rem;">
                                    <li>Add a location suffix (e.g., "Company Name - Dublin")</li>
                                    <li>Include a department (e.g., "Company Name - HR Division")</li>
                                    <li>Add a descriptive identifier (e.g., "Company Name Ltd.")</li>
                                </ul>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-primary modal-close">Try Again</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#duplicate-error-modal').remove();
        $('body').append(modalHtml);
        
        // Focus back on the organisation name field when modal is closed
        $(document).on('click', '#duplicate-error-modal .modal-close', function() {
            $('#duplicate-error-modal').remove();
            $('#org-name').focus().select();
        });
    }
    
    // Handle modal close events for all modal types
    $(document).on('click', '.modal-close', function() {
        $(this).closest('.modal').remove();
    });
    
    $(document).on('click', '.modal', function(e) {
        if (e.target === this) {
            $(this).remove();
        }
    });

    // Debug logging
    console.log('IIPM Organisation Management loaded');
    console.log('AJAX URL:', iipm_ajax.ajax_url);
    console.log('Nonce:', iipm_ajax.nonce);

    
    // Delete Organisation
    $(document).on('click', '.delete-org', function() {
        const orgId = $(this).data('org-id');
        const orgName = $(this).data('org-name');
        
        // Show delete confirmation modal
        showDeleteConfirmationModal(orgId, orgName);
    });

    function showDeleteConfirmationModal(orgId, orgName) {
        // Get organization details first
        $.ajax({
            url: iipm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'iipm_get_organisation_delete_info',
                org_id: orgId,
                nonce: iipm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    const modalHtml = `
                        <div id="delete-confirmation-modal" class="modal delete-confirmation-modal show">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3>⚠️ Delete Organisation</h3>
                                    <button class="modal-close">&times;</button>
                                </div>
                                <div class="modal-body">
                                    <div class="delete-warning">
                                        <div style="display: flex; align-items: flex-start;">
                                            <span class="delete-warning-icon">⚠️</span>
                                            <div>
                                                <strong>Warning: This action cannot be undone!</strong>
                                                <p>You are about to permanently delete the organisation "${orgName}" and all associated data.</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="delete-details">
                                        <h5>📊 What will be deleted:</h5>
                                        <ul>
                                            <li><strong>${data.member_count}</strong> organisation members</li>
                                            <li><strong>${data.import_count}</strong> bulk import records</li>
                                            <li><strong>${data.invitation_count}</strong> pending invitations</li>
                                            <li>All organisation profile data</li>
                                            <li>All related activity logs</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="delete-details">
                                        <h5>👥 Affected Members:</h5>
                                        ${data.members.length > 0 ? 
                                            '<ul>' + data.members.map(member => 
                                                `<li>${member.display_name} (${member.user_email})</li>`
                                            ).join('') + '</ul>' : 
                                            '<p>No members will be affected.</p>'
                                        }
                                    </div>
                                    
                                    <div style="margin: 20px 0; padding: 16px; background: #fef3c7; border-radius: 8px; border: 1px solid #fbbf24;">
                                        <p><strong>⚠️ Alternative:</strong> Consider using "Deactivate" instead to preserve data while making the organisation inactive.</p>
                                    </div>
                                    
                                    <div style="margin: 20px 0;">
                                        <label style="font-weight: 600; margin-bottom: 8px; display: block;">
                                            Type "${orgName}" to confirm deletion:
                                        </label>
                                        <input type="text" id="delete-confirmation-input" placeholder="Type organisation name here..." 
                                               style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px;">
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="button" id="confirm-delete-btn" class="btn btn-danger" disabled data-org-id="${orgId}">
                                            <span class="btn-text">🗑️ Delete Organisation</span>
                                            <span class="btn-loading" style="display:none;">Deleting...</span>
                                        </button>
                                        <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Remove existing modal if any
                    $('#delete-confirmation-modal').remove();
                    
                    // Add modal to body
                    $('body').append(modalHtml);
                    
                    // Handle confirmation input
                    $('#delete-confirmation-input').on('input', function() {
                        const inputValue = $(this).val().trim();
                        const confirmBtn = $('#confirm-delete-btn');
                        
                        console.log('Input value:', inputValue, 'Expected:', orgName); // Debug log
                        
                        if (inputValue === orgName) {
                            confirmBtn.prop('disabled', false);
                            console.log('Button enabled'); // Debug log
                        } else {
                            confirmBtn.prop('disabled', true);
                            console.log('Button disabled'); // Debug log
                        }
                    });
                    
                    // Handle delete confirmation
                    $('#confirm-delete-btn').click(function() {
                        console.log('Delete button clicked!'); // Debug log
                        const $btn = $(this);
                        const orgId = $btn.data('org-id');
                        
                        console.log('Org ID:', orgId); // Debug log
                        console.log('IIPM Ajax:', iipm_ajax); // Debug log
                        
                        if (!iipm_ajax || !iipm_ajax.ajax_url || !iipm_ajax.nonce) {
                            alert('AJAX configuration error. Please refresh the page and try again.');
                            return;
                        }
                        
                        $btn.addClass('loading');
                        
                        $.ajax({
                            url: iipm_ajax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'iipm_delete_organisation',
                                org_id: orgId,
                                nonce: iipm_ajax.nonce
                            },
                            success: function(response) {
                                console.log('Delete response:', response); // Debug log
                                if (response.success) {
                                    alert('Organisation deleted successfully.');
                                    $('#delete-confirmation-modal').remove();
                                    location.reload();
                                } else {
                                    alert('Error: ' + response.data);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.log('Delete error:', xhr, status, error); // Debug log
                                alert('An error occurred while deleting the organisation.');
                            },
                            complete: function() {
                                $btn.removeClass('loading');
                            }
                        });
                    });
                    
                } else {
                    alert('Error loading organisation details: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred while loading organisation details.');
            }
        });
    }
});
</script>

<?php get_footer(); ?>
