<?php
/**
 * Template Name: Leave Request
 * 
 * @package IPM
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

// FORCE LOAD MAIN THEME CSS AND JQUERY
wp_enqueue_style('iipm-main-style', get_template_directory_uri() . '/assets/css/main.min.css', array(), '1.0.0');
wp_enqueue_script('jquery');

// Check if the modular header function exists, otherwise use default header
if (function_exists('iipm_load_header')) {
    iipm_load_header();
} else {
    get_header();
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user's leave requests
global $wpdb;
$leave_requests = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}test_iipm_leave_requests 
     WHERE user_id = %d 
     ORDER BY created_at DESC",
    $user_id
));

// Separate current and past requests with better logic
$current_requests = array();
$past_requests = array();
$current_date = date('Y-m-d');

foreach ($leave_requests as $request) {
    if ($request->status === 'pending') {
        // All pending requests go to "Your Leave Requests"
        $current_requests[] = $request;
    } elseif ($request->status === 'approved') {
        // Approved requests: check if they're still valid (current date is within or before the leave period)
        if ($current_date <= $request->leave_end_date) {
            $current_requests[] = $request;
        } else {
            // Approved but already ended
            $past_requests[] = $request;
        }
    } else {
        // Rejected, cancelled, or any other status goes to "Past Requests"
        $past_requests[] = $request;
    }
}
?>

<div class="iipm-portal-container">
    <div class="iipm-portal-header">
        <h1 class="portal-title">Leave Request</h1>
    </div>

    <div class="leave-request-content">
        <!-- Submit Leave Request Section -->
        <div class="leave-request-submit-section">
            <div class="submit-card">
                <h2>Submit your leave request</h2>
                <button class="leave-request-btn" onclick="openLeaveRequestForm()">
                    Leave Request Form
                </button>
            </div>
            
            <div class="notice-card">
                <h3>Notice</h3>
                <p>If you request a leave from the course, a pro-rata adjustment will be applied based on the portion of the course completed up to the date of your request. This ensures fair and accurate allocation of course credit or fees.</p>
            </div>
        </div>

        <!-- Leave Requests Sections -->
        <div class="leave-requests-grid">
            <!-- Your Leave Requests -->
            <div class="leave-requests-section">
                <h2>Your Leave Requests</h2>
                <div class="leave-requests-list">
                    <?php if (!empty($current_requests)): ?>
                        <?php foreach ($current_requests as $request): ?>
                            <div class="leave-request-item" onclick="openLeaveRequestDetail(<?php echo $request->id; ?>)">
                                <div class="request-header">
                                    <span class="request-date">Requested on <?php echo date('F j, Y, g:iA', strtotime($request->created_at)); ?></span>
                                    <span class="status-badge status-<?php echo esc_attr($request->status); ?>">
                                        <?php echo ucfirst($request->status); ?>
                                    </span>
                                </div>
                                <h4><?php echo esc_html($request->title); ?></h4>
                                <div class="request-details">
                                    <p><strong>Leave Date:</strong> <?php echo date('m/d/Y', strtotime($request->leave_start_date)); ?> - <?php echo date('m/d/Y', strtotime($request->leave_end_date)); ?></p>
                                    <p><strong>Leave Duration:</strong> <?php echo $request->duration_days; ?> days</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-requests">
                            <p>No current leave requests found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Past Requests -->
            <div class="leave-requests-section">
                <h2>Past Requests</h2>
                <div class="leave-requests-list">
                    <?php if (!empty($past_requests)): ?>
                        <?php foreach ($past_requests as $request): ?>
                            <div class="leave-request-item" onclick="openLeaveRequestDetail(<?php echo $request->id; ?>)">
                                <div class="request-header">
                                    <span class="request-date">Requested on <?php echo date('F j, Y, g:iA', strtotime($request->created_at)); ?></span>
                                    <span class="status-badge status-<?php echo esc_attr($request->status); ?>">
                                        <?php echo ucfirst($request->status); ?>
                                    </span>
                                </div>
                                <h4><?php echo esc_html($request->title); ?></h4>
                                <div class="request-details">
                                    <p><strong>Leave Date:</strong> <?php echo date('m/d/Y', strtotime($request->leave_start_date)); ?> - <?php echo date('m/d/Y', strtotime($request->leave_end_date)); ?></p>
                                    <p><strong>Leave Duration:</strong> <?php echo $request->duration_days; ?> days</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-requests">
                            <p>No past leave requests found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Helpful Links -->
        <div class="helpful-links-section">
            <h3>Helpful Links</h3>
            <div class="helpful-links">
                <a href="#" class="helpful-link" onclick="showLeaveGuide()">
                    How to request for leave →
                </a>
                <a href="#" class="helpful-link" onclick="showCancelGuide()">
                    Cancel your leave request →
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Leave Request Form Modal -->
<div id="leaveRequestModal" class="modal" style="display: none;">
    <div class="modal-content leave-form-modal">
        <div class="modal-header">
            <h2>Leave Request Form</h2>
            <span class="close" onclick="closeLeaveRequestForm()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="form-layout">
                <div class="calendar-section">
                    <h3>Choose date of leave</h3>
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <button type="button" class="calendar-nav" id="prevMonth">&lt;</button>
                            <span class="calendar-month-year" id="currentMonthYear"></span>
                            <button type="button" class="calendar-nav" id="nextMonth">&gt;</button>
                        </div>
                        <div class="calendar-grid">
                            <div class="calendar-weekdays">
                                <div class="weekday">Mon</div>
                                <div class="weekday">Tue</div>
                                <div class="weekday">Wed</div>
                                <div class="weekday">Thu</div>
                                <div class="weekday">Fri</div>
                                <div class="weekday">Sat</div>
                                <div class="weekday">Sun</div>
                            </div>
                            <div class="calendar-days" id="calendarDays"></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <form id="leaveRequestForm" class="leave-request-form">
                        <div class="form-group">
                            <label for="date_of_leave">Date of Leave</label>
                            <input type="text" id="date_of_leave" name="date_of_leave" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="duration_of_leave">Duration of Leave</label>
                            <input type="text" id="duration_of_leave" name="duration_of_leave" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="leave_note">Note (optional)</label>
                            <textarea id="leave_note" name="leave_note" rows="3" placeholder="Enter reason for leave..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn-submit-leave">Submit Leave Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModal" class="modal success-modal" style="display: none;">
    <div class="modal-content success-modal-content">
        <div class="success-icon">
            <svg width="60" height="60" viewBox="0 0 60 60" fill="none">
                <circle cx="30" cy="30" r="30" fill="#4ECDC4"/>
                <path d="M18 30L26 38L42 22" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h2>Leave request submitted</h2>
        <p>Your leave request has been submitted.<br>You will be notified of its approval.</p>
        <button class="btn-ok" onclick="closeSuccessModal()">OK</button>
    </div>
</div>

<!-- Leave Request Detail Modal -->
<div id="leaveRequestDetailModal" class="modal" style="display: none;">
    <div class="modal-content leave-detail-modal">
        <div class="modal-header">
            <h2>Leave Request</h2>
            <span class="close" onclick="closeLeaveRequestDetail()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="request-progress">
                <div class="progress-step active" id="step-submitted">
                    <div class="progress-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <polyline points="7,10 12,15 17,10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <line x1="12" y1="15" x2="12" y2="3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <span>Request Submitted</span>
                </div>
                <div class="progress-step" id="step-review">
                    <div class="progress-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <polyline points="14,2 14,8 20,8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <line x1="16" y1="13" x2="8" y2="13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <line x1="16" y1="17" x2="8" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <span>Under Review</span>
                </div>
                <div class="progress-step" id="step-approved">
                    <div class="progress-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <polyline points="20,6 9,17 4,12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <span>Approved</span>
                </div>
            </div>
            
            <div class="request-details-grid">
                <div class="detail-item">
                    <label>Submitted</label>
                    <span id="detail-submitted"></span>
                </div>
                <div class="detail-item">
                    <label>Duration of Leave</label>
                    <span id="detail-duration"></span>
                </div>
                <div class="detail-item">
                    <label>Date of Leave</label>
                    <span id="detail-dates"></span>
                </div>
                <div class="detail-item">
                    <label>Note</label>
                    <span id="detail-note"></span>
                </div>
            </div>
            
            <div class="modal-actions">
                <button class="btn-cancel-request" id="cancelRequestBtn" onclick="cancelLeaveRequestFromDetail()">
                    Cancel request
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Confirmation Modal -->
<div id="cancelConfirmationModal" class="modal" style="display: none;">
    <div class="modal-content cancel-confirmation-modal">
        <div class="cancel-icon">
            <div class="question-mark-icon">
                <span>?</span>
            </div>
        </div>
        <h2>Cancel your leave request</h2>
        <p id="cancelConfirmationText">Confirm the cancellation of your leave request from [DATE_RANGE]. Leave request will be deleted immediately.</p>
        <div class="cancel-modal-actions">
            <button class="btn-cancel-action" onclick="closeCancelConfirmation()">Cancel</button>
            <button class="btn-confirm-cancel" onclick="confirmCancelRequest()">Confirm</button>
        </div>
    </div>
</div>

<!-- Cancel Success Modal -->
<div id="cancelSuccessModal" class="modal" style="display: none;">
    <div class="modal-content cancel-success-modal">
        <div class="cancel-success-icon">
            <div class="x-icon">
                <span>×</span>
            </div>
        </div>
        <h2>Leave request cancelled</h2>
        <p id="cancelSuccessText">Your leave request from [DATE_RANGE] has been cancelled.</p>
        <button class="btn-ok-cancel" onclick="closeCancelSuccess()">OK</button>
    </div>
</div>

<style>
/* LEAVE REQUEST PAGE STYLES */
.iipm-portal-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: 'Gabarito', sans-serif;
}

