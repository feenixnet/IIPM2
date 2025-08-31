<?php
/*
Template Name: CPD Reports & Analytics
*/

// Security check - only allow admins
if (!is_user_logged_in() || !current_user_can('administrator')) {
    wp_redirect(home_url('/login/'));
    exit;
}

get_header();

// Get reporting data
global $wpdb;
$current_year = date('Y');
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : $current_year;

// Get overall statistics
$stats = iipm_get_cpd_compliance_stats($selected_year);
?>

<style>
/* Report card styling */
.report-card.active {
    border: 2px solid #667eea !important;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15) !important;
    transform: translateY(-2px) !important;
}

/* Ensure button text is visible */
.report-card button {
    color: white !important;
}

/* Smooth transitions */
.report-card {
    transition: all 0.3s ease !important;
}

/* Loading spinner animation */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-spinner {
    animation: spin 1s linear infinite;
}
</style>

<div class="cpd-reports-page" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-top: 140px;">
    <div class="container" style="max-width: 1400px; margin: 0 auto; padding: 0 20px;">
        
        <!-- Page Header -->
        <div class="page-header" style="text-align: center; margin-bottom: 40px;">
            <h1 style="color: white; font-size: 2.5rem; margin-bottom: 10px;">CPD Reports & Analytics</h1>
            <p style="color: rgba(255,255,255,0.9); font-size: 1.1rem;">
                Monitor member compliance and track CPD progress across the organization
            </p>
            
            <!-- Year Selector -->
            <div style="margin-top: 20px;">
                <select id="year-selector" onchange="changeYear(this.value)" 
                        style="background: white; padding: 10px 15px; border-radius: 8px; border: none; font-size: 16px;">
                    <?php 
                    // Include both current year and next year for testing
                    $start_year = $current_year + 1;
                    $end_year = $current_year - 5;
                    for ($year = $start_year; $year >= $end_year; $year--): 
                    ?>
                        <option value="<?php echo $year; ?>" <?php selected($year, $selected_year); ?>>
                            <?php echo $year; ?> CPD Year
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <!-- Overview Statistics -->
        <div class="stats-overview" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px;">
            <div style="background: white; padding: 25px; border-radius: 15px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                <div style="font-size: 2.5rem; color: #059669; margin-bottom: 10px;">📊</div>
                <h3 style="margin: 0 0 5px 0; color: #374151;">Total Members</h3>
                <p style="font-size: 2rem; font-weight: bold; color: #059669; margin: 0;"><?php echo $stats['total_members']; ?></p>
            </div>
            
            <div style="background: white; padding: 25px; border-radius: 15px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                <div style="font-size: 2.5rem; color: #10b981; margin-bottom: 10px;">✅</div>
                <h3 style="margin: 0 0 5px 0; color: #374151;">Compliant</h3>
                <p style="font-size: 2rem; font-weight: bold; color: #10b981; margin: 0;"><?php echo $stats['compliant_members']; ?></p>
                <small style="color: #6b7280;"><?php echo round(($stats['compliant_members'] / max($stats['total_members'], 1)) * 100, 1); ?>%</small>
            </div>
            
            <div style="background: white; padding: 25px; border-radius: 15px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                <div style="font-size: 2.5rem; color: #f59e0b; margin-bottom: 10px;">⚠️</div>
                <h3 style="margin: 0 0 5px 0; color: #374151;">At Risk</h3>
                <p style="font-size: 2rem; font-weight: bold; color: #f59e0b; margin: 0;"><?php echo $stats['at_risk_members']; ?></p>
                <small style="color: #6b7280;"><?php echo round(($stats['at_risk_members'] / max($stats['total_members'], 1)) * 100, 1); ?>%</small>
            </div>
            
            <div style="background: white; padding: 25px; border-radius: 15px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                <div style="font-size: 2.5rem; color: #ef4444; margin-bottom: 10px;">❌</div>
                <h3 style="margin: 0 0 5px 0; color: #374151;">Non-Compliant</h3>
                <p style="font-size: 2rem; font-weight: bold; color: #ef4444; margin: 0;"><?php echo $stats['non_compliant_members']; ?></p>
                <small style="color: #6b7280;"><?php echo round(($stats['non_compliant_members'] / max($stats['total_members'], 1)) * 100, 1); ?>%</small>
            </div>
        </div>

        <!-- Report Type Selection -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px;" id="report-cards-container">
            
            <!-- Compliance Report -->
            <div class="report-card" id="compliance-card" style="background: white; border-radius: 15px; padding: 25px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 2px solid transparent; transition: all 0.3s ease;">
                <h3 style="color: #374151; margin-bottom: 15px;">Compliance Report</h3>
                <p style="color: #6b7280; margin-bottom: 20px;">View member CPD compliance status</p>
                <button onclick="showComplianceReport()" 
                        style="background: #667eea; color: white !important; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;">
                    Generate Report
                </button>
            </div>
            
            <!-- Course Popularity -->
            <div class="report-card" id="popularity-card" style="background: white; border-radius: 15px; padding: 25px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 2px solid transparent; transition: all 0.3s ease;">
                <h3 style="color: #374151; margin-bottom: 15px;">Course Popularity</h3>
                <p style="color: #6b7280; margin-bottom: 20px;">Most popular CPD courses</p>
                <button onclick="showCoursePopularity()" 
                        style="background: #10b981; color: white !important; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;">
                    Generate Report
                </button>
            </div>
            
            <!-- Provider Analysis -->
            <div class="report-card" id="provider-card" style="background: white; border-radius: 15px; padding: 25px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 2px solid transparent; transition: all 0.3s ease;">
                <h3 style="color: #374151; margin-bottom: 15px;">Provider Analysis</h3>
                <p style="color: #6b7280; margin-bottom: 20px;">CPD points by provider</p>
                <button onclick="showProviderAnalysis()" 
                        style="background: #f59e0b; color: white !important; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;">
                    Generate Report
                </button>
            </div>
        </div>

        <!-- Main Content Area -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            
            <!-- Left Column - Reports -->
            <div style="background: white; border-radius: 20px; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
                
                <!-- Report Tabs -->
                <div class="report-tabs" style="border-bottom: 2px solid #e5e7eb; margin-bottom: 30px;">
                    <div style="display: flex; gap: 20px;">
                        <button class="tab-btn active" onclick="showReport('compliance')" 
                                style="padding: 15px 20px; border: none; background: none; color: #667eea; border-bottom: 3px solid #667eea; font-weight: 600; cursor: pointer;">
                            Compliance Overview
                        </button>
                        <button class="tab-btn" onclick="showReport('categories')" 
                                style="padding: 15px 20px; border: none; background: none; color: #6b7280; font-weight: 600; cursor: pointer;">
                            By Categories
                        </button>
                        <button class="tab-btn" onclick="showReport('members')" 
                                style="padding: 15px 20px; border: none; background: none; color: #6b7280; font-weight: 600; cursor: pointer;">
                            Member Details
                        </button>
                    </div>
                </div>

                <!-- Compliance Report -->
                <div id="compliance-report" class="report-content">
                    <h3 style="color: #374151; margin-bottom: 20px;">📋 Member Compliance Status</h3>
                    
                    <!-- Non-Compliant Members Table -->
                    <div style="margin-bottom: 30px;">
                        <h4 style="color: #ef4444; margin-bottom: 15px;">❌ Non-Compliant Members (<?php echo $stats['non_compliant_members']; ?>)</h4>
                        <div style="overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 8px;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead style="background: #f8fafc;">
                                    <tr>
                                        <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Member</th>
                                        <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Points Earned</th>
                                        <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Required</th>
                                        <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Shortage</th>
                                        <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="non-compliant-table">
                                    <!-- Will be loaded via AJAX -->
                                    <tr>
                                        <td colspan="5" style="padding: 20px; text-align: center; color: #6b7280;">
                                            <div class="loading-spinner" style="display: inline-block; width: 20px; height: 20px; border: 2px solid #e5e7eb; border-top: 2px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                                            Loading compliance data...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- At Risk Members -->
                    <div>
                        <h4 style="color: #f59e0b; margin-bottom: 15px;">⚠️ At Risk Members (<?php echo $stats['at_risk_members']; ?>)</h4>
                        <div style="overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 8px;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead style="background: #f8fafc;">
                                    <tr>
                                        <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Member</th>
                                        <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Progress</th>
                                        <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Days Left</th>
                                        <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="at-risk-table">
                                    <!-- Will be loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Categories Report -->
                <div id="categories-report" class="report-content" style="display: none;">
                    <h3 style="color: #374151; margin-bottom: 20px;">📊 CPD by Categories</h3>
                    <div id="categories-chart" style="height: 400px; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">
                        <!-- Chart will be rendered here -->
                        <p style="text-align: center; color: #6b7280; margin-top: 150px;">Category analytics chart will be displayed here</p>
                    </div>
                </div>

                <!-- Members Report -->
                <div id="members-report" class="report-content" style="display: none;">
                    <h3 style="color: #374151; margin-bottom: 20px;">👥 Individual Member Reports</h3>
                    
                    <!-- Members Table -->
                    <div style="background: white; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb;">
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead style="background: #f8fafc;">
                                    <tr>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Member</th>
                                        <th style="padding: 15px; text-align: center; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Membership Level</th>
                                        <th style="padding: 15px; text-align: center; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">CPD Progress</th>
                                        <th style="padding: 15px; text-align: center; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Compliance Status</th>
                                        <th style="padding: 15px; text-align: center; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="members-table-body">
                                    <!-- Loading state -->
                                    <tr>
                                        <td colspan="5" style="padding: 40px; text-align: center; color: #6b7280;">
                                            <div class="loading-spinner" style="display: inline-block; width: 24px; height: 24px; border: 3px solid #e5e7eb; border-top: 3px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 15px;"></div>
                                            <div>Loading members...</div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Individual Report Content -->
                    <div id="individual-report-content" style="display: none; margin-top: 30px;">
                        <!-- Report content will be displayed here when a member is selected -->
                    </div>
                </div>
            </div>

            <!-- Right Column - Quick Actions -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                
                <!-- Export Reports -->
                <div style="background: white; border-radius: 15px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                    <h3 style="color: #374151; margin-bottom: 20px;">📥 Export Reports</h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <button onclick="exportReport('compliance')" 
                                style="background: #059669; color: white; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                            📊 Export Compliance Report
                        </button>
                        
                        <button onclick="exportReport('non-compliant')" 
                                style="background: #ef4444; color: white; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                            ❌ Export Non-Compliant List
                        </button>
                        
                        <button onclick="exportReport('all-members')" 
                                style="background: #667eea; color: white; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                            👥 Export All Members
                        </button>
                        
                        <button onclick="exportReport('categories')" 
                                style="background: #f59e0b; color: white; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                            📈 Export Category Stats
                        </button>
                    </div>
                </div>

                <!-- Send Reminders -->
                <div style="background: white; border-radius: 15px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                    <h3 style="color: #374151; margin-bottom: 20px;">📧 Send Reminders</h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <button onclick="sendReminders('non-compliant')" 
                                style="background: #ef4444; color: white; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                            📮 Remind Non-Compliant
                        </button>
                        
                        <button onclick="sendReminders('at-risk')" 
                                style="background: #f59e0b; color: white; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                            ⚠️ Remind At-Risk Members
                        </button>
                        
                        <button onclick="sendReminders('all')" 
                                style="background: #6b7280; color: white; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                            📨 Send General Reminder
                        </button>
                    </div>
                    
                    <div id="reminder-status" style="margin-top: 15px; padding: 10px; border-radius: 8px; display: none;"></div>
                </div>

                <!-- Quick Stats -->
                <div style="background: white; border-radius: 15px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                    <h3 style="color: #374151; margin-bottom: 20px;">⚡ Quick Stats</h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f8fafc; border-radius: 8px;">
                            <span style="color: #374151; font-weight: 500;">Average CPD Points:</span>
                            <span style="color: #667eea; font-weight: bold;"><?php echo number_format($stats['average_points'], 1); ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f8fafc; border-radius: 8px;">
                            <span style="color: #374151; font-weight: 500;">Total CPD Logged:</span>
                            <span style="color: #059669; font-weight: bold;"><?php echo number_format($stats['total_cpd_logged']); ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f8fafc; border-radius: 8px;">
                            <span style="color: #374151; font-weight: 500;">Pending Approvals:</span>
                            <span style="color: #f59e0b; font-weight: bold;"><?php echo $stats['pending_approvals']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables
