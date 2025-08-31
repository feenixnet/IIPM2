<?php
/**
 * Template Name: Training History
 * 
 * Displays member's complete CPD training history with filtering
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
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

// Include header
get_header();
?>

<div class="training-history-page">
    <!-- Hero Section -->
    <div class="portal-hero">
        <div class="container">
            <div class="hero-content">
                <nav class="breadcrumb">
                    <a href="<?php echo home_url('/member-portal/'); ?>">Home</a>
                    <span class="separator">></span>
                    <span class="current">Your Training History</span>
                </nav>
                <h1 class="portal-title">Your Training History</h1>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="training-content">
            <!-- Progress Summary Sidebar -->
            <div class="progress-sidebar">
                <div class="progress-card">
                    <h2>Your Progress</h2>
                    <div class="progress-categories" id="progress-categories">
                        <!-- Progress will be loaded dynamically -->
                    </div>
                    <div class="total-progress">
                        <div class="total-label">Total</div>
                        <div class="total-count" id="total-progress">0/4</div>
                    </div>
                    <button class="log-training-btn" onclick="showCPDForm()">Log my training</button>
                </div>

                <!-- Filters -->
                <div class="filters-card">
                    <h3>Filter</h3>
                    <div class="filter-group">
                        <label for="title-filter">Title</label>
                        <div class="search-input">
                            <input type="text" id="title-filter" placeholder="Insert course title">
                            <span class="search-icon">🔍</span>
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <label for="lia-code-filter">LIA Code</label>
                        <div class="search-input">
                            <input type="text" id="lia-code-filter" placeholder="">
                            <span class="search-icon">🔍</span>
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <label for="date-range-filter">Date Range</label>
                        <div class="date-range">
                            <input type="date" id="date-from" placeholder="DD/MM/YYYY">
                            <span class="date-separator">-</span>
                            <input type="date" id="date-to" placeholder="DD/MM/YYYY">
                            <span class="search-icon">🔍</span>
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <label>Category</label>
                        <div class="category-filters" id="category-filters">
                            <!-- Categories will be loaded dynamically -->
                        </div>
                    </div>
                    
                    <button class="clear-filters-btn" onclick="clearAllFilters()">Clear all filters</button>
                </div>
            </div>

            <!-- Main Training History -->
            <div class="training-main">
                <div class="training-header">
                    <div class="results-info">
                        <span id="results-count">Loading training records...</span>
                    </div>
                    <div class="view-controls">
                        <button class="export-btn" onclick="exportTrainingHistory()">📊 Export</button>
                        <button class="print-btn" onclick="printTrainingHistory()">🖨️ Print</button>
                    </div>
                </div>
                
                <div class="training-list" id="training-list">
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <p>Loading your training history...</p>
                    </div>
                </div>
                
                <!-- Pagination -->
                <div class="pagination-container" id="pagination-container" style="display: none;">
                    <div class="pagination-info">
                        <span id="pagination-info">Showing 1-10 of 15</span>
                    </div>
                    <div class="pagination-controls">
                        <button class="pagination-btn" id="prev-page" onclick="changePage(-1)">
                            <span>‹</span>
                        </button>
                        <div class="page-numbers" id="page-numbers">
                            <!-- Page numbers will be populated by JavaScript -->
                        </div>
                        <button class="pagination-btn" id="next-page" onclick="changePage(1)">
                            <span>›</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CPD Form Modal -->
<div class="modal" id="cpdSelectionModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h5 class="modal-title">Log CPD Training</h5>
            <button type="button" class="modal-close" onclick="closeCPDModal('cpdSelectionModal')">&times;</button>
        </div>
        <div class="modal-body" style="padding: 30px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <p style="font-size: 16px; color: #6b7280; margin-bottom: 30px;">Choose how you'd like to log your CPD training:</p>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="cpd-option-card" onclick="selectCPDType('course')" style="border: 2px solid #e5e7eb; border-radius: 12px; padding: 25px; cursor: pointer; text-align: center; transition: all 0.3s; background: white;">
                    <div style="font-size: 3rem; margin-bottom: 15px;">📚</div>
                    <h3 style="margin: 0 0 10px 0; color: #1f2937; font-size: 18px;">Pre-approved Course</h3>
                    <p style="margin: 0; color: #6b7280; font-size: 14px; line-height: 1.5;">Select from our library of approved courses.</p>
                </div>
                
                <div class="cpd-option-card" onclick="selectCPDType('external')" style="border: 2px solid #e5e7eb; border-radius: 12px; padding: 25px; cursor: pointer; text-align: center; transition: all 0.3s; background: white;">
                    <div style="font-size: 3rem; margin-bottom: 15px;">📝</div>
                    <h3 style="margin: 0 0 10px 0; color: #1f2937; font-size: 18px;">External Training</h3>
                    <p style="margin: 0; color: #6b7280; font-size: 14px; line-height: 1.5;">Submit training from external providers.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal" id="deleteConfirmationModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body" style="padding: 40px; text-align: center;">
                <div class="delete-icon">
                    <div class="question-circle">
                        <span>?</span>
                    </div>
                </div>
                <h3 class="delete-title">Confirm course removal</h3>
                <p class="delete-message" id="deleteMessage">
                    <!-- Message will be populated by JavaScript -->
                </p>
                <div class="delete-actions">
                    <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                    <button type="button" class="btn-confirm" onclick="confirmDelete()">Confirm</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Success Modal -->
<div class="modal" id="deleteSuccessModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body" style="padding: 40px; text-align: center;">
                <div class="success-icon">
                    <div class="check-circle">
                        <span>✓</span>
                    </div>
                </div>
                <h3 class="success-title">Course removed from your CPD</h3>
                <p class="success-message" id="successMessage">
                    <!-- Message will be populated by JavaScript -->
                </p>
                <div class="success-actions">
                    <button type="button" class="btn-undo" onclick="undoDelete()">Undo</button>
                    <button type="button" class="btn-ok" onclick="closeSuccessModal()">OK</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Training History Styles */
