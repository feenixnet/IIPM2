<?php
/*
Template Name: File Dashboard
*/

// Check if user is logged in and has appropriate permissions
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;

get_header(); ?>

<!-- Hero Section -->
<div class="file-dashboard-hero">
    <div class="container">
        <div class="hero-content">
            <div class="page-header">
                <h1>File Dashboard</h1>
                <p class="page-subtitle">Manage and access your documents and files</p>
            </div>
            <div class="user-welcome">
                <h2>Welcome back, <?php echo esc_html($current_user->display_name); ?>!</h2>
                <p class="designation">
                    <?php 
                    $user_meta = get_user_meta($current_user->ID);
                    $designation = isset($user_meta['designation']) ? $user_meta['designation'][0] : 'Member';
                    echo esc_html($designation);
                    ?>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="file-dashboard-page">
    <div class="container">
        <div class="file-dashboard-layout">
            <!-- Sidebar Navigation -->
            <div class="file-sidebar">
                <!-- Upload Button -->
                <button class="upload-btn sidebar-upload" onclick="openUploadModal()">
                    <span class="upload-icon simple-upload-icon"></span>
                    Upload new file
                </button>
                
                <nav class="file-nav">
                    <ul class="file-nav-list">
                        <li class="nav-item" data-section="my-files" onclick="switchTab('my-files')">
                            <span class="nav-icon folder-icon"></span>
                            <span>My Files</span>
                        </li>
                        <li class="nav-item" data-section="download-history" onclick="switchTab('download-history')">
                            <span class="nav-icon download-icon"></span>
                            <span>Download History</span>
                        </li>
                        <li class="nav-item" data-section="document-repository" onclick="switchTab('document-repository')">
                            <span class="nav-icon document-icon"></span>
                            <span>Document Repository</span>
                        </li>
                        <li class="nav-separator"></li>
                        <li class="nav-item" data-section="faqs" onclick="switchTab('faqs')">
                            <span class="nav-icon faq-icon"></span>
                            <span>FAQs</span>
                        </li>
                        <li class="nav-item" data-section="contact" onclick="switchTab('contact')">
                            <span class="nav-icon contact-icon"></span>
                            <span>Contact</span>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="file-main-content">
                <!-- Search Section -->
                <div class="search-section">
                    <div class="search-container">
                        <input type="text" class="search-input" placeholder="Enter search inquiry" id="file-search">
                        <button class="search-btn" onclick="performSearch()">Search</button>
                    </div>
                </div>

                <!-- Default View (Recent Files + Download History) -->
                <div class="content-section" id="default-view">
                    <!-- Recent Files Section -->
                    <div class="files-section" id="recent-files-section">
                        <h2 class="section-title">Recent Files</h2>
                        <div class="files-table-container">
                            <table class="files-table">
                                <thead>
                                    <tr>
                                        <th>FILENAME</th>
                                        <th>UPLOADED BY</th>
                                        <th>DATE UPLOADED</th>
                                        <th>CATEGORY</th>
                                        <th>ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-files-tbody">
                                    <!-- Files will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Download History Section -->
                    <div class="files-section" id="download-history-section">
                        <h2 class="section-title">Your Download History</h2>
                        <div class="files-table-container">
                            <table class="files-table">
                                <thead>
                                    <tr>
                                        <th>FILENAME</th>
                                        <th>UPLOADED BY</th>
                                        <th>DATE UPLOADED</th>
                                        <th>CATEGORY</th>
                                        <th>ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody id="download-history-tbody">
                                    <!-- Download history will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- My Files Tab Content -->
                <div class="content-section" id="my-files-content" style="display: none;">
                    <div class="my-files-header">
                        <h2 class="section-title">My Files</h2>
                        
                        <div class="my-files-actions">
                            <div class="selection-info">
                                <span id="selected-count">0 selected files</span>
                                <button class="action-btn delete-btn" id="delete-selected-btn" disabled onclick="deleteSelectedFiles()">
                                    <span class="btn-icon delete-icon-btn"></span> Delete
                                </button>
                                <button class="action-btn download-btn" id="download-selected-btn" disabled onclick="downloadSelectedFiles()">
                                    <span class="btn-icon download-icon-btn"></span> Batch download
                                </button>
                                <button class="action-btn deselect-btn" id="deselect-btn" disabled onclick="deselectAllFiles()">
                                    <span class="btn-icon deselect-icon-btn"></span> Deselect
                                </button>
                            </div>
                            
                            <div class="filter-container">
                                <button class="filter-btn" onclick="toggleFilter()">
                                    <span class="btn-icon filter-icon-btn"></span> Filter
                                </button>
                                
                                <!-- Filter Dropdown -->
                                <div class="filter-dropdown" id="filter-dropdown" style="display: none;">
                                    <div class="filter-section">
                                        <h4>Filter by category</h4>
                                        <div class="filter-checkboxes">
                                            <label class="filter-checkbox">
                                                <input type="checkbox" value="council" checked onchange="updateCategoryFilter()">
                                                <span class="checkmark"></span>
                                                Council
                                            </label>
                                            <label class="filter-checkbox">
                                                <input type="checkbox" value="general" checked onchange="updateCategoryFilter()">
                                                <span class="checkmark"></span>
                                                General
                                            </label>
                                            <label class="filter-checkbox">
                                                <input type="checkbox" value="marketing" checked onchange="updateCategoryFilter()">
                                                <span class="checkmark"></span>
                                                Marketing
                                            </label>
                                            <label class="filter-checkbox">
                                                <input type="checkbox" value="membership" checked onchange="updateCategoryFilter()">
                                                <span class="checkmark"></span>
                                                Membership
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="filter-section">
                                        <h4>Filter by Date</h4>
                                        <div class="date-range">
                                            <input type="text" class="date-input" placeholder="DD/MM/YY" id="start-date">
                                            <span class="date-separator">–</span>
                                            <input type="text" class="date-input" placeholder="DD/MM/YY" id="end-date">
                                        </div>
                                    </div>
                                    
                                    <div class="filter-section">
                                        <h4>Filter by file type</h4>
                                        <select class="file-type-select" id="file-type-filter">
                                            <option value="">All file types</option>
                                            <option value="pdf" selected>PDF</option>
                                            <option value="doc">DOC</option>
                                            <option value="docx">DOCX</option>
                                            <option value="xls">XLS</option>
                                            <option value="xlsx">XLSX</option>
                                            <option value="ppt">PPT</option>
                                            <option value="pptx">PPTX</option>
                                            <option value="txt">TXT</option>
                                            <option value="jpg">JPG</option>
                                            <option value="png">PNG</option>
                                        </select>
                                    </div>
                                    
                                    <button class="set-filter-btn" onclick="applyFilters()">Set filter</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="files-table-container">
                        <table class="files-table my-files-table">
                            <thead>
                                <tr>
                                    <th class="checkbox-column">
                                        <input type="checkbox" id="select-all-checkbox" onchange="toggleSelectAll()">
                                    </th>
                                    <th>FILE NAME</th>
                                    <th>OWNER</th>
                                    <th>DATE UPLOADED</th>
                                    <th>CATEGORY</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody id="my-files-tbody">
                                <!-- My files will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="pagination-container">
                        <div class="pagination-controls">
                            <button class="pagination-btn" onclick="previousPage()" id="prev-btn" disabled>❮</button>
                            <span class="pagination-info">
                                <span id="current-page">1</span> of <span id="total-pages">9</span>
                            </span>
                            <button class="pagination-btn" onclick="nextPage()" id="next-btn">❯</button>
                        </div>
                    </div>
                </div>

                <!-- Download History Tab Content -->
                <div class="content-section" id="download-history-content" style="display: none;">
                    <div class="download-history-header">
                        <h2 class="section-title">Download History</h2>
                        
                        <div class="download-history-actions">
                            <div class="selection-info">
                                <span id="download-selected-count">0 selected files</span>
                                <button class="action-btn download-btn" id="batch-download-btn" disabled onclick="batchDownloadFiles()">
                                    <span class="btn-icon download-icon-btn"></span> Batch download
                                </button>
                                <button class="action-btn deselect-btn" id="download-deselect-btn" disabled onclick="deselectAllDownloadFiles()">
                                    <span class="btn-icon deselect-icon-btn"></span> Deselect
                                </button>
                            </div>
                            
                            <div class="filter-container">
                                <button class="filter-btn" onclick="toggleDownloadFilter()">
                                    <span class="btn-icon filter-icon-btn"></span> Filter
                                </button>
                                
                                <!-- Download Filter Dropdown -->
                                <div class="filter-dropdown" id="download-filter-dropdown" style="display: none;">
                                    <div class="filter-section">
                                        <h4>Filter by category</h4>
                                        <div class="filter-checkboxes">
                                            <label class="filter-checkbox">
                                                <input type="checkbox" value="council" checked onchange="updateDownloadCategoryFilter()">
                                                <span class="checkmark"></span>
                                                Council
                                            </label>
                                            <label class="filter-checkbox">
                                                <input type="checkbox" value="general" checked onchange="updateDownloadCategoryFilter()">
                                                <span class="checkmark"></span>
                                                General
                                            </label>
                                            <label class="filter-checkbox">
                                                <input type="checkbox" value="marketing" checked onchange="updateDownloadCategoryFilter()">
                                                <span class="checkmark"></span>
                                                Marketing
                                            </label>
                                            <label class="filter-checkbox">
                                                <input type="checkbox" value="membership" checked onchange="updateDownloadCategoryFilter()">
                                                <span class="checkmark"></span>
                                                Membership
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="filter-section">
                                        <h4>Filter by Date</h4>
                                        <div class="date-range">
                                            <input type="text" class="date-input" placeholder="DD/MM/YY" id="download-start-date">
                                            <span class="date-separator">–</span>
                                            <input type="text" class="date-input" placeholder="DD/MM/YY" id="download-end-date">
                                        </div>
                                    </div>
                                    
                                    <div class="filter-section">
                                        <h4>Filter by file type</h4>
                                        <select class="file-type-select" id="download-file-type-filter">
                                            <option value="">All file types</option>
                                            <option value="pdf" selected>PDF</option>
                                            <option value="doc">DOC</option>
                                            <option value="docx">DOCX</option>
                                            <option value="xls">XLS</option>
                                            <option value="xlsx">XLSX</option>
                                            <option value="ppt">PPT</option>
                                            <option value="pptx">PPTX</option>
                                            <option value="txt">TXT</option>
                                            <option value="jpg">JPG</option>
                                            <option value="png">PNG</option>
                                        </select>
                                    </div>
                                    
                                    <button class="set-filter-btn" onclick="applyDownloadFilters()">Set filter</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="files-table-container">
                        <table class="files-table download-history-table">
                            <thead>
                                <tr>
                                    <th class="checkbox-column">
                                        <input type="checkbox" id="download-select-all-checkbox" onchange="toggleDownloadSelectAll()">
                                    </th>
                                    <th>FILE NAME</th>
                                    <th>OWNER</th>
                                    <th>DATE UPLOADED</th>
                                    <th>CATEGORY</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody id="download-history-tab-tbody">
                                <!-- Download history will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="pagination-container">
                        <div class="pagination-controls">
                            <button class="pagination-btn" onclick="previousDownloadPage()" id="download-prev-btn" disabled>❮</button>
                            <span class="pagination-info">
                                <span id="download-current-page">1</span> of <span id="download-total-pages">9</span>
                            </span>
                            <button class="pagination-btn" onclick="nextDownloadPage()" id="download-next-btn">❯</button>
                        </div>
                    </div>
                </div>

                <!-- Document Repository Tab Content -->
                <div class="content-section" id="document-repository-content" style="display: none;">
                    <div class="document-repository-header">
                        <h2 class="section-title">Document Repository</h2>
                        
                        <div class="document-repository-actions">
                            <div class="selection-info">
                                <span id="repository-selected-count">0 selected files</span>
                                <button class="action-btn download-btn" id="repository-batch-download-btn" disabled onclick="repositoryBatchDownloadFiles()">
                                    <span class="btn-icon download-icon-btn"></span> Batch download
                                </button>
                                <button class="action-btn deselect-btn" id="repository-deselect-btn" disabled onclick="deselectAllRepositoryFiles()">
                                    <span class="btn-icon deselect-icon-btn"></span> Deselect
                                </button>
                            </div>
                            
                            <div class="filter-sort-buttons">
                                <div class="filter-container">
                                    <button class="filter-btn" onclick="toggleRepositoryFilter()">
                                        <span class="btn-icon filter-icon-btn"></span> Filter
                                    </button>
                                    
                                    <!-- Repository Filter Dropdown -->
                                    <div class="filter-dropdown" id="repository-filter-dropdown" style="display: none;">
                                        <div class="filter-section">
                                            <h4>Filter by category</h4>
                                            <div class="filter-checkboxes">
                                                <label class="filter-checkbox">
                                                    <input type="checkbox" value="council" checked onchange="updateRepositoryCategoryFilter()">
                                                    <span class="checkmark"></span>
                                                    Council
                                                </label>
                                                <label class="filter-checkbox">
                                                    <input type="checkbox" value="general" checked onchange="updateRepositoryCategoryFilter()">
                                                    <span class="checkmark"></span>
                                                    General
                                                </label>
                                                <label class="filter-checkbox">
                                                    <input type="checkbox" value="marketing" checked onchange="updateRepositoryCategoryFilter()">
                                                    <span class="checkmark"></span>
                                                    Marketing
                                                </label>
                                                <label class="filter-checkbox">
                                                    <input type="checkbox" value="membership" checked onchange="updateRepositoryCategoryFilter()">
                                                    <span class="checkmark"></span>
                                                    Membership
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="filter-section">
                                            <h4>Filter by Date</h4>
                                            <div class="date-range">
                                                <input type="text" class="date-input" placeholder="DD/MM/YY" id="repository-start-date">
                                                <span class="date-separator">–</span>
                                                <input type="text" class="date-input" placeholder="DD/MM/YY" id="repository-end-date">
                                            </div>
                                        </div>
                                        
                                        <div class="filter-section">
                                            <h4>Filter by file type</h4>
                                            <select class="file-type-select" id="repository-file-type-filter">
                                                <option value="">All file types</option>
                                                <option value="pdf" selected>PDF</option>
                                                <option value="doc">DOC</option>
                                                <option value="docx">DOCX</option>
                                                <option value="xls">XLS</option>
                                                <option value="xlsx">XLSX</option>
                                                <option value="ppt">PPT</option>
                                                <option value="pptx">PPTX</option>
                                                <option value="txt">TXT</option>
                                                <option value="jpg">JPG</option>
                                                <option value="png">PNG</option>
                                            </select>
                                        </div>
                                        
                                        <button class="set-filter-btn" onclick="applyRepositoryFilters()">Set filter</button>
                                    </div>
                                </div>
                                
                                <div class="sort-container">
                                    <button class="sort-btn" onclick="toggleRepositorySort()">
                                        <span class="btn-icon sort-icon-btn"></span> Sort
                                    </button>
                                    
                                    <!-- Sort Dropdown -->
                                    <div class="sort-dropdown" id="repository-sort-dropdown" style="display: none;">
                                        <div class="sort-option active" onclick="applySorting('repository', 'newest')">
                                            Newest
                                        </div>
                                        <div class="sort-option" onclick="applySorting('repository', 'oldest')">
                                            Oldest
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="files-table-container">
                        <table class="files-table document-repository-table">
                            <thead>
                                <tr>
                                    <th class="checkbox-column">
                                        <input type="checkbox" id="repository-select-all-checkbox" onchange="toggleRepositorySelectAll()">
                                    </th>
                                    <th>FILE NAME</th>
                                    <th>OWNER</th>
                                    <th>DATE UPLOADED</th>
                                    <th>CATEGORY</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody id="document-repository-tbody">
                                <!-- Repository files will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="pagination-container">
                        <div class="pagination-controls">
                            <button class="pagination-btn" onclick="previousRepositoryPage()" id="repository-prev-btn" disabled>❮</button>
                            <span class="pagination-info">
                                <span id="repository-current-page">1</span> of <span id="repository-total-pages">9</span>
                            </span>
                            <button class="pagination-btn" onclick="nextRepositoryPage()" id="repository-next-btn">❯</button>
                        </div>
                    </div>
                </div>

                <!-- FAQs Tab Content -->
                <div class="content-section" id="faqs-content" style="display: none;">
                    <h2 class="section-title">Frequently Asked Questions</h2>
                    <div class="faq-section">
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <span>How do I upload files to my dashboard?</span>
                                <span class="faq-arrow">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>You can upload files by clicking the "Upload new file" button in the sidebar. Select the files from your computer and they will be added to your personal file collection.</p>
                            </div>
                        </div>
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <span>What file types are supported?</span>
                                <span class="faq-arrow">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>We support common document formats including PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, and image formats like JPG, JPEG, and PNG.</p>
                            </div>
                        </div>
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <span>How can I organize my files?</span>
                                <span class="faq-arrow">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>Files are automatically categorized based on their type and content. You can also use the search function to quickly find specific files.</p>
                            </div>
                        </div>
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <span>Is there a file size limit?</span>
                                <span class="faq-arrow">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>The maximum file size limit is 50MB per file. For larger files, please contact our support team for assistance.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Tab Content -->
                <div class="content-section" id="contact-content" style="display: none;">
                    <h2 class="section-title">Contact Support</h2>
                    <div class="contact-grid">
                        <div class="contact-info">
                            <h3>Get in Touch</h3>
                            <p>Need help with your files or have questions about the dashboard? Our support team is here to assist you.</p>
                            
                            <div class="contact-methods">
                                <div class="contact-method">
                                    <strong>Email Support</strong>
                                    <p>support@iipm.edu</p>
                                </div>
                                <div class="contact-method">
                                    <strong>Phone Support</strong>
                                    <p>+1 (555) 123-4567</p>
                                </div>
                                <div class="contact-method">
                                    <strong>Office Hours</strong>
                                    <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-form-section">
                            <h3>Send a Message</h3>
                            <form class="dashboard-contact-form">
                                <div class="form-group">
                                    <label for="contact-subject">Subject</label>
                                    <input type="text" id="contact-subject" name="subject" required>
                                </div>
                                <div class="form-group">
                                    <label for="contact-message">Message</label>
                                    <textarea id="contact-message" name="message" rows="5" required></textarea>
                                </div>
                                <button type="submit" class="submit-btn">Send Message</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Bottom Action Cards (shown only in default view) -->
                <div class="bottom-cards" id="bottom-action-cards">
                    <div class="action-card faq-card" onclick="switchTab('faqs')">
                        <h3>Frequently Asked Questions</h3>
                        <p>See FAQs</p>
                        <span class="card-arrow">→</span>
                    </div>
                    <div class="action-card contact-card" onclick="switchTab('contact')">
                        <h3>Contact</h3>
                        <p>Reach out</p>
                        <span class="card-arrow">→</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div id="upload-modal" class="upload-modal" style="display: none;">
    <div class="upload-modal-content">
        <div class="upload-modal-header">
            <h2>Upload files</h2>
            <button class="close-modal" onclick="closeUploadModal()">&times;</button>
        </div>
        
        <div class="upload-drop-zone" id="upload-drop-zone">
            <div class="upload-icon-large">
                <span class="upload-arrow-icon"></span>
            </div>
            <p class="upload-text">Drag & drop your files or choose manually.</p>
            <p class="upload-limit">You can choose up to 5 files at a time, up to 50MB per file.</p>
            <input type="file" id="file-upload-input" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png" style="display: none;">
            <button class="choose-files-btn" onclick="triggerFileUpload()">Choose Files</button>
        </div>
        
        <div class="upload-files-list" id="upload-files-list" style="display: none;">
            <!-- Uploaded files will appear here -->
        </div>
        
        <div class="upload-modal-footer" id="upload-modal-footer" style="display: none;">
            <button class="finish-upload-btn" onclick="finishUpload()">Finish Upload</button>
        </div>
    </div>