.iipm-portal-header {
    margin-bottom: 30px;
}

.portal-title {
    color: #8b5a96;
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
}

.leave-request-submit-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 40px;
}

.submit-card, .notice-card {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.submit-card h2, .notice-card h3 {
    color: #333;
    margin-bottom: 20px;
    font-weight: 600;
}

.leave-request-btn {
    background: #ff6b35;
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
    font-size: 16px;
}

.leave-request-btn:hover {
    background: #e55a2b;
    transform: translateY(-2px);
}

.notice-card p {
    color: #666;
    line-height: 1.6;
    margin: 0;
}

.leave-requests-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

.leave-requests-section h2 {
    color: #8b5a96;
    font-size: 1.5rem;
    margin-bottom: 20px;
    font-weight: 600;
}

.leave-request-item {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border-left: 4px solid #8b5a96;
}

.request-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.request-date {
    font-size: 12px;
    color: #666;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-approved {
    background: #d4edda;
    color: #155724;
}

.status-rejected {
    background: #f8d7da;
    color: #721c24;
}

.status-cancelled {
    background: #e2e3e5;
    color: #383d41;
}

.leave-request-item h4 {
    color: #333;
    margin: 10px 0;
    font-weight: 600;
}

.request-details p {
    margin: 5px 0;
    color: #666;
    font-size: 14px;
}

.no-requests {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.helpful-links-section {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.helpful-links-section h3 {
    color: #333;
    margin-bottom: 20px;
    font-weight: 600;
}

.helpful-links {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.helpful-link {
    color: #8b5a96;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.helpful-link:hover {
    color: #6d4576;
}

/* Modal Styles */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.leave-form-modal {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 900px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 30px;
    border-bottom: 1px solid #eee;
}

.modal-header h2 {
    margin: 0;
    color: #8b5a96;
    font-size: 1.5rem;
    font-weight: 600;
}

.close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
    background: none;
    border: none;
}

.close:hover {
    color: #000;
}

.modal-body {
    padding: 30px;
}

.form-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
}

.calendar-section h3 {
    color: #333;
    margin-bottom: 20px;
    font-weight: 600;
}

.calendar-container {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
}

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-bottom: 1px solid #ddd;
}

.calendar-nav {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    padding: 5px 10px;
    border-radius: 4px;
    transition: background 0.3s ease;
}

.calendar-nav:hover {
    background: #e9ecef;
}

.calendar-month-year {
    font-weight: 600;
    color: #333;
}

.calendar-grid {
    padding: 15px;
}

.calendar-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
    margin-bottom: 10px;
}