.training-history-page {
    background: #f8fafc;
    min-height: 100vh;
    margin: 0;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    padding-bottom: 30px;
}

.portal-hero {
    background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('<?php echo get_template_directory_uri(); ?>/assets/images/portal-hero-bg.jpg');
    background-size: cover;
    background-position: center;
    color: white;
    padding-top: 120px;
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

.breadcrumb {
    font-size: 14px;
    color: rgba(255,255,255,0.8);
    margin-bottom: 20px;
}

.breadcrumb a {
    color: rgba(255,255,255,0.9);
    text-decoration: none;
}

.breadcrumb .separator {
    margin: 0 8px;
}

.breadcrumb .current {
    color: white;
}

.container {
    max-width: 1300px !important;
    margin: 0 auto !important;
    padding: 0 15px !important;
    width: 100% !important;
}

.training-content {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 30px;
    align-items: start;
    margin-top: -30px;
    position: relative;
    z-index: 10;
}

/* Progress Sidebar */
.progress-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
    position: sticky;
    top: 20px;
}

.progress-card,
.filters-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.07);
    width: 100%;
    box-sizing: border-box;
    overflow: hidden;
}

.progress-card h2,
.filters-card h3 {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 20px 0;
}

.progress-categories {
    margin-bottom: 20px;
}

.category-progress {
    margin-bottom: 16px;
}

.category-label {
    font-size: 12px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
}

.progress-bar {
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 6px;
}

.progress-fill {
    height: 100%;
    background: #ef4444;
    border-radius: 4px;
    transition: all 0.3s ease;
    width: 0%;
}

.progress-fill.complete {
    background: #ff6b35;
}

.category-count {
    font-size: 12px;
    color: #6b7280;
    text-align: right;
}

.total-progress {
    padding-top: 16px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.total-label {
    font-weight: 600;
    color: #1f2937;
}

.total-count {
    font-weight: 600;
    color: #ef4444;
}

.log-training-btn {
    width: 100%;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
}

.log-training-btn:hover {
    background: #dc2626;
}

/* Filters */
.filter-group {
    margin-bottom: 16px;
}

.filter-group label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
}

.search-input {
    position: relative;
}

.search-input input,
.filter-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    background: white;
    box-sizing: border-box;
}

.search-input input {
    padding-right: 40px;
}

