<?php
/**
 * Complete Notification System Component
 * 
 * Provides a comprehensive notification system for the entire project including:
 * 
 * 1. MAIN NOTIFICATION SYSTEM (floating notifications):
 *    - 4 types: success, error, warning, info
 *    - Auto-dismiss with progress bars
 *    - Action buttons and persistent notifications
 *    - Mobile responsive with dark mode support
 * 
 * 2. HEADER NOTIFICATION BELL:
 *    - Bell icon with red badge counter
 *    - Dropdown showing recent notifications
 *    - Local storage persistence
 *    - Time ago formatting (1m ago, 2h ago, etc.)
 *    - Mark as read functionality
 *    - Clear all notifications
 * 
 * 3. INTEGRATION:
 *    - All main notifications automatically appear in header bell
 *    - PHP session messages support
 *    - Server-side notification helpers
 *    - Touch device optimized
 * 
 * USAGE:
 *   JavaScript: notifications.success('Title', 'Message');
 *   PHP: add_success_notification('Title', 'Message');
 *   Header Bell: Automatically shows all notifications
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Notification Container -->
<div id="notification-container" class="notification-container"></div>

<style>
/* Notification System Styles */
.notification-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10000;
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-width: 400px;
    pointer-events: none;
}

.notification {
    background: white;
    border-radius: 12px;
    padding: 16px 20px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    border-left: 4px solid #6b7280;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: auto;
    position: relative;
    overflow: hidden;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.notification.show {
    opacity: 1;
    transform: translateX(0);
}

.notification.hide {
    opacity: 0;
    transform: translateX(100%);
}

.notification::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    background: rgba(107, 114, 128, 0.3);
    width: 100%;
    transform-origin: left;
    animation: notificationProgress 5s linear forwards;
}

/* Notification Types */
.notification.success {
    border-left-color: #10b981;
}

.notification.success::before {
    background: #10b981;
}

.notification.error {
    border-left-color: #ef4444;
}

.notification.error::before {
    background: #ef4444;
}

.notification.warning {
    border-left-color: #f59e0b;
}

.notification.warning::before {
    background: #f59e0b;
}

.notification.info {
    border-left-color: #3b82f6;
}

.notification.info::before {
    background: #3b82f6;
}

.notification-icon {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: white;
    font-size: 12px;
    font-weight: 600;
    margin-top: 1px;
}

.notification.success .notification-icon {
    background: #10b981;
}

.notification.error .notification-icon {
    background: #ef4444;
}

.notification.warning .notification-icon {
    background: #f59e0b;
}

.notification.info .notification-icon {
    background: #3b82f6;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-title {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 4px 0;
    line-height: 1.4;
}

.notification-message {
    font-size: 13px;
    color: #6b7280;
    margin: 0;
    line-height: 1.4;
    word-wrap: break-word;
}

.notification-close {
    flex-shrink: 0;
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.2s;
    font-size: 16px;
    line-height: 1;
}

.notification-close:hover {
    background: #f3f4f6;
    color: #6b7280;
}

/* Progress Animation */
@keyframes notificationProgress {
    0% {
        transform: scaleX(1);
    }
    100% {
        transform: scaleX(0);
    }
}

/* Action Button */
.notification-action {
    margin-top: 8px;
    padding: 6px 12px;
    background: transparent;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    color: #374151;
}

.notification-action:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.notification.success .notification-action {
    border-color: #10b981;
    color: #059669;
}

.notification.success .notification-action:hover {
    background: #ecfdf5;
}

.notification.error .notification-action {
    border-color: #ef4444;
    color: #dc2626;
}

.notification.error .notification-action:hover {
    background: #fef2f2;
}

.notification.warning .notification-action {
    border-color: #f59e0b;
    color: #d97706;
}

.notification.warning .notification-action:hover {
    background: #fffbeb;
}

.notification.info .notification-action {
    border-color: #3b82f6;
    color: #2563eb;
}

.notification.info .notification-action:hover {
    background: #eff6ff;
}