.weekday {
    text-align: center;
    font-size: 12px;
    font-weight: 600;
    color: #666;
    padding: 8px 0;
}

.calendar-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
}

.calendar-day {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border-radius: 4px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.calendar-day:hover {
    background: #f0f0f0;
}

.calendar-day.other-month {
    color: #ccc;
}

.calendar-day.selected {
    background: #8b5a96;
    color: white;
}

.calendar-day.in-range {
    background: #e8d5ea;
    color: #8b5a96;
}

.form-section {
    display: flex;
    flex-direction: column;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #8b5a96;
}

.btn-submit-leave {
    background: #8b5a96;
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
    font-size: 16px;
    margin-top: auto;
}

.btn-submit-leave:hover {
    background: #6d4576;
}

/* Success Modal */
.success-modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 400px;
    text-align: center;
    padding: 40px 30px;
}

.success-icon {
    margin-bottom: 20px;
}

.success-modal-content h2 {
    color: #333;
    margin-bottom: 15px;
    font-size: 1.5rem;
    font-weight: 600;
}

.success-modal-content p {
    color: #666;
    line-height: 1.6;
    margin-bottom: 30px;
}

.btn-ok {
    background: #8b5a96;
    color: white;
    border: none;
    padding: 12px 40px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 16px;
}