</div>

<style>
/* Hero Section Styles */
.file-dashboard-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 160px 0 60px 0;
    position: relative;
    overflow: hidden;
}

.file-dashboard-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.1);
    z-index: 1;
}

.hero-content {
    position: relative;
    z-index: 2;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 24px;
}

.page-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 8px 0;
    text-align: left;
}

.page-subtitle {
    font-size: 1.1rem;
    margin: 0;
    color:white !important;
    text-align: left;
}

.user-welcome {
    text-align: right;
}

.user-welcome h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0 0 4px 0;
}

.designation {
    font-size: 1rem;
    margin: 0;
    opacity: 0.8;
    font-weight: 400;
}

/* File Dashboard Styles */
.file-dashboard-page {
    background: #f8fafc;
    min-height: 100vh;
    padding: 40px 0;
}

.file-dashboard-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 40px;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Sidebar Styles */
.file-sidebar {
    padding: 24px;
    height: fit-content;
    position: sticky;
    top: 40px;
}

.sidebar-upload {
    width: 100%;
    margin-bottom: 24px;
}

.dashboard-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #8b5cf6;
    margin: 0 0 32px 0;
}

.file-nav-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.2s;
    margin-bottom: 8px;
    font-size: 14px;
    color: #4b5563;
    font-weight: 500;
}

