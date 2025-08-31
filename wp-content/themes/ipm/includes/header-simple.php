<?php
/**
 * Simple Header Template
 * Used for portal pages like Leave Request, CPD, etc.
 * 
 * @package IPM
 */
?>

<header class="header-simple">
    <div class="header-simple__container">
        <div class="header-simple__inner">
            <!-- Home Icon -->
            <div class="header-simple__home">
                <a href="<?php echo home_url(); ?>" class="home-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="#666666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9 22V12H15V22" stroke="#666666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            </div>

            <!-- IIPM Logo -->
            <div class="header-simple__logo">
                <a href="<?php echo home_url(); ?>">
                    <?php
                    $image = get_field('header_logo', 'option');
                    if ($image) {
                        echo '<img src="' . esc_url($image) . '" alt="IIPM Logo" class="logo-img">';
                    } else {
                        // Fallback logo
                        echo '<div class="logo-text">IIPM</div>';
                    }
                    ?>
                </a>
            </div>

            <!-- Right Side Icons -->
            <div class="header-simple__actions">
                <?php if (is_user_logged_in()): ?>
                    <!-- Notification Icon -->
                    <div class="header-simple__notification">
                        <button class="notification-btn" onclick="toggleNotifications()">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18 8C18 6.4087 17.3679 4.88258 16.2426 3.75736C15.1174 2.63214 13.5913 2 12 2C10.4087 2 8.88258 2.63214 7.75736 3.75736C6.63214 4.88258 6 6.4087 6 8C6 15 3 17 3 17H21C21 17 18 15 18 8Z" stroke="#666666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M13.73 21C13.5542 21.3031 13.3019 21.5547 12.9982 21.7295C12.6946 21.9044 12.3504 21.9965 12 21.9965C11.6496 21.9965 11.3054 21.9044 11.0018 21.7295C10.6982 21.5547 10.4458 21.3031 10.27 21" stroke="#666666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="notification-badge" id="notificationBadge" style="display: none;">3</span>
                        </button>
                        
                        <!-- Notification Dropdown -->
                        <div class="notification-dropdown" id="notificationDropdown" style="display: none;">
                            <div class="notification-header">
                                <h4>Notifications</h4>
                                <button class="mark-all-read" onclick="markAllAsRead()">Mark all as read</button>
                            </div>
                            <div class="notification-list">
                                <div class="notification-item unread">
                                    <div class="notification-content">
                                        <p><strong>Leave Request Approved</strong></p>
                                        <p>Your leave request for March 15-20 has been approved.</p>
                                        <span class="notification-time">2 hours ago</span>
                                    </div>
                                </div>
                                <div class="notification-item">
                                    <div class="notification-content">
                                        <p><strong>CPD Course Available</strong></p>
                                        <p>New course "Advanced Pension Management" is now available.</p>
                                        <span class="notification-time">1 day ago</span>
                                    </div>
                                </div>
                                <div class="notification-item">
                                    <div class="notification-content">
                                        <p><strong>Profile Update Required</strong></p>
                                        <p>Please update your professional designation.</p>
                                        <span class="notification-time">3 days ago</span>
                                    </div>
                                </div>
                            </div>
                            <div class="notification-footer">
                                <a href="<?php echo home_url('/notifications/'); ?>">View all notifications</a>
                            </div>
                        </div>
                    </div>

                    <!-- User Profile Icon -->
                    <div class="header-simple__profile">
                        <div class="profile-dropdown">
                            <button class="profile-btn" onclick="toggleProfileMenu()">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="#666666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="12" cy="7" r="4" stroke="#666666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            
                            <!-- Profile Dropdown Menu -->
                            <div class="profile-dropdown-menu" id="profileDropdown" style="display: none;">
                                <div class="profile-info">
                                    <div class="profile-avatar">
                                        <?php echo strtoupper(substr(wp_get_current_user()->display_name, 0, 2)); ?>
                                    </div>
                                    <div class="profile-details">
                                        <p class="profile-name"><?php echo esc_html(wp_get_current_user()->display_name); ?></p>
                                        <p class="profile-email"><?php echo esc_html(wp_get_current_user()->user_email); ?></p>
                                    </div>
                                </div>
                                
                                <div class="profile-menu-items">
                                    <?php 
                                    $current_user = wp_get_current_user();
                                    $user_roles = $current_user->roles;
                                    $is_admin = in_array('administrator', $user_roles) || in_array('iipm_admin', $user_roles);
                                    $is_corporate_admin = in_array('iipm_corporate_admin', $user_roles);
                                    
                                    // Dashboard link for admins
                                    if ($is_admin || $is_corporate_admin): ?>
                                        <a href="<?php echo home_url('/dashboard/'); ?>" class="profile-menu-item">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <rect x="3" y="3" width="7" height="7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <rect x="14" y="3" width="7" height="7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <rect x="14" y="14" width="7" height="7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <rect x="3" y="14" width="7" height="7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Dashboard
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="<?php echo home_url('/member-portal/'); ?>" class="profile-menu-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        Member Portal
                                    </a>
                                    
                                    <a href="<?php echo home_url('/leave-request/'); ?>" class="profile-menu-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <line x1="16" y1="2" x2="16" y2="6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <line x1="8" y1="2" x2="8" y2="6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        Leave Request
                                    </a>
                                    
                                    <a href="<?php echo home_url('/cpd-courses/'); ?>" class="profile-menu-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M2 3H8C9.06087 3 10.0783 3.42143 10.8284 4.17157C11.5786 4.92172 12 5.93913 12 7V21C12 20.2044 11.6839 19.4413 11.1213 18.8787C10.5587 18.3161 9.79565 18 9 18H2V3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M22 3H16C14.9391 3 13.9217 3.42143 13.1716 4.17157C12.4214 4.92172 12 5.93913 12 7V21C12 20.2044 12.3161 19.4413 12.8787 18.8787C13.4413 18.3161 14.2044 18 15 18H22V3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        CPD Courses
                                    </a>
                                </div>
                                
                                <div class="profile-menu-footer">
                                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="profile-menu-item logout">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <polyline points="16,17 21,12 16,7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <line x1="21" y1="12" x2="9" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        Logout
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Login Button for Non-logged Users -->
                    <div class="header-simple__login">
                        <a href="<?php echo home_url('/login/'); ?>" class="login-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <polyline points="10,17 15,12 10,7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <line x1="15" y1="12" x2="3" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Login
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<style>
/* Simple Header Styles */
.header-simple {
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    padding: 16px 0;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.header-simple__container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.header-simple__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.header-simple__home .home-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    transition: background-color 0.2s ease;
    text-decoration: none;
}

