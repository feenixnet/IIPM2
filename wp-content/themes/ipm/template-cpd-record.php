<?php
/**
 * Template Name: CPD Record
 * 
 * Displays user's CPD record for a selected year
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
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Include header
get_header();
?>

<div class="cpd-record-page">
    <!-- Hero Section -->
    <div class="cpd-record-hero">       
        <div class="container">
            <div class="hero-content">
                <div class="breadcrumb">
                    <a href="<?php echo home_url('/member-portal/'); ?>">🏠</a>
                    <span class="separator">></span>
                    <span class="current">CPD Record</span>
                </div>
                <div class="hero-main">
                    <div class="hero-text">
                        <h1>CPD Record</h1>
                        <p>View your continuing professional development progress and download certificates</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="cpd-record-layout">
            <!-- Left Sidebar - 2/7 width -->
            <div class="cpd-record-left-sidebar">
                <!-- Year Selector -->
                <div class="sidebar-card">
                    <h3>Select Year</h3>
                    <select id="year-select" onchange="changeYear(this.value)" class="year-select-dropdown">
                        <?php
                        $current_year = date('Y');
                        for ($year = $current_year; $year >= $current_year - 5; $year--) {
                            $selected = $year == $selected_year ? 'selected' : '';
                            echo "<option value='$year' $selected>$year</option>";
                        }
                        ?>
                    </select>
                    <button class="show-record-btn" onclick="loadCPDRecord()">Show record</button>
                </div>

                <!-- Quick Links -->
                <div class="sidebar-card">
                    <h3>Quick Links</h3>
                    <div class="quick-links">
                        <a href="<?php echo home_url('/cpd-certificates/'); ?>" class="quick-link">
                            See all certificates →
                        </a>
                        <a href="<?php echo home_url('/training-history/'); ?>" class="quick-link">
                            Training History →
                        </a>
                    </div>
                </div>

                <!-- Helpful Links -->
                <div class="sidebar-card">
                    <h3>Helpful Links</h3>
                    <div class="helpful-links">
                        <a href="#" class="helpful-link">
                            When is certificate issued? →
                        </a>
                        <a href="#" class="helpful-link">
                            There's an error on my record →
                        </a>
                        <a href="#" class="helpful-link">
                            I can't download my certificate/it's not available →
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content - 5/7 width -->
            <div class="cpd-record-main">
                <!-- Summary Section -->
                <div class="summary-section">
                    <div class="summary-grid">
                        <div class="summary-card">
                            <h3>Summary</h3>
                            <div class="summary-items">
                                <div class="summary-item">
                                    <span class="label">Status</span>
                                    <span class="value status-badge" id="completion-status">LOADING...</span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Start Date</span>
                                    <span class="value" id="start-date">January 7, 2024</span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Completion Date</span>
                                    <span class="value" id="completion-date">-</span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Total CPD Minutes</span>
                                    <span class="value" id="total-minutes">0 minutes</span>
                                </div>
                            </div>
                        </div>

                        <div class="summary-card">
                            <h3>Courses Summary</h3>
                            <div class="courses-summary-table">
                                <div class="table-header">
                                    <span>ITEM</span>
                                    <span>CPD MINUTES</span>
                                    <span>CREDITS</span>
                                </div>
                                <div class="table-body" id="courses-summary">
                                    <div class="loading-row">Loading...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Certificate Section -->
                <div class="certificate-section" id="certificate-section">
                    <h3>Certificate</h3>
                    <div class="certificate-content">
                        <div class="certificate-info">
                            <p id="certificate-message">Certificate available for download when compliance requirements are met.</p>
                            <p id="certificate-issue-date" class="certificate-issue-date" style="display: none;"></p>
                        </div>
                        <button class="download-certificate-btn" id="download-cert-btn" disabled>
                            Download Certificate
                        </button>
                    </div>
                </div>

                <!-- Training Record Section -->
                <div class="training-record-section">
                    <h3>Training Record</h3>
                    <div class="training-record-list" id="training-record-list">
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                            <p>Loading training records...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* CPD Record Page Styles */
.cpd-record-page {
    background: #f8fafc;
    min-height: 100vh;
    margin: 0;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    padding-bottom: 40px;
}