.nav-item:hover {
    background: #f3f4f6;
}

.nav-item.active {
    background: #ede9fe;
    color: #8b5cf6;
}

.nav-separator {
    height: 1px;
    background: #e5e7eb;
    margin: 16px 0;
    list-style: none;
}

.nav-icon {
    width: 16px;
    height: 16px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Simple Black and White Icons */
.folder-icon::before {
    content: '';
    width: 14px;
    height: 11px;
    border: 1.5px solid currentColor;
    border-radius: 1px;
    position: relative;
}

.folder-icon::after {
    content: '';
    position: absolute;
    top: 1px;
    left: 1px;
    width: 5px;
    height: 3px;
    border: 1.5px solid currentColor;
    border-bottom: none;
    border-radius: 1px 1px 0 0;
}

.download-icon::before {
    content: '';
    width: 2px;
    height: 10px;
    background: currentColor;
    position: relative;
}

.download-icon::after {
    content: '';
    position: absolute;
    bottom: 2px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 3px solid transparent;
    border-right: 3px solid transparent;
    border-top: 4px solid currentColor;
}

.document-icon::before {
    content: '';
    width: 10px;
    height: 13px;
    border: 1.5px solid currentColor;
    border-radius: 1px;
    position: relative;
}

.document-icon::after {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 6px;
    height: 1px;
    background: currentColor;
    box-shadow: 0 2px 0 currentColor, 0 4px 0 currentColor, 0 6px 0 currentColor;
}

.faq-icon::before {
    content: '';
    width: 12px;
    height: 12px;
    border: 1.5px solid currentColor;
    border-radius: 50%;
    position: relative;
}

.faq-icon::after {
    content: '?';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 8px;
    font-weight: bold;
    color: currentColor;
}

.contact-icon::before {
    content: '';
    width: 8px;
    height: 8px;
    border: 1.5px solid currentColor;
    border-radius: 50%;
    position: relative;
    margin-bottom: 1px;
}

.contact-icon::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 12px;
    height: 6px;
    border: 1.5px solid currentColor;
    border-radius: 6px 6px 0 0;
    border-bottom: none;
}

/* Upload Icon */
.simple-upload-icon {
    width: 16px;
    height: 16px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.simple-upload-icon::before {
    content: '';
    width: 2px;
    height: 8px;
    background: white;
    position: relative;
    z-index: 1;
    box-shadow: 0 9px 0 -1px white, -4px 9px 0 -1px white, 4px 9px 0 -1px white;
}

.simple-upload-icon::after {
    content: '';
    position: absolute;
    top: 1px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 3px solid transparent;
    border-right: 3px solid transparent;
    border-bottom: 4px solid white;
    z-index: 2;
}

/* Main Content Styles */
.file-main-content {
    background: white;
    border-radius: 12px;
    padding: 32px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Upload Button */
.upload-btn {
    background: #f97316;
    color: white;
    border: none;
    border-radius: 16px;
    padding: 16px 24px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background-color 0.2s;
    justify-content: center;
}

.upload-btn:hover {
    background: #ea580c;
}

/* Search Section */
.search-section {
    margin-bottom: 32px;
}

.search-container {
    display: flex;
    gap: 12px;
    align-items: center;
    width: 100%;
}

.search-input {
    flex: 1;
    padding: 16px 24px !important;
    border: 1px solid #e5e7eb;
    border-radius: 50px !important;
    font-size: 14px;
    background: #f9fafb;
    transition: all 0.2s;
    color: #6b7280;
    height: 52px;
    box-sizing: border-box;
}

.search-input:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    background: white;
}

.search-input::placeholder {
    color: #9ca3af;
}

.search-btn {
    background: #724491;
    color: white;
    border: none;
    border-radius: 20px;
    padding: 16px 32px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
    white-space: nowrap;
    height: 52px;
    box-sizing: border-box;
}

.search-btn:hover {
    background: #5a2d68;
}

/* Files Section */
.files-section {
    margin-bottom: 40px;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 24px 0;
}

.files-table-container {
    overflow-x: auto;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.files-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.files-table th {
    background: #f9fafb;
    color: #6b7280;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 16px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.files-table td {
    padding: 16px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
    color: #1f2937;
}

.files-table tbody tr:hover {
    background: #fafbfc;
}

.file-name {
    font-weight: 500;
    color: #1f2937;
}

.uploaded-by {
    color: #4b5563;
}

.upload-date {
    color: #6b7280;
    font-size: 13px;
}

.file-size {
    color: #6b7280;
    font-size: 14px;
}

.category-text {
    color: #374151;
    font-size: 14px;
    font-weight: 400;
    text-transform: capitalize;
}

.actions-menu {
    position: relative;
    display: inline-block;
}

.actions-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    border-radius: 4px;
    color: #6b7280;
    font-size: 16px;
    transition: background-color 0.2s;
}

.actions-btn:hover {
    background: #f3f4f6;
}

.actions-dropdown {
    position: absolute;
    right: 0;
    top: 100%;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    z-index: 10;
    min-width: 200px;
    display: none;
}

.actions-dropdown.show {
    display: block;
}

.dropdown-item {
    padding: 12px 16px;
    font-size: 14px;
    color: #374151;
    cursor: pointer;
    border-bottom: 1px solid #f1f5f9;
    transition: background-color 0.2s;
}

.dropdown-item:hover {
    background: #f9fafb;
}

.dropdown-item:last-child {
    border-bottom: none;
}

/* Updated Dropdown Styling */
.dropdown-header {
    padding: 12px 16px 8px 16px;
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    font-size: 14px;
    color: #374151;
    cursor: pointer;
    border-bottom: 1px solid #f1f5f9;
    transition: background-color 0.2s;
}

.dropdown-item:hover {
    background: #f9fafb;
}

.dropdown-item.download-highlighted {
    background: #e5e7eb;
}

.dropdown-item.download-highlighted:hover {
    background: #d1d5db;
}

.dropdown-item.delete-item {
    color: #dc2626;
}

.dropdown-item.delete-item:hover {
    background: #fef2f2;
}

/* Action Icons */
.action-icon {
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

/* Info Icon (i in circle) */
.info-icon::before {
    content: '';
    width: 16px;
    height: 16px;
    border: 2px solid currentColor;
    border-radius: 50%;
    position: relative;
}

.info-icon::after {
    content: 'i';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 10px;
    font-weight: bold;
    font-style: normal;
}

/* Eye Icon */
.eye-icon::before {
    content: '';
    width: 16px;
    height: 10px;
    border: 2px solid currentColor;
    border-radius: 16px 16px 0 0;
    position: relative;
}

.eye-icon::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 6px;
    height: 6px;
    background: currentColor;
    border-radius: 50%;
}

/* Download Icon */
.download-icon::before {
    content: '';
    width: 2px;
    height: 10px;
    background: currentColor;
    position: relative;
}

.download-icon::after {
    content: '';
    position: absolute;
    top: 8px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 3px solid transparent;
    border-right: 3px solid transparent;
    border-top: 4px solid currentColor;
}

/* Delete Icon */
.delete-icon::before {
    content: '';
    width: 12px;
    height: 2px;
    background: currentColor;
    position: relative;
    transform: rotate(45deg);
}

.delete-icon::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-45deg);
    width: 12px;
    height: 2px;
    background: currentColor;
}

/* Bottom Cards */
.bottom-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-top: 40px;
}

.action-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.action-card:hover {
    background: #f3f4f6;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.action-card h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.action-card p {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
}

.card-arrow {
    position: absolute;
    top: 24px;
    right: 24px;
    font-size: 18px;
    color: #8b5cf6;
}

/* Tab Functionality */
.nav-item {
    cursor: pointer;
    transition: all 0.2s;
}

.nav-item.active {
    background: rgba(114, 68, 145, 0.2);
    color: #724491;
    font-weight: 600;
}

.nav-item.active .nav-icon {
    color: #724491;
}

.content-section {
    min-height: 400px;
}

/* Repository Categories */
.repository-categories {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 24px;
    margin-top: 24px;
}

.category-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
    transition: all 0.2s;
    cursor: pointer;
}

.category-card:hover {
    background: #f3f4f6;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.category-card h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 8px 0;
}

.category-card p {
    font-size: 14px;
    color: #6b7280;
    margin: 0 0 16px 0;
    line-height: 1.5;
}

.file-count {
    display: inline-block;
    background: #8b5cf6;
    color: white;
    font-size: 12px;
    font-weight: 500;
    padding: 4px 8px;
    border-radius: 6px;
}

/* FAQ Section */
.faq-section {
    max-width: 800px;
}

.faq-item {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 16px;
    overflow: hidden;
}

.faq-question {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    background: #f9fafb;
    cursor: pointer;
    transition: background-color 0.2s;
    font-weight: 500;
    color: #1f2937;
}

.faq-question:hover {
    background: #f3f4f6;
}

.faq-arrow {
    font-size: 18px;
    font-weight: bold;
    color: #8b5cf6;
    transition: transform 0.2s;
}

.faq-question.active .faq-arrow {
    transform: rotate(45deg);
}

.faq-answer {
    padding: 0 24px;
    background: white;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out, padding 0.3s ease-out;
}

.faq-answer.active {
    max-height: 200px;
    padding: 20px 24px;
}

.faq-answer p {
    margin: 0;
    color: #6b7280;
    line-height: 1.6;
}

/* Contact Section */
.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-top: 24px;
}

.contact-info h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 16px 0;
}

.contact-info p {
    color: #6b7280;
    line-height: 1.6;
    margin: 0 0 24px 0;
}