.search-input input:focus,
.filter-group select:focus {
    outline: none;
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.search-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
}

.date-range {
    display: flex;
    align-items: center;
    gap: 6px;
    position: relative;
    width: 100%;
}

.date-range input {
    flex: 1;
    min-width: 0;
    max-width: calc(50% - 10px);
    padding: 8px 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 13px;
    box-sizing: border-box;
}

.date-separator {
    color: #6b7280;
    font-weight: 500;
    flex-shrink: 0;
    font-size: 14px;
}

.date-range .search-icon {
    position: static;
    margin-left: auto;
    flex-shrink: 0;
    color: #9ca3af;
    cursor: pointer;
}

.clear-filters-btn {
    width: 100%;
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 10px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.clear-filters-btn:hover {
    background: #e5e7eb;
}

.category-filters {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.custom-checkbox-label {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    color: #374151;
    cursor: pointer;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.custom-checkbox-label:last-child {
    border-bottom: none;
}

.custom-checkbox-label input[type="checkbox"] {
    margin: 0;
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #ff6b35;
    flex-shrink: 0;
    border: 2px solid #d1d5db;
    border-radius: 3px;
    background: white;
}

.custom-checkbox-label input[type="checkbox"]:checked {
    background: #ff6b35;
    border-color: #ff6b35;
}

.custom-checkbox-label input[type="checkbox"]:hover {
    border-color: #ff6b35;
}

.category-text {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex: 1;
}

.course-count {
    color: #9ca3af;
    font-size: 13px;
    font-weight: 500;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .training-content {
        grid-template-columns: 280px 1fr;
        gap: 20px;
    }
    
    .progress-card,
    .filters-card {
        padding: 20px;
    }
}

@media (max-width: 768px) {
    .training-content {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .progress-sidebar {
        order: 2;
    }
    
    .training-main {
        order: 1;
    }
    
    .date-range {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    
    .date-range input {
        max-width: 100%;
    }
    
    .date-separator {
        text-align: center;
    }
    
    .date-range .search-icon {
        align-self: center;
    }
}

/* Main Training Section */
.training-main {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.07);
}

.training-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.results-info {
    font-size: 14px;
    color: #6b7280;
}

.view-controls {
    display: flex;
    gap: 10px;
}

.export-btn,
.print-btn {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 8px 12px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.export-btn:hover,
.print-btn:hover {
    background: #e5e7eb;
}

/* Training List Items */
.training-item {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 16px;
    transition: all 0.2s;
}

.training-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #d1d5db;
}

.training-item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}

.category-badge-container {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.category-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: white;
    width: fit-content;
}

/* Category Badge Colors */
.category-pensions {
    background: #ff6b35;
}

.category-ethics {
    background: #8b5cf6;
}

.category-savings {
    background: #06b6d4;
}

.category-life {
    background: #6b7280;
}

.category-technology {
    background: #10b981;
}

.category-regulation {
    background: #f59e0b;
}

.category-professional {
    background: #ef4444;
}

.category-general {
    background: #3b82f6;
}

.category-default {
    background: #6b7280;
}

.added-date {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 2px;
}

.training-header-item {
    margin-bottom: 12px;
}

.training-title {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    line-height: 1.4;
}

.training-badges {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-shrink: 0;
    margin-left: 16px;
}

.delete-training-btn {
    background: #dc2626;
    border: none;
    color: white;
    cursor: pointer;
    padding: 8px;
    border-radius: 6px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.delete-training-btn:hover {
    background: #b91c1c;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.delete-training-btn:active {
    transform: translateY(0);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.delete-training-btn svg {
    width: 16px;
    height: 16px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.status-completed {
    background: #dcfce7;
    color: #166534;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-approved {
    background: #dcfce7;
    color: #166534;
}

.cpd-points {
    background: #ddd6fe;
    color: #5b21b6;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.training-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    font-size: 14px;
    color: #6b7280;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.meta-icon {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

/* Loading Spinner */
.loading-spinner {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f4f6;
    border-top: 4px solid #ef4444;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.empty-state .icon {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 18px;
    color: #1f2937;
    margin-bottom: 8px;
}

.empty-state p {
    margin-bottom: 20px;
}

/* Pagination */
.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.pagination-info {
    font-size: 14px;
    color: #6b7280;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pagination-btn {
    width: 32px;
    height: 32px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    color: #374151;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.pagination-btn:hover:not(:disabled) {
    background: #f3f4f6;
    border-color: #ef4444;
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.page-numbers {
    display: flex;
    gap: 4px;
}

.page-number {
    width: 32px;
    height: 32px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    color: #374151;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: all 0.2s;
}

.page-number:hover {
    background: #f3f4f6;
    border-color: #ef4444;
}

.page-number.active {
    background: #ef4444;
    color: white;
    border-color: #ef4444;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-dialog {
    background: white;
    border-radius: 12px;
    padding: 0;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #6b7280;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cpd-option-card:hover {
    border-color: #ef4444 !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15) !important;
}

/* Delete Confirmation Modal Styles */
.delete-icon {
    margin-bottom: 24px;
}

.question-circle {
    width: 80px;
    height: 80px;
    background: #dc2626;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

.question-circle span {
    color: white;
    font-size: 36px;
    font-weight: bold;
}

.delete-title {
    font-size: 20px;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 16px 0;
}

.delete-message {
    font-size: 16px;
    color: #6b7280;
    margin: 0 0 32px 0;
    line-height: 1.5;
}

.delete-actions {
    display: flex;
    gap: 16px;
    justify-content: center;
}

.btn-cancel,
.btn-confirm {
    padding: 12px 32px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 120px;
}

.btn-cancel {
    background: #f3f4f6;
    color: #374151;
}

.btn-cancel:hover {
    background: #e5e7eb;
}

.btn-confirm {
    background: #dc2626;
    color: white;
}

.btn-confirm:hover {
    background: #b91c1c;
    transform: translateY(-1px);
}

/* Delete Success Modal Styles */
.success-icon {
    margin-bottom: 24px;
}

.check-circle {
    width: 80px;
    height: 80px;
    background: #000000;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.check-circle span {
    color: white;
    font-size: 36px;
    font-weight: bold;
}

.success-title {
    font-size: 20px;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 16px 0;
}

.success-message {
    font-size: 16px;
    color: #6b7280;
    margin: 0 0 32px 0;
    line-height: 1.5;
}

.success-actions {
    display: flex;
    gap: 16px;
    justify-content: center;
}

.btn-undo,
.btn-ok {
    padding: 12px 32px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 120px;
}

.btn-undo {
    background: #f3f4f6;
    color: #374151;
}

.btn-undo:hover {
    background: #e5e7eb;
}

.btn-ok {
    background: #8b5cf6;
    color: white;
}

.btn-ok:hover {
    background: #7c3aed;
    transform: translateY(-1px);
}

/* Responsive Design */
@media (max-width: 1024px) {
    .training-content {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .progress-sidebar {
        position: static;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
}

@media (max-width: 768px) {
    .portal-title {
        font-size: 2rem;
    }
    
    .progress-sidebar {
        grid-template-columns: 1fr;
    }
    
    .training-header {
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
    }
    
    .view-controls {
        justify-content: center;
    }
    
    .training-item-header {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
    }
    
    .delete-training-btn {
        align-self: flex-end;
    }
    
    .training-meta {
        grid-template-columns: 1fr;
    }
    
    .pagination-container {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .portal-hero {
        padding-top: 120px;
        padding-bottom: 40px;
    }
    
    .training-content {
        margin-top: -20px;
    }
    
    .progress-card,
    .filters-card,
    .training-main {
        padding: 16px;
    }
}
</style>

<script>
// Global variables
let currentPage = 1;
let totalPages = 1;
let trainingRecords = [];
let filteredRecords = [];
const itemsPerPage = 10;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadTrainingHistory();
    setupFilters();
});

// Load training history data
function loadTrainingHistory() {
    const formData = new FormData();
    formData.append('action', 'iipm_get_training_history');
    formData.append('nonce', '<?php echo wp_create_nonce('iipm_cpd_nonce'); ?>');
    formData.append('user_id', <?php echo get_current_user_id(); ?>);
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            trainingRecords = data.data.records || [];
            filteredRecords = [...trainingRecords];
            updateProgressSummary(data.data.summary || {});
            displayTrainingRecords();
            updatePagination();
        } else {
            showError('Failed to load training history');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Failed to load training history');
    });
}

// Update progress summary and category filters
function updateProgressSummary(summary) {
    const categories = [
        { name: 'Pensions', key: 'Pensions' },
        { name: 'Savings & Investment', key: 'Savings & Investment' },
        { name: 'Ethics', key: 'Ethics' },
        { name: 'Life Assurance', key: 'Life Assurance' }
    ];
    
    let totalProgress = 0;
    let progressHTML = '';
    
    // Build progress display and collect category counts for filters
    const categoryCounts = {};
    
    categories.forEach(category => {
        const categoryData = summary.categories && summary.categories[category.key];
        const points = categoryData ? categoryData.points : 0;
        const required = 1; // Each category requires 1 point minimum
        const progress = Math.min((points / required) * 100, 100);
        const isComplete = progress >= 100;
        
        if (isComplete) totalProgress++;
        
        progressHTML += `
            <div class="category-progress">
                <div class="category-label">${category.name}</div>
                <div class="progress-bar">
                    <div class="progress-fill ${isComplete ? 'complete' : ''}" style="width: ${progress}%"></div>
                </div>
                <div class="category-count">${points}/${required}</div>
            </div>
        `;
        
        // Count records for this category
        const categoryRecords = trainingRecords.filter(record => record.category_name === category.key);
        categoryCounts[category.key] = categoryRecords.length;
    });
    
    document.getElementById('progress-categories').innerHTML = progressHTML;
    
    // Update total progress
    const totalElement = document.getElementById('total-progress');
    if (totalElement) {
        totalElement.textContent = `${totalProgress}/4`;
        totalElement.style.color = totalProgress === 4 ? '#ff6b35' : '#ef4444';
    }
    
    // Build category filters
    buildCategoryFilters(categories, categoryCounts);
}

// Build category filter checkboxes
function buildCategoryFilters(categories, categoryCounts) {
    let categoryFiltersHTML = '';
    
    categories.forEach(category => {
        const count = categoryCounts[category.key] || 0;
        categoryFiltersHTML += `
            <label class="custom-checkbox-label">
                <input type="checkbox" name="category" value="${category.key}" checked>
                <span class="category-text">
                    ${category.name}
                    <span class="course-count">(${count})</span>
                </span>
            </label>
        `;
    });
    
    document.getElementById('category-filters').innerHTML = categoryFiltersHTML;
    
    // Re-setup filter event listeners for new checkboxes
    setupCategoryFilters();
}

// Display training records
function displayTrainingRecords() {
    const trainingList = document.getElementById('training-list');
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const pageRecords = filteredRecords.slice(startIndex, endIndex);
    
    if (pageRecords.length === 0) {
        trainingList.innerHTML = `
            <div class="empty-state">
                <div class="icon">📚</div>
                <h3>No training records found</h3>
                <p>Start by logging your CPD training activities.</p>
                <button class="log-training-btn" onclick="showCPDForm()" style="width: auto; padding: 12px 24px;">Log Training</button>
            </div>
        `;
        return;
    }
    
    trainingList.innerHTML = pageRecords.map(record => `
        <div class="training-item" data-record-id="${record.id}">
            <div class="training-item-header">
                <div class="category-badge-container">
                    <span class="category-badge ${getCategoryBadgeClass(record.category_name)}">${escapeHtml(record.category_name || 'General')}</span>
                    <span class="added-date">Added on ${formatAddedDate(record.created_at || record.completion_date)}</span>
                </div>
                <button class="delete-training-btn" onclick="showDeleteConfirmation(${record.id}, '${escapeHtml(record.activity_title || record.course_title || 'Training Activity').replace(/'/g, "\\'")}')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 6H5H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M19 6V20C19 21 18 22 17 22H7C6 22 5 21 5 20V6M8 6V4C8 3 9 2 10 2H14C15 2 16 3 16 4V6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M10 11V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M14 11V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            <div class="training-header-item">
                <h4 class="training-title">${escapeHtml(record.activity_title || record.course_title || 'Training Activity')}</h4>
            </div>
            <div class="training-meta">
                <div class="meta-item">
                    <span class="meta-icon">🏢</span>
                    <span>Provided by: ${escapeHtml(record.provider || record.external_provider || 'Irish Life')}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">🔖</span>
                    <span>LIA Code: ${escapeHtml(record.lia_code || 'LIA1818_2025')}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">📅</span>
                    <span>Date: ${formatDate(record.completion_date)}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">📂</span>
                    <span>Category: ${escapeHtml(record.category_name || 'General')}</span>
                </div>
            </div>
        </div>
    `).join('');
    
    // Update results count
    document.getElementById('results-count').textContent = `Showing ${startIndex + 1}-${Math.min(endIndex, filteredRecords.length)} of ${filteredRecords.length} records`;
}

// Setup filters
function setupFilters() {
    const titleFilter = document.getElementById('title-filter');
    const liaCodeFilter = document.getElementById('lia-code-filter');
    const dateFromFilter = document.getElementById('date-from');
    const dateToFilter = document.getElementById('date-to');
    
    [titleFilter, liaCodeFilter, dateFromFilter, dateToFilter].forEach(filter => {
        if (filter) {
            filter.addEventListener('input', applyFilters);
            filter.addEventListener('change', applyFilters);
        }
    });
}

// Setup category filter checkboxes
function setupCategoryFilters() {
    const categoryCheckboxes = document.querySelectorAll('input[name="category"]');
    categoryCheckboxes.forEach(cb => {
        cb.addEventListener('change', applyFilters);
    });
}

// Apply filters
function applyFilters() {
    const titleFilter = document.getElementById('title-filter').value.toLowerCase();
    const liaCodeFilter = document.getElementById('lia-code-filter').value.toLowerCase();
    const dateFrom = document.getElementById('date-from').value;
    const dateTo = document.getElementById('date-to').value;
    
    // Get selected categories from checkboxes
    const categoryCheckboxes = document.querySelectorAll('input[name="category"]');
    const selectedCategories = Array.from(categoryCheckboxes)
        .filter(cb => cb.checked)
        .map(cb => cb.value);
    
    filteredRecords = trainingRecords.filter(record => {
        // Title filter
        if (titleFilter && !(record.activity_title || record.course_title || '').toLowerCase().includes(titleFilter)) {
            return false;
        }
        
        // LIA Code filter
        if (liaCodeFilter && !(record.lia_code || '').toLowerCase().includes(liaCodeFilter)) {
            return false;
        }
        
        // Date range filter
        if (dateFrom && new Date(record.completion_date) < new Date(dateFrom)) {
            return false;
        }
        if (dateTo && new Date(record.completion_date) > new Date(dateTo)) {
            return false;
        }
        
        // Category filter (checkbox-based)
        if (selectedCategories.length > 0 && !selectedCategories.includes(record.category_name)) {
            return false;
        }
        
        return true;
    });
    
    currentPage = 1;
    displayTrainingRecords();
    updatePagination();
}

// Clear all filters
function clearAllFilters() {
    document.getElementById('title-filter').value = '';
    document.getElementById('lia-code-filter').value = '';
    document.getElementById('date-from').value = '';
    document.getElementById('date-to').value = '';
    
    // Check all category checkboxes
    const categoryCheckboxes = document.querySelectorAll('input[name="category"]');
    categoryCheckboxes.forEach(cb => cb.checked = true);
    
    filteredRecords = [...trainingRecords];
    currentPage = 1;
    displayTrainingRecords();
    updatePagination();
}

// Update pagination
function updatePagination() {
    totalPages = Math.ceil(filteredRecords.length / itemsPerPage);
    const paginationContainer = document.getElementById('pagination-container');
    
    if (totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    }
    
    paginationContainer.style.display = 'flex';
    
    // Update pagination info
    const startIndex = (currentPage - 1) * itemsPerPage + 1;
    const endIndex = Math.min(currentPage * itemsPerPage, filteredRecords.length);
    document.getElementById('pagination-info').textContent = `Showing ${startIndex}-${endIndex} of ${filteredRecords.length}`;
    
    // Update page numbers
    const pageNumbers = document.getElementById('page-numbers');
    pageNumbers.innerHTML = '';
    
    const maxVisiblePages = 5;
    const startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    const endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.className = `page-number ${i === currentPage ? 'active' : ''}`;
        pageBtn.textContent = i;
        pageBtn.onclick = () => goToPage(i);
        pageNumbers.appendChild(pageBtn);
    }
    
    // Update prev/next buttons
    document.getElementById('prev-page').disabled = currentPage === 1;
    document.getElementById('next-page').disabled = currentPage === totalPages;
}

// Page navigation
function changePage(direction) {
    const newPage = currentPage + direction;
    if (newPage >= 1 && newPage <= totalPages) {
        goToPage(newPage);
    }
}

function goToPage(page) {
    currentPage = page;
    displayTrainingRecords();
    updatePagination();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Utility functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getStatusClass(status) {
    switch(status) {
        case 'approved': return 'status-approved';
        case 'completed': return 'status-completed';
        case 'pending': return 'status-pending';
        default: return 'status-pending';
    }
}

function getStatusText(status) {
    switch(status) {
        case 'approved': return 'Completed';
        case 'completed': return 'Completed';
        case 'pending': return 'Pending';
        default: return 'Pending';
    }
}

function getCategoryBadgeClass(categoryName) {
    switch(categoryName) {
        case 'Pensions': return 'category-pensions';
        case 'Ethics': return 'category-ethics';
        case 'Savings & Investment': return 'category-savings';
        case 'Life Assurance': return 'category-life';
        case 'Technology': return 'category-technology';
        case 'Regulation & Compliance': return 'category-regulation';
        case 'Professional Development': return 'category-professional';
        case 'General Insurance': return 'category-general';
        default: return 'category-default';
    }
}

function formatAddedDate(dateString) {
    if (!dateString) return 'Unknown date';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }) + ', ' + date.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

function showError(message) {
    document.getElementById('training-list').innerHTML = `
        <div class="empty-state">
            <div class="icon">⚠️</div>
            <h3>Error Loading Data</h3>
            <p>${message}</p>
            <button onclick="loadTrainingHistory()" style="background: #ef4444; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; margin-top: 16px;">Retry</button>
        </div>
    `;
}

// Export functionality
function exportTrainingHistory() {
    const csvContent = generateCSV(filteredRecords);
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `training_history_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function generateCSV(records) {
    const headers = ['Date', 'Title', 'Provider', 'Category', 'CPD Points', 'Status', 'LIA Code'];
    const csvRows = [headers.join(',')];
    
    records.forEach(record => {
        const row = [
            formatDate(record.completion_date),
            `"${(record.activity_title || record.course_title || '').replace(/"/g, '""')}"`,
            `"${(record.provider || record.external_provider || '').replace(/"/g, '""')}"`,
            `"${(record.category_name || '').replace(/"/g, '""')}"`,
            record.cpd_points || 0,
            record.status || 'pending',
            record.lia_code || ''
        ];
        csvRows.push(row.join(','));
    });
    
    return csvRows.join('\n');
}

// Print functionality
function printTrainingHistory() {
    window.print();
}

// CPD Form functions
function showCPDForm() {
    document.getElementById('cpdSelectionModal').classList.add('show');
    document.getElementById('cpdSelectionModal').style.display = 'flex';
}

function selectCPDType(type) {
    closeCPDModal('cpdSelectionModal');
    // Redirect to member portal for form submission
    window.location.href = '<?php echo home_url('/member-portal/'); ?>?cpd_form=' + type;
}

function closeCPDModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('show');
    modal.style.display = 'none';
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        const modalId = e.target.id;
        if (modalId === 'deleteConfirmationModal') {
            closeDeleteModal();
        } else if (modalId === 'deleteSuccessModal') {
            closeSuccessModal();
        } else {
            closeCPDModal(modalId);
        }
    }
});

