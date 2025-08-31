<?php
/**
 * Template Name: Notifications
 * 
 * Dedicated page for viewing and managing notifications
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

$current_user_id = get_current_user_id();

get_header();
?>

<div class="notifications-page">
    <!-- Hero Section -->
    <div class="notifications-hero">
        <div class="container">
            <div class="hero-content">
                <div class="page-header">
                    <h1>Notifications</h1>
                    <p>Manage and view your notification history</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Filters and Controls -->
        <div class="notifications-controls">
            <div class="filters-section">
                <div class="filter-group">
                    <label for="type-filter">Type:</label>
                    <select id="type-filter" class="filter-select">
                        <option value="">All Types</option>
                        <option value="success">Success</option>
                        <option value="error">Error</option>
                        <option value="warning">Warning</option>
                        <option value="info">Info</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="status-filter">Status:</label>
                    <select id="status-filter" class="filter-select">
                        <option value="">All</option>
                        <option value="unread">Unread</option>
                        <option value="read">Read</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="search-filter">Search:</label>
                    <input type="text" id="search-filter" class="search-input" placeholder="Search notifications...">
                </div>
            </div>
            
            <div class="actions-section">
                <button class="btn btn-secondary" onclick="NotificationsPage.markAllAsRead()">
                    Mark All Read
                </button>
                <button class="btn btn-danger" onclick="NotificationsPage.clearAll()">
                    Clear All
                </button>
                <button class="btn btn-primary" onclick="NotificationsPage.refresh()">
                    Refresh
                </button>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="notifications-content">
            <div class="notifications-stats">
                <div class="stat-item">
                    <span class="stat-number" id="total-count">0</span>
                    <span class="stat-label">Total</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" id="unread-count">0</span>
                    <span class="stat-label">Unread</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" id="today-count">0</span>
                    <span class="stat-label">Today</span>
                </div>
            </div>

            <div class="notifications-list" id="notifications-list">
                <div class="loading-state">
                    <div class="spinner"></div>
                    <p>Loading notifications...</p>
                </div>
            </div>

            <!-- Pagination -->
            <div class="pagination-container" id="pagination-container" style="display: none;">
                <div class="pagination-info">
                    <span id="pagination-info">Showing 0 of 0 notifications</span>
                </div>
                <div class="pagination-controls">
                    <button class="pagination-btn" id="prev-btn" onclick="NotificationsPage.previousPage()">
                        ← Previous
                    </button>
                    <span class="pagination-pages" id="pagination-pages"></span>
                    <button class="pagination-btn" id="next-btn" onclick="NotificationsPage.nextPage()">
                        Next →
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Notifications Page Styles */
.notifications-page {
    background: #f8fafc;
    min-height: 100vh;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Hero Section */
.notifications-hero {
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
    color: white;
    padding: 120px 0 60px 0;
    margin-bottom: 40px;
}

.hero-content {
    text-align: center;
}

.page-header h1 {
    font-size: 3rem;
    font-weight: 700;
    margin: 0 0 16px 0;
    color: white;
}

.page-header p {
    font-size: 1.2rem;
    color: rgba(255, 255, 255, 0.9);
    margin: 0;
}

/* Controls Section */
.notifications-controls {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    flex-wrap: wrap;
}

.filters-section {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    flex: 1;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.filter-group label {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-select,
.search-input {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s;
    min-width: 120px;
}

.search-input {
    min-width: 200px;
}

.filter-select:focus,
.search-input:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.actions-section {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.btn-primary {
    background: #8b5cf6;
    color: white;
}

.btn-primary:hover {
    background: #7c3aed;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

/* Content Section */
.notifications-content {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Stats Section */
.notifications-stats {
    background: #f9fafb;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    gap: 40px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1;
}

.stat-label {
    font-size: 12px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
}

/* Notifications List */
.notifications-list {
    min-height: 400px;
}

.loading-state,
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: #6b7280;
}

.spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #f3f4f6;
    border-top: 3px solid #8b5cf6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 16px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Notification Item */
.notification-item {
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: flex-start;
    gap: 16px;
    transition: all 0.2s;
    cursor: pointer;
    position: relative;
}

.notification-item:hover {
    background: #f9fafb;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item.unread {
    background: rgba(59, 130, 246, 0.05);
    border-left: 3px solid #3b82f6;
}

.notification-item.unread::before {
    content: '';
    position: absolute;
    top: 20px;
    right: 20px;
    width: 8px;
    height: 8px;
    background: #3b82f6;
    border-radius: 50%;
}

.notification-icon {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: white;
    font-weight: 600;
}

.notification-icon.success {
    background: #10b981;
}

.notification-icon.error {
    background: #ef4444;
}

.notification-icon.warning {
    background: #f59e0b;
}

.notification-icon.info {
    background: #3b82f6;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-title {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 6px 0;
    line-height: 1.4;
}

.notification-message {
    font-size: 14px;
    color: #6b7280;
    margin: 0 0 8px 0;
    line-height: 1.5;
}

.notification-meta {
    display: flex;
    align-items: center;
    gap: 16px;
    font-size: 12px;
    color: #9ca3af;
}

.notification-time {
    display: flex;
    align-items: center;
    gap: 4px;
}

.notification-type {
    display: flex;
    align-items: center;
    gap: 4px;
    text-transform: capitalize;
}

.notification-actions {
    flex-shrink: 0;
    display: flex;
    gap: 8px;
    opacity: 0;
    transition: opacity 0.2s;
}

.notification-item:hover .notification-actions {
    opacity: 1;
}

.action-btn {
    background: none;
    border: none;
    padding: 6px;
    border-radius: 4px;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.2s;
    font-size: 14px;
}

.action-btn:hover {
    background: #f3f4f6;
    color: #1f2937;
}

/* Pagination */
.pagination-container {
    padding: 20px 24px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pagination-info {
    font-size: 14px;
    color: #6b7280;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 12px;
}

.pagination-btn {
    padding: 6px 12px;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.pagination-btn:hover:not(:disabled) {
    background: #f9fafb;
    border-color: #9ca3af;
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination-pages {
    display: flex;
    gap: 4px;
}

.page-btn {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s;
}

.page-btn:hover {
    background: #f9fafb;
}

.page-btn.active {
    background: #8b5cf6;
    color: white;
    border-color: #8b5cf6;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .notifications-hero {
        padding: 80px 0 40px 0;
    }
    
    .page-header h1 {
        font-size: 2.5rem;
    }
    
    .notifications-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filters-section {
        flex-direction: column;
        gap: 16px;
    }
    
    .filter-group {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
    
    .filter-select,
    .search-input {
        min-width: 0;
        flex: 1;
        margin-left: 12px;
    }
    
    .actions-section {
        justify-content: center;
    }
    
    .notifications-stats {
        justify-content: space-around;
        gap: 20px;
    }
    
    .notification-item {
        padding: 16px;
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    
    .notification-actions {
        opacity: 1;
        justify-content: center;
    }
    
    .pagination-container {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .container {
        padding: 0 16px;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
    
    .notifications-controls {
        padding: 16px;
    }
    
    .btn {
        padding: 10px 14px;
        font-size: 13px;
    }
}
</style>

<script>
/**
 * Notifications Page Management
 */
class NotificationsPageManager {
    constructor() {
        this.notifications = [];
        this.filteredNotifications = [];
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.filters = {
            type: '',
            status: '',
            search: ''
        };
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadNotifications();
    }
    
    bindEvents() {
        // Filter events
        document.getElementById('type-filter').addEventListener('change', (e) => {
            this.filters.type = e.target.value;
            this.applyFilters();
        });
        
        document.getElementById('status-filter').addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.applyFilters();
        });
        
        document.getElementById('search-filter').addEventListener('input', (e) => {
            this.filters.search = e.target.value.toLowerCase();
            this.applyFilters();
        });
    }
    
    loadNotifications() {
        // Get notifications from localStorage and header notifications
        const headerNotifications = JSON.parse(localStorage.getItem('headerNotifications') || '[]');
        
        // Add some demo notifications if none exist
        if (headerNotifications.length === 0) {
            const demoNotifications = this.generateDemoNotifications();
            this.notifications = demoNotifications;
            
            // Save demo notifications to localStorage
            localStorage.setItem('headerNotifications', JSON.stringify(demoNotifications));
        } else {
            this.notifications = headerNotifications;
        }
        
        this.applyFilters();
    }
    
    generateDemoNotifications() {
        const now = new Date();
        const demo = [];
        
        // Generate some sample notifications with different timestamps
        const samples = [
            { type: 'success', title: 'Profile Updated', message: 'Your profile information has been successfully updated.', hours: 1 },
            { type: 'info', title: 'New Course Available', message: 'A new CPD course "Ethics in Financial Planning" is now available.', hours: 3 },
            { type: 'warning', title: 'CPD Deadline Reminder', message: 'You have 30 days remaining to complete your annual CPD requirements.', hours: 6 },
            { type: 'success', title: 'Certificate Generated', message: 'Your 2024 CPD certificate has been generated and is ready for download.', hours: 12 },
            { type: 'error', title: 'Payment Failed', message: 'Your recent payment attempt was unsuccessful. Please update your payment method.', hours: 24 },
            { type: 'info', title: 'System Maintenance', message: 'Scheduled maintenance will occur this weekend from 2 AM to 6 AM.', hours: 48 },
            { type: 'success', title: 'Course Completed', message: 'Congratulations! You have successfully completed "Investment Principles 2024".', hours: 72 },
            { type: 'warning', title: 'Profile Incomplete', message: 'Please complete your profile to access all member benefits.', hours: 96 }
        ];
        
        samples.forEach((sample, index) => {
            const timestamp = new Date(now.getTime() - (sample.hours * 60 * 60 * 1000));
            demo.push({
                id: `demo_${index}`,
                type: sample.type,
                title: sample.title,
                message: sample.message,
                timestamp: timestamp.toISOString(),
                read: Math.random() > 0.4 // 60% chance of being read
            });
        });
        
        return demo;
    }
    
    applyFilters() {
        this.filteredNotifications = this.notifications.filter(notification => {
            // Type filter
            if (this.filters.type && notification.type !== this.filters.type) {
                return false;
            }
            
            // Status filter
            if (this.filters.status === 'read' && !notification.read) {
                return false;
            }
            if (this.filters.status === 'unread' && notification.read) {
                return false;
            }
            
            // Search filter
            if (this.filters.search) {
                const searchText = this.filters.search;
                const title = notification.title.toLowerCase();
                const message = notification.message.toLowerCase();
                
                if (!title.includes(searchText) && !message.includes(searchText)) {
                    return false;
                }
            }
            
            return true;
        });
        
        this.currentPage = 1;
        this.updateStats();
        this.renderNotifications();
        this.renderPagination();
    }
    
    updateStats() {
        const total = this.notifications.length;
        const unread = this.notifications.filter(n => !n.read).length;
        const today = this.notifications.filter(n => {
            const notificationDate = new Date(n.timestamp);
            const todayDate = new Date();
            return notificationDate.toDateString() === todayDate.toDateString();
        }).length;
        
        document.getElementById('total-count').textContent = total;
        document.getElementById('unread-count').textContent = unread;
        document.getElementById('today-count').textContent = today;
    }
    
    renderNotifications() {
        const listContainer = document.getElementById('notifications-list');
        
        if (this.filteredNotifications.length === 0) {
            listContainer.innerHTML = `
                <div class="empty-state">
                    <p>No notifications found</p>
                </div>
            `;
            return;
        }
        
        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        const pageNotifications = this.filteredNotifications.slice(startIndex, endIndex);
        
        listContainer.innerHTML = pageNotifications.map(notification => {
            const timeAgo = this.getTimeAgo(notification.timestamp);
            const iconSymbol = this.getIconSymbol(notification.type);
            
            return `
                <div class="notification-item ${!notification.read ? 'unread' : ''}" 
                     onclick="NotificationsPage.toggleRead('${notification.id}')">
                    <div class="notification-icon ${notification.type}">
                        ${iconSymbol}
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">${this.escapeHtml(notification.title)}</div>
                        <div class="notification-message">${this.escapeHtml(notification.message)}</div>
                        <div class="notification-meta">
                            <div class="notification-time">
                                <span>🕐</span>
                                <span>${timeAgo}</span>
                            </div>
                            <div class="notification-type">
                                <span>🏷️</span>
                                <span>${notification.type}</span>
                            </div>
                        </div>
                    </div>
                    <div class="notification-actions">
                        <button class="action-btn" onclick="event.stopPropagation(); NotificationsPage.toggleRead('${notification.id}')" 
                                title="${notification.read ? 'Mark as unread' : 'Mark as read'}">
                            ${notification.read ? '👁️' : '✉️'}
                        </button>
                        <button class="action-btn" onclick="event.stopPropagation(); NotificationsPage.deleteNotification('${notification.id}')" 
                                title="Delete">
                            🗑️
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    renderPagination() {
        const container = document.getElementById('pagination-container');
        const totalPages = Math.ceil(this.filteredNotifications.length / this.itemsPerPage);
        
        if (totalPages <= 1) {
            container.style.display = 'none';
            return;
        }
        
        container.style.display = 'flex';
        
        const startItem = (this.currentPage - 1) * this.itemsPerPage + 1;
        const endItem = Math.min(this.currentPage * this.itemsPerPage, this.filteredNotifications.length);
        
        document.getElementById('pagination-info').textContent = 
            `Showing ${startItem}-${endItem} of ${this.filteredNotifications.length} notifications`;
        
        document.getElementById('prev-btn').disabled = this.currentPage === 1;
        document.getElementById('next-btn').disabled = this.currentPage === totalPages;
        
        // Generate page buttons
        const pagesContainer = document.getElementById('pagination-pages');
        const pageButtons = [];
        
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= this.currentPage - 2 && i <= this.currentPage + 2)) {
                pageButtons.push(`
                    <button class="page-btn ${i === this.currentPage ? 'active' : ''}" 
                            onclick="NotificationsPage.goToPage(${i})">
                        ${i}
                    </button>
                `);
            } else if (i === this.currentPage - 3 || i === this.currentPage + 3) {
                pageButtons.push('<span style="padding: 8px;">...</span>');
            }
        }
        
        pagesContainer.innerHTML = pageButtons.join('');
    }
    
    toggleRead(notificationId) {
        const notification = this.notifications.find(n => n.id === notificationId);
        if (notification) {
            notification.read = !notification.read;
            this.saveNotifications();
            this.applyFilters();
            
            // Update header notification system
            if (window.HeaderNotifications) {
                window.HeaderNotifications.headerNotifications = this.notifications;
                window.HeaderNotifications.saveToStorage();
                window.HeaderNotifications.updateBadge();
                window.HeaderNotifications.renderNotifications();
            }
        }
    }
    
    deleteNotification(notificationId) {
        if (confirm('Are you sure you want to delete this notification?')) {
            this.notifications = this.notifications.filter(n => n.id !== notificationId);
            this.saveNotifications();
            this.applyFilters();
            
            // Update header notification system
            if (window.HeaderNotifications) {
                window.HeaderNotifications.headerNotifications = this.notifications;
                window.HeaderNotifications.saveToStorage();
                window.HeaderNotifications.updateBadge();
                window.HeaderNotifications.renderNotifications();
            }
        }
    }
    
    markAllAsRead() {
        this.notifications.forEach(notification => {
            notification.read = true;
        });
        this.saveNotifications();
        this.applyFilters();
        
        // Update header notification system
        if (window.HeaderNotifications) {
            window.HeaderNotifications.headerNotifications = this.notifications;
            window.HeaderNotifications.saveToStorage();
            window.HeaderNotifications.updateBadge();
            window.HeaderNotifications.renderNotifications();
        }
        
        // Show success message
        if (window.notifications) {
            window.notifications.success('Success', 'All notifications marked as read');
        }
    }
    
    clearAll() {
        if (confirm('Are you sure you want to delete all notifications? This action cannot be undone.')) {
            this.notifications = [];
            this.saveNotifications();
            this.applyFilters();
            
            // Update header notification system
            if (window.HeaderNotifications) {
                window.HeaderNotifications.headerNotifications = [];
                window.HeaderNotifications.saveToStorage();
                window.HeaderNotifications.updateBadge();
                window.HeaderNotifications.renderNotifications();
            }
            
            // Show success message
            if (window.notifications) {
                window.notifications.success('Success', 'All notifications cleared');
            }
        }
    }
    
    refresh() {
        this.loadNotifications();
        
        // Show success message
        if (window.notifications) {
            window.notifications.info('Refreshed', 'Notifications have been refreshed');
        }
    }
    
    goToPage(page) {
        this.currentPage = page;
        this.renderNotifications();
        this.renderPagination();
    }
    
    previousPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.renderNotifications();
            this.renderPagination();
        }
    }
    
    nextPage() {
        const totalPages = Math.ceil(this.filteredNotifications.length / this.itemsPerPage);
        if (this.currentPage < totalPages) {
            this.currentPage++;
            this.renderNotifications();
            this.renderPagination();
        }
    }
    
    saveNotifications() {
        localStorage.setItem('headerNotifications', JSON.stringify(this.notifications));
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
    
    getIconSymbol(type) {
        const icons = {
            success: '✓',
            error: '✕',
            warning: '!',
            info: 'i'
        };
        return icons[type] || icons.info;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize notifications page
const NotificationsPage = new NotificationsPageManager();

// Expose to window for global access
window.NotificationsPage = NotificationsPage;
</script>

<?php get_footer(); ?> 