.contact-methods {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.contact-method {
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.contact-method strong {
    display: block;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.contact-method p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

.contact-form-section h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 20px 0;
}

.dashboard-contact-form .form-group {
    margin-bottom: 20px;
}

.dashboard-contact-form label {
    display: block;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
}

.dashboard-contact-form input,
.dashboard-contact-form textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.dashboard-contact-form input:focus,
.dashboard-contact-form textarea:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.submit-btn {
    background: #8b5cf6;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
}

.submit-btn:hover {
    background: #7c3aed;
}

/* Upload Modal Styles */
.upload-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.upload-modal-content {
    background: white;
    border-radius: 12px;
    width: 100%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.upload-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px 24px 0 24px;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 24px;
}

.upload-modal-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #724491;
    margin: 0;
}

.close-modal {
    background: none;
    border: none;
    font-size: 24px;
    color: #6b7280;
    cursor: pointer;
    padding: 8px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.close-modal:hover {
    background: #f3f4f6;
}

.upload-drop-zone {
    margin: 0 24px 24px 24px;
    border: 2px dashed #d1d5db;
    border-radius: 12px;
    padding: 48px 24px;
    text-align: center;
    background: #fafbfc;
    transition: all 0.2s;
}

.upload-drop-zone.drag-over {
    border-color: #724491;
    background: #f3f4f6;
}

.upload-icon-large {
    margin-bottom: 16px;
}

.upload-arrow-icon {
    width: 48px;
    height: 48px;
    display: inline-block;
    position: relative;
    color: #9ca3af;
}

.upload-arrow-icon::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 4px;
    height: 24px;
    background: currentColor;
}

.upload-arrow-icon::after {
    content: '';
    position: absolute;
    top: 18px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-bottom: 8px solid currentColor;
}

.upload-text {
    font-size: 1.1rem;
    font-weight: 500;
    color: #374151;
    margin: 0 0 8px 0;
}

.upload-limit {
    font-size: 14px;
    color: #6b7280;
    margin: 0 0 24px 0;
}

.choose-files-btn {
    background: #724491;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
}

.choose-files-btn:hover {
    background: #5a2d68;
}

.upload-files-list {
    margin: 0 24px;
    max-height: 300px;
    overflow-y: auto;
}

.upload-file-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 12px;
    background: white;
}

.upload-file-info {
    flex: 1;
    min-width: 0;
}

.upload-file-name {
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 4px;
    word-break: break-all;
}

.upload-file-size {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 8px;
}

.upload-progress-bar {
    width: 100%;
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 8px;
}

.upload-progress-fill {
    height: 100%;
    background: #10b981;
    transition: width 0.3s ease;
}

.upload-progress-fill.uploading {
    background: #3b82f6;
}

.upload-file-status {
    font-size: 12px;
    font-weight: 500;
}

.upload-file-status.uploading {
    color: #3b82f6;
}

.upload-file-status.completed {
    color: #10b981;
}

.upload-file-status.error {
    color: #ef4444;
}

.upload-file-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.file-category-select {
    padding: 6px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 12px;
    background: white;
    color: #374151;
}

.remove-file-btn {
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 4px;
    width: 24px;
    height: 24px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transition: background-color 0.2s;
}

.remove-file-btn:hover {
    background: #dc2626;
}

.upload-modal-footer {
    padding: 24px;
    border-top: 1px solid #e5e7eb;
    text-align: center;
}

.finish-upload-btn {
    background: #724491;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 32px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
}

.finish-upload-btn:hover {
    background: #5a2d68;
}

.finish-upload-btn:disabled {
    background: #9ca3af;
    cursor: not-allowed;
}

/* My Files Section Styles */
.my-files-header,
.download-history-header,
.document-repository-header {
    margin-bottom: 24px;
}

.my-files-actions,
.download-history-actions,
.document-repository-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 16px;
    padding: 16px 0;
    border-top: 1px solid #e5e7eb;
}

.filter-sort-buttons {
    display: flex;
    gap: 12px;
}

.selection-info {
    display: flex;
    align-items: center;
    gap: 16px;
}

.selection-info span {
    font-weight: 500;
    color: #374151;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    background: transparent;
    color: #724491;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.action-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.action-btn:hover:not(:disabled) {
    background: rgba(114, 68, 145, 0.1);
    border-radius: 8px;
}

.filter-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    background: transparent;
    color: #724491;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.filter-btn:hover {
    background: rgba(114, 68, 145, 0.1);
    border-radius: 8px;
}

.sort-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    background: transparent;
    color: #724491;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.sort-btn:hover {
    background: rgba(114, 68, 145, 0.1);
    border-radius: 8px;
}

.btn-icon {
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

/* Delete Icon */
.delete-icon-btn::before {
    content: '';
    width: 12px;
    height: 14px;
    border: 1.5px solid currentColor;
    border-radius: 2px 2px 3px 3px;
    position: relative;
}

.delete-icon-btn::after {
    content: '';
    position: absolute;
    top: -2px;
    left: 50%;
    transform: translateX(-50%);
    width: 16px;
    height: 2px;
    background: currentColor;
    border-radius: 1px;
}

/* Download Icon */
.download-icon-btn::before {
    content: '';
    width: 2px;
    height: 10px;
    background: currentColor;
    position: relative;
}

.download-icon-btn::after {
    content: '';
    position: absolute;
    top: 8px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-top: 5px solid currentColor;
}

/* Deselect Icon */
.deselect-icon-btn::before {
    content: '';
    width: 12px;
    height: 2px;
    background: currentColor;
    position: absolute;
    transform: rotate(45deg);
}

.deselect-icon-btn::after {
    content: '';
    width: 12px;
    height: 2px;
    background: currentColor;
    position: absolute;
    transform: rotate(-45deg);
}

/* Filter Icon */
.filter-icon-btn::before {
    content: '';
    width: 14px;
    height: 2px;
    background: currentColor;
    position: absolute;
    top: 3px;
    box-shadow: 0 4px 0 currentColor, 0 8px 0 currentColor;
}

.filter-icon-btn::after {
    content: '';
    position: absolute;
    top: 3px;
    right: 1px;
    width: 0;
    height: 0;
    border-left: 6px solid currentColor;
    border-top: 3px solid transparent;
    border-bottom: 3px solid transparent;
}

/* Sort Icon */
.sort-icon-btn::before {
    content: '';
    position: absolute;
    top: 2px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-bottom: 5px solid currentColor;
}

.sort-icon-btn::after {
    content: '';
    position: absolute;
    bottom: 2px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-top: 5px solid currentColor;
}

.checkbox-column {
    width: 40px;
    text-align: center;
}

.my-files-table .checkbox-column input[type="checkbox"],
.download-history-table .checkbox-column input[type="checkbox"],
.document-repository-table .checkbox-column input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.pagination-container {
    display: flex;
    justify-content: center;
    margin-top: 32px;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 16px;
}

.pagination-btn {
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #374151;
    transition: all 0.2s;
}

.pagination-btn:hover:not(:disabled) {
    background: #f3f4f6;
    border-color: #9ca3af;
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination-info {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
}

/* Filter Dropdown Styles */
.filter-container {
    position: relative;
    display: inline-block;
}

.filter-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    z-index: 100;
    width: 300px;
    padding: 20px;
    margin-top: 8px;
    box-sizing: border-box;
}

.filter-section {
    margin-bottom: 24px;
}

.filter-section:last-child {
    margin-bottom: 0;
}

.filter-section h4 {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 16px 0;
}

.filter-checkboxes {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.filter-checkbox {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    font-size: 14px;
    color: #374151;
    position: relative;
}

.filter-checkbox input[type="checkbox"] {
    display: none;
}

.checkmark {
    width: 20px;
    height: 20px;
    border: 2px solid #d1d5db;
    border-radius: 4px;
    position: relative;
    transition: all 0.2s;
}

.filter-checkbox input[type="checkbox"]:checked + .checkmark {
    background: #10b981;
    border-color: #10b981;
}

.filter-checkbox input[type="checkbox"]:checked + .checkmark::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 6px;
    width: 4px;
    height: 8px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.date-range {
    display: flex;
    align-items: center;
    gap: 8px;
}

.date-input {
    flex: 1;
    min-width: 0;
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 13px;
    color: #6b7280;
    background: white;
    box-sizing: border-box;
}

.date-input:focus {
    outline: none;
    border-color: #724491;
    box-shadow: 0 0 0 3px rgba(114, 68, 145, 0.1);
}

.date-separator {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
    flex-shrink: 0;
    min-width: 12px;
    text-align: center;
}

.file-type-select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    color: #374151;
    background: white;
    cursor: pointer;
}

.file-type-select:focus {
    outline: none;
    border-color: #724491;
    box-shadow: 0 0 0 3px rgba(114, 68, 145, 0.1);
}

.set-filter-btn {
    width: 100%;
    background: #724491;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
    margin-top: 24px;
}

.set-filter-btn:hover {
    background: #5a2d68;
}

/* Sort Dropdown Styles */
.sort-container {
    position: relative;
    display: inline-block;
}

.sort-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 100;
    min-width: 120px;
    overflow: hidden;
    margin-top: 4px;
}

.sort-option {
    padding: 12px 16px;
    font-size: 14px;
    color: #374151;
    cursor: pointer;
    transition: background-color 0.2s;
    border-bottom: 1px solid #f3f4f6;
}

.sort-option:last-child {
    border-bottom: none;
}

.sort-option:hover {
    background: #f3f4f6;
}

.sort-option.active {
    background: #f3f4f6;
    color: #724491;
    font-weight: 600;
}