let currentYear = <?php echo $selected_year; ?>;
let currentReportData = null; // Store current report data for CSV export

// Make sure this is globally accessible
window.iipm_reports_ajax = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('iipm_reports_nonce'); ?>'
};

// Debug: Check if the object is properly created
console.log('🌍 Global AJAX object created:', window.iipm_reports_ajax);
console.log('🔑 AJAX URL:', window.iipm_reports_ajax.ajax_url);
console.log('🎫 Nonce:', window.iipm_reports_ajax.nonce);

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Check if we have a report parameter in URL
    const urlParams = new URLSearchParams(window.location.search);
    const reportType = urlParams.get('report');
    
    if (reportType) {
        switch(reportType) {
            case 'compliance':
                showComplianceReport();
                break;
            case 'popularity':
                showCoursePopularity();
                break;
            case 'provider':
                showProviderAnalysis();
                break;
            default:
                // Default to compliance report
                setActiveReportCard('compliance');
                loadComplianceData();
        }
    } else {
        // Default to compliance report highlighting
        setActiveReportCard('compliance');
        loadComplianceData();
    }
});

// Change year
function changeYear(year) {
    window.location.href = window.location.pathname + '?year=' + year;
}

// Show different report tabs
function showReport(reportType, clickedElement) {
    // Hide all reports
    document.querySelectorAll('.report-content').forEach(el => el.style.display = 'none');
    
    // Remove active class from all tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.style.color = '#6b7280';
        btn.style.borderBottom = 'none';
    });
    
    // Show selected report
    document.getElementById(reportType + '-report').style.display = 'block';
    
    // Activate current tab - handle both event.target and direct element reference
    let targetElement = clickedElement;
    if (!targetElement && typeof event !== 'undefined' && event.target) {
        targetElement = event.target;
    }
    if (!targetElement) {
        // If no element provided, find the tab button for this report type
        const tabSelectors = {
            'compliance': '.tab-btn[onclick*="compliance"]',
            'categories': '.tab-btn[onclick*="categories"]', 
            'members': '.tab-btn[onclick*="members"]'
        };
        targetElement = document.querySelector(tabSelectors[reportType]);
    }
    
    if (targetElement) {
        targetElement.style.color = '#667eea';
        targetElement.style.borderBottom = '3px solid #667eea';
    }
    
    // Load data based on report type
    switch(reportType) {
        case 'compliance':
            loadComplianceData();
            break;
        case 'categories':
            loadCategoriesData();
            break;
        case 'members':
            loadMembersData();
            break;
    }
}

