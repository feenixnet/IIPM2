<?php
/**
 * LIA/IOB Course Import Functions
 * Handles bulk import of courses from CSV files
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Process uploaded CSV file for course import
 */
function iipm_process_course_csv_upload($file) {
    // Validate file upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return array(
            'success' => false,
            'error' => 'File upload failed. Error code: ' . $file['error']
        );
    }

    // Check file type
    $file_info = pathinfo($file['name']);
    if (strtolower($file_info['extension']) !== 'csv') {
        return array(
            'success' => false,
            'error' => 'Invalid file type. Please upload a CSV file.'
        );
    }

    // Check file size (10MB limit)
    if ($file['size'] > 10 * 1024 * 1024) {
        return array(
            'success' => false,
            'error' => 'File too large. Maximum size is 10MB.'
        );
    }

    // Process the CSV file
    $csv_data = iipm_parse_csv_file($file['tmp_name']);
    
    if (!$csv_data['success']) {
        return $csv_data;
    }

    // Import courses to database
    $import_result = iipm_import_courses_to_database($csv_data['data'], isset($_POST['update_existing']));
    
    return $import_result;
}

/**
 * Parse CSV file and validate data
 */
function iipm_parse_csv_file($file_path) {
    if (!file_exists($file_path)) {
        return array(
            'success' => false,
            'error' => 'File not found.'
        );
    }

    $courses = array();
    $errors = array();
    $line_number = 1;

    // Required columns
    $required_columns = array('title', 'provider', 'category', 'cpd_points');
    
    // Valid categories
    $valid_categories = array('pensions', 'savings_investment', 'ethics', 'life_assurance', 'general', 'technology', 'regulation_compliance', 'professional_development');
    
    // Valid course types
    $valid_course_types = array('webinar', 'workshop', 'conference', 'online', 'self_study');

    if (($handle = fopen($file_path, "r")) !== FALSE) {
        // Read header row
        $headers = fgetcsv($handle, 1000, ",");
        if (!$headers) {
            return array(
                'success' => false,
                'error' => 'Invalid CSV format. No headers found.'
            );
        }

        // Clean headers - remove BOM, trim whitespace, convert to lowercase for comparison
        $clean_headers = array();
        foreach ($headers as $header) {
            // Remove BOM if present
            $header = str_replace("\xEF\xBB\xBF", '', $header);
            // Trim whitespace and convert to lowercase for comparison
            $clean_headers[] = trim(strtolower($header));
        }
        
        // Debug information - let's see what headers we actually have
        error_log('CSV Headers found: ' . implode(', ', $clean_headers));
        error_log('Required columns: ' . implode(', ', $required_columns));

        // Validate required columns exist
        foreach ($required_columns as $required_col) {
            if (!in_array(strtolower($required_col), $clean_headers)) {
                return array(
                    'success' => false,
                    'error' => "Missing required column: {$required_col}. Found columns: " . implode(', ', $headers)
                );
            }
        }
        
        // Create mapping from original headers to cleaned headers
        $header_mapping = array();
        for ($i = 0; $i < count($headers); $i++) {
            $original_header = trim(str_replace("\xEF\xBB\xBF", '', $headers[$i]));
            $header_mapping[$original_header] = $i;
        }

        // Process data rows
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $line_number++;
            
            if (count($data) !== count($headers)) {
                $errors[] = "Line {$line_number}: Column count mismatch";
                continue;
            }

            // Create associative array using cleaned headers
            $course_data = array();
            foreach ($header_mapping as $original_header => $index) {
                if (isset($data[$index])) {
                    $course_data[$original_header] = $data[$index];
                }
            }
            
            // Validate required fields
            $line_valid = true;
            foreach ($required_columns as $required_col) {
                if (empty(trim($course_data[$required_col]))) {
                    $errors[] = "Line {$line_number}: Missing required field '{$required_col}'";
                    $line_valid = false;
                }
            }

            if (!$line_valid) {
                continue;
            }

            // Validate category
            if (!in_array($course_data['category'], $valid_categories)) {
                $errors[] = "Line {$line_number}: Invalid category '{$course_data['category']}'. Valid options: " . implode(', ', $valid_categories);
                continue;
            }

            // Validate CPD points
            if (!is_numeric($course_data['cpd_points']) || $course_data['cpd_points'] <= 0) {
                $errors[] = "Line {$line_number}: Invalid CPD points. Must be a positive number.";
                continue;
            }

            // Validate course type if provided
            if (!empty($course_data['course_type']) && !in_array($course_data['course_type'], $valid_course_types)) {
                $errors[] = "Line {$line_number}: Invalid course type '{$course_data['course_type']}'. Valid options: " . implode(', ', $valid_course_types);
                continue;
            }

            // Set defaults for optional fields
            $course_data['description'] = isset($course_data['description']) ? trim($course_data['description']) : '';
            $course_data['course_type'] = isset($course_data['course_type']) && !empty($course_data['course_type']) ? $course_data['course_type'] : 'online';
            $course_data['duration_minutes'] = isset($course_data['duration_minutes']) && is_numeric($course_data['duration_minutes']) ? intval($course_data['duration_minutes']) : null;
            $course_data['lia_code'] = isset($course_data['lia_code']) ? trim($course_data['lia_code']) : '';
            $course_data['external_url'] = isset($course_data['external_url']) ? trim($course_data['external_url']) : '';

            // Add to courses array
            $courses[] = $course_data;
        }
        fclose($handle);
    } else {
        return array(
            'success' => false,
            'error' => 'Could not read CSV file.'
        );
    }

    // Return results
    if (!empty($errors) && empty($courses)) {
        return array(
            'success' => false,
            'error' => 'CSV validation failed: ' . implode('; ', array_slice($errors, 0, 5)) . (count($errors) > 5 ? '... and ' . (count($errors) - 5) . ' more errors.' : '')
        );
    }

    return array(
        'success' => true,
        'data' => $courses,
        'errors' => $errors
    );
}