/* Responsive Design */
@media (max-width: 768px) {
    .file-dashboard-hero {
        padding: 60px 0 40px 0;
    }
    
    .hero-content {
        flex-direction: column;
        text-align: center;
        gap: 16px;
    }
    
    .page-header h1 {
        font-size: 2rem;
        text-align: center;
    }
    
    .page-subtitle {
        text-align: center;
    }
    
    .user-welcome {
        text-align: center;
    }
    
    .user-welcome h2 {
        font-size: 1.25rem;
    }
    
    .file-dashboard-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .file-sidebar {
        position: static;
        padding: 20px;
    }
    
    .sidebar-upload {
        margin-bottom: 20px;
    }
    
    .bottom-cards {
        grid-template-columns: 1fr;
    }
    
    .files-table-container {
        font-size: 12px;
    }
    
    .files-table th,
    .files-table td {
        padding: 12px 8px;
    }
    
    .my-files-actions,
    .download-history-actions,
    .document-repository-actions {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
    }
    
    .filter-sort-buttons {
        justify-content: center;
        gap: 8px;
    }
    
    .filter-dropdown {
        width: 260px;
        left: 0;
        right: auto;
        padding: 16px;
    }
    
    .sort-dropdown {
        left: 0;
        right: auto;
        min-width: 100px;
    }
    
    .date-range {
        flex-direction: column;
        gap: 8px;
    }
    
    .date-separator {
        align-self: center;
        transform: rotate(90deg);
        font-size: 12px;
    }
    
    .selection-info {
        flex-wrap: wrap;
        gap: 8px;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .file-dashboard-hero {
        padding: 40px 0 30px 0;
    }
    
    .page-header h1 {
        font-size: 1.75rem;
    }
    
    .page-subtitle {
        font-size: 1rem;
    }
    
    .user-welcome h2 {
        font-size: 1.1rem;
    }
    
    .designation {
        font-size: 0.9rem;
    }
    
    .file-main-content {
        padding: 20px;
    }
    
    .file-sidebar {
        padding: 16px;
    }
    
    .sidebar-upload {
        margin-bottom: 16px;
        padding: 10px 16px;
        font-size: 13px;
    }
    
    .search-section {
        margin-bottom: 24px;
    }
    
    .nav-item {
        padding: 12px;
        font-size: 13px;
    }
    
    .repository-categories {
        grid-template-columns: 1fr;
    }
    
    .contact-grid {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    
    .category-card {
        padding: 20px;
    }
    
    .search-container {
        flex-direction: column;
        gap: 16px;
    }
    
    .search-btn {
        width: 100%;
        padding: 16px;
    }
    
    .upload-modal-content {
        margin: 10px;
        max-height: 90vh;
    }
    
    .upload-modal-header {
        padding: 20px 20px 0 20px;
    }
    
    .upload-drop-zone {
        margin: 0 20px 20px 20px;
        padding: 32px 16px;
    }
    
    .upload-files-list {
        margin: 0 20px;
    }
    
    .upload-file-item {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    
    .upload-file-actions {
        justify-content: space-between;
    }
    
    .my-files-actions,
    .download-history-actions,
    .document-repository-actions {
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
    }
    
    .filter-sort-buttons {
        justify-content: center;
    }
    
    .selection-info {
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .action-btn {
        font-size: 12px;
        padding: 6px 12px;
    }
    
    .pagination-controls {
        gap: 12px;
    }
}
</style>

<script>
// WordPress AJAX URL
const ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';

// Sample file data - in real implementation, this would come from database
const sampleFiles = [
    { id: 1, name: 'Council Meeting Notes Dec', uploadedBy: 'David Spolan', date: '2024-12-15', category: 'council' },
    { id: 2, name: 'Q4 Financial Report', uploadedBy: 'David Spolan', date: '2024-12-10', category: 'council' },
    { id: 3, name: 'Council Policy Update', uploadedBy: 'Ian Jeffery', date: '2024-12-05', category: 'council' },
    { id: 4, name: 'Marketing Campaign Q1', uploadedBy: 'Elma Fox', date: '2024-11-28', category: 'marketing' },
    { id: 5, name: 'Membership Guidelines', uploadedBy: 'Elma Fox', date: '2024-11-20', category: 'membership' },
    { id: 6, name: 'Member Handbook 2025', uploadedBy: 'Maria Conway', date: '2024-11-15', category: 'membership' },
    { id: 7, name: 'Brand Guidelines Update', uploadedBy: 'Davy Maven', date: '2024-11-08', category: 'marketing' },
    { id: 8, name: 'General Assembly Minutes', uploadedBy: 'Elma Fox', date: '2024-10-30', category: 'general' },
    { id: 9, name: 'Safety Protocols', uploadedBy: 'Maria Conway', date: '2024-10-22', category: 'general' },
    { id: 10, name: 'Training Manual', uploadedBy: 'Ian Jeffery', date: '2024-10-14', category: 'general' },
    { id: 11, name: 'Budget Proposal 2025', uploadedBy: 'David Spolan', date: '2024-10-08', category: 'council' },
    { id: 12, name: 'Social Media Strategy', uploadedBy: 'Elma Fox', date: '2024-09-28', category: 'marketing' },
    { id: 13, name: 'Membership Report Sept', uploadedBy: 'Maria Conway', date: '2024-09-15', category: 'membership' },
    { id: 14, name: 'Event Planning Guide', uploadedBy: 'Ian Jeffery', date: '2024-09-01', category: 'general' },
    { id: 15, name: 'Quarterly Newsletter', uploadedBy: 'Davy Maven', date: '2024-08-20', category: 'marketing' }
];

const sampleDownloadHistory = [
    { id: 1, name: 'Council File 1', uploadedBy: 'David Spolan', date: '2024-12-08', category: 'council' },
    { id: 2, name: 'Council File 2', uploadedBy: 'David Spolan', date: '2024-11-06', category: 'council' },
    { id: 3, name: 'Council File 3', uploadedBy: 'Ian Jeffery', date: '2024-10-15', category: 'council' },
    { id: 4, name: 'Marketing File 1', uploadedBy: 'Elma Fox', date: '2024-10-04', category: 'marketing' },
    { id: 5, name: 'Membership File 1', uploadedBy: 'Elma Fox', date: '2024-10-04', category: 'membership' },
    { id: 6, name: 'Membership File 2', uploadedBy: 'Maria Conway', date: '2024-09-25', category: 'membership' },
    { id: 7, name: 'Marketing File 1', uploadedBy: 'Davy Maven', date: '2024-09-22', category: 'marketing' },
    { id: 8, name: 'General File 1', uploadedBy: 'Elma Fox', date: '2024-09-21', category: 'general' },
    { id: 9, name: 'General File 2', uploadedBy: 'Maria Conway', date: '2024-06-21', category: 'general' },
    { id: 10, name: 'General File 3', uploadedBy: 'Ian Jeffery', date: '2024-05-16', category: 'general' }
];

// Load files into table
function loadFilesTable(containerId, files) {
    const tbody = document.getElementById(containerId);
    if (!tbody) return;
    
    tbody.innerHTML = files.map(file => `
        <tr>
            <td><span class="file-name">${file.name}</span></td>
            <td><span class="uploaded-by">${file.uploadedBy}</span></td>
            <td><span class="upload-date">${file.date}</span></td>
            <td><span class="category-text">${file.category.charAt(0).toUpperCase() + file.category.slice(1)}</span></td>
            <td>
                <div class="actions-menu">
                    <button class="actions-btn" onclick="toggleActionsMenu(${file.id})">⋯</button>
                    <div class="actions-dropdown" id="actions-${file.id}">
                        <div class="dropdown-header">${file.category.charAt(0).toUpperCase() + file.category.slice(1)}</div>
                        <div class="dropdown-item" onclick="viewFile(${file.id})">
                            <span class="action-icon info-icon"></span>
                            <span>See Details</span>
                        </div>
                        <div class="dropdown-item" onclick="previewFile(${file.id})">
                            <span class="action-icon eye-icon"></span>
                            <span>Preview</span>
                        </div>
                        <div class="dropdown-item download-highlighted" onclick="downloadFile(${file.id})">
                            <span class="action-icon download-icon"></span>
                            <span>Download</span>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    `).join('');
}

// Toggle actions dropdown
function toggleActionsMenu(fileId) {
    const dropdown = document.getElementById(`actions-${fileId}`);
    const allDropdowns = document.querySelectorAll('.actions-dropdown');
    
    // Close all other dropdowns
    allDropdowns.forEach(d => {
        if (d.id !== `actions-${fileId}`) {
            d.classList.remove('show');
        }
    });
    
    // Toggle current dropdown
    dropdown.classList.toggle('show');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.actions-menu')) {
        document.querySelectorAll('.actions-dropdown').forEach(d => d.classList.remove('show'));
    }
});

// File actions
function downloadFile(fileId) {
    console.log('Downloading file:', fileId);
    // Implement download functionality
    if (window.notifications) {
        window.notifications.success('Download Started', 'File download has started.');
    }
    closeActionsMenu(fileId);
}

function viewFile(fileId) {
    console.log('Viewing file details:', fileId);
    // Implement file details functionality
    if (window.notifications) {
        window.notifications.info('File Details', 'Opening file details and information.');
    }
    closeActionsMenu(fileId);
}

function previewFile(fileId) {
    console.log('Previewing file:', fileId);
    // Implement file preview functionality
    if (window.notifications) {
        window.notifications.info('File Preview', 'Opening file preview.');
    }
    closeActionsMenu(fileId);
}

function shareFile(fileId) {
    console.log('Sharing file:', fileId);
    // Implement share functionality
    if (window.notifications) {
        window.notifications.info('Share Link', 'Share link has been copied to clipboard.');
    }
    closeActionsMenu(fileId);
}

function deleteFile(fileId) {
    if (confirm('Are you sure you want to delete this file?')) {
        console.log('Deleting file:', fileId);
        // Implement delete functionality
        if (window.notifications) {
            window.notifications.success('File Deleted', 'File has been successfully deleted.');
        }
    }
    closeActionsMenu(fileId);
}

function closeActionsMenu(fileId) {
    document.getElementById(`actions-${fileId}`).classList.remove('show');
}

// Upload Modal Variables
let uploadedFiles = [];
let fileCounter = 1;

// Upload Modal Functions
function openUploadModal() {
    document.getElementById('upload-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    setupDragAndDrop();
}

function closeUploadModal() {
    document.getElementById('upload-modal').style.display = 'none';
    document.body.style.overflow = 'auto';
    uploadedFiles = [];
    updateUploadDisplay();
}

function triggerFileUpload() {
    document.getElementById('file-upload-input').click();
}

function setupDragAndDrop() {
    const dropZone = document.getElementById('upload-drop-zone');
    
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropZone.classList.add('drag-over');
    });
    
    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
    });
    
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        
        const files = Array.from(e.dataTransfer.files);
        handleFileSelection(files);
    });
}

function handleFileSelection(files) {
    // Check file limit
    if (uploadedFiles.length + files.length > 5) {
        if (window.notifications) {
            window.notifications.error('Upload Limit', 'You can only upload up to 5 files at a time.');
        }
        return;
    }
    
    // Process each file
    files.forEach(file => {
        // Check file size (50MB limit)
        if (file.size > 50 * 1024 * 1024) {
            if (window.notifications) {
                window.notifications.error('File Too Large', `${file.name} exceeds the 50MB limit.`);
            }
            return;
        }
        
        // Create file object
        const fileObj = {
            id: fileCounter++,
            file: file,
            name: file.name,
            size: file.size,
            progress: 0,
            status: 'uploading',
            category: 'general'
        };
        
        uploadedFiles.push(fileObj);
        simulateUpload(fileObj);
    });
    
    updateUploadDisplay();
}

function simulateUpload(fileObj) {
    const uploadInterval = setInterval(() => {
        fileObj.progress += Math.random() * 20;
        
        if (fileObj.progress >= 100) {
            fileObj.progress = 100;
            fileObj.status = 'completed';
            clearInterval(uploadInterval);
        }
        
        updateUploadDisplay();
    }, 200);
}

