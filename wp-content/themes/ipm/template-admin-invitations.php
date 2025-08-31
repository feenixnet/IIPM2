<?php
/*
Template Name: Admin Invitations
*/

// Check if user has permission - Updated to include corporate admins
if (!current_user_can('manage_iipm_members') && 
    !current_user_can('administrator') && 
    !current_user_can('manage_organisation_members')) {
    wp_redirect(home_url('/login/'));
    exit;
}

// Check if user is organisation admin and get their organisation
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

get_header();
?>

<main id="primary" class="site-main">
    <!-- Hero Section with Background Image -->
    <section class="hero-section admin-hero">
        <div class="hero-background">
            <!-- Background image removed -->
            <div class="hero-overlay"></div>
        </div>
        <div class="hero-content">
            <div class="container">
                <h1 class="hero-title">
                    <?php if ($is_org_admin && !$is_site_admin): ?>
                        <?php echo esc_html($user_organisation->name); ?> - Member Invitations
                    <?php else: ?>
                        IIPM Member Invitations
                    <?php endif; ?>
                </h1>
                <p class="hero-subtitle">
                    <?php if ($is_org_admin && !$is_site_admin): ?>
                        Manage member invitations for your organisation
                    <?php else: ?>
                        Manage member invitations and registrations
                    <?php endif; ?>
                </p>
                <div class="admin-nav">
                    <a href="<?php echo home_url('/dashboard/'); ?>" class="btn btn-outline">Dashboard</a>
                    <?php if ($is_org_admin && !$is_site_admin): ?>
                        <a href="<?php echo home_url('/bulk-import/'); ?>" class="btn btn-outline">Bulk Import</a>
                    <?php endif; ?>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-outline">Logout</a>
                </div>
            </div>
        </div>
    </section>

    <div class="admin-invitations-page">
        <div class="container">
            <div class="invitation-form-container">
                <h2>Send Individual Invitation</h2>
                <form id="send-invitation-form" class="invitation-form">
                    <?php wp_nonce_field('iipm_invitation_nonce', 'nonce'); ?>
                    
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
                
                <!-- Debug Information -->
                <div id="debug-info" class="debug-section" style="display:none;">
                    <h3>Debug Information</h3>
                    <div id="debug-content"></div>
                </div>
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
                        <tbody>
                            <?php
                            // Build query based on user permissions
                            if ($is_org_admin && !$is_site_admin) {
                                // Corporate admin - only their organisation's invitations
                                $invitations = $wpdb->get_results($wpdb->prepare("
                                    SELECT i.*, o.name as org_name 
                                    FROM {$wpdb->prefix}test_iipm_invitations i
                                    LEFT JOIN {$wpdb->prefix}test_iipm_organisations o ON i.organisation_id = o.id
                                    WHERE i.organisation_id = %d
                                    ORDER BY i.created_at DESC 
                                    LIMIT 20
                                ", $user_organisation->id));
                            } else {
                                // Site admin - all invitations
                                $invitations = $wpdb->get_results("
                                    SELECT i.*, o.name as org_name 
                                    FROM {$wpdb->prefix}test_iipm_invitations i
                                    LEFT JOIN {$wpdb->prefix}test_iipm_organisations o ON i.organisation_id = o.id
                                    ORDER BY i.created_at DESC 
                                    LIMIT 20
                                ");
                            }
                            
                            if (empty($invitations)) {
                                $colspan = $is_site_admin ? 7 : 6;
                                echo "<tr><td colspan='{$colspan}' class='no-data'>No invitations found</td></tr>";
                            } else {
                                foreach ($invitations as $invitation) {
                                    $status = $invitation->used_at ? 'Used' : (strtotime($invitation->expires_at) < time() ? 'Expired' : 'Pending');
                                    $status_class = strtolower($status);
                                    
                                    echo "<tr>";
                                    echo "<td>" . esc_html($invitation->email) . "</td>";
                                    echo "<td>" . esc_html($invitation->invitation_type) . "</td>";
                                    if ($is_site_admin) {
                                        echo "<td>" . esc_html($invitation->org_name ?? 'Individual') . "</td>";
                                    }
                                    echo "<td><span class='status-{$status_class}'>{$status}</span></td>";
                                    echo "<td>" . date('M j, Y g:i A', strtotime($invitation->created_at)) . "</td>";
                                    echo "<td>" . date('M j, Y g:i A', strtotime($invitation->expires_at)) . "</td>";
                                    echo "<td>";
                                    if ($status === 'Pending') {
                                        echo "<button class='btn-small resend-invitation' data-email='{$invitation->email}'>Resend</button>";
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

<script>
jQuery(document).ready(function($) {
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
                console.log('Response:', response); // Debug log
                
                if (response.success) {
                    $('#invitation-result').html('<div class="success">Invitation sent successfully!</div>').show();
                    $('#send-invitation-form')[0].reset();
                    // Reload page to show new invitation in table
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    var errorMsg = response.data || 'Unknown error occurred';
                    $('#invitation-result').html('<div class="error">Error: ' + errorMsg + '</div>').show();
                    
                    // Show debug information
                    if (response.debug) {
                        $('#debug-content').html('<pre>' + JSON.stringify(response.debug, null, 2) + '</pre>');
                        $('#debug-info').show();
                    }
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr.responseText); // Debug log
                $('#invitation-result').html('<div class="error">AJAX Error: ' + error + '</div>').show();
            },
            complete: function() {
                submitBtn.text(originalText).prop('disabled', false);
            }
        });
    });
    
    // Test email configuration (site admins only)
    <?php if ($is_site_admin): ?>
    $('#test-email').click(function() {
        var btn = $(this);
        btn.text('Testing...').prop('disabled', true);
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'iipm_test_email',
                nonce: '<?php echo wp_create_nonce('iipm_test_email'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#email-test-result').html('<div class="success">' + response.data + '</div>').show();
                } else {
                    $('#email-test-result').html('<div class="error">Error: ' + response.data + '</div>').show();
                }
            },
            complete: function() {
                btn.text('Test Email Configuration').prop('disabled', false);
            }
        });
    });
    <?php endif; ?>
    
    // Resend invitation
    $('.resend-invitation').click(function() {
        var email = $(this).data('email');
        var btn = $(this);
        
        if (confirm('Resend invitation to ' + email + '?')) {
            btn.text('Sending...').prop('disabled', true);
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'iipm_resend_invitation',
                    email: email,
                    nonce: '<?php echo wp_create_nonce('iipm_invitation_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Invitation resent successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                complete: function() {
                    btn.text('Resend').prop('disabled', false);
                }
            });
        }
    });
});
</script>