/* Hero Section */
.cpd-record-hero {
    position: relative;
    background:rgb(22, 109, 124);
    color: white;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    z-index: -1;
}

.hero-background::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.2);
    z-index: 1;
}

.hero-content {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    gap: 30px;
    margin-top: 120px;
    margin-bottom: 30px;
}

.breadcrumb {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.8);
}

.breadcrumb a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: color 0.2s;
}

.breadcrumb a:hover {
    color: white;
}

.breadcrumb .separator {
    margin: 0 8px;
}

.breadcrumb .current {
    color: white;
    font-weight: 500;
}

.hero-main {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
}

.hero-text h1 {
    margin: 0 0 12px 0;
    font-size: 3rem;
    font-weight: 700;
    color: white;
    line-height: 1.1;
}

.hero-text p {
    margin: 0;
    font-size: 1.2rem;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.5;
    max-width: 600px;
}

.year-selector {
    display: flex;
    flex-direction: column;
    gap: 8px;
    align-items: flex-end;
}

.year-selector label {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
}

.year-selector select {
    padding: 12px 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50px;
    font-size: 14px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    min-width: 200px;
    color: white;
    font-weight: 600;
    transition: all 0.3s ease;
}

.year-selector select:hover,
.year-selector select:focus {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
    outline: none;
}