function updateUploadDisplay() {
    const filesList = document.getElementById('upload-files-list');
    const footer = document.getElementById('upload-modal-footer');
    
    if (uploadedFiles.length === 0) {
        filesList.style.display = 'none';
        footer.style.display = 'none';
        return;
    }
    
    filesList.style.display = 'block';
    footer.style.display = 'block';
    
    filesList.innerHTML = uploadedFiles.map(fileObj => `
        <div class="upload-file-item">
            <div class="upload-file-info">
                <div class="upload-file-name">${fileObj.name}</div>
                <div class="upload-file-size">${formatFileSize(fileObj.progress === 100 ? fileObj.size : (fileObj.size * fileObj.progress / 100))} of ${formatFileSize(fileObj.size)}</div>
                <div class="upload-progress-bar">
                    <div class="upload-progress-fill ${fileObj.status}" style="width: ${fileObj.progress}%"></div>
                </div>
                <div class="upload-file-status ${fileObj.status}">
                    ${fileObj.status === 'uploading' ? 'Uploading...' : 'Upload Completed'}
                </div>
            </div>
            <div class="upload-file-actions">
                ${fileObj.status === 'completed' ? `
                    <select class="file-category-select" onchange="updateFileCategory(${fileObj.id}, this.value)" value="${fileObj.category}">
                        <option value="general" ${fileObj.category === 'general' ? 'selected' : ''}>General</option>
                        <option value="council" ${fileObj.category === 'council' ? 'selected' : ''}>Council</option>
                        <option value="marketing" ${fileObj.category === 'marketing' ? 'selected' : ''}>Marketing</option>
                        <option value="membership" ${fileObj.category === 'membership' ? 'selected' : ''}>Membership</option>
                    </select>
                ` : ''}
                <button class="remove-file-btn" onclick="removeUploadFile(${fileObj.id})" title="Remove file">×</button>
            </div>
        </div>
    `).join('');
    
    // Update finish button state
    const finishBtn = document.querySelector('.finish-upload-btn');
    const allCompleted = uploadedFiles.every(f => f.status === 'completed');
    finishBtn.disabled = !allCompleted || uploadedFiles.length === 0;
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function updateFileCategory(fileId, category) {
    const fileObj = uploadedFiles.find(f => f.id === fileId);
    if (fileObj) {
        fileObj.category = category;
    }
}

function removeUploadFile(fileId) {
    uploadedFiles = uploadedFiles.filter(f => f.id !== fileId);
    updateUploadDisplay();
}

function finishUpload() {
    const completedFiles = uploadedFiles.filter(f => f.status === 'completed');
    
    if (completedFiles.length === 0) {
        return;
    }
    
    // Add files to the sample data (simulate real upload)
    completedFiles.forEach(fileObj => {
        const newFile = {
            id: Date.now() + Math.random(),
            name: fileObj.name.replace(/\.[^/.]+$/, ''),
            uploadedBy: 'Current User',
            date: new Date().toISOString().split('T')[0],
            category: fileObj.category
        };
        sampleFiles.unshift(newFile);
    });
    
    // Show success notification
    if (window.notifications) {
        window.notifications.success('Upload Complete', `Successfully uploaded ${completedFiles.length} file(s).`);
    }
    
    // Refresh the current view
    loadFilesTable('recent-files-tbody', sampleFiles);
    
    // Close modal
    closeUploadModal();
}

// File input change handler
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('file-upload-input').addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        if (files.length > 0) {
            handleFileSelection(files);
        }
        // Reset the input
        e.target.value = '';
    });
});

// Handle search button click
function performSearch() {
    const searchInput = document.getElementById('file-search');
    const searchTerm = searchInput.value.toLowerCase().trim();
    
    if (searchTerm === '') {
        // If search is empty, show all files
        loadFilesTable('recent-files-tbody', sampleFiles);
        if (window.notifications) {
            window.notifications.info('Search', 'Showing all files.');
        }
    } else {
        // Filter files based on search term
        const filteredFiles = sampleFiles.filter(file => 
            file.name.toLowerCase().includes(searchTerm) ||
            file.uploadedBy.toLowerCase().includes(searchTerm) ||
            file.category.toLowerCase().includes(searchTerm)
        );
        
        loadFilesTable('recent-files-tbody', filteredFiles);
        
        if (window.notifications) {
            window.notifications.success('Search Results', `Found ${filteredFiles.length} file(s) matching "${searchTerm}".`);
        }
    }
}

// Handle Enter key press in search input
document.getElementById('file-search').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        performSearch();
    }
});

// Tab Switching Functionality
function switchTab(tabName) {
    // Remove active class from all nav items
    document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
    
    // Add active class to clicked nav item
    const activeNavItem = document.querySelector(`[data-section="${tabName}"]`);
    if (activeNavItem) {
        activeNavItem.classList.add('active');
    }
    
    // Clear selected files when switching tabs
    selectedFiles.clear();
    selectedDownloadFiles.clear();
    selectedRepositoryFiles.clear();
    
    // Hide all content sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.style.display = 'none';
    });
    
    // Show default content (Recent Files and Download History) or specific tab content
    switch(tabName) {
        case 'my-files':
            document.getElementById('my-files-content').style.display = 'block';
            document.getElementById('bottom-action-cards').style.display = 'none';
            loadMyFilesTable();
            break;
            
        case 'download-history':
            document.getElementById('download-history-content').style.display = 'block';
            document.getElementById('bottom-action-cards').style.display = 'none';
            loadDownloadHistoryTable();
            break;
            
        case 'document-repository':
            document.getElementById('document-repository-content').style.display = 'block';
            document.getElementById('bottom-action-cards').style.display = 'none';
            loadDocumentRepositoryTable();
            break;
            
        case 'faqs':
            document.getElementById('faqs-content').style.display = 'block';
            document.getElementById('bottom-action-cards').style.display = 'none';
            break;
            
        case 'contact':
            document.getElementById('contact-content').style.display = 'block';
            document.getElementById('bottom-action-cards').style.display = 'none';
            break;
            
        default:
            showDefaultView();
            break;
    }
}

// Show default view (Recent Files + Download History + Bottom Cards)
function showDefaultView() {
    document.getElementById('default-view').style.display = 'block';
    document.getElementById('bottom-action-cards').style.display = 'grid';
    
    // Clear any selected files when leaving My Files
    selectedFiles.clear();
    selectedDownloadFiles.clear();
    selectedRepositoryFiles.clear();
    
    // Remove active class from all nav items when showing default view
    document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
}

// My Files variables
let selectedFiles = new Set();
let currentPage = 1;
let filesPerPage = 10;

// Download History variables
let selectedDownloadFiles = new Set();
let downloadCurrentPage = 1;
let downloadFilesPerPage = 10;

// Document Repository variables
let selectedRepositoryFiles = new Set();
let repositoryCurrentPage = 1;
let repositoryFilesPerPage = 10;

// Load My Files table
function loadMyFilesTable() {
    const tbody = document.getElementById('my-files-tbody');
    if (!tbody) return;
    
    // Calculate pagination
    const startIndex = (currentPage - 1) * filesPerPage;
    const endIndex = startIndex + filesPerPage;
    const paginatedFiles = sampleFiles.slice(startIndex, endIndex);
    
    tbody.innerHTML = paginatedFiles.map(file => `
        <tr>
            <td class="checkbox-column">
                <input type="checkbox" 
                       value="${file.id}" 
                       onchange="toggleFileSelection(${file.id})"
                       ${selectedFiles.has(file.id) ? 'checked' : ''}>
            </td>
            <td><span class="file-name">${file.name}</span></td>
            <td><span class="uploaded-by">${file.uploadedBy}</span></td>
            <td><span class="upload-date">${file.date}</span></td>
            <td><span class="category-text">${file.category.charAt(0).toUpperCase() + file.category.slice(1)}</span></td>
            <td>
                <div class="actions-menu">
                    <button class="actions-btn" onclick="toggleActionsMenu(${file.id})">⋯</button>
                    <div class="actions-dropdown" id="actions-${file.id}">
                        <div class="dropdown-item" onclick="viewFile(${file.id})">
                            <span class="action-icon info-icon"></span>
                            <span>See Details</span>
                        </div>
                        <div class="dropdown-item" onclick="previewFile(${file.id})">
                            <span class="action-icon eye-icon"></span>
                            <span>Preview</span>
                        </div>
                        <div class="dropdown-item download-highlighted" onclick="downloadFile(${file.id})">
                            <span class="action-icon download-icon"></span>
                            <span>Download</span>
                        </div>
                        <div class="dropdown-item delete-item" onclick="deleteFile(${file.id})">
                            <span class="action-icon delete-icon"></span>
                            <span>Delete</span>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    `).join('');
    
    updateSelectionUI();
    updatePagination();
}

// File selection functions
function toggleFileSelection(fileId) {
    if (selectedFiles.has(fileId)) {
        selectedFiles.delete(fileId);
    } else {
        selectedFiles.add(fileId);
    }
    updateSelectionUI();
}

function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const allCheckboxes = document.querySelectorAll('#my-files-tbody input[type="checkbox"]');
    
    if (selectAllCheckbox.checked) {
        allCheckboxes.forEach(checkbox => {
            selectedFiles.add(parseInt(checkbox.value));
            checkbox.checked = true;
        });
    } else {
        selectedFiles.clear();
        allCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
    }
    updateSelectionUI();
}

function updateSelectionUI() {
    const selectedCount = selectedFiles.size;
    const selectedCountEl = document.getElementById('selected-count');
    const deleteBtn = document.getElementById('delete-selected-btn');
    const downloadBtn = document.getElementById('download-selected-btn');
    const deselectBtn = document.getElementById('deselect-btn');
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    
    selectedCountEl.textContent = `${selectedCount} selected file${selectedCount !== 1 ? 's' : ''}`;
    
    deleteBtn.disabled = selectedCount === 0;
    downloadBtn.disabled = selectedCount === 0;
    deselectBtn.disabled = selectedCount === 0;
    
    // Update select all checkbox state
    const allVisible = document.querySelectorAll('#my-files-tbody input[type="checkbox"]').length;
    const selectedVisible = document.querySelectorAll('#my-files-tbody input[type="checkbox"]:checked').length;
    
    if (selectedVisible === 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    } else if (selectedVisible === allVisible) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
    }
}