.header-simple__home .home-icon:hover {
    background-color: #f3f4f6;
}

.header-simple__logo {
    flex: 1;
    display: flex;
    justify-content: center;
}

.header-simple__logo .logo-img {
    height: 40px;
    width: auto;
}

.header-simple__logo .logo-text {
    font-size: 24px;
    font-weight: 700;
    color: #8b5a96;
    font-family: 'Gabarito', sans-serif;
}

.header-simple__actions {
    display: flex;
    align-items: center;
    gap: 16px;
}

/* Notification Styles */
.header-simple__notification {
    position: relative;
}

.notification-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border: none;
    background: transparent;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.2s ease;
    position: relative;
}

.notification-btn:hover {
    background-color: #f3f4f6;
}

.notification-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    background: #ef4444;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 11px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    width: 320px;
    border: 1px solid #e5e7eb;
    margin-top: 8px;
    z-index: 1000;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #f3f4f6;
}

.notification-header h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #111827;
}

.mark-all-read {
    background: none;
    border: none;
    color: #8b5a96;
    font-size: 12px;
    cursor: pointer;
    font-weight: 500;
}

.notification-list {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    padding: 16px 20px;
    border-bottom: 1px solid #f9fafb;
    transition: background-color 0.2s ease;
}

.notification-item:hover {
    background-color: #f9fafb;
}

