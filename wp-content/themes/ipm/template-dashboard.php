<?php
/**
 * Template Name: IIPM Dashboard
 * 
 * Main dashboard page with role-based navigation
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

// Redirect normal members to member-portal instead of dashboard
$current_user = wp_get_current_user();
$user_roles = $current_user->roles;

// Check if user is NOT an admin or corporate admin
$is_admin = in_array('administrator', $user_roles) || in_array('iipm_admin', $user_roles);
$is_corporate_admin = in_array('iipm_corporate_admin', $user_roles);

// If user is just a regular member, redirect to member-portal
if (!$is_admin && !$is_corporate_admin) {
    wp_redirect(home_url('/member-portal/'));
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user data
global $wpdb;
$member = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d",
    $user_id
));

$profile = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}test_iipm_member_profiles WHERE user_id = %d",
    $user_id
));

// Get user roles
$user_roles = $current_user->roles;
$is_admin = in_array('administrator', $user_roles) || in_array('iipm_admin', $user_roles);
$is_corporate_admin = in_array('iipm_corporate_admin', $user_roles);
$is_council_member = in_array('iipm_council_member', $user_roles);
$is_member = in_array('iipm_member', $user_roles);

// Get organization info if corporate admin
$organisation = null;
if ($is_corporate_admin) {
    $organisation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_organisations WHERE admin_user_id = %d",
        $user_id
    ));
}

// Calculate profile completion
$profile_completion = iipm_calculate_profile_completion($user_id);

// Get dashboard statistics
$stats = iipm_get_dashboard_stats($user_id);

get_header();
?>

<div class="iipm-dashboard-page">
    <!-- Hero Section -->
    <section class="dashboard-hero">
        <div class="container">
            <div class="hero-content">
                <div class="welcome-section">
                    <h1>Welcome, <?php echo esc_html($current_user->first_name ?: $current_user->display_name); ?></h1>
                    <div class="user-role">
                        <?php if ($is_admin): ?>
                            <span class="role-badge admin">Super Admin</span>
                        <?php elseif ($is_corporate_admin): ?>
                            <span class="role-badge corporate">Organization Admin</span>
                            <?php if ($organisation): ?>
                                <span class="org-name"><?php echo esc_html($organisation->name); ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="dashboard-avatar">
                    <div class="avatar-circle">
                        <?php echo strtoupper(substr($current_user->first_name ?: $current_user->display_name, 0, 1)); ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if ($is_admin || $is_corporate_admin): ?>
    <!-- Navigation Cards -->
    <section class="dashboard-navigation">
        <div class="container">
            <h2>Quick Access</h2>
            <div class="nav-grid">
                <?php if ($is_admin): ?>
                <!-- Admin Only - Organization Management -->
                <div class="nav-card admin">
                    <div class="card-icon">🏢</div>
                    <h3>Organization Management</h3>
                    <p>Manage organizations, assign admins, and oversee corporate memberships</p>
                    <a href="<?php echo home_url('/organisation-management/'); ?>" class="card-button">
                        Manage Organizations <span class="arrow">→</span>
                    </a>
                </div>

                <!-- Admin Only - User Management -->
                <div class="nav-card admin">
                    <div class="card-icon">👥</div>
                    <h3>User Management</h3>
                    <p>Manage all users, view their details, and handle user-related tasks</p>
                    <a href="<?php echo home_url('/user-management/'); ?>" class="card-button">
                        Manage Users <span class="arrow">→</span>
                    </a>
                </div>

                <!-- Admin Only - Leave Administration -->
                <div class="nav-card admin">
                    <div class="card-icon">⚖️</div>
                    <h3>Leave Administration</h3>
                    <p>Review and manage leave requests from all members</p>
                    <a href="<?php echo home_url('/leave-admin/'); ?>" class="card-button">
                        Manage Leave <span class="arrow">→</span>
                    </a>
                </div>

                <?php elseif ($is_corporate_admin): ?>
                <!-- Corporate Admin - Organization Management -->
                <div class="nav-card corporate">
                    <div class="card-icon">🏢</div>
                    <h3>Organization Management</h3>
                    <p>View your organization's member statistics and manage team members</p>
                    <a href="<?php echo home_url('/member-portal/#organization'); ?>" class="card-button">
                        View Dashboard <span class="arrow">→</span>
                    </a>
                </div>

                <!-- Corporate Admin - Employee Management -->
                <div class="nav-card corporate">
                    <div class="card-icon">👥</div>
                    <h3>Employee Management</h3>
                    <p>Manage your organization's employees and their information</p>
                    <a href="<?php echo home_url('/user-management/'); ?>" class="card-button">
                        Manage Employees <span class="arrow">→</span>
                    </a>
                </div>

                <!-- Corporate Admin - Leave Administration -->
                <div class="nav-card corporate">
                    <div class="card-icon">⚖️</div>
                    <h3>Leave Administration</h3>
                    <p>Review and manage leave requests from your team members</p>
                    <a href="<?php echo home_url('/leave-admin/'); ?>" class="card-button">
                        Manage Leave <span class="arrow">→</span>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
</div>

<style>
/* Dashboard Specific Styles - Enhanced with Leave Request Features */
.iipm-dashboard-page {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    line-height: 1.6;
    color: #333;
}