// Batch operations
function deleteSelectedFiles() {
    if (selectedFiles.size === 0) return;
    
    if (confirm(`Are you sure you want to delete ${selectedFiles.size} selected file(s)?`)) {
        selectedFiles.forEach(fileId => {
            const index = sampleFiles.findIndex(f => f.id === fileId);
            if (index > -1) {
                sampleFiles.splice(index, 1);
            }
        });
        
        selectedFiles.clear();
        loadMyFilesTable();
        
        if (window.notifications) {
            window.notifications.success('Files Deleted', 'Selected files have been deleted.');
        }
    }
}

function downloadSelectedFiles() {
    if (selectedFiles.size === 0) return;
    
    if (window.notifications) {
        window.notifications.success('Download Started', `Downloading ${selectedFiles.size} selected file(s).`);
    }
}

function deselectAllFiles() {
    selectedFiles.clear();
    loadMyFilesTable();
}

function toggleFilter() {
    const dropdown = document.getElementById('filter-dropdown');
    const isVisible = dropdown.style.display === 'block';
    
    // Close all filter dropdowns first
    document.querySelectorAll('.filter-dropdown').forEach(d => d.style.display = 'none');
    
    // Toggle the current dropdown
    dropdown.style.display = isVisible ? 'none' : 'block';
}

// Pagination functions
function updatePagination() {
    const totalPages = Math.ceil(sampleFiles.length / filesPerPage);
    document.getElementById('current-page').textContent = currentPage;
    document.getElementById('total-pages').textContent = totalPages;
    
    document.getElementById('prev-btn').disabled = currentPage === 1;
    document.getElementById('next-btn').disabled = currentPage === totalPages;
}

function previousPage() {
    if (currentPage > 1) {
        currentPage--;
        loadMyFilesTable();
    }
}

function nextPage() {
    const totalPages = Math.ceil(sampleFiles.length / filesPerPage);
    if (currentPage < totalPages) {
        currentPage++;
        loadMyFilesTable();
    }
}

// Download History Functions
function toggleDownloadFileSelection(fileId) {
    if (selectedDownloadFiles.has(fileId)) {
        selectedDownloadFiles.delete(fileId);
    } else {
        selectedDownloadFiles.add(fileId);
    }
    updateDownloadSelectionUI();
}

function toggleDownloadSelectAll() {
    const selectAllCheckbox = document.getElementById('download-select-all-checkbox');
    const allCheckboxes = document.querySelectorAll('#download-history-tab-tbody input[type="checkbox"]');
    
    if (selectAllCheckbox.checked) {
        allCheckboxes.forEach(checkbox => {
            selectedDownloadFiles.add(parseInt(checkbox.value));
            checkbox.checked = true;
        });
    } else {
        selectedDownloadFiles.clear();
        allCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
    }
    updateDownloadSelectionUI();
}

function updateDownloadSelectionUI() {
    const selectedCount = selectedDownloadFiles.size;
    const selectedCountEl = document.getElementById('download-selected-count');
    const downloadBtn = document.getElementById('batch-download-btn');
    const deselectBtn = document.getElementById('download-deselect-btn');
    const selectAllCheckbox = document.getElementById('download-select-all-checkbox');
    
    selectedCountEl.textContent = `${selectedCount} selected file${selectedCount !== 1 ? 's' : ''}`;
    
    downloadBtn.disabled = selectedCount === 0;
    deselectBtn.disabled = selectedCount === 0;
    
    // Update select all checkbox state
    const allVisible = document.querySelectorAll('#download-history-tab-tbody input[type="checkbox"]').length;
    const selectedVisible = document.querySelectorAll('#download-history-tab-tbody input[type="checkbox"]:checked').length;
    
    if (selectedVisible === 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    } else if (selectedVisible === allVisible) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
    }
}

function batchDownloadFiles() {
    if (selectedDownloadFiles.size === 0) return;
    
    if (window.notifications) {
        window.notifications.success('Download Started', `Downloading ${selectedDownloadFiles.size} selected file(s) from download history.`);
    }
}

function deselectAllDownloadFiles() {
    selectedDownloadFiles.clear();
    loadDownloadHistoryTable();
}

function toggleDownloadFilter() {
    const dropdown = document.getElementById('download-filter-dropdown');
    const isVisible = dropdown.style.display === 'block';
    
    // Close all filter dropdowns first
    document.querySelectorAll('.filter-dropdown').forEach(d => d.style.display = 'none');
    
    // Toggle the current dropdown
    dropdown.style.display = isVisible ? 'none' : 'block';
}

// Download History Pagination
function updateDownloadPagination() {
    const totalPages = Math.ceil(sampleDownloadHistory.length / downloadFilesPerPage);
    document.getElementById('download-current-page').textContent = downloadCurrentPage;
    document.getElementById('download-total-pages').textContent = totalPages;
    
    document.getElementById('download-prev-btn').disabled = downloadCurrentPage === 1;
    document.getElementById('download-next-btn').disabled = downloadCurrentPage === totalPages;
}

function previousDownloadPage() {
    if (downloadCurrentPage > 1) {
        downloadCurrentPage--;
        loadDownloadHistoryTable();
    }
}

function nextDownloadPage() {
    const totalPages = Math.ceil(sampleDownloadHistory.length / downloadFilesPerPage);
    if (downloadCurrentPage < totalPages) {
        downloadCurrentPage++;
        loadDownloadHistoryTable();
    }
}

// Document Repository Functions
function loadDocumentRepositoryTable() {
    const tbody = document.getElementById('document-repository-tbody');
    if (!tbody) return;
    
    // Calculate pagination
    const startIndex = (repositoryCurrentPage - 1) * repositoryFilesPerPage;
    const endIndex = startIndex + repositoryFilesPerPage;
    const paginatedFiles = sampleFiles.slice(startIndex, endIndex);
    
    tbody.innerHTML = paginatedFiles.map(file => `
        <tr>
            <td class="checkbox-column">
                <input type="checkbox" 
                       value="${file.id}" 
                       onchange="toggleRepositoryFileSelection(${file.id})"
                       ${selectedRepositoryFiles.has(file.id) ? 'checked' : ''}>
            </td>
            <td><span class="file-name">${file.name}</span></td>
            <td><span class="uploaded-by">${file.uploadedBy}</span></td>
            <td><span class="upload-date">${file.date}</span></td>
            <td><span class="category-text">${file.category.charAt(0).toUpperCase() + file.category.slice(1)}</span></td>
            <td>
                <div class="actions-menu">
                    <button class="actions-btn" onclick="toggleActionsMenu(${file.id})">⋯</button>
                    <div class="actions-dropdown" id="actions-${file.id}">
                        <div class="dropdown-item" onclick="viewFile(${file.id})">
                            <span class="action-icon info-icon"></span>
                            <span>See Details</span>
                        </div>
                        <div class="dropdown-item" onclick="previewFile(${file.id})">
                            <span class="action-icon eye-icon"></span>
                            <span>Preview</span>
                        </div>
                        <div class="dropdown-item download-highlighted" onclick="downloadFile(${file.id})">
                            <span class="action-icon download-icon"></span>
                            <span>Download</span>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    `).join('');
    
    updateRepositorySelectionUI();
    updateRepositoryPagination();
}

function toggleRepositoryFileSelection(fileId) {
    if (selectedRepositoryFiles.has(fileId)) {
        selectedRepositoryFiles.delete(fileId);
    } else {
        selectedRepositoryFiles.add(fileId);
    }
    updateRepositorySelectionUI();
}

function toggleRepositorySelectAll() {
    const selectAllCheckbox = document.getElementById('repository-select-all-checkbox');
    const allCheckboxes = document.querySelectorAll('#document-repository-tbody input[type="checkbox"]');
    
    if (selectAllCheckbox.checked) {
        allCheckboxes.forEach(checkbox => {
            selectedRepositoryFiles.add(parseInt(checkbox.value));
            checkbox.checked = true;
        });
    } else {
        selectedRepositoryFiles.clear();
        allCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
    }
    updateRepositorySelectionUI();
}

function updateRepositorySelectionUI() {
    const selectedCount = selectedRepositoryFiles.size;
    const selectedCountEl = document.getElementById('repository-selected-count');
    const downloadBtn = document.getElementById('repository-batch-download-btn');
    const deselectBtn = document.getElementById('repository-deselect-btn');
    const selectAllCheckbox = document.getElementById('repository-select-all-checkbox');
    
    selectedCountEl.textContent = `${selectedCount} selected file${selectedCount !== 1 ? 's' : ''}`;
    
    downloadBtn.disabled = selectedCount === 0;
    deselectBtn.disabled = selectedCount === 0;
    
    // Update select all checkbox state
    const allVisible = document.querySelectorAll('#document-repository-tbody input[type="checkbox"]').length;
    const selectedVisible = document.querySelectorAll('#document-repository-tbody input[type="checkbox"]:checked').length;
    
    if (selectedVisible === 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    } else if (selectedVisible === allVisible) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
    }
}

function repositoryBatchDownloadFiles() {
    if (selectedRepositoryFiles.size === 0) return;
    
    if (window.notifications) {
        window.notifications.success('Download Started', `Downloading ${selectedRepositoryFiles.size} selected file(s) from document repository.`);
    }
}

function deselectAllRepositoryFiles() {
    selectedRepositoryFiles.clear();
    loadDocumentRepositoryTable();
}

function toggleRepositoryFilter() {
    const dropdown = document.getElementById('repository-filter-dropdown');
    const isVisible = dropdown.style.display === 'block';
    
    // Close all filter dropdowns first
    document.querySelectorAll('.filter-dropdown').forEach(d => d.style.display = 'none');
    
    // Toggle the current dropdown
    dropdown.style.display = isVisible ? 'none' : 'block';
}

function toggleRepositorySort() {
    const dropdown = document.getElementById('repository-sort-dropdown');
    const isVisible = dropdown.style.display === 'block';
    
    // Close all dropdowns first
    document.querySelectorAll('.filter-dropdown, .sort-dropdown').forEach(d => d.style.display = 'none');
    
    // Toggle the current dropdown
    dropdown.style.display = isVisible ? 'none' : 'block';
}

// Filter Functions
let activeFilters = {
    categories: ['council', 'general', 'marketing', 'membership'],
    startDate: null,
    endDate: null,
    fileType: 'pdf'
};

let activeDownloadFilters = {
    categories: ['council', 'general', 'marketing', 'membership'],
    startDate: null,
    endDate: null,
    fileType: 'pdf'
};

