<?php
/**
 * Template Name: CPD Courses
 * 
 * All CPD Courses page with filtering and search
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

$current_user_id = get_current_user_id();

// Get user's CPD progress
global $wpdb;
$current_year = date('Y');

// Debug: Check if tables exist
if (!iipm_cpd_tables_exist()) {
    // Try to create tables
    iipm_create_cpd_tables();
}

// Get CPD progress by category for the main 4 categories
$cpd_progress = $wpdb->get_results($wpdb->prepare(
    "SELECT 
        cc.name as category_name,
        cc.min_points_required as required_points,
        COALESCE(SUM(CASE WHEN cr.status = 'approved' THEN cr.cpd_points ELSE 0 END), 0) as earned_points
     FROM {$wpdb->prefix}test_iipm_cpd_categories cc
     LEFT JOIN {$wpdb->prefix}test_iipm_cpd_records cr ON cc.id = cr.category_id 
         AND cr.user_id = %d AND cr.cpd_year = %d
     WHERE cc.is_mandatory = 1 AND cc.is_active = 1
     GROUP BY cc.id, cc.name, cc.min_points_required
     ORDER BY cc.sort_order ASC",
    $current_user_id,
    $current_year
));

// If no progress data, create default structure
if (empty($cpd_progress)) {
    $cpd_progress = array(
        (object) array('category_name' => 'Pensions', 'required_points' => 1, 'earned_points' => 0),
        (object) array('category_name' => 'Savings & Investment', 'required_points' => 1, 'earned_points' => 0),
        (object) array('category_name' => 'Ethics', 'required_points' => 1, 'earned_points' => 0),
        (object) array('category_name' => 'Life Assurance', 'required_points' => 1, 'earned_points' => 0)
    );
}

// Calculate totals
$total_required = 0;
$total_earned = 0;
foreach ($cpd_progress as $progress) {
    $total_required += $progress->required_points;
    $total_earned += min($progress->earned_points, $progress->required_points); // Cap at required
}

// Pagination setup
$courses_per_page = 12;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $courses_per_page;

// Debug pagination
if (current_user_can('administrator')) {
    error_log("CPD Pagination Debug - Current Page: $current_page, Offset: $offset, Courses per page: $courses_per_page");
    error_log("CPD Pagination Debug - Query params: " . print_r($query_params, true));
}

// Build WHERE clause for filters
$where_conditions = array("c.is_active = 1");
$query_params = array();

// Title search filter
if (!empty($_GET['title_search'])) {
    $where_conditions[] = "c.title LIKE %s";
    $query_params[] = '%' . sanitize_text_field($_GET['title_search']) . '%';
}

// LIA code search filter
if (!empty($_GET['lia_search'])) {
    $where_conditions[] = "c.lia_code LIKE %s";
    $query_params[] = '%' . sanitize_text_field($_GET['lia_search']) . '%';
}

// Date range filters
if (!empty($_GET['date_from'])) {
    $where_conditions[] = "DATE(c.created_at) >= %s";
    $query_params[] = sanitize_text_field($_GET['date_from']);
}

if (!empty($_GET['date_to'])) {
    $where_conditions[] = "DATE(c.created_at) <= %s";
    $query_params[] = sanitize_text_field($_GET['date_to']);
}

// Category filter
if (!empty($_GET['categories'])) {
    $categories = explode(',', sanitize_text_field($_GET['categories']));
    $category_placeholders = implode(',', array_fill(0, count($categories), '%s'));
    $where_conditions[] = "cat.name IN ($category_placeholders)";
    $query_params = array_merge($query_params, $categories);
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(c.id) 
                FROM {$wpdb->prefix}test_iipm_cpd_courses c
                LEFT JOIN {$wpdb->prefix}test_iipm_cpd_categories cat ON c.category_id = cat.id
                $where_clause";

if (!empty($query_params)) {
    $total_courses = $wpdb->get_var($wpdb->prepare($count_query, $query_params));
} else {
    $total_courses = $wpdb->get_var($count_query);
}

$total_pages = ceil($total_courses / $courses_per_page);

// Get CPD courses with pagination and filters
$courses_query = "SELECT c.*, cat.name as category_name 
                  FROM {$wpdb->prefix}test_iipm_cpd_courses c
                  LEFT JOIN {$wpdb->prefix}test_iipm_cpd_categories cat ON c.category_id = cat.id
                  $where_clause
                  ORDER BY c.created_at DESC
                  LIMIT %d OFFSET %d";

// Build final parameters and execute query
if (!empty($query_params)) {
    $final_params = array_merge($query_params, array($courses_per_page, $offset));
    $prepared_query = $wpdb->prepare($courses_query, $final_params);
    $all_courses = $wpdb->get_results($prepared_query);
    
    // Debug
    if (current_user_can('administrator')) {
        error_log("CPD Query Debug (with filters) - Final params: " . print_r($final_params, true));
        error_log("CPD Query Debug (with filters) - Prepared query: " . $prepared_query);
    }
} else {
    // No filter parameters, just pagination
    $prepared_query = $wpdb->prepare($courses_query, $courses_per_page, $offset);
    $all_courses = $wpdb->get_results($prepared_query);
    
    // Debug
    if (current_user_can('administrator')) {
        error_log("CPD Query Debug (no filters) - Courses per page: $courses_per_page, Offset: $offset");
        error_log("CPD Query Debug (no filters) - Prepared query: " . $prepared_query);
    }
}

// Debug courses loaded
if (current_user_can('administrator')) {
    error_log("CPD Courses Debug - Loaded " . count($all_courses) . " courses for page $current_page");
    if (!empty($all_courses)) {
        error_log("First course ID: " . $all_courses[0]->id . ", Title: " . $all_courses[0]->title);
        error_log("Last course ID: " . end($all_courses)->id . ", Title: " . end($all_courses)->title);
    }
}

// Get course counts by category
$category_counts = $wpdb->get_results(
    "SELECT cat.name as category_name, COUNT(c.id) as course_count
     FROM {$wpdb->prefix}test_iipm_cpd_categories cat
     LEFT JOIN {$wpdb->prefix}test_iipm_cpd_courses c ON cat.id = c.category_id AND c.is_active = 1
     WHERE cat.is_active = 1 AND cat.is_mandatory = 1
     GROUP BY cat.id, cat.name
     ORDER BY cat.sort_order ASC"
);

// Create array for easy lookup
$category_count_lookup = array();
foreach ($category_counts as $count) {
    $category_count_lookup[$count->category_name] = $count->course_count;
}

// Debug output (remove in production)
if (current_user_can('administrator')) {
    error_log('CPD Debug: Found ' . count($all_courses) . ' courses');
    error_log('CPD Debug: Progress categories: ' . count($cpd_progress));
}

get_header(); 
?>

<div class="cpd-courses-page">
    <!-- Header -->
    <div class="cpd-header">
        <div class="container">
            <div class="header-content">
                <div class="breadcrumb">
                    <a href="<?php echo home_url('/dashboard/'); ?>">🏠</a>
                    <span class="separator">></span>
                    <span>All CPD Courses</span>
                </div>
                <h1>All CPD Courses</h1>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="cpd-courses-layout">
            <!-- Left Sidebar -->
            <div class="cpd-sidebar">
                <!-- Your Progress Section -->
                <div class="progress-widget">
                    <h3>Your Progress</h3>
                    <div class="progress-categories">
                        <?php foreach ($cpd_progress as $progress): 
                            $earned = min($progress->earned_points, $progress->required_points);
                            $is_complete = $earned >= $progress->required_points;
                        ?>
                        <div class="progress-item <?php echo $is_complete ? 'complete' : ''; ?>">
                            <span class="category-name"><?php echo esc_html($progress->category_name); ?></span>
                            <span class="progress-count"><?php echo intval($earned); ?>/<?php echo intval($progress->required_points); ?></span>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="progress-item">
                            <span class="category-name"><strong>Total</strong></span>
                            <span class="progress-count"><strong><?php echo intval($total_earned); ?>/<?php echo intval($total_required); ?></strong></span>
                        </div>
                    </div>
                    
                    <a href="<?php echo home_url('/training-history/'); ?>" class="training-history-link">
                        See training history →
                    </a>
                </div>

                <!-- Filters Section -->
                <div class="filters-widget">
                    <h3>Filter Courses</h3>
                    
                    <div class="filter-group">
                        <label for="title-search">Title</label>
                        <div class="search-input">
                            <input type="text" id="title-search" placeholder="Search course title" value="<?php echo esc_attr(isset($_GET['title_search']) ? $_GET['title_search'] : ''); ?>">
                            <span class="search-icon">🔍</span>
                        </div>
                    </div>

                    <div class="filter-group">
                        <label for="lia-code-search">LIA Code</label>
                        <div class="search-input">
                            <input type="text" id="lia-code-search" placeholder="Search LIA code" value="<?php echo esc_attr(isset($_GET['lia_search']) ? $_GET['lia_search'] : ''); ?>">
                            <span class="search-icon">🔍</span>
                        </div>
                    </div>

                    <div class="filter-group">
                        <label>Date Range</label>
                        <div class="date-range">
                            <input type="date" id="date-from" placeholder="From" value="<?php echo esc_attr(isset($_GET['date_from']) ? $_GET['date_from'] : ''); ?>">
                            <span class="date-separator">to</span>
                            <input type="date" id="date-to" placeholder="To" value="<?php echo esc_attr(isset($_GET['date_to']) ? $_GET['date_to'] : ''); ?>">
                        </div>
                    </div>

                    <div class="filter-group">
                        <label>Category</label>
                        <div class="category-filters">
                            <?php 
                            $selected_categories = array();
                            if (!empty($_GET['categories'])) {
                                $selected_categories = explode(',', sanitize_text_field($_GET['categories']));
                            }
                            
                            foreach ($cpd_progress as $progress): 
                                $course_count = isset($category_count_lookup[$progress->category_name]) ? $category_count_lookup[$progress->category_name] : 0;
                                $is_checked = empty($selected_categories) || in_array($progress->category_name, $selected_categories);
                            ?>
                            <label class="custom-checkbox-label">
                                <input type="checkbox" name="category" value="<?php echo esc_attr($progress->category_name); ?>" <?php echo $is_checked ? 'checked' : ''; ?>>
                                <span class="category-text">
                                    <?php echo esc_html($progress->category_name); ?>
                                    <span class="course-count">(<?php echo $course_count; ?>)</span>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Filter Options -->
                    <div class="filter-options">
                        <label>
                            <input type="checkbox" name="show_completed" value="1">
                            Show completed courses
                        </label>
                    </div>

                    <button class="clear-filters-btn">Clear all filters</button>
                </div>
            </div>

            <!-- Main Content -->
            <div class="cpd-main-content">
                <?php if (empty($all_courses)): ?>
                    <div class="no-courses-message">
                        <h3>No Courses Available</h3>
                        <p>No CPD courses are currently available. Please check back later or contact support.</p>
                        <?php if (current_user_can('administrator')): ?>
                            <p><strong>Admin Note:</strong> <a href="<?php echo get_template_directory_uri(); ?>/debug-cpd-setup.php" target="_blank">Run CPD Debug Setup</a></p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Loading Indicator -->
                    <div class="loading-overlay" id="loading-overlay" style="display: none;">
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                            <p>Loading courses...</p>
                        </div>
                    </div>

                    <div class="courses-grid" id="courses-grid">
                        <?php foreach ($all_courses as $course): 
                            $course_date = new DateTime($course->created_at);
                        ?>
                        <div class="course-card" 
                             data-title="<?php echo esc_attr(strtolower($course->title)); ?>"
                             data-lia-code="<?php echo esc_attr(strtolower($course->lia_code)); ?>"
                             data-category="<?php echo esc_attr($course->category_name); ?>"
                             data-date="<?php echo $course_date->format('Y-m-d'); ?>"
                             data-course-id="<?php echo $course->id; ?>">
                            
                            <div class="course-header">
                                <div class="course-badges">
                                    <span class="category-badge"><?php echo esc_html($course->category_name); ?></span>
                                    <button class="favorite-btn" title="Add to favorites">
                                        <span class="heart-icon">🤍</span>
                                    </button>
                                </div>
                                <button class="add-course-btn" title="Add to learning path">
                                    <span class="plus-icon">➕</span>
                                </button>
                            </div>

                            <div class="course-content">
                                <h4 class="course-title"><?php echo esc_html($course->title); ?></h4>
                                <p class="course-provider">Provided by <?php echo esc_html($course->provider); ?></p>
                                
                                <div class="course-meta">
                                    <div class="meta-item">
                                        <span class="meta-label">LIA Code:</span>
                                        <span class="meta-value"><?php echo esc_html($course->lia_code); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">Date:</span>
                                        <span class="meta-value"><?php echo $course_date->format('F j, Y'); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">CPD Points:</span>
                                        <span class="meta-value"><?php echo intval($course->cpd_points); ?> <?php echo intval($course->cpd_points) == 1 ? 'Point' : 'Points'; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): 
                        // Build query string for pagination links
                        $query_args = array();
                        if (!empty($_GET['title_search'])) $query_args['title_search'] = sanitize_text_field($_GET['title_search']);
                        if (!empty($_GET['lia_search'])) $query_args['lia_search'] = sanitize_text_field($_GET['lia_search']);
                        if (!empty($_GET['date_from'])) $query_args['date_from'] = sanitize_text_field($_GET['date_from']);
                        if (!empty($_GET['date_to'])) $query_args['date_to'] = sanitize_text_field($_GET['date_to']);
                        if (!empty($_GET['categories'])) $query_args['categories'] = sanitize_text_field($_GET['categories']);
                    ?>
                    <div class="pagination">
                        <!-- Previous Button -->
                        <?php if ($current_page > 1): ?>
                            <button class="pagination-btn prev" data-page="<?php echo ($current_page - 1); ?>">‹ Previous</button>
                        <?php else: ?>
                            <span class="pagination-btn prev disabled">‹ Previous</span>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <div class="pagination-numbers">
                            <?php
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);
                            
                            // Show first page if we're not showing it
                            if ($start_page > 1): ?>
                                <button class="pagination-number" data-page="1">1</button>
                                <?php if ($start_page > 2): ?>
                                    <span class="pagination-dots">...</span>
                                <?php endif; ?>
                            <?php endif;

                            // Show page numbers
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <?php if ($i == $current_page): ?>
                                    <span class="pagination-number active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <button class="pagination-number" data-page="<?php echo $i; ?>"><?php echo $i; ?></button>
                                <?php endif; ?>
                            <?php endfor;

                            // Show last page if we're not showing it
                            if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <span class="pagination-dots">...</span>
                                <?php endif; ?>
                                <button class="pagination-number" data-page="<?php echo $total_pages; ?>"><?php echo $total_pages; ?></button>
                            <?php endif; ?>
                        </div>

                        <!-- Next Button -->
                        <?php if ($current_page < $total_pages): ?>
                            <button class="pagination-btn next" data-page="<?php echo ($current_page + 1); ?>">Next ›</button>
                        <?php else: ?>
                            <span class="pagination-btn next disabled">Next ›</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pagination Info -->
                    <div class="pagination-info">
                        <?php
                        $start_item = ($current_page - 1) * $courses_per_page + 1;
                        $end_item = min($current_page * $courses_per_page, $total_courses);
                        ?>
                        Showing <?php echo $start_item; ?>-<?php echo $end_item; ?> of <?php echo $total_courses; ?> courses
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.cpd-courses-page {
    background: #f8fafc;
    min-height: 100vh;
    padding-top: 0;
}

.cpd-header {
    background: linear-gradient(135deg, #8b5a96 0%, #6b4c93 100%);
    color: white;
    padding: 40px 0;
    margin-bottom: 30px;
    padding-top:120px;
}

.header-content {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.breadcrumb {
    font-size: 14px;
    opacity: 0.9;
}

.breadcrumb a {
    color: white;
    text-decoration: none;
}

.separator {
    margin: 0 8px;
}

.cpd-header h1 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 600;
}

.cpd-courses-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 30px;
    align-items: start;
}

.cpd-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.progress-widget,
.filters-widget {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.progress-widget h3,
.filters-widget h3 {
    margin: 0 0 20px 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
}

.progress-categories {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 20px;
}

.progress-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
}

.progress-item.complete .progress-count {
    color: #10b981;
    font-weight: 600;
}

.progress-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-top: 2px solid #e5e7eb;
    margin-top: 8px;
}

.category-name {
    font-size: 14px;
    color: #6b7280;
}

.progress-count {
    font-size: 14px;
    font-weight: 500;
    color: #374151;
}

.training-history-link {
    color: #8b5a96;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.training-history-link:hover {
    text-decoration: underline;
}

.filter-group {
    margin-bottom: 20px;
}

.filter-group label {
    /* display: block; */
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
}