// Load compliance data
function loadComplianceData() {
    fetch(window.iipm_reports_ajax.ajax_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'iipm_get_compliance_data',
            year: currentYear,
            nonce: window.iipm_reports_ajax.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateComplianceTables(data.data);
        }
    })
    .catch(error => console.error('Error loading compliance data:', error));
}

// Update compliance tables
function updateComplianceTables(data) {
    // Update non-compliant table
    const nonCompliantTable = document.getElementById('non-compliant-table');
    if (data.non_compliant.length === 0) {
        nonCompliantTable.innerHTML = '<tr><td colspan="5" style="padding: 20px; text-align: center; color: #059669;">🎉 All members are compliant!</td></tr>';
    } else {
        nonCompliantTable.innerHTML = data.non_compliant.map(member => 
            `<tr>
                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">
                    <strong>${member.name}</strong><br>
                    <small style="color: #6b7280;">${member.email}</small>
                </td>
                <td style="padding: 12px; text-align: center; border-bottom: 1px solid #e5e7eb;">${member.earned_points}</td>
                <td style="padding: 12px; text-align: center; border-bottom: 1px solid #e5e7eb;">${member.required_points}</td>
                <td style="padding: 12px; text-align: center; border-bottom: 1px solid #e5e7eb; color: #ef4444; font-weight: bold;">${member.shortage}</td>
                <td style="padding: 12px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                    <button onclick="sendIndividualReminder(${member.user_id})" style="background: #667eea; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px;">
                        Send Reminder
                    </button>
                </td>
            </tr>`
        ).join('');
    }
    
    // Update at-risk table
    const atRiskTable = document.getElementById('at-risk-table');
    if (data.at_risk.length === 0) {
        atRiskTable.innerHTML = '<tr><td colspan="4" style="padding: 20px; text-align: center; color: #6b7280;">No members at risk</td></tr>';
    } else {
        atRiskTable.innerHTML = data.at_risk.map(member => 
            `<tr>
                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">
                    <strong>${member.name}</strong><br>
                    <small style="color: #6b7280;">${member.email}</small>
                </td>
                <td style="padding: 12px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                    <div style="background: #f3f4f6; border-radius: 10px; height: 8px; position: relative;">
                        <div style="background: #f59e0b; height: 100%; border-radius: 10px; width: ${member.progress_percentage}%;"></div>
                    </div>
                    <small style="color: #6b7280;">${member.earned_points}/${member.required_points}</small>
                </td>
                <td style="padding: 12px; text-align: center; border-bottom: 1px solid #e5e7eb; color: #f59e0b; font-weight: bold;">${member.days_left}</td>
                <td style="padding: 12px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                    <button onclick="sendIndividualReminder(${member.user_id})" style="background: #f59e0b; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px;">
                        Send Reminder
                    </button>
                </td>
            </tr>`
        ).join('');
    }
}