/**
 * Import validated courses to database
 */
function iipm_import_courses_to_database($courses, $update_existing = false) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'test_iipm_cpd_courses';
    $category_table = $wpdb->prefix . 'test_iipm_cpd_categories';
    
    $imported = 0;
    $skipped = array();
    $errors = array();

    foreach ($courses as $course) {
        // Get category ID
        $category_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$category_table} WHERE name = %s OR id = %s",
            ucwords(str_replace('_', ' & ', $course['category'])),
            $course['category']
        ));

        // If category not found by name, try by mapping
        if (!$category_id) {
            $category_mapping = array(
                'pensions' => 'Pensions',
                'savings_investment' => 'Savings & Investment',
                'ethics' => 'Ethics',
                'life_assurance' => 'Life Assurance',
                'general' => 'General Insurance',
                'technology' => 'Technology',
                'regulation_compliance' => 'Regulation & Compliance',
                'professional_development' => 'Professional Development'
            );
            
            if (isset($category_mapping[$course['category']])) {
                $category_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$category_table} WHERE name = %s",
                    $category_mapping[$course['category']]
                ));
            }
        }

        if (!$category_id) {
            $errors[] = "Category not found for course: {$course['title']}";
            continue;
        }

        // Check if course already exists
        $existing_course = null;
        if (!empty($course['lia_code'])) {
            $existing_course = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE lia_code = %s",
                $course['lia_code']
            ));
        } else {
            $existing_course = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE title = %s AND provider = %s",
                $course['title'],
                $course['provider']
            ));
        }

        if ($existing_course && !$update_existing) {
            $skipped[] = $course['title'];
            continue;
        }

        // Prepare course data
        $course_data = array(
            'title' => sanitize_text_field($course['title']),
            'description' => sanitize_textarea_field($course['description']),
            'provider' => sanitize_text_field($course['provider']),
            'category_id' => $category_id,
            'cpd_points' => floatval($course['cpd_points']),
            'course_type' => sanitize_text_field($course['course_type']),
            'duration_minutes' => $course['duration_minutes'],
            'lia_code' => sanitize_text_field($course['lia_code']),
            'external_url' => esc_url_raw($course['external_url']),
            'is_active' => 1,
            'approval_status' => 'approved' // Auto-approve imported courses
        );

        $format = array('%s', '%s', '%s', '%d', '%f', '%s', '%d', '%s', '%s', '%d', '%s');

        if ($existing_course && $update_existing) {
            // Update existing course
            $result = $wpdb->update(
                $table_name,
                $course_data,
                array('id' => $existing_course->id),
                $format,
                array('%d')
            );
        } else {
            // Insert new course
            $result = $wpdb->insert($table_name, $course_data, $format);
        }

        if ($result !== false) {
            $imported++;
        } else {
            $errors[] = "Failed to import course: {$course['title']}";
        }
    }

    return array(
        'success' => true,
        'imported' => $imported,
        'skipped' => $skipped,
        'errors' => $errors
    );
}