/* Mobile Responsive */
@media (max-width: 640px) {
    .notification-container {
        top: 10px;
        right: 10px;
        left: 10px;
        max-width: none;
    }
    
    .notification {
        padding: 14px 16px;
        transform: translateY(-100%);
    }
    
    .notification.show {
        transform: translateY(0);
    }
    
    .notification.hide {
        transform: translateY(-100%);
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .notification {
        background: #1f2937;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    }
    
    .notification-title {
        color: #f9fafb;
    }
    
    .notification-message {
        color: #d1d5db;
    }
    
    .notification-close {
        color: #6b7280;
    }
    
    .notification-close:hover {
        background: #374151;
        color: #9ca3af;
    }
}
</style>

<script>
/**
 * Global Notification System
 */
class NotificationSystem {
    constructor() {
        this.container = document.getElementById('notification-container');
        this.notifications = new Map();
        this.defaultDuration = 5000;
        
        // Initialize container if not found
        if (!this.container) {
            this.createContainer();
        }
    }
    
    createContainer() {
        this.container = document.createElement('div');
        this.container.id = 'notification-container';
        this.container.className = 'notification-container';
        document.body.appendChild(this.container);
    }
    
    show(options) {
        const {
            type = 'info',
            title = '',
            message = '',
            duration = this.defaultDuration,
            action = null,
            persistent = false,
            id = null
        } = options;
        
        const notificationId = id || this.generateId();
        
        // Remove existing notification with same ID
        if (this.notifications.has(notificationId)) {
            this.hide(notificationId);
        }
        
        const notification = this.createElement(type, title, message, action, notificationId);
        this.container.appendChild(notification);
        
        // Store notification reference
        this.notifications.set(notificationId, {
            element: notification,
            timeout: null
        });
        
        // Add to header notifications if available
        if (window.HeaderNotifications && title && message) {
            window.HeaderNotifications.addNotification({
                id: notificationId,
                type: type,
                title: title,
                message: message
            });
        }
        
        // Trigger show animation
        requestAnimationFrame(() => {
            notification.classList.add('show');
        });
        
        // Auto-hide unless persistent
        if (!persistent && duration > 0) {
            const timeout = setTimeout(() => {
                this.hide(notificationId);
            }, duration);
            
            this.notifications.get(notificationId).timeout = timeout;
        }
        
        return notificationId;
    }
    
    createElement(type, title, message, action, id) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.dataset.id = id;
        
        const icons = {
            success: '✓',
            error: '✕',
            warning: '!',
            info: 'i'
        };
        
        let actionHtml = '';
        if (action) {
            actionHtml = `<button class="notification-action" onclick="${action.onClick}">${action.text}</button>`;
        }
        
        notification.innerHTML = `
            <div class="notification-icon">${icons[type] || icons.info}</div>
            <div class="notification-content">
                ${title ? `<div class="notification-title">${this.escapeHtml(title)}</div>` : ''}
                ${message ? `<div class="notification-message">${this.escapeHtml(message)}</div>` : ''}
                ${actionHtml}
            </div>
            <button class="notification-close" onclick="notifications.hide('${id}')">&times;</button>
        `;
        
        return notification;
    }
    
    hide(id) {
        const notification = this.notifications.get(id);
        if (!notification) return;
        
        // Clear timeout
        if (notification.timeout) {
            clearTimeout(notification.timeout);
        }
        
        // Hide animation
        notification.element.classList.remove('show');
        notification.element.classList.add('hide');
        
        // Remove from DOM after animation
        setTimeout(() => {
            if (notification.element.parentNode) {
                notification.element.parentNode.removeChild(notification.element);
            }
            this.notifications.delete(id);
        }, 300);
    }
    
    hideAll() {
        this.notifications.forEach((notification, id) => {
            this.hide(id);
        });
    }
    
    success(title, message, options = {}) {
        return this.show({ type: 'success', title, message, ...options });
    }
    
    error(title, message, options = {}) {
        return this.show({ type: 'error', title, message, ...options });
    }
    
    warning(title, message, options = {}) {
        return this.show({ type: 'warning', title, message, ...options });
    }
    
    info(title, message, options = {}) {
        return this.show({ type: 'info', title, message, ...options });
    }
    
    generateId() {
        return 'notification_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize global notification system
const notifications = new NotificationSystem();

// Expose to window for global access
window.notifications = notifications;

/**
 * Header Notification Management System
 */
class HeaderNotificationSystem {
    constructor() {
        this.headerNotifications = JSON.parse(localStorage.getItem('headerNotifications') || '[]');
        this.maxHeaderNotifications = 10;
        this.init();
    }
    
    init() {
        // Bind click events
        const bellBtn = document.getElementById('notification-bell');
        const dropdown = document.getElementById('notification-dropdown');
        
        if (bellBtn && dropdown) {
            // Toggle dropdown on click instead of hover for better mobile experience
            bellBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleDropdown();
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!bellBtn.contains(e.target) && !dropdown.contains(e.target)) {
                    this.closeDropdown();
                }
            });
            
            // Prevent dropdown from closing when clicking inside
            dropdown.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }
        
        this.updateBadge();
        this.renderNotifications();
    }
    
    toggleDropdown() {
        const dropdown = document.getElementById('notification-dropdown');
        if (dropdown) {
            const isVisible = dropdown.style.display === 'block';
            dropdown.style.display = isVisible ? 'none' : 'block';
            
            if (!isVisible) {
                this.markAllAsRead();
            }
        }
    }
    
    closeDropdown() {
        const dropdown = document.getElementById('notification-dropdown');
        if (dropdown) {
            dropdown.style.display = 'none';
        }
    }
    
    addNotification(notification) {
        const headerNotification = {
            id: notification.id || this.generateId(),
            type: notification.type,
            title: notification.title,
            message: notification.message,
            timestamp: new Date().toISOString(),
            read: false
        };
        
        // Add to beginning of array
        this.headerNotifications.unshift(headerNotification);
        
        // Keep only the most recent notifications
        if (this.headerNotifications.length > this.maxHeaderNotifications) {
            this.headerNotifications = this.headerNotifications.slice(0, this.maxHeaderNotifications);
        }
        
        this.saveToStorage();
        this.updateBadge();
        this.renderNotifications();
        
        return headerNotification.id;
    }
    
    markAllAsRead() {
        this.headerNotifications.forEach(notification => {
            notification.read = true;
        });
        this.saveToStorage();
        this.updateBadge();
        this.renderNotifications();
    }
    
    clearAll() {
        this.headerNotifications = [];
        this.saveToStorage();
        this.updateBadge();
        this.renderNotifications();
    }
    
    updateBadge() {
        const badge = document.getElementById('notification-badge');
        if (badge) {
            const unreadCount = this.headerNotifications.filter(n => !n.read).length;
            
            if (unreadCount > 0) {
                badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    
    renderNotifications() {
        const list = document.getElementById('header-notification-list');
        if (!list) return;
        
        if (this.headerNotifications.length === 0) {
            list.innerHTML = `
                <div class="no-notifications">
                    <p>No recent notifications</p>
                </div>
            `;
            return;
        }
        
        list.innerHTML = this.headerNotifications.map(notification => {
            const timeAgo = this.getTimeAgo(notification.timestamp);
            const iconSymbol = this.getIconSymbol(notification.type);
            
            return `
                <div class="notification-item ${!notification.read ? 'unread' : ''}" onclick="HeaderNotifications.markAsRead('${notification.id}')">
                    <div class="notification-icon ${notification.type}">
                        ${iconSymbol}
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">${this.escapeHtml(notification.title)}</div>
                        <div class="notification-message">${this.escapeHtml(notification.message)}</div>
                        <div class="notification-time">${timeAgo}</div>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    markAsRead(notificationId) {
        const notification = this.headerNotifications.find(n => n.id === notificationId);
        if (notification) {
            notification.read = true;
            this.saveToStorage();
            this.updateBadge();
            this.renderNotifications();
        }
    }
    
    getIconSymbol(type) {
        const icons = {
            success: '✓',
            error: '✕',
            warning: '!',
            info: 'i'
        };
        return icons[type] || icons.info;
    }
    
    getTimeAgo(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diffInMinutes = Math.floor((now - time) / 60000);
        
        if (diffInMinutes < 1) return 'Just now';
        if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
        
        const diffInHours = Math.floor(diffInMinutes / 60);
        if (diffInHours < 24) return `${diffInHours}h ago`;
        
        const diffInDays = Math.floor(diffInHours / 24);
        if (diffInDays < 7) return `${diffInDays}d ago`;
        
        return time.toLocaleDateString();
    }
    
    viewAll() {
        // Close dropdown and redirect to notifications page
        this.closeDropdown();
        
        // Redirect to the dedicated notifications page
        window.location.href = '<?php echo home_url('/notifications/'); ?>';
    }
    
    saveToStorage() {
        localStorage.setItem('headerNotifications', JSON.stringify(this.headerNotifications));
    }
    
    generateId() {
        return 'header_notification_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize header notifications
const HeaderNotifications = new HeaderNotificationSystem();

// Expose to window for global access
window.HeaderNotifications = HeaderNotifications;

// PHP Session Messages Integration
document.addEventListener('DOMContentLoaded', function() {
    // Check for PHP session messages
    <?php
    if (isset($_SESSION['notification_messages'])) {
        foreach ($_SESSION['notification_messages'] as $message) {
            echo "notifications.{$message['type']}('{$message['title']}', '{$message['message']}');\n";
        }
        unset($_SESSION['notification_messages']);
    }
    ?>
    
    // Show welcome notification for logged-in users (only once per session)
    <?php if (is_user_logged_in()): ?>
        if (!sessionStorage.getItem('welcomeShown')) {
            setTimeout(() => {
                notifications.info('Welcome back!', 'Check your notification bell for recent updates.');
                sessionStorage.setItem('welcomeShown', 'true');
            }, 2000);
        }
    <?php endif; ?>
});
</script>

<?php
/**
 * PHP Helper Functions for Server-side Notifications
 */

// Add notification to session for display on next page load
function add_notification($type, $title, $message = '') {
    if (!session_id()) {
        session_start();
    }
    
    if (!isset($_SESSION['notification_messages'])) {
        $_SESSION['notification_messages'] = [];
    }
    
    $_SESSION['notification_messages'][] = [
        'type' => $type,
        'title' => addslashes($title),
        'message' => addslashes($message)
    ];
}

function add_success_notification($title, $message = '') {
    add_notification('success', $title, $message);
}

function add_error_notification($title, $message = '') {
    add_notification('error', $title, $message);
}

function add_warning_notification($title, $message = '') {
    add_notification('warning', $title, $message);
}

function add_info_notification($title, $message = '') {
    add_notification('info', $title, $message);
}
?> 