// Undo delete functionality
function undoDelete() {
    if (!deletedRecordData) {
        showErrorMessage('No record to restore');
        return;
    }
    
    // Show loading state on undo button
    const undoBtn = document.querySelector('.btn-undo');
    const originalText = undoBtn.textContent;
    undoBtn.textContent = 'Restoring...';
    undoBtn.disabled = true;
    
    const formData = new FormData();
    formData.append('action', 'iipm_restore_training_record');
    formData.append('nonce', '<?php echo wp_create_nonce('iipm_cpd_nonce'); ?>');
    formData.append('record_data', JSON.stringify(deletedRecordData));
    formData.append('user_id', <?php echo get_current_user_id(); ?>);
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add the record back to local arrays
            trainingRecords.push(deletedRecordData);
            // Re-apply current filters
            applyFilters();
            
            closeSuccessModal();
            
            // Show restore success message
            showSuccessMessage('Training record restored successfully');
            
            // Update progress summary if provided
            if (data.data && data.data.summary) {
                updateProgressSummary(data.data.summary);
            }
        } else {
            showErrorMessage(data.data || 'Failed to restore training record');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('Failed to restore training record');
    })
    .finally(() => {
        // Reset button state
        undoBtn.textContent = originalText;
        undoBtn.disabled = false;
    });
}