/**
 * Generate sample LIA courses for testing
 */
function iipm_generate_sample_lia_courses() {
    return array(
        array(
            'title' => 'Pension Scheme Administration Fundamentals',
            'description' => 'Comprehensive overview of pension scheme administration processes and regulations',
            'provider' => 'LIA',
            'category' => 'pensions',
            'cpd_points' => 2.0,
            'course_type' => 'online',
            'duration_minutes' => 120,
            'lia_code' => 'LIA-PSA-001',
            'external_url' => 'https://lia.ie/courses/pension-administration'
        ),
        array(
            'title' => 'Advanced Investment Strategies',
            'description' => 'Deep dive into modern investment portfolio management techniques',
            'provider' => 'IOB',
            'category' => 'savings_investment',
            'cpd_points' => 3.0,
            'course_type' => 'workshop',
            'duration_minutes' => 180,
            'lia_code' => 'IOB-INV-002',
            'external_url' => 'https://iob.ie/courses/investment-strategies'
        ),
        array(
            'title' => 'Professional Ethics in Financial Services',
            'description' => 'Ethical considerations and best practices in financial services',
            'provider' => 'IIPM',
            'category' => 'ethics',
            'cpd_points' => 1.5,
            'course_type' => 'webinar',
            'duration_minutes' => 90,
            'lia_code' => 'IIPM-ETH-001',
            'external_url' => 'https://iipm.ie/webinars/ethics'
        ),
        array(
            'title' => 'Life Assurance Underwriting Principles',
            'description' => 'Modern approaches to life insurance underwriting and risk assessment',
            'provider' => 'LIA',
            'category' => 'life_assurance',
            'cpd_points' => 2.5,
            'course_type' => 'conference',
            'duration_minutes' => 150,
            'lia_code' => 'LIA-LAU-003',
            'external_url' => 'https://lia.ie/courses/underwriting'
        ),
        array(
            'title' => 'Regulatory Compliance Update 2025',
            'description' => 'Latest regulatory changes and compliance requirements',
            'provider' => 'IOB',
            'category' => 'regulation_compliance',
            'cpd_points' => 1.0,
            'course_type' => 'webinar',
            'duration_minutes' => 60,
            'lia_code' => 'IOB-REG-004',
            'external_url' => 'https://iob.ie/webinars/compliance-2025'
        )
    );
}

/**
 * AJAX handler for course search with advanced filtering
 */
function iipm_handle_advanced_course_search() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_nonce')) {
        wp_die('Security check failed');
    }

    global $wpdb;
    
    $search_term = sanitize_text_field($_POST['search_term'] ?? '');
    $category_filter = sanitize_text_field($_POST['category_filter'] ?? '');
    $provider_filter = sanitize_text_field($_POST['provider_filter'] ?? '');
    $points_min = floatval($_POST['points_min'] ?? 0);
    $points_max = floatval($_POST['points_max'] ?? 999);
    
    $table_courses = $wpdb->prefix . 'test_iipm_cpd_courses';
    $table_categories = $wpdb->prefix . 'test_iipm_cpd_categories';
    
    // Build query
    $query = "SELECT c.*, cat.name as category_name 
              FROM {$table_courses} c 
              LEFT JOIN {$table_categories} cat ON c.category_id = cat.id 
              WHERE c.is_active = 1";
    
    $query_params = array();
    
    // Add search term filter
    if (!empty($search_term)) {
        $query .= " AND (c.title LIKE %s OR c.description LIKE %s OR c.provider LIKE %s)";
        $search_param = '%' . $wpdb->esc_like($search_term) . '%';
        $query_params[] = $search_param;
        $query_params[] = $search_param;
        $query_params[] = $search_param;
    }
    
    // Add category filter
    if (!empty($category_filter)) {
        $query .= " AND c.category_id = %d";
        $query_params[] = intval($category_filter);
    }
    
    // Add provider filter
    if (!empty($provider_filter)) {
        $query .= " AND c.provider = %s";
        $query_params[] = $provider_filter;
    }
    
    // Add points range filter
    $query .= " AND c.cpd_points >= %f AND c.cpd_points <= %f";
    $query_params[] = $points_min;
    $query_params[] = $points_max;
    
    $query .= " ORDER BY c.title ASC LIMIT 50";
    
    // Execute query
    if (!empty($query_params)) {
        $courses = $wpdb->get_results($wpdb->prepare($query, $query_params));
    } else {
        $courses = $wpdb->get_results($query);
    }
    
    wp_send_json_success(array(
        'courses' => $courses,
        'total_found' => count($courses)
    ));
}