// Export reports
function exportReport(type) {
    const url = `${window.iipm_reports_ajax.ajax_url}?action=iipm_export_report&type=${type}&year=${currentYear}&nonce=${window.iipm_reports_ajax.nonce}`;
    window.open(url, '_blank');
}

// Send reminders
function sendReminders(type) {
    const statusDiv = document.getElementById('reminder-status');
    statusDiv.style.display = 'block';
    statusDiv.style.background = '#f0f9ff';
    statusDiv.style.color = '#0369a1';
    statusDiv.innerHTML = '📤 Sending reminders...';
    
    fetch(window.iipm_reports_ajax.ajax_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'iipm_send_bulk_reminders',
            type: type,
            year: currentYear,
            nonce: window.iipm_reports_ajax.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusDiv.style.background = '#ecfdf5';
            statusDiv.style.color = '#059669';
            statusDiv.innerHTML = `✅ Sent ${data.data.sent_count} reminders successfully!`;
        } else {
            statusDiv.style.background = '#fef2f2';
            statusDiv.style.color = '#dc2626';
            statusDiv.innerHTML = '❌ Failed to send reminders: ' + data.data.message;
        }
        
        setTimeout(() => {
            statusDiv.style.display = 'none';
        }, 5000);
    })
    .catch(error => {
        statusDiv.style.background = '#fef2f2';
        statusDiv.style.color = '#dc2626';
        statusDiv.innerHTML = '❌ Error sending reminders';
        console.error('Error:', error);
    });
}

// Send individual reminder (referenced in table buttons)
function sendIndividualReminder(userId) {
    alert('Individual reminder functionality will be implemented in next phase');
}