// Delete functionality
let deleteRecordId = null;
let deletedRecordData = null;

function showDeleteConfirmation(recordId, courseTitle) {
    deleteRecordId = recordId;
    const message = `${courseTitle} (LIA18419_2025) will be removed from your CPD.`;
    document.getElementById('deleteMessage').textContent = message;
    
    const modal = document.getElementById('deleteConfirmationModal');
    modal.classList.add('show');
    modal.style.display = 'flex';
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteConfirmationModal');
    modal.classList.remove('show');
    modal.style.display = 'none';
    deleteRecordId = null;
}

function closeSuccessModal() {
    const modal = document.getElementById('deleteSuccessModal');
    modal.classList.remove('show');
    modal.style.display = 'none';
    deletedRecordData = null;
}

function showSuccessModal(courseTitle) {
    const message = `${courseTitle} (LIA18419_2025) has been removed from your CPD.`;
    document.getElementById('successMessage').textContent = message;
    
    const modal = document.getElementById('deleteSuccessModal');
    modal.classList.add('show');
    modal.style.display = 'flex';
}

function confirmDelete() {
    if (!deleteRecordId) return;
    
    // Show loading state
    const confirmBtn = document.querySelector('.btn-confirm');
    const originalText = confirmBtn.textContent;
    confirmBtn.textContent = 'Deleting...';
    confirmBtn.disabled = true;
    
    const formData = new FormData();
    formData.append('action', 'iipm_delete_training_record');
    formData.append('nonce', '<?php echo wp_create_nonce('iipm_cpd_nonce'); ?>');
    formData.append('record_id', deleteRecordId);
    formData.append('user_id', <?php echo get_current_user_id(); ?>);
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Store deleted record data for potential undo
            deletedRecordData = trainingRecords.find(record => record.id == deleteRecordId);
            
            // Remove the record from local arrays
            trainingRecords = trainingRecords.filter(record => record.id != deleteRecordId);
            filteredRecords = filteredRecords.filter(record => record.id != deleteRecordId);
            
            // Remove the element from DOM with animation
            const trainingItem = document.querySelector(`[data-record-id="${deleteRecordId}"]`);
            if (trainingItem) {
                trainingItem.style.transform = 'translateX(100%)';
                trainingItem.style.opacity = '0';
                trainingItem.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    // Refresh the display
                    displayTrainingRecords();
                    updatePagination();
                    
                    // Update progress summary if provided
                    if (data.data && data.data.summary) {
                        updateProgressSummary(data.data.summary);
                    }
                }, 300);
            }
            
            closeDeleteModal();
            
            // Show success modal with course title
            const courseTitle = deletedRecordData ? (deletedRecordData.activity_title || deletedRecordData.course_title || 'Training Activity') : 'Training Activity';
            showSuccessModal(courseTitle);
        } else {
            showErrorMessage(data.data || 'Failed to delete training record');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('Failed to delete training record');
    })
    .finally(() => {
        // Reset button state
        confirmBtn.textContent = originalText;
        confirmBtn.disabled = false;
    });
}

function showSuccessMessage(message) {
    // Create a temporary success notification
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #10b981;
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1001;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

function showErrorMessage(message) {
    // Create a temporary error notification
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #ef4444;
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1001;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after 4 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 4000);
}
</script>

<?php get_footer(); ?> 