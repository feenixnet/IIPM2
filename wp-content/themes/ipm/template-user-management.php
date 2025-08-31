<?php
/**
 * Template Name: User Management
 * 
 * User Management page for Site Admins and Organization Admins
 */

 // Storm worked here...

// Security check - only allow admins and org admins
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

$current_user = wp_get_current_user();
$is_site_admin = current_user_can('administrator');
$user_organisation = null;
$is_org_admin = in_array('iipm_corporate_admin', $current_user->roles) || current_user_can('manage_organisation_members');

if (!$is_site_admin && !$is_org_admin) {
    wp_redirect(home_url('/dashboard/'));
    exit;
}

if ($is_org_admin) {
    global $wpdb;
    $user_organisation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_organisations WHERE admin_user_id = %d",
        $current_user->ID
    ));
}

// Get the active tab from URL, default to 'users'
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'users';

get_header();
?>

<main class="user-management-page" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; position: relative; padding-top:120px !important; padding-bottom: 60px">
    <div class="container" style="position: relative; z-index: 2;">
        
        <!-- Page Header -->
        <div class="page-header" style="text-align: center; margin-bottom: 40px;">
            <div>
                <h1 style="color: white; font-size: 2.5rem; margin-bottom: 10px;">User Management</h1>
                <p style="color: rgba(255,255,255,0.9); font-size: 1.1rem;">
                    <?php if ($is_site_admin): ?>
                        Manage all users and invitations in the system
                    <?php else: ?>
                        Manage users and invitations in your organization
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-navigation" style="margin-bottom: 30px;">
            <div style="display: flex; justify-content: center; gap: 20px;">
                <a href="?tab=users" class="tab-button <?php echo $active_tab === 'users' ? 'active' : ''; ?>" 
                   style="padding: 12px 24px; background: <?php echo $active_tab === 'users' ? '#f8a135' : '#6b4c93'; ?>; color: white; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    <span style="margin-right: 8px;">👥</span>
                    Users
                </a>
                <a href="?tab=invitations" class="tab-button <?php echo $active_tab === 'invitations' ? 'active' : ''; ?>"
                   style="padding: 12px 24px; background: <?php echo $active_tab === 'invitations' ? '#f8a135' : '#6b4c93'; ?>; color: white; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    <span style="margin-right: 8px;">✉️</span>
                    Invitations
                </a>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
            <?php if ($active_tab === 'users'): ?>
                <!-- Users Management Content -->
                <div class="users-content">
                    <!-- Action Buttons -->
                    <div style="display: flex; justify-content: flex-end; gap: 20px; margin-bottom: 30px;">
                        <?php if ($is_site_admin): ?>
                        <button id="sync-login-data" style="display: inline-flex; align-items: center; padding: 12px 24px; background: #10B981; color: white; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                            <span style="margin-right: 8px;">🔄</span>
                            Sync Login Data
                        </button>
                        <?php endif; ?>
                        <a href="<?php echo home_url('/bulk-import/'); ?>" style="display: inline-flex; align-items: center; padding: 12px 24px; background: #8B5CF6; color: white; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                            <span style="margin-right: 8px;">📥</span>
                            Bulk Member Upload
                        </a>
                    </div>

                    <!-- Search and Filter Section -->
                    <div class="search-filter-section" style="margin-bottom: 30px; display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                        <div class="search-box" style="flex: 1; min-width: 250px;">
                            <input type="text" id="user-search" placeholder="Search users by name or email..." 
                                   style="width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                        </div>
                        
                        <div class="filter-controls" style="display: flex; gap: 15px; align-items: center;">
                            <select id="role-filter" style="padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                                <option value="">All Roles</option>
                                <option value="iipm_member">IIPM Member</option>
                                <option value="iipm_corporate_admin">Corporate Admin</option>
                                <option value="iipm_council_member">Council Member</option>
                                <option value="administrator">Administrator</option>
                            </select>
                            
                            <select id="status-filter" style="padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                            
                            <button id="refresh-users" style="padding: 12px 20px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                                Refresh
                            </button>
                        </div>
                    </div>

                    <!-- Users Table -->
                    <div class="users-table-container" style="overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 12px;">
                        <table id="users-table" style="width: 100%; border-collapse: collapse;">
                            <thead style="background: #f8fafc;">
                                <tr>
                                    <th style="padding: 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Name</th>
                                    <th style="padding: 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Email</th>
                                    <th style="padding: 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Role</th>
                                    <th style="padding: 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Status</th>
                                    <th style="padding: 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Organization</th>
                                    <th style="padding: 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Last Login</th>
                                    <th style="padding: 16px; text-align: center; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="users-table-body">
                                <!-- Users will be loaded here via AJAX -->
                                <tr>
                                    <td colspan="7" style="padding: 40px; text-align: center; color: #6b7280;">
                                        <div class="loading-spinner" style="display: inline-block; width: 20px; height: 20px; border: 2px solid #e5e7eb; border-top: 2px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                                        <span style="margin-left: 10px;">Loading users...</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div id="pagination-container" style="margin-top: 20px; display: flex; justify-content: center; align-items: center; gap: 10px;">
                        <!-- Pagination will be loaded here -->
                    </div>
                </div>
            <?php else: ?>
                <!-- Invitations Content -->
                <div class="invitations-content">
                    <div class="invitation-form-container">
                        <h2>Send Individual Invitation</h2>
                        <form id="send-invitation-form" class="invitation-form">
                            <?php wp_nonce_field('iipm_user_management_nonce', 'nonce'); ?>
                            
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" name="email" id="email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="type">Invitation Type *</label>
                                <select name="type" id="type" required>
                                    <option value="individual">Individual Member</option>
                                    <option value="bulk">Organisation Member</option>
                                </select>
                            </div>
                            
                            <?php if ($is_org_admin && !$is_site_admin): ?>
                                <!-- Corporate admin - auto-set organisation -->
                                <input type="hidden" name="organisation_id" value="<?php echo $user_organisation->id; ?>">
                                <div class="form-group">
                                    <label>Organisation</label>
                                    <div class="readonly-field">
                                        <span class="organisation-badge">
                                            🏢 <?php echo esc_html($user_organisation->name); ?>
                                        </span>
                                        <small class="field-note">Invitations will be sent for your organisation</small>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Site admin - can select organisation -->
                                <div class="form-group organisation-field" style="display:none;">
                                    <label for="organisation_id">Organisation</label>
                                    <select name="organisation_id" id="organisation_id">
                                        <option value="">Select Organisation</option>
                                        <?php
                                        global $wpdb;
                                        $organisations = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}test_iipm_organisations WHERE is_active = 1 ORDER BY name");
                                        foreach ($organisations as $org) {
                                            echo "<option value='{$org->id}'>" . esc_html($org->name) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <button type="submit" class="btn btn-primary">Send Invitation</button>
                        </form>
                        
                        <div id="invitation-result" style="display:none;"></div>
                    </div>
                    
                    <div class="recent-invitations">
                        <h2>Recent Invitations</h2>
                        <div class="table-container">
                            <table class="invitations-table">
                                <thead>
                                    <tr>
                                        <th>Email</th>
                                        <th>Type</th>
                                        <?php if ($is_site_admin): ?>
                                            <th>Organisation</th>
                                        <?php endif; ?>
                                        <th>Status</th>
                                        <th>Sent Date</th>
                                        <th>Expires</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="invitations-table-body">
                                    <tr>
                                        <td colspan="<?php echo $is_site_admin ? '7' : '6'; ?>" style="text-align: center; padding: 20px;">
                                            <div class="loading-spinner"></div>
                                            Loading invitations...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Edit User Modal -->
<div id="edit-user-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: white; border-radius: 12px; padding: 30px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #374151;">Edit User</h3>
            <button id="close-edit-modal" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280;">&times;</button>
        </div>
        
        <form id="edit-user-form">
            <input type="hidden" id="edit-user-id" name="user_id">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #374151;">First Name</label>
                    <input type="text" id="edit-first-name" name="first_name" required 
                           style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #374151;">Last Name</label>
                    <input type="text" id="edit-last-name" name="last_name" required 
                           style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #374151;">Email</label>
                <input type="email" id="edit-email" name="email" required 
                       style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #374151;">Password</label>
                <input type="password" id="edit-password" name="password" 
                       style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                       placeholder="Leave blank to keep current password">
                <small style="display: block; margin-top: 4px; color: #6b7280;">Only enter a new password if you want to change it</small>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #374151;">Role</label>
                    <select id="edit-role" name="role" 
                            style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="iipm_member">IIPM Member</option>
                        <option value="iipm_corporate_admin">Corporate Admin</option>
                        <option value="iipm_council_member">Council Member</option>
                        <?php if ($is_site_admin): ?>
                        <option value="administrator">Administrator</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #374151;">Status</label>
                    <select id="edit-status" name="status" 
                            style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" id="cancel-edit" 
                        style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    Cancel
                </button>
                <button type="submit" 
                        style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-user-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: white; border-radius: 12px; padding: 30px; width: 90%; max-width: 400px;">
        <div class="modal-header" style="text-align: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #dc2626;">Delete User</h3>
        </div>
        
        <p style="text-align: center; margin-bottom: 20px; color: #374151;">
            Are you sure you want to delete this user? This action cannot be undone.
        </p>
        
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button id="cancel-delete" 
                    style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer;">
                Cancel
            </button>
            <button id="confirm-delete" 
                    style="padding: 10px 20px; background: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer;">
                Delete User
            </button>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.user-row:hover {
    background-color: #f8fafc !important;
}

.action-btn {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
    margin: 0 2px;
    transition: all 0.2s ease;
}

.edit-btn {
    background: #3b82f6;
    color: white;
}

.edit-btn:hover {
    background: #2563eb;
}

.delete-btn {
    background: #dc2626;
    color: white;
}

.delete-btn:hover {
    background: #ef4444;
}

/* Invitation Form Styles */
.invitation-form {
    max-width: 600px;
    margin: 0 auto 40px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #374151;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.organisation-badge {
    display: inline-block;
    padding: 8px 16px;
    background: #f3f4f6;
    border-radius: 6px;
    font-size: 14px;
    color: #374151;
}

.field-note {
    display: block;
    margin-top: 4px;
    color: #6b7280;
    font-size: 12px;
}

.btn-primary {
    display: inline-block;
    padding: 12px 24px;
    background: #6b4c93;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: #f8a135;
}

.invitations-table {
    width: 100%;
    border-collapse: collapse;
}

.invitations-table th,
.invitations-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.invitations-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #374151;
}

