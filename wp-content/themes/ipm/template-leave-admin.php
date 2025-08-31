<?php
/**
 * Template Name: Leave Admin
 * 
 * @package IPM
 */

// Redirect if not logged in or not admin
if (!is_user_logged_in() || (!current_user_can('administrator') && !current_user_can('iipm_admin'))) {
    wp_redirect(home_url('/login/'));
    exit;
}

// FORCE LOAD MAIN THEME CSS
wp_enqueue_style('iipm-main-style', get_template_directory_uri() . '/assets/css/main.min.css', array(), '1.0.0');

// Check if the modular header function exists, otherwise use default header
if (function_exists('iipm_load_header')) {
    iipm_load_header();
} else {
    get_header();
}

// Get all leave requests
global $wpdb;
$leave_requests = $wpdb->get_results(
    "SELECT lr.*, u.display_name, u.user_email 
     FROM {$wpdb->prefix}test_iipm_leave_requests lr
     LEFT JOIN {$wpdb->prefix}users u ON lr.user_id = u.ID
     ORDER BY lr.created_at DESC"
);

// Separate by status
$pending_requests = array();
$approved_requests = array();
$rejected_requests = array();

foreach ($leave_requests as $request) {
    switch ($request->status) {
        case 'pending':
            $pending_requests[] = $request;
            break;
        case 'approved':
            $approved_requests[] = $request;
            break;
        case 'rejected':
            $rejected_requests[] = $request;
            break;
    }
}
?>

<div class="iipm-portal-container">
    <div class="iipm-portal-header">
        <h1 class="portal-title">Leave Request Administration</h1>
        <p class="portal-subtitle">Review and manage all leave requests</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card pending">
            <h3><?php echo count($pending_requests); ?></h3>
            <p>Pending Requests</p>
        </div>
        <div class="stat-card approved">
            <h3><?php echo count($approved_requests); ?></h3>
            <p>Approved Requests</p>
        </div>
        <div class="stat-card rejected">
            <h3><?php echo count($rejected_requests); ?></h3>
            <p>Rejected Requests</p>
        </div>
        <div class="stat-card total">
            <h3><?php echo count($leave_requests); ?></h3>
            <p>Total Requests</p>
        </div>
    </div>

    <!-- Pending Requests Section -->
    <div class="admin-section">
        <h2>Pending Requests (<?php echo count($pending_requests); ?>)</h2>
        <div class="requests-list">
            <?php if (!empty($pending_requests)): ?>
                <?php foreach ($pending_requests as $request): ?>
                    <div class="admin-request-item pending">
                        <div class="request-header">
                            <div class="user-info">
                                <h4><?php echo esc_html($request->display_name); ?></h4>
                                <span class="user-email"><?php echo esc_html($request->user_email); ?></span>
                            </div>
                            <div class="request-actions">
                                <button class="btn-approve" onclick="approveRequest(<?php echo $request->id; ?>)">
                                    ✓ Approve
                                </button>
                                <button class="btn-reject" onclick="rejectRequest(<?php echo $request->id; ?>)">
                                    ✗ Reject
                                </button>
                            </div>
                        </div>
                        <div class="request-details">
                            <h5><?php echo esc_html($request->title); ?></h5>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <strong>Reason:</strong> <?php echo ucfirst($request->reason); ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Duration:</strong> <?php echo $request->duration_days; ?> days
                                </div>
                                <div class="detail-item">
                                    <strong>Start Date:</strong> <?php echo date('M j, Y', strtotime($request->leave_start_date)); ?>
                                </div>
                                <div class="detail-item">
                                    <strong>End Date:</strong> <?php echo date('M j, Y', strtotime($request->leave_end_date)); ?>
                                </div>
                            </div>
                            <?php if ($request->description): ?>
                                <div class="request-description">
                                    <strong>Additional Details:</strong>
                                    <p><?php echo esc_html($request->description); ?></p>
                                </div>
                            <?php endif; ?>
                            <div class="request-meta">
                                <span>Submitted on <?php echo date('F j, Y \a\t g:i A', strtotime($request->created_at)); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-requests">
                    <p>No pending requests found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Approved Requests Section -->
    <div class="admin-section">
        <h2>Approved Requests (<?php echo count($approved_requests); ?>)</h2>
        <div class="requests-list">
            <?php if (!empty($approved_requests)): ?>
                <?php foreach (array_slice($approved_requests, 0, 5) as $request): ?>
                    <div class="admin-request-item approved">
                        <div class="request-header">
                            <div class="user-info">
                                <h4><?php echo esc_html($request->display_name); ?></h4>
                                <span class="user-email"><?php echo esc_html($request->user_email); ?></span>
                            </div>
                            <div class="status-badge status-approved">Approved</div>
                        </div>
                        <div class="request-details">
                            <h5><?php echo esc_html($request->title); ?></h5>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <strong>Duration:</strong> <?php echo $request->duration_days; ?> days
                                </div>
                                <div class="detail-item">
                                    <strong>Leave Period:</strong> <?php echo date('M j', strtotime($request->leave_start_date)); ?> - <?php echo date('M j, Y', strtotime($request->leave_end_date)); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (count($approved_requests) > 5): ?>
                    <div class="show-more">
                        <button onclick="showAllApproved()">Show All Approved (<?php echo count($approved_requests); ?>)</button>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-requests">
                    <p>No approved requests found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Rejected Requests Section -->
    <div class="admin-section">
        <h2>Rejected Requests (<?php echo count($rejected_requests); ?>)</h2>
        <div class="requests-list">
            <?php if (!empty($rejected_requests)): ?>
                <?php foreach (array_slice($rejected_requests, 0, 3) as $request): ?>
                    <div class="admin-request-item rejected">
                        <div class="request-header">
                            <div class="user-info">
                                <h4><?php echo esc_html($request->display_name); ?></h4>
                                <span class="user-email"><?php echo esc_html($request->user_email); ?></span>
                            </div>
                            <div class="status-badge status-rejected">Rejected</div>
                        </div>
                        <div class="request-details">
                            <h5><?php echo esc_html($request->title); ?></h5>
                            <?php if ($request->rejection_reason): ?>
                                <div class="rejection-reason">
                                    <strong>Rejection Reason:</strong>
                                    <p><?php echo esc_html($request->rejection_reason); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (count($rejected_requests) > 3): ?>
                    <div class="show-more">
                        <button onclick="showAllRejected()">Show All Rejected (<?php echo count($rejected_requests); ?>)</button>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-requests">
                    <p>No rejected requests found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div id="rejectionModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Reject Leave Request</h2>
            <span class="close" onclick="closeRejectionModal()">&times;</span>
        </div>
        <form id="rejectionForm" method="post">
            <div class="form-group">
                <label for="rejection_reason">Reason for Rejection *</label>
                <textarea id="rejection_reason" name="rejection_reason" rows="4" required placeholder="Please provide a reason for rejecting this request..."></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeRejectionModal()">Cancel</button>
                <button type="submit" class="btn-reject">Reject Request</button>
            </div>
        </form>
    </div>