.dashboard-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 0;
    margin-bottom: 40px;
}

.hero-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 40px;
}

.welcome-section h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 16px 0;
}

.user-role {
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.role-badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.role-badge.admin {
    background: #ef4444;
    color: white;
}

.role-badge.corporate {
    background: #8b5a96;
    color: white;
}

.role-badge.council {
    background: #f59e0b;
    color: white;
}

.role-badge.member {
    background: #10b981;
    color: white;
}

.org-name {
    font-size: 1rem;
    opacity: 0.9;
}

.quick-stats {
    display: flex;
    gap: 32px;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.stat-label {
    font-size: 0.875rem;
    opacity: 0.8;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 600;
}

.progress-bar {
    width: 120px;
    height: 8px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: #10b981;
    transition: width 0.3s ease;
    border-radius: 4px;
}

.dashboard-avatar {
    flex-shrink: 0;
}

.avatar-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 700;
    border: 3px solid rgba(255, 255, 255, 0.3);
}

.dashboard-navigation {
    margin-bottom: 60px;
}

.dashboard-navigation h2 {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 32px;
    color: #1f2937;
}

.nav-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 24px;
}

.nav-card {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.nav-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.nav-card.primary {
    background: linear-gradient(135deg, #8b5a96 0%, #6b4c93 100%);
    color: white;
}

.nav-card.admin {
    border-left: 4px solid #ef4444;
}

.nav-card.corporate {
    border-left: 4px solid #8b5a96;
}

.nav-card.council {
    border-left: 4px solid #f59e0b;
}

.card-icon {
    font-size: 2.5rem;
    margin-bottom: 16px;
    display: block;
}

.nav-card h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 12px 0;
    color: inherit;
}

.nav-card p {
    margin: 0 0 24px 0;
    opacity: 0.8;
    line-height: 1.5;
}

.card-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: rgba(0, 0, 0, 0.1);
    color: inherit;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.nav-card.primary .card-button {
    background: rgba(255, 255, 255, 0.2);
}

.nav-card:not(.primary) .card-button {
    background: #8b5a96;
    color: white;
}

.card-button:hover {
    background: rgba(0, 0, 0, 0.2);
    transform: translateX(4px);
}

.nav-card.primary .card-button:hover {
    background: rgba(255, 255, 255, 0.3);
}

.arrow {
    transition: transform 0.2s ease;
}

.card-button:hover .arrow {
    transform: translateX(4px);
}

.dashboard-activity {
    background: #f8fafc;
    padding: 60px 0;
    margin-bottom: 40px;
}

.dashboard-activity h2 {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 32px;
    color: #1f2937;
}

.activity-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
}

