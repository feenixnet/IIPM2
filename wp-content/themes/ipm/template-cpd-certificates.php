<?php
/**
 * Template Name: CPD Certificates
 * 
 * Displays user's CPD certificates list
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

<div class="cpd-certificates-page">
    <!-- Hero Section -->
    <div class="cpd-certificates-hero">
        <div class="container">
            <div class="hero-content">
                <!-- Back Navigation -->
                <div class="back-navigation">
                    <a href="<?php echo home_url('/cpd-record/'); ?>" class="back-link">
                        ← Back to CPD Record
                    </a>
                </div>

                <!-- Page Header -->
                <div class="page-header">
                    <h1>CPD Certificates</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Certificates Table -->
        <div class="certificates-section">
            <div class="certificates-table">
                <div class="table-header">
                    <div class="header-cell file-name">FILE NAME</div>
                    <div class="header-cell date-uploaded">DATE UPLOADED</div>
                    <div class="header-cell category">CATEGORY</div>
                    <div class="header-cell action">ACTION</div>
                </div>
                
                <div class="table-body" id="certificates-table-body">
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <p>Loading certificates...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Helpful Links -->
        <div class="helpful-links-section">
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
</div>

<style>
/* CPD Certificates Page Styles */
.cpd-certificates-page {
    background: #f8fafc;
    min-height: 100vh;
    margin: 0;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    padding-bottom: 30px;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Hero Section */
.cpd-certificates-hero {
    position: relative;
    background: linear-gradient(135deg,rgb(123, 134, 150) 0%,rgb(51, 77, 134) 100%);
    color: white;
    overflow: hidden;
}

.cpd-certificates-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3);
    z-index: 1;
}

.hero-content {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    gap: 30px;
    margin-top: 120px;
    margin-bottom: 60px;
}

/* Back Navigation */
.back-navigation {
    margin-bottom: 0;
}

.back-link {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    font-size: 16px;
    font-weight: 500;
    transition: color 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.back-link:hover {
    color: white;
    text-decoration: underline;
}

/* Page Header */
.page-header {
    margin-bottom: 0;
}

.page-header h1 {
    margin: 0 0 16px 0;
    font-size: 3rem;
    font-weight: 700;
    color: white;
    line-height: 1.1;
}

.page-header p {
    margin: 0;
    font-size: 1.2rem;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.5;
    max-width: 600px;
}

/* Certificates Section */
.certificates-section {
    background: white;
    border-radius: 12px;
    padding: 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin: 40px 0 50px 0;
    overflow: hidden;
}

/* Certificates Table */
.certificates-table {
    width: 100%;
}

.table-header {
    display: grid;
    grid-template-columns: 3fr 1.5fr 1fr 1fr;
    gap: 20px;
    padding: 20px 24px;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
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
    grid-template-columns: 3fr 1.5fr 1fr 1fr;
    gap: 20px;
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
    align-items: center;
    transition: background-color 0.2s;
}

.table-row:hover {
    background: #f9fafb;
}

.table-row:last-child {
    border-bottom: none;
}

.file-name {
    font-size: 14px;
    color: #1f2937;
    font-weight: 500;
}

.date-uploaded {
    font-size: 14px;
    color: #6b7280;
}

.category {
    font-size: 14px;
    color: #6b7280;
}

.action {
    display: flex;
    gap: 12px;
    align-items: center;
}

.action-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 6px;
    border-radius: 6px;
    transition: background-color 0.2s;
    color: #6b7280;
    font-size: 16px;
}

.action-btn:hover {
    background: #f3f4f6;
    color: #1f2937;
}

.download-btn::before {
    content: "⬇";
}

.view-btn::before {
    content: "👁";
}

/* Helpful Links Section */
.helpful-links-section {
    margin-top: 40px;
}

.helpful-links-section h3 {
    margin: 0 0 20px 0;
    font-size: 24px;
    font-weight: 600;
    color: #1f2937;
}