.year-selector select option {
    background: #1f2937;
    color: white;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.cpd-record-layout {
    display: grid;
    grid-template-columns: 2fr 5fr;
    gap: 40px;
    margin-top: 30px;
    align-items: start;
}

/* Left Sidebar - 2/7 width */
.cpd-record-left-sidebar {
    display: flex;
    flex-direction: column;
    gap: 24px;
    position: sticky;
    top: 20px;
}

/* Year Selector Dropdown */
.year-select-dropdown {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    background: white;
    color: #1f2937;
    font-weight: 500;
    transition: all 0.3s ease;
    cursor: pointer;
}

.year-select-dropdown:hover,
.year-select-dropdown:focus {
    border-color: rgb(22, 109, 124);
    outline: none;
    box-shadow: 0 0 0 3px rgba(22, 109, 124, 0.1);
}

.year-select-dropdown option {
    background: white;
    color: #1f2937;
    padding: 8px;
}

/* Show Record Button */
.show-record-btn {
    width: 100%;
    margin-top: 16px;
    padding: 12px 24px;
    background: #8b5cf6;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.show-record-btn:hover {
    background: #7c3aed;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
}

.cpd-record-main {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

/* Summary Section */
.summary-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.summary-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.summary-card h3 {
    margin: 0 0 20px 0;
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
}

.summary-items {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.summary-item .label {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
}

.summary-item .value {
    font-size: 14px;
    color: #1f2937;
    font-weight: 600;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-completed {
    background: #fed7aa;
    color: #ea580c;
}

.status-in-progress {
    background: #fef3c7;
    color: #92400e;
}

.status-not-started {
    background: #fee2e2;
    color: #991b1b;
}

/* Courses Summary Table */
.courses-summary-table {
    width: 100%;
}

.table-header {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 16px;
    padding: 12px 0;
    border-bottom: 2px solid #e5e7eb;
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-body {
    display: flex;
    flex-direction: column;
}

.table-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 16px;
    padding: 16px 0;
    border-bottom: 1px solid #f1f5f9;
    align-items: center;
}

.table-row:last-child {
    border-bottom: none;
    font-weight: 600;
    background: #f9fafb;
    margin-top: 8px;
    padding: 16px;
    border-radius: 8px;
}

.category-name {
    font-size: 14px;
    color: #1f2937;
    font-weight: 500;
}

.minutes-value {
    font-size: 14px;
    color: #1f2937;
    font-weight: 600;
}

.target-value {
    font-size: 14px;
    color: #6b7280;
}

.loading-row {
    padding: 20px;
    text-align: center;
    color: #6b7280;
}

/* Certificate Section */
.certificate-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.certificate-section.certificate-enabled {
    background: linear-gradient(135deg, #a855f7 0%, #8b5cf6 100%);
    color: white;
}

.certificate-section.certificate-disabled {
    background: #f3f4f6;
}

.certificate-section h3 {
    margin: 0 0 20px 0;
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
}

.certificate-section.certificate-enabled h3 {
    color: white;
}

.certificate-section.certificate-disabled h3 {
    color: #6b7280;
}

.certificate-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.certificate-info p {
    margin: 0;
    font-size: 14px;
    color: #6b7280;
    line-height: 1.5;
}

.certificate-section.certificate-enabled .certificate-info p {
    color: rgba(255, 255, 255, 0.9);
}

.certificate-section.certificate-disabled .certificate-info p {
    color: #6b7280;
}

.certificate-issue-date {
    font-size: 12px;
    margin-top: 8px !important;
    font-weight: 500;
}

.download-certificate-btn {
    background: #8b5cf6;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.download-certificate-btn:hover:not(:disabled) {
    background: #7c3aed;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
}

.download-certificate-btn:disabled {
    background: #9ca3af;
    color: #6b7280;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.certificate-section.certificate-enabled .download-certificate-btn {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.certificate-section.certificate-enabled .download-certificate-btn:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

/* Training Record Section */
.training-record-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.training-record-section h3 {
    margin: 0 0 20px 0;
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
}

.training-record-item {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 16px;
    transition: all 0.2s;
}

.training-record-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #d1d5db;
}

.record-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}

.record-badge-container {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.record-category-badge {
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

.record-date {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 2px;
}

.record-title {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 12px 0;
    line-height: 1.4;
}

.record-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    font-size: 14px;
    color: #6b7280;
}

.record-meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Category Badge Colors */
.category-pensions { background: #ff6b35; }
.category-ethics { background: #8b5cf6; }
.category-savings { background: #06b6d4; }
.category-life { background: #6b7280; }
.category-technology { background: #10b981; }
.category-regulation { background: #f59e0b; }
.category-professional { background: #ef4444; }
.category-general { background: #3b82f6; }
.category-default { background: #6b7280; }

/* Sidebar Cards */
.sidebar-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.sidebar-card h3 {
    margin: 0 0 16px 0;
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
}

.quick-links,
.helpful-links {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.quick-link,
.helpful-link {
    color: #8b5cf6;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
    padding: 8px 0;
}

.quick-link:hover,
.helpful-link:hover {
    color: #7c3aed;
    text-decoration: underline;
}

/* Loading Spinner */
.loading-spinner {
    text-align: center;
    padding: 40px 20px;
    color: #6b7280;
}

.spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #f3f4f6;
    border-top: 3px solid #8b5cf6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 16px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 1024px) {
    .cpd-record-layout {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .cpd-record-left-sidebar {
        position: static;
        order: -1;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    .show-record-btn {
        margin-top: 12px;
        padding: 10px 20px;
        font-size: 13px;
    }
}

@media (max-width: 768px) {
    .hero-text h1 {
        font-size: 2.5rem;
    }
    
    .hero-text p {
        font-size: 1.1rem;
    }
    
    .hero-main {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
    }
    
    .summary-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .certificate-content {
        flex-direction: column;
        align-items: stretch;
        gap: 16px;
    }
    
    .certificate-issue-date {
        font-size: 11px;
    }
    
    .cpd-record-left-sidebar {
        grid-template-columns: 1fr;
    }
    
    .table-header,
    .table-row {
        grid-template-columns: 2fr 1fr 1fr;
        gap: 8px;
        font-size: 12px;
    }
}

@media (max-width: 480px) {
    .cpd-record-hero {
        padding: 80px 0 40px;
    }
    
    .hero-text h1 {
        font-size: 2rem;
    }
    
    .hero-text p {
        font-size: 1rem;
    }
    
    .container {
        padding: 0 16px;
    }
    
    .summary-section,
    .certificate-section,
    .training-record-section,
    .sidebar-card {
        padding: 16px;
    }
    
    .cpd-record-layout {
        gap: 20px;
    }
    
    .show-record-btn {
        margin-top: 10px;
        padding: 10px 16px;
        font-size: 13px;
    }
}
</style>

<script>
// Global variables
let currentYear = <?php echo $selected_year; ?>;
let trainingRecords = [];
let progressSummary = {};

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadCPDRecord();
});

// Change year function
function changeYear(year) {
    currentYear = parseInt(year);
    loadCPDRecord();
}

// Load CPD record data
function loadCPDRecord() {
    // Show loading notification
    const loadingId = notifications.info('Loading CPD Record', 'Fetching your latest CPD data...', { persistent: true });
    
    // Simulate API call delay
    setTimeout(() => {
        // Sample data matching the image
        progressSummary = {
            total_minutes: 330,
            completion_date: '2024-11-30',
            certificate_issued: true,
            certificate_issue_date: '2025-12-07',
            categories: {
                'Pensions': { minutes: 60 },
                'Savings & Investment': { minutes: 120 },
                'Ethics': { minutes: 60 },
                'Life Assurance': { minutes: 90 }
            }
        };
        
        trainingRecords = [
            {
                activity_title: 'Conduct Risk',
                provider: 'Irish Life',
                lia_code: 'LIA18419_2025',
                cpd_points: 60,
                completion_date: '2025-02-16',
                category_name: 'Life Assurance',
                created_at: '2025-02-16 14:35:00'
            },
            {
                activity_title: 'Investment Outlook 2025 January 2025',
                provider: 'Irish Life',
                lia_code: 'LIA18419_2025',
                cpd_points: 60,
                completion_date: '2025-02-16',
                category_name: 'Savings & Investment',
                created_at: '2025-02-16 14:35:00'
            },
            {
                activity_title: 'Conduct Risk',
                provider: 'Irish Life',
                lia_code: 'LIA18419_2025',
                cpd_points: 60,
                completion_date: '2025-02-16',
                category_name: 'Life Assurance',
                created_at: '2025-02-16 14:35:00'
            },
            {
                activity_title: 'Open Day Five Building a solid defence – group protection for SMEs',
                provider: 'Irish Life',
                lia_code: 'LIA18419_2025',
                cpd_points: 60,
                completion_date: '2025-02-16',
                category_name: 'Pensions',
                created_at: '2025-02-16 14:35:00'
            },
            {
                activity_title: '15 Pension Landmines 2025',
                provider: 'Irish Life',
                lia_code: 'LIA18419_2025',
                cpd_points: 60,
                completion_date: '2025-02-16',
                category_name: 'Ethics',
                created_at: '2025-02-16 14:35:00'
            }
        ];
        
        updateSummarySection();
        updateCoursesSummary();
        updateTrainingRecords();
        updateCertificateStatus();
        
        // Hide loading and show success
        notifications.hide(loadingId);
        notifications.success('CPD Record Updated', `Loaded ${trainingRecords.length} training records for ${currentYear}`);
    }, 1500);
}

// Update summary section
function updateSummarySection() {
    const totalMinutes = progressSummary.total_minutes || 0;
    
    // Update status
    const statusElement = document.getElementById('completion-status');
    statusElement.textContent = 'COMPLETED';
    statusElement.className = 'value status-badge status-completed';
    
    // Update start date
    document.getElementById('start-date').textContent = 'January 7, 2024';
    
    // Update completion date
    const completionDateElement = document.getElementById('completion-date');
    completionDateElement.textContent = 'November 30, 2024';
    
    // Update total minutes
    document.getElementById('total-minutes').textContent = `${totalMinutes} minutes`;
}

// Update courses summary table
function updateCoursesSummary() {
    const categories = [
        { name: 'Pensions', key: 'Pensions', minutes: 60, credits: '1/1' },
        { name: 'Savings & Investment', key: 'Savings & Investment', minutes: 120, credits: '1/1' },
        { name: 'Ethics', key: 'Ethics', minutes: 60, credits: '1/1' },
        { name: 'Life Assurance', key: 'Life Assurance', minutes: 90, credits: '1/1' }
    ];
    
    let summaryHTML = '';
    
    categories.forEach(category => {
        summaryHTML += `
            <div class="table-row">
                <span class="category-name">${category.name}</span>
                <span class="minutes-value">${category.minutes} minutes</span>
                <span class="target-value">${category.credits}</span>
            </div>
        `;
    });
    
    // Add total row
    summaryHTML += `
        <div class="table-row">
            <span class="category-name">Total</span>
            <span class="minutes-value">330 minutes</span>
            <span class="target-value">1/4</span>
        </div>
    `;
    
    document.getElementById('courses-summary').innerHTML = summaryHTML;
}

// Update training records
function updateTrainingRecords() {
    const trainingList = document.getElementById('training-record-list');
    
    if (trainingRecords.length === 0) {
        trainingList.innerHTML = `
            <div class="empty-state">
                <p>No training records found for ${currentYear}.</p>
            </div>
        `;
        return;
    }
    
    trainingList.innerHTML = trainingRecords.map(record => `
        <div class="training-record-item">
            <div class="record-header">
                <div class="record-badge-container">
                    <span class="record-category-badge ${getCategoryBadgeClass(record.category_name)}">${escapeHtml(record.category_name || 'General')}</span>
                    <span class="record-date">Added on ${formatAddedDate(record.created_at || record.completion_date)}</span>
                </div>
            </div>
            <h4 class="record-title">${escapeHtml(record.activity_title || record.course_title || 'Training Activity')}</h4>
            <div class="record-meta">
                <div class="record-meta-item">
                    <span>🏢</span>
                    <span>Provided by ${escapeHtml(record.provider || record.external_provider || 'Irish Life')}</span>
                </div>
                <div class="record-meta-item">
                    <span>🔖</span>
                    <span>LIA Code: ${escapeHtml(record.lia_code || 'LIA18419_2025')}</span>
                </div>
                <div class="record-meta-item">
                    <span>⏱️</span>
                    <span>CPD Minutes: ${record.cpd_points || 0} minutes</span>
                </div>
                <div class="record-meta-item">
                    <span>📅</span>
                    <span>Date: ${formatDate(record.completion_date)}</span>
                </div>
            </div>
        </div>
    `).join('');
}

// Update certificate status
function updateCertificateStatus() {
    const downloadBtn = document.getElementById('download-cert-btn');
    const certificateSection = document.getElementById('certificate-section');
    const certificateMessage = document.getElementById('certificate-message');
    const certificateIssueDate = document.getElementById('certificate-issue-date');
    
    const totalMinutes = progressSummary.total_minutes || 0;
    const requiredMinutes = 240; // Minimum requirement
    const requirementsMet = totalMinutes >= requiredMinutes;
    const certificateIssued = progressSummary.certificate_issued || false;
    
    // Reset classes
    certificateSection.classList.remove('certificate-enabled', 'certificate-disabled');
    
    if (requirementsMet && certificateIssued) {
        // Certificate is available for download
        certificateSection.classList.add('certificate-enabled');
        certificateMessage.textContent = 'Certificate available for download. Your certificate has been issued.';
        certificateIssueDate.textContent = `Issued on: ${formatDate(progressSummary.certificate_issue_date)}`;
        certificateIssueDate.style.display = 'block';
        downloadBtn.disabled = false;
        downloadBtn.onclick = downloadCertificate;
    } else if (requirementsMet && !certificateIssued) {
        // Requirements met but certificate not yet issued
        certificateSection.classList.add('certificate-disabled');
        certificateMessage.textContent = 'Certificate has not been issued. When certificate has been issued you can download it here in PDF format.';
        certificateIssueDate.style.display = 'none';
        downloadBtn.disabled = true;
    } else {
        // Requirements not yet met
        certificateSection.classList.add('certificate-disabled');
        certificateMessage.textContent = 'Certificate available for download when compliance requirements are met.';
        certificateIssueDate.style.display = 'none';
        downloadBtn.disabled = true;
    }
}

// Download certificate
function downloadCertificate() {
    // Show loading notification
    const loadingId = notifications.info('Downloading Certificate', 'Preparing your CPD certificate for download...', { persistent: true });
    
    // Simulate download process (replace with actual implementation)
    setTimeout(() => {
        notifications.hide(loadingId);
        notifications.success('Download Complete', 'Your CPD certificate has been downloaded successfully.', {
            action: {
                text: 'View Certificates',
                onClick: `window.location.href='${window.location.origin}/cpd-certificates/'`
            }
        });
        
        // Simulate file download
        const link = document.createElement('a');
        link.href = '#'; // Replace with actual file URL
        link.download = `CPD_Certificate_${currentYear}.pdf`;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }, 2000);
}

// Utility functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
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

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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

function showError(message) {
    document.getElementById('training-record-list').innerHTML = `
        <div class="error-state">
            <p style="color: #ef4444;">${message}</p>
            <button onclick="loadCPDRecord()" style="background: #ef4444; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; margin-top: 12px;">Retry</button>
        </div>
    `;
}
</script>

<?php get_footer(); ?> 