.activity-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.activity-card h3 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 20px 0;
    color: #1f2937;
}

.activity-stats {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
}

.stat {
    text-align: center;
    flex: 1;
    min-width: 80px;
}

.stat .number {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: #8b5a96;
    margin-bottom: 4px;
}

.stat .label {
    font-size: 0.875rem;
    color: #6b7280;
}

.stat-link {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    color: #8b5a96;
    transition: color 0.2s ease;
}

.stat-link:hover {
    color: #6b4c93;
}

.stat-link .icon {
    font-size: 1.5rem;
    margin-bottom: 4px;
}

/* Personal Leave Request Section */
.dashboard-personal {
    padding: 40px 0;
    background: white;
}

.dashboard-personal h2 {
    font-size: 1.75rem;
    font-weight: 600;
    margin-bottom: 24px;
    color: #1f2937;
}

.personal-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.personal-stat-card {
    background: #f8fafc;
    border-radius: 12px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.2s ease;
}

.personal-stat-card:hover {
    background: #f1f5f9;
    transform: translateY(-2px);
}

.personal-stat-card.action-card {
    background: linear-gradient(135deg, #8b5a96 0%, #6b4c93 100%);
    color: white;
}

.personal-stat-card.action-card:hover {
    background: linear-gradient(135deg, #6b4c93 0%, #553c75 100%);
}

.stat-icon {
    font-size: 2rem;
    opacity: 0.8;
}

.stat-content {
    flex: 1;
}

.stat-number {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #8b5a96;
    margin-bottom: 4px;
}

.action-card .stat-number {
    color: white;
}

.action-link {
    text-decoration: none;
    color: inherit;
}

.dashboard-actions {
    padding: 40px 0;
    background: white;
    border-top: 1px solid #e5e7eb;
}

.actions-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.action-group h3 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 16px 0;
    color: #1f2937;
}

.action-buttons {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.875rem;
}

.action-btn.warning {
    background: #f59e0b;
    color: white;
}

.action-btn.info {
    background: #3b82f6;
    color: white;
}

.action-btn.primary {
    background: #8b5a96;
    color: white;
}

.action-btn.admin {
    background: #ef4444;
    color: white;
}

.action-btn.secondary {
    background: #6b7280;
    color: white;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Status badges */
.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.status-active {
    background: #10b981;
    color: white;
}

.status-pending {
    background: #f59e0b;
    color: white;
}

.status-inactive {
    background: #ef4444;
    color: white;
}

.status-suspended {
    background: #6b7280;
    color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-content {
        flex-direction: column;
        text-align: center;
    }
    
    .welcome-section h1 {
        font-size: 2rem;
    }
    
    .quick-stats {
        justify-content: center;
    }
    
    .nav-grid {
        grid-template-columns: 1fr;
    }
    
    .activity-stats {
        justify-content: center;
    }
    
    .personal-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .actions-bar {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Email verification button
    $('#verify-email-btn').click(function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<span class="icon">⏳</span> Verifying...');
        
        $.ajax({
            url: iimp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'iipm_local_verify_email',
                user_id: <?php echo $user_id; ?>,
                nonce: iimp_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    btn.html('<span class="icon">✅</span> Verified!').removeClass('info').addClass('success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    btn.html('<span class="icon">❌</span> Failed').removeClass('info').addClass('warning');
                    setTimeout(function() {
                        btn.prop('disabled', false).html('<span class="icon">✉️</span> Verify Email').removeClass('warning').addClass('info');
                    }, 2000);
                }
            },
            error: function() {
                btn.html('<span class="icon">❌</span> Error').removeClass('info').addClass('warning');
                setTimeout(function() {
                    btn.prop('disabled', false).html('<span class="icon">✉️</span> Verify Email').removeClass('warning').addClass('info');
                }, 2000);
            }
        });
    });
});
</script>

<?php get_footer(); ?>