// Load categories data (referenced in tab switching)
function loadCategoriesData() {
    const categoriesChart = document.getElementById('categories-chart');
    categoriesChart.innerHTML = '<p style="text-align: center; color: #6b7280; margin-top: 150px;">Category analytics will be available in the next update</p>';
}

// Load members data (referenced in tab switching)
function loadMembersData() {
    console.log('📋 loadMembersData() called');
    loadMembersTable();
}

// Load all members into the table
function loadMembersTable() {
    console.log('📊 Loading members table...');
    
    // Show loading state
    const tableBody = document.getElementById('members-table-body');
    if (!tableBody) {
        console.error('❌ Members table body not found!');
        return;
    }
    
    tableBody.innerHTML = `
        <tr>
            <td colspan="5" style="padding: 40px; text-align: center; color: #6b7280;">
                <div class="loading-spinner" style="display: inline-block; width: 24px; height: 24px; border: 3px solid #e5e7eb; border-top: 3px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 15px;"></div>
                <div>Loading members...</div>
            </td>
        </tr>
    `;
    
    // Fetch all members with their CPD progress
    fetch(window.iipm_reports_ajax.ajax_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'iipm_get_all_members_for_reports',
            year: currentYear,
            nonce: window.iipm_reports_ajax.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('📦 Members data received:', data);
        if (data.success && data.data.length > 0) {
            displayMembersTable(data.data);
        } else {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" style="padding: 40px; text-align: center; color: #6b7280;">
                        <div style="font-size: 1.2rem; margin-bottom: 10px;">👥</div>
                        <div>No active members found</div>
                    </td>
                </tr>
            `;
        }
    })
    .catch(error => {
        console.error('❌ Error loading members:', error);
        tableBody.innerHTML = `
            <tr>
                <td colspan="5" style="padding: 40px; text-align: center; color: #ef4444;">
                    <div style="font-size: 1.2rem; margin-bottom: 10px;">⚠️</div>
                    <div>Failed to load members</div>
                </td>
            </tr>
        `;
    });
}

// Display members in the table
function displayMembersTable(members) {
    console.log('🎨 Displaying', members.length, 'members in table');
    
    const tableBody = document.getElementById('members-table-body');
    
    const tableRows = members.map(member => {
        const progressPercentage = member.required_points > 0 ? 
            Math.round((member.earned_points / member.required_points) * 100) : 0;
        
        let statusIcon, statusText, statusColor;
        if (progressPercentage >= 100) {
            statusIcon = '✅';
            statusText = 'Compliant';
            statusColor = '#10b981';
        } else if (progressPercentage >= 75) {
            statusIcon = '⚠️';
            statusText = 'At Risk';
            statusColor = '#f59e0b';
        } else {
            statusIcon = '❌';
            statusText = 'Non-Compliant';
            statusColor = '#ef4444';
        }
        
        let progressColor = '#ef4444';
        if (progressPercentage >= 100) progressColor = '#10b981';
        else if (progressPercentage >= 75) progressColor = '#f59e0b';
        
        return `
            <tr style="border-bottom: 1px solid #e5e7eb; transition: background-color 0.2s;" 
                onmouseover="this.style.backgroundColor='#f8fafc'" 
                onmouseout="this.style.backgroundColor='white'">
                <td style="padding: 15px;">
                    <div style="display: flex; align-items: center;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; margin-right: 12px; font-size: 14px;">
                            ${member.display_name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <div style="font-weight: 600; color: #374151;">${member.display_name}</div>
                            <div style="font-size: 0.85rem; color: #6b7280;">${member.user_email}</div>
                        </div>
                    </div>
                </td>
                <td style="padding: 15px; text-align: center;">
                    <span style="background: #f3f4f6; color: #374151; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 500; text-transform: capitalize;">
                        ${member.membership_level || 'Member'}
                    </span>
                </td>
                <td style="padding: 15px; text-align: center;">
                    <div style="margin-bottom: 5px;">
                        <span style="font-weight: 600; color: ${progressColor};">${member.earned_points}</span>
                        <span style="color: #6b7280;">/${member.required_points} points</span>
                    </div>
                    <div style="background: #f3f4f6; border-radius: 10px; height: 6px; overflow: hidden;">
                        <div style="background: ${progressColor}; height: 100%; width: ${Math.min(100, progressPercentage)}%; transition: width 0.3s ease;"></div>
                    </div>
                    <div style="font-size: 0.75rem; color: #6b7280; margin-top: 2px;">${progressPercentage}%</div>
                </td>
                <td style="padding: 15px; text-align: center;">
                    <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 500; color: ${statusColor}; background: ${statusColor}20;">
                        ${statusIcon} ${statusText}
                    </span>
                </td>
                <td style="padding: 15px; text-align: center;">
                    <button onclick="generateMemberReport(${member.ID}, '${member.display_name}')" 
                            style="background: #667eea; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 0.9rem; transition: background 0.2s;"
                            onmouseover="this.style.background='#5a67d8'" 
                            onmouseout="this.style.background='#667eea'">
                        📊 Generate Report
                    </button>
                </td>
            </tr>
        `;
    }).join('');
    
    tableBody.innerHTML = tableRows;
    console.log('✅ Members table displayed successfully');
}

// Generate member report from table button
function generateMemberReport(userId, memberName) {
    console.log('📊 Generating report for member:', userId, memberName);
    console.log('🔍 Debug info:', {
        userId: userId,
        memberName: memberName,
        currentYear: currentYear,
        ajaxUrl: window.iipm_reports_ajax?.ajax_url,
        nonce: window.iipm_reports_ajax?.nonce
    });
    
    // Show the individual report section
    const reportSection = document.getElementById('individual-report-content');
    reportSection.style.display = 'block';
    
    // Show loading state
    reportSection.innerHTML = `
        <div style="background: white; padding: 40px; border-radius: 12px; text-align: center; border: 1px solid #e5e7eb;">
            <div class="loading-spinner" style="display: inline-block; width: 30px; height: 30px; border: 3px solid #e5e7eb; border-top: 3px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 15px;"></div>
            <h4 style="color: #374151; margin: 10px 0;">Generating Report for ${memberName}</h4>
            <p style="color: #6b7280; margin: 0;">Please wait while we gather the CPD data...</p>
        </div>
    `;
    
    // Scroll to the report section
    reportSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    
    // Fetch individual report data
    fetch(window.iipm_reports_ajax.ajax_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'iipm_get_individual_report',
            user_id: userId,
            year: currentYear,
            nonce: window.iipm_reports_ajax.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('📦 Individual report data:', data);
        if (data.success) {
            displayIndividualReport(data.data, memberName);
        } else {
            reportSection.innerHTML = `
                <div style="background: white; padding: 40px; border-radius: 12px; text-align: center; border: 1px solid #e5e7eb;">
                    <div style="font-size: 2rem; margin-bottom: 15px;">⚠️</div>
                    <h4 style="color: #ef4444; margin: 10px 0;">Failed to Generate Report</h4>
                    <p style="color: #6b7280; margin: 0;">${data.data?.message || 'Unknown error occurred'}</p>
                    <button onclick="document.getElementById('individual-report-content').style.display='none'" 
                            style="background: #6b7280; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; margin-top: 15px;">
                        Close
                    </button>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('❌ Report generation error:', error);
        reportSection.innerHTML = `
            <div style="background: white; padding: 40px; border-radius: 12px; text-align: center; border: 1px solid #e5e7eb;">
                <div style="font-size: 2rem; margin-bottom: 15px;">❌</div>
                <h4 style="color: #ef4444; margin: 10px 0;">Report Generation Failed</h4>
                <p style="color: #6b7280; margin: 0;">Network error or server issue. Please try again.</p>
                <button onclick="document.getElementById('individual-report-content').style.display='none'" 
                        style="background: #6b7280; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; margin-top: 15px;">
                    Close
                </button>
            </div>
        `;
    });
}

// Legacy functions - no longer needed with table view

// Display individual report
function displayIndividualReport(reportData, memberName) {
    console.log('🎨 Displaying individual report for:', memberName);
    
    // Store report data globally for CSV export
    currentReportData = reportData;
    
    // Display the complete report
    document.getElementById('individual-report-content').innerHTML = `
        <!-- Report Header -->
        <div id="report-header" style="background: white; padding: 25px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #e5e7eb;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h4 style="color: #374151; margin: 0;">📊 CPD Report: ${memberName}</h4>
                <div style="display: flex; gap: 10px;">
                    <button onclick="exportIndividualReportCSV()" style="background: #059669; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px;">
                        📊 Export CSV
                    </button>
                    <button onclick="exportIndividualReport()" style="background: #10b981; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px;">
                        📄 Export PDF
                    </button>
                    <button onclick="emailIndividualReport()" style="background: #667eea; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px;">
                        📧 Email Report
                    </button>
                    <button onclick="document.getElementById('individual-report-content').style.display='none'" style="background: #6b7280; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px;">
                        ✕ Close
                    </button>
                </div>
            </div>
            
            <!-- Progress Summary -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                <div style="text-align: center; padding: 15px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: #667eea;" id="total-earned">${reportData.total_earned}</div>
                    <div style="font-size: 0.85rem; color: #6b7280;">Points Earned</div>
                </div>
                <div style="text-align: center; padding: 15px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: #374151;" id="total-required">${reportData.required_points}</div>
                    <div style="font-size: 0.85rem; color: #6b7280;">Points Required</div>
                </div>
                <div style="text-align: center; padding: 15px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: ${getProgressColor(reportData.progress_percentage)}" id="progress-percentage">${Math.round(reportData.progress_percentage)}%</div>
                    <div style="font-size: 0.85rem; color: #6b7280;">Progress</div>
                </div>
                <div style="text-align: center; padding: 15px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 1.5rem;" id="compliance-status-icon">${getComplianceIcon(reportData.compliance_status)}</div>
                    <div style="font-size: 0.85rem; color: #6b7280;" id="compliance-status-text">${getComplianceText(reportData.compliance_status)}</div>
                </div>
            </div>
        </div>

        <!-- Category Breakdown -->
        <div style="background: white; padding: 25px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #e5e7eb;">
            <h4 style="color: #374151; margin-bottom: 20px;">📊 CPD Progress by Category</h4>
            <div id="category-breakdown" style="display: grid; gap: 15px;">
                ${generateCategoryBreakdown(reportData.categories)}
            </div>
        </div>

        <!-- Course History -->
        <div style="background: white; padding: 25px; border-radius: 12px; border: 1px solid #e5e7eb;">
            <h4 style="color: #374151; margin-bottom: 20px;">📚 Complete Course History</h4>
            <div style="overflow-x: auto;">
                <table id="course-history-table" style="width: 100%; border-collapse: collapse;">
                    <thead style="background: #f8fafc;">
                        <tr>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Date</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Course</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Provider</th>
                            <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Category</th>
                            <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Points</th>
                            <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Status</th>
                            <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Certificate</th>
                        </tr>
                    </thead>
                    <tbody id="course-history-body">
                        ${generateCourseHistory(reportData.all_courses)}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

// Helper functions for report display
function getProgressColor(percentage) {
    if (percentage >= 100) return '#10b981';
    if (percentage >= 75) return '#f59e0b';
    return '#ef4444';
}

function getComplianceIcon(status) {
    switch(status) {
        case 'compliant': return '✅';
        case 'at_risk': return '⚠️';
        case 'non_compliant': return '❌';
        default: return '⏳';
    }
}

function getComplianceText(status) {
    switch(status) {
        case 'compliant': return 'Compliant';
        case 'at_risk': return 'At Risk';
        case 'non_compliant': return 'Non-Compliant';
        default: return 'Pending';
    }
}

function generateCategoryBreakdown(categories) {
    return Object.entries(categories).map(([key, category]) => {
        const percentage = category.min_required > 0 ? (category.earned / category.min_required) * 100 : 0;
        const color = percentage >= 100 ? '#10b981' : percentage >= 75 ? '#f59e0b' : '#ef4444';
        
        return `
            <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h5 style="margin: 0; color: #374151;">${category.name}</h5>
                    <span style="font-weight: bold; color: ${color};">${category.earned}/${category.min_required} points</span>
                </div>
                <div style="background: #f3f4f6; border-radius: 8px; height: 8px; overflow: hidden;">
                    <div style="background: ${color}; height: 100%; width: ${Math.min(100, percentage)}%; transition: width 0.3s ease;"></div>
                </div>
                <div style="text-align: right; margin-top: 5px; font-size: 0.85rem; color: #6b7280;">
                    ${Math.round(percentage)}% complete
                </div>
            </div>
        `;
    }).join('');
}

function generateCourseHistory(courses) {
    if (!courses || courses.length === 0) {
        return '<tr><td colspan="7" style="padding: 20px; text-align: center; color: #6b7280;">No courses found</td></tr>';
    }
    
    return courses.map(course => {
        const statusColor = course.status === 'approved' ? '#10b981' : course.status === 'pending' ? '#f59e0b' : '#ef4444';
        const date = course.completion_date ? new Date(course.completion_date).toLocaleDateString() : new Date(course.created_at).toLocaleDateString();
        
        return `
            <tr>
                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">${date}</td>
                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">${course.course_title || course.title || 'N/A'}</td>
                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">${course.provider || 'N/A'}</td>
                <td style="padding: 12px; text-align: center; border-bottom: 1px solid #e5e7eb;">${course.category_name || 'N/A'}</td>
                <td style="padding: 12px; text-align: center; border-bottom: 1px solid #e5e7eb; font-weight: bold;">${course.cpd_points}</td>
                <td style="padding: 12px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                    <span style="background: ${statusColor}; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; text-transform: capitalize;">
                        ${course.status}
                    </span>
                </td>
                <td style="padding: 12px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                    ${course.certificate_file ? 
                        `<a href="${course.certificate_file}" target="_blank" style="color: #667eea; text-decoration: none;">📄 View</a>` : 
                        '<span style="color: #9ca3af;">No certificate</span>'
                    }
                </td>
            </tr>
        `;
    }).join('');
}

// Export individual report as CSV
function exportIndividualReportCSV() {
    if (!currentReportData) {
        alert('No report data available to export');
        return;
    }
    
    console.log('📊 Exporting individual report as CSV');
    
    // Prepare CSV content
    let csvContent = '';
    
    // Header information
    csvContent += `CPD Individual Report\n`;
    csvContent += `Member: ${currentReportData.member.display_name}\n`;
    csvContent += `Email: ${currentReportData.member.user_email}\n`;
    csvContent += `Year: ${currentReportData.year}\n`;
    csvContent += `Report Generated: ${new Date().toLocaleDateString()}\n\n`;
    
    // Summary
    csvContent += `SUMMARY\n`;
    csvContent += `Points Earned,Points Required,Progress,Compliance Status\n`;
    csvContent += `${currentReportData.total_earned},${currentReportData.required_points},${Math.round(currentReportData.progress_percentage)}%,${getComplianceText(currentReportData.compliance_status)}\n\n`;
    
    // Category breakdown
    csvContent += `CATEGORY BREAKDOWN\n`;
    csvContent += `Category,Points Earned,Points Required,Progress\n`;
    Object.entries(currentReportData.categories).forEach(([key, category]) => {
        const percentage = category.min_required > 0 ? Math.round((category.earned / category.min_required) * 100) : 0;
        csvContent += `"${category.name}",${category.earned},${category.min_required},${percentage}%\n`;
    });
    
    // Course history
    if (currentReportData.all_courses && currentReportData.all_courses.length > 0) {
        csvContent += `\nCOURSE HISTORY\n`;
        csvContent += `Date,Course,Provider,Category,Points,Status,Certificate\n`;
        currentReportData.all_courses.forEach(course => {
            const date = course.completion_date ? new Date(course.completion_date).toLocaleDateString() : new Date(course.created_at).toLocaleDateString();
            const courseName = (course.course_title || course.title || 'N/A').replace(/"/g, '""');
            const provider = (course.provider || 'N/A').replace(/"/g, '""');
            const category = (course.category_name || 'N/A').replace(/"/g, '""');
            const certificate = course.certificate_file ? 'Yes' : 'No';
            
            csvContent += `"${date}","${courseName}","${provider}","${category}",${course.cpd_points},"${course.status}","${certificate}"\n`;
        });
    } else {
        csvContent += `\nCOURSE HISTORY\n`;
        csvContent += `No courses found\n`;
    }
    
    // Create and download file
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const filename = `CPD_Report_${currentReportData.member.display_name.replace(/\s+/g, '_')}_${currentReportData.year}.csv`;
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        console.log('✅ CSV export completed:', filename);
    } else {
        alert('CSV export is not supported in this browser');
    }
}

// Placeholder functions for export and email
function exportIndividualReport() {
    alert('PDF export functionality will be implemented in the next phase');
}

function emailIndividualReport() {
    alert('Email report functionality will be implemented in the next phase');
}

// Function to highlight active report card
function setActiveReportCard(cardType) {
    // Remove active class from all cards
    document.querySelectorAll('.report-card').forEach(card => {
        card.style.border = '2px solid transparent';
        card.style.boxShadow = '0 10px 30px rgba(0,0,0,0.1)';
        card.style.transform = 'none';
    });
    
    // Add active styling to selected card
    const activeCard = document.getElementById(cardType + '-card');
    if (activeCard) {
        let borderColor, shadowColor;
        switch(cardType) {
            case 'compliance':
                borderColor = '#667eea';
                shadowColor = 'rgba(102, 126, 234, 0.3)';
                break;
            case 'popularity':
                borderColor = '#10b981';
                shadowColor = 'rgba(16, 185, 129, 0.3)';
                break;
            case 'provider':
                borderColor = '#f59e0b';
                shadowColor = 'rgba(245, 158, 11, 0.3)';
                break;
        }
        
        activeCard.style.border = `2px solid ${borderColor}`;
        activeCard.style.boxShadow = `0 15px 40px ${shadowColor}`;
        activeCard.style.transform = 'translateY(-2px)';
    }
}

// Functions for the main report type buttons
function showComplianceReport() {
    // Highlight the compliance card
    setActiveReportCard('compliance');
    
    // Show the compliance section and load data
    showReport('compliance');
    loadComplianceData();
    
    // Update URL without page reload
    const url = new URL(window.location);
    url.searchParams.set('report', 'compliance');
    window.history.pushState({}, '', url);
    
    // Scroll to the report area
    document.getElementById('compliance-report').scrollIntoView({ behavior: 'smooth' });
}

function showCoursePopularity() {
    // Highlight the popularity card
    setActiveReportCard('popularity');
    
    // Show the categories section 
    showReport('categories');
    loadCategoriesData();
    
    // Update URL without page reload
    const url = new URL(window.location);
    url.searchParams.set('report', 'popularity');
    window.history.pushState({}, '', url);
    
    // Scroll to the report area
    document.getElementById('categories-report').scrollIntoView({ behavior: 'smooth' });
}

function showProviderAnalysis() {
    // Highlight the provider card
    setActiveReportCard('provider');
    
    // For now, same as course popularity - can be expanded later
    showReport('categories');
    loadCategoriesData();
    
    // Update URL without page reload
    const url = new URL(window.location);
    url.searchParams.set('report', 'provider');
    window.history.pushState({}, '', url);
    
    // Scroll to the report area
    document.getElementById('categories-report').scrollIntoView({ behavior: 'smooth' });
}

// CSS for loading spinner animation
const style = document.createElement('style');
style.textContent = `
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
`;
document.head.appendChild(style);
</script>

<?php get_footer(); ?> 