.btn-ok:hover {
    background: #6d4576;
}

/* Responsive Design */
@media (max-width: 768px) {
    .leave-request-submit-section,
    .leave-requests-grid,
    .form-layout {
        grid-template-columns: 1fr;
    }
    
    .portal-title {
        font-size: 2rem;
    }
    
    .leave-form-modal {
        width: 95%;
        margin: 20px;
    }
}

/* Leave Request Detail Modal */
.leave-detail-modal {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.request-progress {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
    position: relative;
}

.request-progress::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 20px;
    right: 20px;
    height: 2px;
    background: #e0e0e0;
    z-index: 1;
}

.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    position: relative;
    z-index: 2;
}

.progress-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    transition: all 0.3s ease;
}

.progress-step.active .progress-icon {
    background: #4ECDC4;
    color: white;
}

.progress-step.completed .progress-icon {
    background: #4ECDC4;
    color: white;
}

.progress-step span {
    font-size: 12px;
    color: #666;
    text-align: center;
    max-width: 80px;
}

.progress-step.active span {
    color: #4ECDC4;
    font-weight: 600;
}

.request-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.detail-item label {
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.detail-item span {
    color: #666;
    font-size: 14px;
}

.modal-actions {
    text-align: center;
}

.btn-cancel-request {
    background: #dc3545;
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
}

.btn-cancel-request:hover {
    background: #c82333;
}

.btn-cancel-request:disabled {
    background: #6c757d;
    cursor: not-allowed;
}

.leave-request-item {
    cursor: pointer;
    transition: transform 0.2s ease;
}

.leave-request-item:hover {
    transform: translateY(-2px);
}

.progress-step.rejected .progress-icon {
    background: #dc3545;
    color: white;
}

.progress-step.cancelled .progress-icon {
    background: #6c757d;
    color: white;
}

.progress-step.rejected span {
    color: #dc3545;
    font-weight: 600;
}

.progress-step.cancelled span {
    color: #6c757d;
    font-weight: 600;
}

/* Cancel Confirmation Modal */
.cancel-confirmation-modal {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 400px;
    text-align: center;
    padding: 40px 30px;
}

.cancel-icon {
    margin-bottom: 20px;
}

.question-mark-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #dc3545;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.question-mark-icon span {
    color: white;
    font-size: 30px;
    font-weight: bold;
}

.cancel-confirmation-modal h2 {
    color: #333;
    margin-bottom: 15px;
    font-size: 1.5rem;
    font-weight: 600;
}

.cancel-confirmation-modal p {
    color: #666;
    line-height: 1.6;
    margin-bottom: 30px;
    font-size: 14px;
}

.cancel-modal-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.btn-cancel-action {
    background: #6c757d;
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
}

.btn-cancel-action:hover {
    background: #5a6268;
}

.btn-confirm-cancel {
    background: #dc3545;
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
}

.btn-confirm-cancel:hover {
    background: #c82333;
}

/* Cancel Success Modal */
.cancel-success-modal {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 400px;
    text-align: center;
    padding: 40px 30px;
}

.cancel-success-icon {
    margin-bottom: 20px;
}

.x-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #333;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.x-icon span {
    color: white;
    font-size: 30px;
    font-weight: bold;
}

.cancel-success-modal h2 {
    color: #333;
    margin-bottom: 15px;
    font-size: 1.5rem;
    font-weight: 600;
}