.status-pending {
    display: inline-block;
    padding: 4px 8px;
    background: #fef3c7;
    color: #92400e;
    border-radius: 4px;
    font-size: 12px;
}

.status-used {
    display: inline-block;
    padding: 4px 8px;
    background: #d1fae5;
    color: #065f46;
    border-radius: 4px;
    font-size: 12px;
}

.status-expired {
    display: inline-block;
    padding: 4px 8px;
    background: #fee2e2;
    color: #991b1b;
    border-radius: 4px;
    font-size: 12px;
}

.btn-small {
    padding: 4px 8px;
    background: #6b4c93;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-small:hover {
    background: #f8a135;
}

#invitation-result {
    margin-top: 20px;
    padding: 12px;
    border-radius: 6px;
}

#invitation-result .error {
    background: #fee2e2;
    color: #991b1b;
}

#invitation-result .success {
    background: #d1fae5;
    color: #065f46;
}

.no-data {
    text-align: center;
    color: #6b7280;
    padding: 40px !important;
}
</style>

<script>
jQuery(document).ready(function($) {
    let currentPage = 1;
    let currentSearch = '';
    let currentRoleFilter = '';
    let currentStatusFilter = '';
    let userToDelete = null;

    // Load users on page load
    loadUsers();

    // Search functionality
    $('#user-search').on('input', function() {
        currentSearch = $(this).val();
        currentPage = 1;
        loadUsers();
    });

    // Filter functionality
    $('#role-filter, #status-filter').on('change', function() {
        currentRoleFilter = $('#role-filter').val();
        currentStatusFilter = $('#status-filter').val();
        currentPage = 1;
        loadUsers();
    });

    // Refresh button
    $('#refresh-users').on('click', function() {
        loadUsers();
    });

    // Sync login data button
    $('#sync-login-data').on('click', function() {
        const button = $(this);
        const originalText = button.html();
        
        button.html('<span style="margin-right: 8px;">⏳</span>Syncing...').prop('disabled', true);
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'iipm_sync_last_login_data',
                nonce: '<?php echo wp_create_nonce('iipm_user_management_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    loadUsers(); // Refresh the user list
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Error: Failed to sync login data. Please try again.');
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });

    // Load users function
    function loadUsers() {
        $('#users-table-body').html(`
            <tr>
                <td colspan="7" style="padding: 40px; text-align: center; color: #6b7280;">
                    <div class="loading-spinner" style="display: inline-block; width: 20px; height: 20px; border: 2px solid #e5e7eb; border-top: 2px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    <span style="margin-left: 10px;">Loading users...</span>
                </td>
            </tr>
        `);

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'iipm_get_users',
                nonce: '<?php echo wp_create_nonce('iipm_user_management_nonce'); ?>',
                page: currentPage,
                search: currentSearch,
                role_filter: currentRoleFilter,
                status_filter: currentStatusFilter
            },
            success: function(response) {
                if (response.success) {
                    displayUsers(response.data.users);
                    displayPagination(response.data.pagination);
                } else {
                    $('#users-table-body').html(`
                        <tr>
                            <td colspan="7" style="padding: 40px; text-align: center; color: #dc2626;">
                                Error: ${response.data}
                            </td>
                        </tr>
                    `);
                }
            },
            error: function() {
                $('#users-table-body').html(`
                    <tr>
                        <td colspan="7" style="padding: 40px; text-align: center; color: #dc2626;">
                            Error loading users. Please try again.
                        </td>
                    </tr>
                `);
            }
        });
    }

    // Display users in table
    function displayUsers(users) {
        if (users.length === 0) {
            $('#users-table-body').html(`
                <tr>
                    <td colspan="7" style="padding: 40px; text-align: center; color: #6b7280;">
                        No users found.
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        users.forEach(function(user) {
            html += `
                <tr class="user-row" style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 16px;">
                        <div style="font-weight: 500; color: #374151;">${user.display_name}</div>
                    </td>
                    <td style="padding: 16px; color: #6b7280;">${user.user_email}</td>
                    <td style="padding: 16px;">
                        <span class="role-badge">${user.role_display}</span>
                    </td>
                    <td style="padding: 16px;">
                        <span class="status-badge status-${user.membership_status}">${user.membership_status}</span>
                    </td>
                    <td style="padding: 16px; color: #6b7280;">${user.organisation_name || 'N/A'}</td>
                    <td style="padding: 16px; color: #6b7280;">${user.last_login || 'Never'}</td>
                    <td style="padding: 16px; text-align: center;">
                        <button class="action-btn edit-btn" onclick="editUser(${user.ID})">Edit</button>
                        <button class="action-btn delete-btn" onclick="deleteUser(${user.ID}, '${user.display_name}')">Delete</button>
                    </td>
                </tr>
            `;
        });
        $('#users-table-body').html(html);
    }

    // Display pagination
    function displayPagination(pagination) {
        if (pagination.total_pages <= 1) {
            $('#pagination-container').html('');
            return;
        }

        let html = '';
        
        // Previous button
        if (pagination.current_page > 1) {
            html += `<button onclick="changePage(${pagination.current_page - 1})" style="padding: 8px 12px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer;">Previous</button>`;
        }

        // Page numbers
        for (let i = Math.max(1, pagination.current_page - 2); i <= Math.min(pagination.total_pages, pagination.current_page + 2); i++) {
            const isActive = i === pagination.current_page;
            html += `<button onclick="changePage(${i})" style="padding: 8px 12px; border: 1px solid #d1d5db; background: ${isActive ? '#667eea' : 'white'}; color: ${isActive ? 'white' : '#374151'}; border-radius: 4px; cursor: pointer;">${i}</button>`;
        }

        // Next button
        if (pagination.current_page < pagination.total_pages) {
            html += `<button onclick="changePage(${pagination.current_page + 1})" style="padding: 8px 12px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer;">Next</button>`;
        }

        $('#pagination-container').html(html);
    }

    // Change page function (global)
    window.changePage = function(page) {
        currentPage = page;
        loadUsers();
    };

    // Edit user function (global)
    window.editUser = function(userId) {
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'iipm_get_user_details',
                nonce: '<?php echo wp_create_nonce('iipm_user_management_nonce'); ?>',
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    const user = response.data;
                    $('#edit-user-id').val(user.ID);
                    $('#edit-first-name').val(user.first_name);
                    $('#edit-last-name').val(user.last_name);
                    $('#edit-email').val(user.user_email);
                    $('#edit-role').val(user.roles[0]);
                    $('#edit-status').val(user.membership_status);
                    $('#edit-user-modal').css('display', 'flex');
                } else {
                    alert('Error loading user details: ' + response.data);
                }
            }
        });
    };

    // Delete user function (global)
    window.deleteUser = function(userId, userName) {
        userToDelete = userId;
        $('#delete-user-modal p').html(`Are you sure you want to delete <strong>${userName}</strong>? This action cannot be undone.`);
        $('#delete-user-modal').css('display', 'flex');
    };

    // Modal close handlers
    $('#close-edit-modal, #cancel-edit').on('click', function() {
        $('#edit-user-modal').hide();
    });

    $('#cancel-delete').on('click', function() {
        $('#delete-user-modal').hide();
        userToDelete = null;
    });

    // Edit user form submission
    $('#edit-user-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'iipm_update_user',
            nonce: '<?php echo wp_create_nonce('iipm_user_management_nonce'); ?>',
            user_id: $('#edit-user-id').val(),
            first_name: $('#edit-first-name').val(),
            last_name: $('#edit-last-name').val(),
            email: $('#edit-email').val(),
            role: $('#edit-role').val(),
            status: $('#edit-status').val()
        };

        // Only include password if it's not empty
        const password = $('#edit-password').val();
        if (password) {
            formData.password = password;
        }
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#edit-user-modal').hide();
                    $('#edit-password').val(''); // Clear password field
                    loadUsers();
                    alert('User updated successfully!');
                } else {
                    alert('Error updating user: ' + response.data);
                }
            }
        });
    });

    // Confirm delete
    $('#confirm-delete').on('click', function() {
        if (!userToDelete) return;

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'iipm_delete_user',
                nonce: '<?php echo wp_create_nonce('iipm_user_management_nonce'); ?>',
                user_id: userToDelete
            },
            success: function(response) {
                if (response.success) {
                    $('#delete-user-modal').hide();
                    loadUsers();
                    alert('User deleted successfully!');
                } else {
                    alert('Error deleting user: ' + response.data);
                }
                userToDelete = null;
            }
        });
    });

    // Close modals when clicking outside
    $(document).on('click', function(e) {
        if ($(e.target).is('#edit-user-modal')) {
            $('#edit-user-modal').hide();
        }
        if ($(e.target).is('#delete-user-modal')) {
            $('#delete-user-modal').hide();
            userToDelete = null;
        }
    });

    // Invitation form handling
    var isOrgAdmin = <?php echo json_encode($is_org_admin && !$is_site_admin); ?>;
    var userOrganisationId = <?php echo json_encode($user_organisation ? $user_organisation->id : null); ?>;
    
    // Show/hide organisation field for site admins only
    if (!isOrgAdmin) {
        $('#type').change(function() {
            if ($(this).val() === 'bulk') {
                $('.organisation-field').show();
            } else {
                $('.organisation-field').hide();
            }
        });
    }
    
    // Function to load invitations
    function loadInvitations() {
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'iipm_load_invitations',
                nonce: '<?php echo wp_create_nonce('iipm_user_management_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var html = '';
                    if (response.data.total === 0) {
                        html = '<tr><td colspan="<?php echo $is_site_admin ? '7' : '6'; ?>" class="no-data">No invitations found</td></tr>';
                    } else {
                        response.data.pending.forEach(function(invitation) {
                            html += formatInvitationRow(invitation, 'Pending');
                        });
                        response.data.used.forEach(function(invitation) {
                            html += formatInvitationRow(invitation, 'Used');
                        });
                        response.data.expired.forEach(function(invitation) {
                            html += formatInvitationRow(invitation, 'Expired');
                        });
                    }
                    $('#invitations-table-body').html(html);
                } else {
                    $('#invitations-table-body').html('<tr><td colspan="<?php echo $is_site_admin ? '7' : '6'; ?>" class="error">Error loading invitations</td></tr>');
                }
            },
            error: function() {
                $('#invitations-table-body').html('<tr><td colspan="<?php echo $is_site_admin ? '7' : '6'; ?>" class="error">Failed to load invitations</td></tr>');
            }
        });
    }

    // Function to format invitation row
    function formatInvitationRow(invitation, status) {
        var row = '<tr>';
        row += '<td>' + invitation.email + '</td>';
        row += '<td>' + invitation.invitation_type + '</td>';
        <?php if ($is_site_admin): ?>
        row += '<td>' + (invitation.org_name || 'Individual') + '</td>';
        <?php endif; ?>
        row += '<td><span class="status-' + status.toLowerCase() + '">' + status + '</span></td>';
        row += '<td>' + formatDate(invitation.created_at) + '</td>';
        row += '<td>' + formatDate(invitation.expires_at) + '</td>';
        row += '<td>';
        if (status === 'Pending') {
            row += '<button class="btn-small resend-invitation" data-email="' + invitation.email + '">Resend</button>';
        }
        row += '</td>';
        row += '</tr>';
        return row;
    }

    // Helper function to format dates
    function formatDate(dateStr) {
        var date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { 
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: 'numeric',
            hour12: true
        });
    }

    // Load invitations on page load if we're on the invitations tab
    if ($('.invitations-content').length) {
        loadInvitations();
    }

    // Handle invitation form submission
    $('#send-invitation-form').submit(function(e) {
        e.preventDefault();
        
        // Validate organization selection for bulk invitations
        if ($('#type').val() === 'bulk' && !isOrgAdmin) {
            var selectedOrg = $('#organisation_id').val();
            if (!selectedOrg) {
                $('#invitation-result').html('<div class="error">Error: Please select an organization for organization member invitations.</div>').show();
                return false;
            }
        }
        
        var formData = $(this).serialize() + '&action=iipm_send_invitation';
        
        // For org admins, ensure organisation_id is set
        if (isOrgAdmin && userOrganisationId) {
            formData += '&organisation_id=' + userOrganisationId;
        }
        
        // Show loading state
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.text();
        submitBtn.text('Sending...').prop('disabled', true);
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Handle both string and object responses
                    const message = typeof response.data === 'string' ? response.data : (response.data.message || 'Success');
                    $('#invitation-result').html('<div class="success">' + message + '</div>').show();
                    $('#send-invitation-form')[0].reset();
                    $('.organisation-field').hide();
                    
                    // Reload invitations instead of page
                    loadInvitations();
                } else {
                    // Handle both string and object error messages
                    const errorMsg = typeof response.data === 'string' ? response.data : (response.data.message || 'Unknown error occurred');
                    $('#invitation-result').html('<div class="error">Error: ' + errorMsg + '</div>').show();
                }
            },
            error: function() {
                $('#invitation-result').html('<div class="error">Error: Failed to send invitation. Please try again.</div>').show();
            },
            complete: function() {
                submitBtn.text(originalText).prop('disabled', false);
            }
        });
    });
    
    // Handle resend invitation using event delegation
    $(document).on('click', '.resend-invitation', function(e) {
        e.preventDefault();
        console.log('resend-invitation clicked');
        var button = $(this);
        var email = button.data('email');
        var originalText = button.text();
        
        button.text('Resending...').prop('disabled', true);
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'iipm_resend_invitation',
                email: email,
                nonce: '<?php echo wp_create_nonce('iipm_user_management_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Invitation resent successfully!');
                    // Reload invitations to refresh the list
                    loadInvitations();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', {xhr, status, error});
                alert('Failed to resend invitation. Please try again.');
            },
            complete: function() {
                button.text(originalText).prop('disabled', false);
            }
        });
    });
});
</script>

<?php get_footer(); ?>