.helpful-links {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.helpful-link {
    color: #8b5cf6;
    text-decoration: none;
    font-size: 16px;
    font-weight: 500;
    transition: color 0.2s;
    padding: 8px 0;
}

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
@media (max-width: 768px) {
    .hero-content {
        margin-top: 80px;
        margin-bottom: 40px;
        gap: 20px;
    }
    
    .page-header h1 {
        font-size: 2.5rem;
    }
    
    .page-header p {
        font-size: 1.1rem;
    }
    
    .table-header,
    .table-row {
        grid-template-columns: 2fr 1fr 1fr;
        gap: 12px;
        padding: 16px;
    }
    
    .date-uploaded {
        display: none;
    }
    
    .header-cell.date-uploaded {
        display: none;
    }
    
    .file-name {
        font-size: 13px;
    }
    
    .action {
        gap: 8px;
    }
    
    .action-btn {
        padding: 4px;
        font-size: 14px;
    }
    
    .certificates-section {
        margin: 30px 0 40px 0;
    }
}

@media (max-width: 480px) {
    .hero-content {
        margin-top: 60px;
        margin-bottom: 30px;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
    
    .page-header p {
        font-size: 1rem;
    }
    
    .container {
        padding: 0 16px;
    }
    
    .certificates-section {
        margin: 20px 0 30px 0;
    }
    
    .table-header,
    .table-row {
        padding: 12px;
        gap: 8px;
    }
}
</style>

<script>
// Global variables
let certificates = [];

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadCertificates();
});

// Load certificates data
function loadCertificates() {
    // Sample certificates data
    certificates = [
        {
            id: 1,
            file_name: 'John Doe - CPD Certificate 2024',
            date_uploaded: '2024-12-08',
            category: 'Certificate',
            year: 2024,
            file_url: '#'
        },
        {
            id: 2,
            file_name: 'John Doe - CPD Certificate 2023',
            date_uploaded: '2024-11-06',
            category: 'Certificate',
            year: 2023,
            file_url: '#'
        },
        {
            id: 3,
            file_name: 'John Doe - CPD Certificate 2022',
            date_uploaded: '2024-10-05',
            category: 'Certificate',
            year: 2022,
            file_url: '#'
        },
        {
            id: 4,
            file_name: 'John Doe - CPD Certificate 2021',
            date_uploaded: '2024-10-04',
            category: 'Certificate',
            year: 2021,
            file_url: '#'
        },
        {
            id: 5,
            file_name: 'John Doe - CPD Certificate 2020',
            date_uploaded: '2024-10-04',
            category: 'Certificate',
            year: 2020,
            file_url: '#'
        }
    ];
    
    updateCertificatesTable();
}

// Update certificates table
function updateCertificatesTable() {
    const tableBody = document.getElementById('certificates-table-body');
    
    if (certificates.length === 0) {
        tableBody.innerHTML = `
            <div class="empty-state">
                <p>No certificates found.</p>
            </div>
        `;
        return;
    }
    
    tableBody.innerHTML = certificates.map(cert => `
        <div class="table-row">
            <div class="file-name">${escapeHtml(cert.file_name)}</div>
            <div class="date-uploaded">${formatDate(cert.date_uploaded)}</div>
            <div class="category">${escapeHtml(cert.category)}</div>
            <div class="action">
                <button class="action-btn download-btn" onclick="downloadCertificate(${cert.id})" title="Download"></button>
                <button class="action-btn view-btn" onclick="viewCertificate(${cert.id})" title="View"></button>
            </div>
        </div>
    `).join('');
}

// Download certificate
function downloadCertificate(certId) {
    const certificate = certificates.find(cert => cert.id === certId);
    if (certificate) {
        // Show download notification
        const downloadId = notifications.info('Downloading Certificate', `Preparing ${certificate.file_name} for download...`, { persistent: true });
        
        // Simulate download process
        setTimeout(() => {
            notifications.hide(downloadId);
            notifications.success('Download Complete', `${certificate.file_name} has been downloaded successfully.`);
            
            // Simulate file download
            const link = document.createElement('a');
            link.href = certificate.file_url || '#';
            link.download = certificate.file_name + '.pdf';
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }, 1500);
    }
}

// View certificate
function viewCertificate(certId) {
    const certificate = certificates.find(cert => cert.id === certId);
    if (certificate) {
        // Show view notification
        notifications.info('Opening Certificate', `Opening ${certificate.file_name} in a new window...`, { duration: 2000 });
        
        // Simulate opening in new window
        setTimeout(() => {
            // In a real implementation, this would open the certificate in a new window/modal
            window.open(certificate.file_url || '#', '_blank');
            console.log('View certificate:', certificate);
        }, 500);
    }
}

// Utility functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php get_footer(); ?> 