.cancel-success-modal p {
    color: #666;
    line-height: 1.6;
    margin-bottom: 30px;
    font-size: 14px;
}

.btn-ok-cancel {
    background: #8b5a96;
    color: white;
    border: none;
    padding: 12px 40px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 16px;
}

.btn-ok-cancel:hover {
    background: #6d4576;
}
</style>

<script>
// Global AJAX configuration - Available immediately
window.iipm_ajax = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('iipm_portal_nonce'); ?>'
};

// Calendar functionality
let currentDate = new Date();
let selectedStartDate = null;
let selectedEndDate = null;
let isSelectingRange = false;

document.addEventListener('DOMContentLoaded', function() {
    console.log('Leave Request JS loaded');
    
    // Initialize calendar
    initializeCalendar();
    
    // Calendar navigation
    document.getElementById('prevMonth').addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });
    
    document.getElementById('nextMonth').addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });
});

function initializeCalendar() {
    renderCalendar();
}

function renderCalendar() {
    const monthNames = ["January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"];
    
    document.getElementById('currentMonthYear').textContent = 
        monthNames[currentDate.getMonth()] + ' ' + currentDate.getFullYear();
    
    const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - (firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1));
    
    const calendarDays = document.getElementById('calendarDays');
    calendarDays.innerHTML = '';
    
    for (let i = 0; i < 42; i++) {
        const day = new Date(startDate);
        day.setDate(startDate.getDate() + i);
        
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        dayElement.textContent = day.getDate();
        
        if (day.getMonth() !== currentDate.getMonth()) {
            dayElement.classList.add('other-month');
        }
        
        if (day < new Date().setHours(0, 0, 0, 0)) {
            dayElement.style.opacity = '0.3';
            dayElement.style.cursor = 'not-allowed';
        } else {
            dayElement.addEventListener('click', function() {
                selectDate(day);
            });
        }
        
        // Highlight selected dates
        if (selectedStartDate && isSameDate(day, selectedStartDate)) {
            dayElement.classList.add('selected');
        }
        if (selectedEndDate && isSameDate(day, selectedEndDate)) {
            dayElement.classList.add('selected');
        }
        if (selectedStartDate && selectedEndDate && day > selectedStartDate && day < selectedEndDate) {
            dayElement.classList.add('in-range');
        }
        
        calendarDays.appendChild(dayElement);
    }
}

function selectDate(date) {
    if (date < new Date().setHours(0, 0, 0, 0)) return;
    
    if (!selectedStartDate || (selectedStartDate && selectedEndDate)) {
        // Start new selection
        selectedStartDate = new Date(date);
        selectedEndDate = null;
        isSelectingRange = true;
    } else if (selectedStartDate && !selectedEndDate) {
        // Complete the range
        if (date >= selectedStartDate) {
            selectedEndDate = new Date(date);
        } else {
            selectedEndDate = selectedStartDate;
            selectedStartDate = new Date(date);
        }
        isSelectingRange = false;
        updateFormFields();
    }
    
    renderCalendar();
}

function updateFormFields() {
    if (selectedStartDate && selectedEndDate) {
        const startStr = formatDate(selectedStartDate);
        const endStr = formatDate(selectedEndDate);
        const duration = Math.ceil((selectedEndDate - selectedStartDate) / (1000 * 60 * 60 * 24)) + 1;
        
        document.getElementById('date_of_leave').value = `${startStr} - ${endStr}`;
        document.getElementById('duration_of_leave').value = `${duration} days`;
    }
}

function formatDate(date) {
    return String(date.getDate()).padStart(2, '0') + '/' + 
           String(date.getMonth() + 1).padStart(2, '0') + '/' + 
           date.getFullYear();
}

function isSameDate(date1, date2) {
    return date1.getDate() === date2.getDate() &&
           date1.getMonth() === date2.getMonth() &&
           date1.getFullYear() === date2.getFullYear();
}

function openLeaveRequestForm() {
    document.getElementById('leaveRequestModal').style.display = 'flex';
    // Reset selections
    selectedStartDate = null;
    selectedEndDate = null;
    renderCalendar();
    document.getElementById('leaveRequestForm').reset();
}

function closeLeaveRequestForm() {
    document.getElementById('leaveRequestModal').style.display = 'none';
}