</div>

<style>
/* ADMIN LEAVE REQUEST STYLES */
.iipm-portal-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: 'Gabarito', sans-serif;
}

.portal-subtitle {
    color: #666;
    margin-top: 10px;
    font-size: 1.1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-left: 4px solid;
}

.stat-card.pending { border-left-color: #ffc107; }
.stat-card.approved { border-left-color: #28a745; }
.stat-card.rejected { border-left-color: #dc3545; }
.stat-card.total { border-left-color: #8b5a96; }

.stat-card h3 {
    font-size: 2.5rem;
    margin: 0 0 10px 0;
    font-weight: 700;
}

.stat-card.pending h3 { color: #ffc107; }
.stat-card.approved h3 { color: #28a745; }
.stat-card.rejected h3 { color: #dc3545; }
.stat-card.total h3 { color: #8b5a96; }

.stat-card p {
    margin: 0;
    color: #666;
    font-weight: 500;
}

.admin-section {
    margin-bottom: 40px;
}

.admin-section h2 {
    color: #8b5a96;
    font-size: 1.5rem;
    margin-bottom: 20px;
    font-weight: 600;
}

.admin-request-item {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border-left: 4px solid;
}

.admin-request-item.pending { border-left-color: #ffc107; }
.admin-request-item.approved { border-left-color: #28a745; }
.admin-request-item.rejected { border-left-color: #dc3545; }

.request-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.user-info h4 {
    margin: 0 0 5px 0;
    color: #333;
    font-weight: 600;
}

.user-email {
    color: #666;
    font-size: 14px;
}

.request-actions {
    display: flex;
    gap: 10px;
}

.btn-approve, .btn-reject {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
}

.btn-approve {
    background: #28a745;
    color: white;
}

.btn-approve:hover {
    background: #218838;
}

.btn-reject {
    background: #dc3545;
    color: white;
}

.btn-reject:hover {
    background: #c82333;
}

.request-details h5 {
    color: #333;
    margin: 0 0 15px 0;
    font-weight: 600;
    font-size: 1.1rem;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.detail-item {
    font-size: 14px;
}

.detail-item strong {
    color: #333;
}

.request-description {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin: 15px 0;
}

.request-description p {
    margin: 5px 0 0 0;
    color: #666;
}

.request-meta {
    font-size: 12px;
    color: #999;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-approved {
    background: #d4edda;
    color: #155724;
}

.status-rejected {
    background: #f8d7da;
    color: #721c24;
}

.no-requests {
    text-align: center;
    padding: 40px 20px;
    color: #666;
    background: white;
    border-radius: 12px;
}

.show-more {
    text-align: center;
    margin-top: 20px;
}

.show-more button {
    background: #8b5a96;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
}

.show-more button:hover {
    background: #6d4576;
}

.rejection-reason {
    background: #f8d7da;
    padding: 15px;
    border-radius: 8px;
    margin: 15px 0;
}

.rejection-reason p {
    margin: 5px 0 0 0;
    color: #721c24;
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

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
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
    color: #333;
}

.close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
}

.close:hover {
    color: #000;
}

.form-group {
    padding: 30px;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: #333;
}

.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
    font-family: inherit;
}

.form-group textarea:focus {
    outline: none;
    border-color: #8b5a96;
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    padding: 0 30px 30px 30px;
}

.btn-primary, .btn-secondary {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

/* Responsive Design */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .request-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .detail-grid {
        grid-template-columns: 1fr;
    }
    
    .request-actions {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>

<script>
// Define global AJAX variables IMMEDIATELY (not inside document ready)
window.iipm_ajax = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('iipm_portal_nonce'); ?>'
};

// Global variable for tracking current request ID
let currentRequestId = null;

// Wait for DOM to be ready before setting up event handlers
document.addEventListener('DOMContentLoaded', function() {
    console.log('Leave Admin JS loaded');
    console.log('AJAX object:', window.iipm_ajax);
    
    // Set up rejection form handler
    const rejectionForm = document.getElementById('rejectionForm');
    if (rejectionForm) {
        rejectionForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            
            if (!window.iipm_ajax || !window.iipm_ajax.nonce) {
                alert('Error: AJAX configuration not loaded properly. Please refresh the page.');
                return;
            }
            
            const reason = document.getElementById('rejection_reason').value;
            
            if (!reason.trim()) {
                alert('Please provide a reason for rejection.');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'iipm_reject_leave_request');
            formData.append('nonce', window.iipm_ajax.nonce);
            formData.append('request_id', currentRequestId);
            formData.append('rejection_reason', reason);
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Rejecting...';
            submitBtn.disabled = true;
            
            fetch(window.iipm_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Reject response:', data);
                if (data.success) {
                    alert('Leave request rejected successfully!');
                    closeRejectionModal();
                    location.reload();
                } else {
                    alert('Error: ' + (data.data || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while rejecting the request.');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });
    }
});

function approveRequest(requestId) {
    console.log('Approve request called with ID:', requestId);
    
    if (!window.iipm_ajax || !window.iipm_ajax.nonce) {
        alert('Error: AJAX configuration not loaded properly. Please refresh the page.');
        return;
    }
    
    if (!confirm('Are you sure you want to approve this leave request?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'iipm_approve_leave_request');
    formData.append('nonce', window.iipm_ajax.nonce);
    formData.append('request_id', requestId);
    
    fetch(window.iipm_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Approve response:', data);
        if (data.success) {
            alert('Leave request approved successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.data || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while approving the request.');
    });
}

function rejectRequest(requestId) {
    console.log('Reject request called with ID:', requestId);
    
    if (!window.iipm_ajax || !window.iipm_ajax.nonce) {
        alert('Error: AJAX configuration not loaded properly. Please refresh the page.');
        return;
    }
    
    currentRequestId = requestId;
    document.getElementById('rejectionModal').style.display = 'flex';
}

function closeRejectionModal() {
    document.getElementById('rejectionModal').style.display = 'none';
    document.getElementById('rejectionForm').reset();
    currentRequestId = null;
}

function showAllApproved() {
    alert('Feature to show all approved requests will be implemented.');
}

function showAllRejected() {
    alert('Feature to show all rejected requests will be implemented.');
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('rejectionModal');
    if (event.target === modal) {
        closeRejectionModal();
    }
});
</script>

<?php get_footer(); ?>