.search-input {
    position: relative;
}

.search-input input {
    width: 100%;
    padding: 10px 35px 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
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
    flex-direction: column;
    gap: 8px;
}

.date-range input {
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.date-separator {
    text-align: center;
    font-size: 12px;
    color: #6b7280;
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

.clear-filters-btn {
    width: 100%;
    padding: 10px;
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    color: #374151;
    cursor: pointer;
    transition: background 0.2s;
}

.clear-filters-btn:hover {
    background: #e5e7eb;
}

.no-courses-message {
    background: white;
    border-radius: 12px;
    padding: 40px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.no-courses-message h3 {
    margin: 0 0 16px 0;
    color: #1f2937;
}

.no-courses-message p {
    color: #6b7280;
    margin: 0 0 16px 0;
}

.courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.course-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.course-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.course-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}

.course-badges {
    display: flex;
    align-items: center;
    gap: 8px;
}

.category-badge {
    background: #ff6b35;
    color: white;
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.favorite-btn,
.add-course-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: background 0.2s;
}

.favorite-btn:hover,
.add-course-btn:hover {
    background: #f3f4f6;
}

.course-title {
    margin: 0 0 8px 0;
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    line-height: 1.4;
}

.course-provider {
    margin: 0 0 16px 0;
    font-size: 14px;
    color: #6b7280;
}

.course-meta {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.meta-item {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
}

.meta-label {
    color: #6b7280;
}

.meta-value {
    color: #374151;
    font-weight: 500;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    padding: 30px 0 20px 0;
}

.pagination-numbers {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pagination-btn {
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 10px 16px;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    color: #374151;
    font-weight: 500;
    font-size: 14px;
    outline: none;
}

.pagination-btn:hover:not(.disabled) {
    background: #f9fafb;
    border-color: #8b5a96;
    color: #8b5a96;
}

.pagination-btn.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    color: #9ca3af;
    background: #f9fafb;
}

.pagination-number {
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 8px 12px;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    color: #374151;
    font-weight: 500;
    font-size: 14px;
    min-width: 40px;
    text-align: center;
    outline: none;
}

.pagination-number:hover:not(.active):not(.disabled) {
    background: #f9fafb;
    border-color: #8b5a96;
    color: #8b5a96;
}

.pagination-number.active {
    background: #8b5a96;
    border-color: #8b5a96;
    color: white;
}

button.pagination-btn,
button.pagination-number {
    font-family: inherit;
    display: inline-block;
}

.pagination-dots {
    color: #9ca3af;
    padding: 0 4px;
    font-weight: bold;
}

.pagination-info {
    font-size: 14px;
    color: #6b7280;
    text-align: center;
    padding: 10px 0;
    border-top: 1px solid #e5e7eb;
    margin-top: 10px;
}

.loading-overlay {
    position: relative;
    background: rgba(248, 250, 252, 0.9);
    border-radius: 12px;
    padding: 60px 20px;
    text-align: center;
    margin-bottom: 30px;
}

.loading-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e5e7eb;
    border-top: 4px solid #8b5a96;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-spinner p {
    margin: 0;
    color: #6b7280;
    font-size: 16px;
}

@media (max-width: 768px) {
    .cpd-courses-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .courses-grid {
        grid-template-columns: 1fr;
    }
    
    .cpd-header h1 {
        font-size: 2rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const titleSearch = document.getElementById('title-search');
    const liaCodeSearch = document.getElementById('lia-code-search');
    const dateFrom = document.getElementById('date-from');
    const dateTo = document.getElementById('date-to');
    const categoryCheckboxes = document.querySelectorAll('input[name="category"]');
    const clearFiltersBtn = document.querySelector('.clear-filters-btn');
    const courseCards = document.querySelectorAll('.course-card');

    // Check if we have URL parameters (pagination)
    const urlParams = new URLSearchParams(window.location.search);
    const currentPageFromURL = urlParams.get('paged');
    
    // Debug pagination
    console.log('Current URL:', window.location.href);
    console.log('Current page from URL:', currentPageFromURL);
    console.log('All URL params:', Object.fromEntries(urlParams.entries()));
    
    // Only apply client-side filtering if we're on page 1 or no pagination
    const shouldUseClientSideFiltering = !currentPageFromURL || currentPageFromURL === '1';
    
    console.log('Should use client-side filtering:', shouldUseClientSideFiltering);
    
    // Flag to prevent auto-triggering during page load
    let isPageLoading = true;
    
    // Set flag after page load is complete
    setTimeout(() => {
        isPageLoading = false;
        console.log('Page loading complete, events can now trigger filters');
    }, 100);
    
    console.log('JavaScript initialized, setting up pagination...');
    
    // Check if pagination elements exist
    const paginationBtns = document.querySelectorAll('.pagination-btn');
    const paginationNumbers = document.querySelectorAll('.pagination-number');
    console.log('Found pagination buttons:', paginationBtns.length);
    console.log('Found pagination numbers:', paginationNumbers.length);
    
    // Log all pagination elements
    paginationBtns.forEach((btn, index) => {
        console.log(`Button ${index}:`, btn.outerHTML);
    });
    paginationNumbers.forEach((btn, index) => {
        console.log(`Number ${index}:`, btn.outerHTML);
    });

    function filterCourses() {
        // Prevent filtering during page load
        if (isPageLoading) {
            console.log('Ignoring filter call during page load');
            return;
        }
        
        // If we're on a paginated page, redirect to page 1 with filters
        if (!shouldUseClientSideFiltering) {
            applyFiltersWithRedirect();
            return;
        }

        const titleFilter = titleSearch.value.toLowerCase();
        const liaCodeFilter = liaCodeSearch.value.toLowerCase();
        const dateFromFilter = dateFrom.value;
        const dateToFilter = dateTo.value;
        const selectedCategories = Array.from(categoryCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        let visibleCount = 0;
        courseCards.forEach(card => {
            const title = card.dataset.title;
            const liaCode = card.dataset.liaCode;
            const category = card.dataset.category;
            const courseDate = card.dataset.date;

            let show = true;

            // Title filter
            if (titleFilter && !title.includes(titleFilter)) {
                show = false;
            }

            // LIA Code filter
            if (liaCodeFilter && !liaCodeFilter.includes(liaCode)) {
                show = false;
            }

            // Category filter
            if (selectedCategories.length > 0 && !selectedCategories.includes(category)) {
                show = false;
            }

            // Date range filter
            if (dateFromFilter && courseDate < dateFromFilter) {
                show = false;
            }
            if (dateToFilter && courseDate > dateToFilter) {
                show = false;
            }

            card.style.display = show ? 'block' : 'none';
            if (show) visibleCount++;
        });

        // Update pagination info for filtered results
        updatePaginationInfo(visibleCount);
    }

    function applyFiltersWithRedirect() {
        // Prevent redirecting during page load
        if (isPageLoading) {
            console.log('Ignoring redirect call during page load');
            return;
        }
        
        console.log('applyFiltersWithRedirect called - this should NOT happen on page load!');
        
        // Build URL with filters but reset to page 1
        const params = new URLSearchParams();
        
        if (titleSearch && titleSearch.value) {
            params.set('title_search', titleSearch.value);
        }
        if (liaCodeSearch && liaCodeSearch.value) {
            params.set('lia_search', liaCodeSearch.value);
        }
        if (dateFrom && dateFrom.value) {
            params.set('date_from', dateFrom.value);
        }
        if (dateTo && dateTo.value) {
            params.set('date_to', dateTo.value);
        }
        
        const selectedCategories = Array.from(categoryCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        if (selectedCategories.length > 0 && selectedCategories.length < categoryCheckboxes.length) {
            params.set('categories', selectedCategories.join(','));
        }

        // Redirect to page 1 with filters
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        console.log('Redirecting to:', newUrl);
        window.location.href = newUrl;
    }

    function updatePaginationInfo(visibleCount) {
        const paginationInfo = document.querySelector('.pagination-info');
        if (paginationInfo && shouldUseClientSideFiltering && visibleCount !== courseCards.length) {
            paginationInfo.textContent = `Showing ${visibleCount} filtered courses`;
        }
    }

    // Add event listeners only for page 1 or no pagination
    if (shouldUseClientSideFiltering) {
        console.log('Setting up client-side filtering for page 1');
        if (titleSearch) titleSearch.addEventListener('input', filterCourses);
        if (liaCodeSearch) liaCodeSearch.addEventListener('input', filterCourses);
        if (dateFrom) dateFrom.addEventListener('change', filterCourses);
        if (dateTo) dateTo.addEventListener('change', filterCourses);
        categoryCheckboxes.forEach(cb => cb.addEventListener('change', filterCourses));
    } else {
        console.log('Setting up filter redirects for paginated pages');
        // For paginated pages, redirect on filter change (but NOT on page load!)
        if (titleSearch) titleSearch.addEventListener('input', applyFiltersWithRedirect);
        if (liaCodeSearch) liaCodeSearch.addEventListener('input', applyFiltersWithRedirect);
        if (dateFrom) dateFrom.addEventListener('change', applyFiltersWithRedirect);
        if (dateTo) dateTo.addEventListener('change', applyFiltersWithRedirect);
        categoryCheckboxes.forEach(cb => cb.addEventListener('change', applyFiltersWithRedirect));
    }

    // AJAX Pagination
    let currentPage = <?php echo intval($current_page); ?>;
    const totalPages = <?php echo intval($total_pages); ?>;
    
    console.log('Current page:', currentPage);
    console.log('Total pages:', totalPages);
    
    function loadPage(page) {
        console.log('loadPage called with page:', page);
        console.log('Total pages:', totalPages);
        
        if (page < 1 || page > totalPages) {
            console.log('Page out of range, returning');
            return;
        }
        
        console.log('Starting to load page:', page);
        
        // Show loading
        const loadingOverlay = document.getElementById('loading-overlay');
        const coursesGrid = document.getElementById('courses-grid');
        
        if (loadingOverlay) {
            loadingOverlay.style.display = 'block';
            console.log('Loading overlay shown');
        } else {
            console.log('Loading overlay not found');
        }
        
        if (coursesGrid) {
            coursesGrid.style.opacity = '0.5';
            console.log('Courses grid dimmed');
        } else {
            console.log('Courses grid not found');
        }
        
        // Build URL parameters
        const params = new URLSearchParams(window.location.search);
        params.set('paged', page);
        
        // Add current filters
        if (titleSearch && titleSearch.value) params.set('title_search', titleSearch.value);
        if (liaCodeSearch && liaCodeSearch.value) params.set('lia_search', liaCodeSearch.value);
        if (dateFrom && dateFrom.value) params.set('date_from', dateFrom.value);
        if (dateTo && dateTo.value) params.set('date_to', dateTo.value);
        
        const selectedCategories = Array.from(categoryCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        if (selectedCategories.length > 0 && selectedCategories.length < categoryCheckboxes.length) {
            params.set('categories', selectedCategories.join(','));
        }
        
        // Make AJAX request
        const requestUrl = window.location.pathname + '?' + params.toString();
        console.log('AJAX Request URL:', requestUrl);
        
        fetch(requestUrl)
            .then(response => {
                console.log('AJAX Response status:', response.status);
                return response.text();
            })
            .then(html => {
                console.log('AJAX Response received, length:', html.length);
                
                // Parse the response to extract courses grid
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newCoursesGrid = doc.querySelector('#courses-grid');
                const newPaginationInfo = doc.querySelector('.pagination-info');
                
                console.log('New courses grid found:', !!newCoursesGrid);
                if (newCoursesGrid) {
                    console.log('New courses count:', newCoursesGrid.querySelectorAll('.course-card').length);
                    console.log('Current courses count:', document.getElementById('courses-grid').querySelectorAll('.course-card').length);
                    
                    // Replace courses grid content
                    document.getElementById('courses-grid').innerHTML = newCoursesGrid.innerHTML;
                    console.log('Courses grid updated');
                    
                    // Update pagination info
                    if (newPaginationInfo) {
                        const currentPaginationInfo = document.querySelector('.pagination-info');
                        if (currentPaginationInfo) {
                            currentPaginationInfo.innerHTML = newPaginationInfo.innerHTML;
                        }
                        console.log('Pagination info updated');
                    }
                    
                    // Update pagination buttons
                    updatePaginationButtons(page);
                    
                    // Update URL without reload
                    const newUrl = window.location.pathname + '?' + params.toString();
                    history.pushState({page: page}, '', newUrl);
                    
                    // Update current page
                    currentPage = page;
                    
                    // Scroll to top of content
                    document.querySelector('.cpd-main-content').scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start' 
                    });
                } else {
                    console.log('No courses grid found in response');
                }
            })
            .catch(error => {
                console.error('Error loading page:', error);
                // Fallback to regular page load
                window.location.href = window.location.pathname + '?' + params.toString();
            })
            .finally(() => {
                // Hide loading
                document.getElementById('loading-overlay').style.display = 'none';
                document.getElementById('courses-grid').style.opacity = '1';
            });
    }
    
    function updatePaginationButtons(page) {
        // Update active page number
        document.querySelectorAll('.pagination-number').forEach(btn => {
            if (btn.textContent == page) {
                btn.className = 'pagination-number active';
                btn.outerHTML = `<span class="pagination-number active">${page}</span>`;
            } else if (btn.classList.contains('active')) {
                btn.className = 'pagination-number';
                btn.outerHTML = `<button class="pagination-number" data-page="${btn.textContent}">${btn.textContent}</button>`;
            }
        });
        
        // Update prev/next buttons
        const prevBtn = document.querySelector('.pagination-btn.prev');
        const nextBtn = document.querySelector('.pagination-btn.next');
        
        if (prevBtn) {
            if (page > 1) {
                prevBtn.className = 'pagination-btn prev';
                prevBtn.setAttribute('data-page', page - 1);
                prevBtn.disabled = false;
            } else {
                prevBtn.className = 'pagination-btn prev disabled';
                prevBtn.removeAttribute('data-page');
                prevBtn.disabled = true;
            }
        }
        
        if (nextBtn) {
            if (page < totalPages) {
                nextBtn.className = 'pagination-btn next';
                nextBtn.setAttribute('data-page', parseInt(page) + 1);
                nextBtn.disabled = false;
            } else {
                nextBtn.className = 'pagination-btn next disabled';
                nextBtn.removeAttribute('data-page');
                nextBtn.disabled = true;
            }
        }
    }
    
    // Add click handlers for pagination buttons
    document.addEventListener('click', function(e) {
        // Only log if clicking near pagination area
        if (e.target.closest('.pagination')) {
            console.log('Click detected on:', e.target);
            console.log('Target classes:', e.target.className);
            console.log('Data-page attribute:', e.target.getAttribute('data-page'));
            console.log('Target tag:', e.target.tagName);
            console.log('Target classList contains pagination-btn:', e.target.classList.contains('pagination-btn'));
            console.log('Target classList contains pagination-number:', e.target.classList.contains('pagination-number'));
        }
        
        if (e.target.classList.contains('pagination-btn') || e.target.classList.contains('pagination-number')) {
            console.log('Pagination element clicked');
            const page = e.target.getAttribute('data-page');
            console.log('Page to load:', page);
            
            if (page && !e.target.classList.contains('disabled')) {
                console.log('Loading page:', page);
                e.preventDefault();
                loadPage(parseInt(page));
            } else {
                console.log('Page load prevented - no page or disabled');
            }
        }
    });
    
    // Handle browser back/forward
    window.addEventListener('popstate', function(e) {
        if (e.state && e.state.page) {
            loadPage(e.state.page);
        } else {
            location.reload();
        }
    });

    // Clear filters
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            // Always redirect to page 1 when clearing filters
            window.location.href = window.location.pathname;
        });
    }

    // Favorite functionality
    document.querySelectorAll('.favorite-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const heartIcon = this.querySelector('.heart-icon');
            if (heartIcon.textContent === '🤍') {
                heartIcon.textContent = '❤️';
            } else {
                heartIcon.textContent = '🤍';
            }
        });
    });

    // Add course functionality
    document.querySelectorAll('.add-course-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Add course to learning path logic here
            alert('Course added to learning path!');
        });
    });
});
</script>

<?php get_footer(); ?>