function closeSuccessModal() {
    document.getElementById('successModal').style.display = 'none';
    location.reload(); // Refresh to show new request
}

// Handle form submission
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('leaveRequestForm');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!selectedStartDate || !selectedEndDate) {
                alert('Please select your leave dates from the calendar.');
                return;
            }
            
            const note = document.getElementById('leave_note').value;
            const duration = Math.ceil((selectedEndDate - selectedStartDate) / (1000 * 60 * 60 * 24)) + 1;
            
            const formData = new FormData();
            formData.append('action', 'iipm_submit_leave_request');
            formData.append('nonce', window.iipm_ajax.nonce);
            formData.append('leave_title', `Leave Request for ${duration} days`);
            formData.append('leave_reason', 'personal');
            formData.append('leave_start_date', selectedStartDate.toISOString().split('T')[0]);
            formData.append('leave_end_date', selectedEndDate.toISOString().split('T')[0]);
            formData.append('leave_description', note);
            
            // Disable submit button
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Submitting...';
            submitBtn.disabled = true;
            
            fetch(window.iipm_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeLeaveRequestForm();
                    document.getElementById('successModal').style.display = 'flex';
                } else {
                    alert('Error: ' + (data.data || 'Unknown error occurred'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting your request. Please try again.');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });
    }
});

// Cancel leave request function
let pendingCancelRequestId = null;

function cancelLeaveRequestFromDetail() {
    if (!currentRequestDetail) return;
    
    // Store the request ID for later use
    pendingCancelRequestId = currentRequestDetail.id;
    
    // Update the confirmation text with actual dates
    const dateRange = formatDateRange(currentRequestDetail.leave_start_date, currentRequestDetail.leave_end_date);
    document.getElementById('cancelConfirmationText').textContent = 
        `Confirm the cancellation of your leave request from ${dateRange}. Leave request will be deleted immediately.`;
    
    // Close the detail modal and show confirmation
    closeLeaveRequestDetail();
    document.getElementById('cancelConfirmationModal').style.display = 'flex';
}

function closeCancelConfirmation() {
    document.getElementById('cancelConfirmationModal').style.display = 'none';
    pendingCancelRequestId = null;
}

function confirmCancelRequest() {
    if (!pendingCancelRequestId) return;
    
    const formData = new FormData();
    formData.append('action', 'iipm_cancel_leave_request');
    formData.append('nonce', window.iipm_ajax.nonce);
    formData.append('request_id', pendingCancelRequestId);
    
    // Get the request data for success message
    const requestData = getRequestData(pendingCancelRequestId);
    const dateRange = formatDateRange(requestData.leave_start_date, requestData.leave_end_date);
    
    fetch(window.iipm_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close confirmation modal
            document.getElementById('cancelConfirmationModal').style.display = 'none';
            
            // Update success message and show success modal
            document.getElementById('cancelSuccessText').textContent = 
                `Your leave request from ${dateRange} has been cancelled.`;
            document.getElementById('cancelSuccessModal').style.display = 'flex';
        } else {
            alert('Error: ' + (data.data || 'Unknown error'));
            closeCancelConfirmation();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while cancelling your request.');
        closeCancelConfirmation();
    });
}

function closeCancelSuccess() {
    document.getElementById('cancelSuccessModal').style.display = 'none';
    pendingCancelRequestId = null;
    location.reload(); // Refresh to show updated requests
}

// Helper functions
window.showLeaveGuide = function() {
    alert('Leave Request Guide:\n\n1. Click "Leave Request Form" button\n2. Select your leave dates from the calendar\n3. Add any additional notes\n4. Submit your request\n\nYour request will be reviewed by the administration team.');
};

window.showCancelGuide = function() {
    alert('Cancel Leave Request Guide:\n\n1. Find your pending request in "Your Leave Requests" section\n2. Click the "Cancel Request" button\n3. Confirm the cancellation\n\nNote: You can only cancel requests that are still pending.');
};

// Close modal when clicking outside - update the existing event listener
window.addEventListener('click', function(event) {
    const leaveModal = document.getElementById('leaveRequestModal');
    const successModal = document.getElementById('successModal');
    const cancelConfirmModal = document.getElementById('cancelConfirmationModal');
    const cancelSuccessModal = document.getElementById('cancelSuccessModal');
    
    if (event.target === leaveModal) {
        closeLeaveRequestForm();
    }
    if (event.target === successModal) {
        closeSuccessModal();
    }
    if (event.target === cancelConfirmModal) {
        closeCancelConfirmation();
    }
    if (event.target === cancelSuccessModal) {
        closeCancelSuccess();
    }
});