/**
 * AJAX handler for downloading sample CSV
 */
function iipm_handle_download_sample_csv() {
    // Verify nonce
    if (!wp_verify_nonce($_GET['nonce'], 'iipm_download_csv')) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('administrator')) {
        wp_die('Insufficient permissions');
    }
    
    // Generate sample CSV content
    $csv_data = array(
        array('title', 'description', 'provider', 'category', 'cpd_points', 'course_type', 'duration_minutes', 'lia_code', 'external_url'),
        array(
            'Pension Administration Fundamentals',
            'Basic principles of pension scheme administration and regulatory compliance',
            'LIA',
            'pensions',
            '2.0',
            'online',
            '120',
            'LIA001',
            'https://lia.ie/course1'
        ),
        array(
            'Investment Portfolio Management',
            'Advanced investment strategies and portfolio management techniques',
            'IOB',
            'savings_investment',
            '3.0',
            'workshop',
            '180',
            'IOB002',
            'https://iob.ie/course2'
        ),
        array(
            'Professional Ethics in Finance',
            'Ethical considerations and best practices in financial services',
            'IIPM',
            'ethics',
            '1.5',
            'webinar',
            '90',
            'IIPM001',
            'https://iipm.ie/webinar1'
        ),
        array(
            'Life Insurance Underwriting',
            'Modern approaches to life insurance underwriting and risk assessment',
            'LIA',
            'life_assurance',
            '2.5',
            'conference',
            '150',
            'LIA003',
            'https://lia.ie/course3'
        ),
        array(
            'Regulatory Compliance Update 2025',
            'Latest regulatory changes and compliance requirements for financial services',
            'IOB',
            'regulation_compliance',
            '1.0',
            'webinar',
            '60',
            'IOB004',
            'https://iob.ie/webinar1'
        ),
        array(
            'Savings and Investment Planning',
            'Comprehensive guide to savings and investment planning strategies',
            'IIPM',
            'savings_investment',
            '2.0',
            'online',
            '120',
            'IIPM002',
            'https://iipm.ie/course2'
        ),
        array(
            'Pension Scheme Design',
            'Advanced pension scheme design and implementation',
            'LIA',
            'pensions',
            '3.0',
            'workshop',
            '240',
            'LIA004',
            'https://lia.ie/workshop1'
        ),
        array(
            'Technology in Financial Services',
            'Digital transformation and technology trends in financial services',
            'External',
            'technology',
            '1.5',
            'conference',
            '90',
            'EXT001',
            'https://external-provider.com/tech-conference'
        )
    );
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=sample-courses-template.csv');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Create file handle for output
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8 to ensure proper encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write CSV data
    foreach ($csv_data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Register AJAX handlers
add_action('wp_ajax_iipm_advanced_course_search', 'iipm_handle_advanced_course_search');
add_action('wp_ajax_nopriv_iipm_advanced_course_search', 'iipm_handle_advanced_course_search');
add_action('wp_ajax_iimp_download_sample_csv', 'iipm_handle_download_sample_csv');
add_action('wp_ajax_nopriv_iimp_download_sample_csv', 'iipm_handle_download_sample_csv'); 