<style>
/* Existing styles remain the same... */

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

/* Hero Section */
.hero-section.admin-hero {
    position: relative;
    height: 400px;
    overflow: hidden;
    margin-bottom: 40px;
}

.hero-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.hero-bg-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.4) 100%);
}

.hero-content {
    position: relative;
    z-index: 2;
    height: 100%;
    display: flex;
    align-items: center;
    color: white;
}

.hero-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.hero-subtitle {
    font-size: 1.2rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.admin-nav {
    display: flex;
    gap: 1rem;
}

.btn-outline {
    background: transparent;
    color: white;
    border: 2px solid white;
    padding: 12px 24px;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.btn-outline:hover {
    background: white;
    color: #333;
}

/* Main Content */
.admin-invitations-page {
    padding: 0 0 40px 0;
}

.invitation-form-container {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 40px;
}

.invitation-form .form-group {
    margin-bottom: 20px;
}

.invitation-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #333;
}

.invitation-form input,
.invitation-form select {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.invitation-form input:focus,
.invitation-form select:focus {
    outline: none;
    border-color: #0073aa;
    box-shadow: 0 0 0 2px rgba(0,115,170,0.1);
}

.btn {
    background: #0073aa;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    transition: background 0.3s ease;
}

.btn:hover {
    background: #005a87;
}

.btn:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.btn-secondary {
    background: #666;
}

.btn-secondary:hover {
    background: #555;
}

.btn-small {
    padding: 6px 12px;
    font-size: 14px;
    background: #0073aa;
    color: white;
    border: none;
    border-radius: 3px;
    cursor: pointer;
}

.btn-small:hover {
    background: #005a87;
}

/* Tables */
.table-container {
    overflow-x: auto;
}

.invitations-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 40px;
}

.invitations-table th,
.invitations-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.invitations-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
}

.no-data {
    text-align: center;
    color: #666;
    font-style: italic;
}

/* Status badges */
.status-pending { 
    color: #856404; 
    background: #fff3cd; 
    padding: 4px 8px; 
    border-radius: 4px; 
    font-size: 12px;
    font-weight: 600;
}

.status-used { 
    color: #155724; 
    background: #d4edda; 
    padding: 4px 8px; 
    border-radius: 4px; 
    font-size: 12px;
    font-weight: 600;
}

.status-expired { 
    color: #721c24; 
    background: #f8d7da; 
    padding: 4px 8px; 
    border-radius: 4px; 
    font-size: 12px;
    font-weight: 600;
}

/* Messages */
.success {
    background: #d4edda;
    color: #155724;
    padding: 15px;
    border-radius: 4px;
    margin-top: 15px;
    border: 1px solid #c3e6cb;
}

.error {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 4px;
    margin-top: 15px;
    border: 1px solid #f5c6cb;
}

/* Debug section */
.debug-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 4px;
    margin-top: 20px;
    border: 1px solid #dee2e6;
}

.debug-section h3 {
    margin-top: 0;
    color: #495057;
}

.debug-section pre {
    background: #fff;
    padding: 10px;
    border-radius: 4px;
    overflow-x: auto;
    font-size: 12px;
}

/* Email test section */
.email-test-section {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 40px;
}

.email-test-section h2 {
    margin-top: 0;
    color: #333;
}

/* Responsive */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .hero-subtitle {
        font-size: 1rem;
    }
    
    .admin-nav {
        flex-direction: column;
    }
    
    .invitation-form-container,
    .email-test-section {
        padding: 20px;
    }
    
    .invitations-table {
        font-size: 14px;
    }
    
    .invitations-table th,
    .invitations-table td {
        padding: 8px;
    }
}
</style>

<?php get_footer(); ?>