.notification-item.unread {
    background-color: #fef3f2;
    border-left: 3px solid #ef4444;
}

.notification-content p {
    margin: 0 0 4px 0;
    font-size: 14px;
    line-height: 1.4;
}

.notification-content p:first-child {
    font-weight: 600;
    color: #111827;
}

.notification-content p:nth-child(2) {
    color: #6b7280;
}

.notification-time {
    font-size: 12px;
    color: #9ca3af;
}

.notification-footer {
    padding: 12px 20px;
    border-top: 1px solid #f3f4f6;
    text-align: center;
}

.notification-footer a {
    color: #8b5a96;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

/* Profile Styles */
.header-simple__profile {
    position: relative;
}

.profile-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border: none;
    background: transparent;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.profile-btn:hover {
    background-color: #f3f4f6;
}

.profile-dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    width: 280px;
    border: 1px solid #e5e7eb;
    margin-top: 8px;
    z-index: 1000;
}

.profile-info {
    display: flex;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #f3f4f6;
}

.profile-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #8b5a96;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
    margin-right: 12px;
}

.profile-details {
    flex: 1;
}

.profile-name {
    margin: 0 0 4px 0;
    font-weight: 600;
    color: #111827;
    font-size: 14px;
}

.profile-email {
    margin: 0;
    color: #6b7280;
    font-size: 12px;
}

.profile-menu-items {
    padding: 8px 0;
}

.profile-menu-item {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #374151;
    text-decoration: none;
    transition: background-color 0.2s ease;
    font-size: 14px;
    font-weight: 500;
}

.profile-menu-item:hover {
    background-color: #f9fafb;
    color: #8b5a96;
}

.profile-menu-item svg {
    margin-right: 12px;
    flex-shrink: 0;
}

.profile-menu-footer {
    border-top: 1px solid #f3f4f6;
    padding: 8px 0;
}

.profile-menu-item.logout {
    color: #ef4444;
}

.profile-menu-item.logout:hover {
    background-color: #fef2f2;
    color: #dc2626;
}

/* Login Button */
.header-simple__login .login-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: #8b5a96;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.2s ease;
}

.header-simple__login .login-btn:hover {
    background: #6d4576;
    transform: translateY(-1px);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .header-simple__container {
        padding: 0 16px;
    }
    
    .header-simple__logo .logo-img {
        height: 32px;
    }
    
    .header-simple__actions {
        gap: 12px;
    }
    
    .notification-dropdown,
    .profile-dropdown-menu {
        width: 280px;
        right: -20px;
    }
}

@media (max-width: 480px) {
    .notification-dropdown,
    .profile-dropdown-menu {
        width: calc(100vw - 32px);
        right: -16px;
    }
}
</style>

<script>
// Toggle Notifications
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    const profileDropdown = document.getElementById('profileDropdown');
    
    // Close profile dropdown if open
    if (profileDropdown) {
        profileDropdown.style.display = 'none';
    }
    
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }
}

// Toggle Profile Menu
function toggleProfileMenu() {
    const dropdown = document.getElementById('profileDropdown');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    // Close notification dropdown if open
    if (notificationDropdown) {
        notificationDropdown.style.display = 'none';
    }
    
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }
}

// Mark all notifications as read
function markAllAsRead() {
    const badge = document.getElementById('notificationBadge');
    const unreadItems = document.querySelectorAll('.notification-item.unread');
    
    unreadItems.forEach(item => {
        item.classList.remove('unread');
    });
    
    if (badge) {
        badge.style.display = 'none';
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    const notificationBtn = event.target.closest('.notification-btn');
    const profileBtn = event.target.closest('.profile-btn');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const profileDropdown = document.getElementById('profileDropdown');
    
    if (!notificationBtn && notificationDropdown) {
        notificationDropdown.style.display = 'none';
    }
    
    if (!profileBtn && profileDropdown) {
        profileDropdown.style.display = 'none';
    }
});
</script>