let currentRequestDetail = null;

function openLeaveRequestDetail(requestId) {
    // Find the request data (you'll need to pass this from PHP)
    const requestData = getRequestData(requestId);
    if (!requestData) return;
    
    currentRequestDetail = requestData;
    
    // Update modal content
    document.getElementById('detail-submitted').textContent = formatDateTime(requestData.created_at);
    document.getElementById('detail-duration').textContent = requestData.duration_days + ' days';
    document.getElementById('detail-dates').textContent = formatDateRange(requestData.leave_start_date, requestData.leave_end_date);
    document.getElementById('detail-note').textContent = requestData.description || 'No additional notes';
    
    // Update progress steps
    updateProgressSteps(requestData.status);
    
    // Show/hide cancel button based on status
    const cancelBtn = document.getElementById('cancelRequestBtn');
    if (requestData.status === 'pending') {
        cancelBtn.style.display = 'block';
    } else {
        cancelBtn.style.display = 'none';
    }
    
    document.getElementById('leaveRequestDetailModal').style.display = 'flex';
}

function closeLeaveRequestDetail() {
    document.getElementById('leaveRequestDetailModal').style.display = 'none';
    currentRequestDetail = null;
}

function updateProgressSteps(status) {
    // Reset all steps and restore original content
    document.querySelectorAll('.progress-step').forEach(step => {
        step.classList.remove('active', 'completed', 'rejected', 'cancelled');
    });
    
    // Reset to original content
    document.getElementById('step-approved').style.display = 'flex';
    document.querySelector('#step-review span').textContent = 'Under Review';
    document.querySelector('#step-approved span').textContent = 'Approved';
    
    // Reset icons to original
    document.querySelector('#step-review .progress-icon').innerHTML = `
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <polyline points="14,2 14,8 20,8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <line x1="16" y1="13" x2="8" y2="13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <line x1="16" y1="17" x2="8" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
    `;
    
    document.querySelector('#step-approved .progress-icon').innerHTML = `
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
            <polyline points="20,6 9,17 4,12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    `;
    
    // Always mark submitted as completed
    document.getElementById('step-submitted').classList.add('completed');
    
    if (status === 'pending') {
        document.getElementById('step-review').classList.add('active');
    } else if (status === 'approved') {
        document.getElementById('step-review').classList.add('completed');
        document.getElementById('step-approved').classList.add('active');
    } else if (status === 'rejected') {
        document.getElementById('step-review').classList.add('completed');
        document.getElementById('step-approved').classList.add('rejected');
        // Change the text and icon for rejected state
        document.querySelector('#step-approved span').textContent = 'Rejected';
        document.querySelector('#step-approved .progress-icon').innerHTML = `
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                <path d="m15 9-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="m9 9 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        `;
    } else if (status === 'cancelled') {
        document.getElementById('step-review').classList.add('cancelled');
        // Change the text and icon for cancelled state
        document.querySelector('#step-review span').textContent = 'Cancelled';
        document.querySelector('#step-review .progress-icon').innerHTML = `
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                <path d="m15 9-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="m9 9 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        `;
        // Hide the approved step for cancelled requests
        document.getElementById('step-approved').style.display = 'none';
    }
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ', ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) + (date.getHours() >= 12 ? 'PM' : 'AM');
}

function formatDateRange(startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    return String(start.getDate()).padStart(2, '0') + '/' + 
           String(start.getMonth() + 1).padStart(2, '0') + '/' + 
           start.getFullYear() + ' - ' +
           String(end.getDate()).padStart(2, '0') + '/' + 
           String(end.getMonth() + 1).padStart(2, '0') + '/' + 
           end.getFullYear();
}

// You'll need to create this function to get request data
function getRequestData(requestId) {
    // This should return the request data for the given ID
    // You might need to pass this data from PHP or make an AJAX call
    const requests = <?php echo json_encode(array_merge($current_requests, $past_requests)); ?>;
    return requests.find(request => request.id == requestId);
}
</script>

<?php get_footer(); ?>