let activeRepositoryFilters = {
    categories: ['council', 'general', 'marketing', 'membership'],
    startDate: null,
    endDate: null,
    fileType: 'pdf'
};

function updateCategoryFilter() {
    const checkboxes = document.querySelectorAll('#filter-dropdown input[type="checkbox"]');
    activeFilters.categories = Array.from(checkboxes)
        .filter(cb => cb.checked)
        .map(cb => cb.value);
}

function updateDownloadCategoryFilter() {
    const checkboxes = document.querySelectorAll('#download-filter-dropdown input[type="checkbox"]');
    activeDownloadFilters.categories = Array.from(checkboxes)
        .filter(cb => cb.checked)
        .map(cb => cb.value);
}

function updateRepositoryCategoryFilter() {
    const checkboxes = document.querySelectorAll('#repository-filter-dropdown input[type="checkbox"]');
    activeRepositoryFilters.categories = Array.from(checkboxes)
        .filter(cb => cb.checked)
        .map(cb => cb.value);
}

function applyFilters() {
    // Get filter values
    activeFilters.startDate = document.getElementById('start-date').value;
    activeFilters.endDate = document.getElementById('end-date').value;
    activeFilters.fileType = document.getElementById('file-type-filter').value;
    
    // Apply filters to My Files
    let filteredFiles = sampleFiles.filter(file => {
        // Category filter
        if (!activeFilters.categories.includes(file.category)) return false;
        
        // Date filter (simplified - would need proper date parsing in real implementation)
        // For now, just check if any date filter is set
        if (activeFilters.startDate || activeFilters.endDate) {
            // Date filtering logic would go here
        }
        
        // File type filter (simplified - assumes all files match type for demo)
        if (activeFilters.fileType && activeFilters.fileType !== '') {
            // File type filtering logic would go here
        }
        
        return true;
    });
    
    // Update table with filtered data
    const tbody = document.getElementById('my-files-tbody');
    if (tbody) {
        // Calculate pagination for filtered results
        const startIndex = (currentPage - 1) * filesPerPage;
        const endIndex = startIndex + filesPerPage;
        const paginatedFiles = filteredFiles.slice(startIndex, endIndex);
        
        tbody.innerHTML = paginatedFiles.map(file => `
            <tr>
                <td class="checkbox-column">
                    <input type="checkbox" 
                           value="${file.id}" 
                           onchange="toggleFileSelection(${file.id})"
                           ${selectedFiles.has(file.id) ? 'checked' : ''}>
                </td>
                <td><span class="file-name">${file.name}</span></td>
                <td><span class="uploaded-by">${file.uploadedBy}</span></td>
                <td><span class="upload-date">${file.date}</span></td>
                <td><span class="category-text">${file.category.charAt(0).toUpperCase() + file.category.slice(1)}</span></td>
                <td>
                    <div class="actions-menu">
                        <button class="actions-btn" onclick="toggleActionsMenu(${file.id})">⋯</button>
                        <div class="actions-dropdown" id="actions-${file.id}">
                            <div class="dropdown-item" onclick="viewFile(${file.id})">
                                <span class="action-icon info-icon"></span>
                                <span>See Details</span>
                            </div>
                            <div class="dropdown-item" onclick="previewFile(${file.id})">
                                <span class="action-icon eye-icon"></span>
                                <span>Preview</span>
                            </div>
                            <div class="dropdown-item download-highlighted" onclick="downloadFile(${file.id})">
                                <span class="action-icon download-icon"></span>
                                <span>Download</span>
                            </div>
                            <div class="dropdown-item delete-item" onclick="deleteFile(${file.id})">
                                <span class="action-icon delete-icon"></span>
                                <span>Delete</span>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        `).join('');
        
        updateSelectionUI();
        updatePagination();
    }
    
    // Close dropdown
    document.getElementById('filter-dropdown').style.display = 'none';
    
    // Show notification
    if (window.notifications) {
        window.notifications.success('Filter Applied', `Filtered by ${activeFilters.categories.length} categories`);
    }
}

function applyDownloadFilters() {
    // Get filter values
    activeDownloadFilters.startDate = document.getElementById('download-start-date').value;
    activeDownloadFilters.endDate = document.getElementById('download-end-date').value;
    activeDownloadFilters.fileType = document.getElementById('download-file-type-filter').value;
    
    // Apply similar filtering logic for download history
    loadDownloadHistoryTable();
    
    // Close dropdown
    document.getElementById('download-filter-dropdown').style.display = 'none';
    
    // Show notification
    if (window.notifications) {
        window.notifications.success('Filter Applied', 'Download history filters applied');
    }
}

function applyRepositoryFilters() {
    // Get filter values
    activeRepositoryFilters.startDate = document.getElementById('repository-start-date').value;
    activeRepositoryFilters.endDate = document.getElementById('repository-end-date').value;
    activeRepositoryFilters.fileType = document.getElementById('repository-file-type-filter').value;
    
    // Apply similar filtering logic for document repository
    loadDocumentRepositoryTable();
    
    // Close dropdown
    document.getElementById('repository-filter-dropdown').style.display = 'none';
    
    // Show notification
    if (window.notifications) {
        window.notifications.success('Filter Applied', 'Document repository filters applied');
    }
}

// Sort Functions
let currentSort = {
    repository: 'newest',
    myFiles: 'newest',
    downloadHistory: 'newest'
};

function applySorting(section, sortOrder) {
    currentSort[section] = sortOrder;
    
    // Update active state in dropdown
    const dropdown = document.getElementById(`${section}-sort-dropdown`);
    if (dropdown) {
        dropdown.querySelectorAll('.sort-option').forEach(option => {
            option.classList.remove('active');
        });
        dropdown.querySelector(`[onclick*="${sortOrder}"]`).classList.add('active');
    }
    
    // Apply sorting based on section
    if (section === 'repository') {
        sortRepositoryFiles(sortOrder);
    }
    
    // Close dropdown
    document.querySelectorAll('.sort-dropdown').forEach(d => d.style.display = 'none');
    
    // Show notification
    if (window.notifications) {
        const sortText = sortOrder === 'newest' ? 'Newest First' : 'Oldest First';
        window.notifications.success('Sort Applied', `Files sorted by ${sortText}`);
    }
}

function sortRepositoryFiles(sortOrder) {
    // Sort the sample files array
    sampleFiles.sort((a, b) => {
        const dateA = new Date(a.date);
        const dateB = new Date(b.date);
        
        if (sortOrder === 'newest') {
            return dateB - dateA; // Newest first
        } else {
            return dateA - dateB; // Oldest first
        }
    });
    
    // Reload the table with sorted data
    loadDocumentRepositoryTable();
}

// Close filter and sort dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.filter-container') && !e.target.closest('.sort-container')) {
        document.querySelectorAll('.filter-dropdown, .sort-dropdown').forEach(d => d.style.display = 'none');
    }
});

// Document Repository Pagination
function updateRepositoryPagination() {
    const totalPages = Math.ceil(sampleFiles.length / repositoryFilesPerPage);
    document.getElementById('repository-current-page').textContent = repositoryCurrentPage;
    document.getElementById('repository-total-pages').textContent = totalPages;
    
    document.getElementById('repository-prev-btn').disabled = repositoryCurrentPage === 1;
    document.getElementById('repository-next-btn').disabled = repositoryCurrentPage === totalPages;
}

function previousRepositoryPage() {
    if (repositoryCurrentPage > 1) {
        repositoryCurrentPage--;
        loadDocumentRepositoryTable();
    }
}

function nextRepositoryPage() {
    const totalPages = Math.ceil(sampleFiles.length / repositoryFilesPerPage);
    if (repositoryCurrentPage < totalPages) {
        repositoryCurrentPage++;
        loadDocumentRepositoryTable();
    }
}

// Load download history table with appropriate columns
function loadDownloadHistoryTable() {
    const tbody = document.getElementById('download-history-tab-tbody');
    if (!tbody) return;
    
    // Calculate pagination
    const startIndex = (downloadCurrentPage - 1) * downloadFilesPerPage;
    const endIndex = startIndex + downloadFilesPerPage;
    const paginatedFiles = sampleDownloadHistory.slice(startIndex, endIndex);
    
    tbody.innerHTML = paginatedFiles.map(file => `
        <tr>
            <td class="checkbox-column">
                <input type="checkbox" 
                       value="${file.id}" 
                       onchange="toggleDownloadFileSelection(${file.id})"
                       ${selectedDownloadFiles.has(file.id) ? 'checked' : ''}>
            </td>
            <td><span class="file-name">${file.name}</span></td>
            <td><span class="uploaded-by">${file.uploadedBy}</span></td>
            <td><span class="upload-date">${file.date}</span></td>
            <td><span class="category-text">${file.category.charAt(0).toUpperCase() + file.category.slice(1)}</span></td>
            <td>
                <div class="actions-menu">
                    <button class="actions-btn" onclick="toggleActionsMenu(${file.id})">⋯</button>
                    <div class="actions-dropdown" id="actions-${file.id}">
                        <div class="dropdown-item" onclick="viewFile(${file.id})">
                            <span class="action-icon info-icon"></span>
                            <span>See Details</span>
                        </div>
                        <div class="dropdown-item download-highlighted" onclick="downloadFile(${file.id})">
                            <span class="action-icon download-icon"></span>
                            <span>Download Again</span>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    `).join('');
    
    updateDownloadSelectionUI();
    updateDownloadPagination();
}

// FAQ Toggle Functionality
function toggleFAQ(element) {
    const faqItem = element.parentElement;
    const answer = faqItem.querySelector('.faq-answer');
    const arrow = element.querySelector('.faq-arrow');
    
    // Close all other FAQ items
    document.querySelectorAll('.faq-question').forEach(question => {
        if (question !== element) {
            question.classList.remove('active');
            question.parentElement.querySelector('.faq-answer').classList.remove('active');
        }
    });
    
    // Toggle current FAQ item
    element.classList.toggle('active');
    answer.classList.toggle('active');
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Load default tables
    loadFilesTable('recent-files-tbody', sampleFiles);
    loadFilesTable('download-history-tbody', sampleDownloadHistory);
    
    // Show default view (no tabs active)
    showDefaultView();
    
    // Initialize contact form submission
    const contactForm = document.querySelector('.dashboard-contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (window.notifications) {
                window.notifications.success('Message Sent', 'Your message has been sent successfully! We will get back to you soon.');
            }
            
            // Reset form
            contactForm.reset();
        });
    }
});
</script>

<?php get_footer(); ?> 