<?php
/**
 * Test Leave Request System
 * 
 * @package IPM
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "🧪 Testing Leave Request System...\n\n";

// Test 1: Check if tables exist
echo "1. Checking database tables...\n";
global $wpdb;

$table_name = $wpdb->prefix . 'test_iipm_leave_requests';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

if ($table_exists) {
    echo "✅ Leave requests table exists\n";
    
    // Check table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    echo "📋 Table columns: " . count($columns) . "\n";
    
    foreach ($columns as $column) {
        echo "   - {$column->Field} ({$column->Type})\n";
    }
} else {
    echo "❌ Leave requests table does not exist\n";
    echo "Run create-leave-request-tables.php first\n";
    exit;
}

echo "\n";

// Test 2: Check if functions are loaded
echo "2. Checking functions...\n";

$functions_to_check = array(
    'iipm_get_user_leave_requests',
    'iipm_get_all_leave_requests',
    'iipm_format_leave_status',
    'iipm_format_leave_reason',
    'iipm_validate_leave_dates',
    'iipm_get_leave_reasons'
);

foreach ($functions_to_check as $function) {
    if (function_exists($function)) {
        echo "✅ Function $function exists\n";
    } else {
        echo "❌ Function $function missing\n";
    }
}

echo "\n";

// Test 3: Test data retrieval
echo "3. Testing data retrieval...\n";

$total_requests = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
echo "📊 Total leave requests in database: $total_requests\n";

if ($total_requests > 0) {
    // Get sample data
    $sample_request = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");
    echo "📋 Sample request:\n";
    echo "   - ID: {$sample_request->id}\n";
    echo "   - Title: {$sample_request->title}\n";
    echo "   - Status: {$sample_request->status}\n";
    echo "   - Duration: {$sample_request->duration_days} days\n";
}

echo "\n";

// Test 4: Test helper functions
echo "4. Testing helper functions...\n";

// Test status formatting
$status_test = iipm_format_leave_status('pending');
echo "✅ Status formatting: {$status_test['icon']} {$status_test['label']}\n";

// Test reason formatting
$reason_test = iipm_format_leave_reason('vacation');
echo "✅ Reason formatting: {$reason_test['icon']} {$reason_test['label']}\n";

// Test date validation
$date_errors = iipm_validate_leave_dates('2024-01-01', '2024-01-05');
if (empty($date_errors)) {
    echo "✅ Date validation working (no errors for valid dates)\n";
} else {
    echo "⚠️ Date validation returned errors: " . implode(', ', $date_errors) . "\n";
}

// Test invalid dates
$invalid_date_errors = iipm_validate_leave_dates('2024-01-05', '2024-01-01');
if (!empty($invalid_date_errors)) {
    echo "✅ Date validation working (caught invalid date range)\n";
} else {
    echo "❌ Date validation not working properly\n";
}

echo "\n";

// Test 5: Test AJAX handlers
echo "5. Checking AJAX handlers...\n";

$ajax_actions = array(
    'iipm_submit_leave_request',
    'iipm_cancel_leave_request',
    'iipm_approve_leave_request',
    'iipm_reject_leave_request'
);

foreach ($ajax_actions as $action) {
    if (has_action("wp_ajax_$action")) {
        echo "✅ AJAX handler $action registered\n";
    } else {
        echo "❌ AJAX handler $action not registered\n";
    }
}

echo "\n";

// Test 6: Check page templates
echo "6. Checking page templates...\n";

$template_files = array(
    'template-leave-request.php',
    'template-leave-admin.php'
);

foreach ($template_files as $template) {
    $template_path = get_template_directory() . '/' . $template;
    if (file_exists($template_path)) {
        echo "✅ Template $template exists\n";
    } else {
        echo "❌ Template $template missing\n";
    }
}

echo "\n";

// Test 7: Check pages exist
echo "7. Checking WordPress pages...\n";

$pages_to_check = array(
    'leave-request' => 'Leave Request',
    'leave-admin' => 'Leave Admin'
);

foreach ($pages_to_check as $slug => $title) {
    $page = get_page_by_path($slug);
    if ($page) {
        echo "✅ Page '$title' exists (ID: {$page->ID})\n";
        echo "   URL: " . home_url("/$slug/") . "\n";
    } else {
        echo "❌ Page '$title' missing\n";
        echo "   Run create-leave-admin-page.php to create missing pages\n";
    }
}

echo "\n";

// Test 8: Check user capabilities
echo "8. Checking user capabilities...\n";

$admin_caps = array(
    'manage_leave_requests',
    'approve_leave_requests',
    'reject_leave_requests'
);

$admin_role = get_role('administrator');
if ($admin_role) {
    foreach ($admin_caps as $cap) {
        if ($admin_role->has_cap($cap)) {
            echo "✅ Administrator has capability: $cap\n";
        } else {
            echo "❌ Administrator missing capability: $cap\n";
        }
    }
} else {
    echo "❌ Administrator role not found\n";
}

echo "\n";

// Test Summary
echo "🎯 TEST SUMMARY\n";
echo "================\n";

if ($table_exists) {
    echo "✅ Database: Ready\n";
} else {
    echo "❌ Database: Not ready\n";
}

$functions_loaded = 0;
foreach ($functions_to_check as $function) {
    if (function_exists($function)) {
        $functions_loaded++;
    }
}

echo "📊 Functions: $functions_loaded/" . count($functions_to_check) . " loaded\n";

$templates_exist = 0;
foreach ($template_files as $template) {
    if (file_exists(get_template_directory() . '/' . $template)) {
        $templates_exist++;
    }
}

echo "📄 Templates: $templates_exist/" . count($template_files) . " exist\n";

$pages_exist = 0;
foreach ($pages_to_check as $slug => $title) {
    if (get_page_by_path($slug)) {
        $pages_exist++;
    }
}

echo "📝 Pages: $pages_exist/" . count($pages_to_check) . " exist\n";

if ($table_exists && $functions_loaded === count($functions_to_check) && $templates_exist === count($template_files) && $pages_exist === count($pages_to_check)) {
    echo "\n🎉 Leave Request System is fully functional!\n";
    echo "🔗 Test the system at: " . home_url('/leave-request/') . "\n";
    echo "⚖️ Admin interface at: " . home_url('/leave-admin/') . "\n";
} else {
    echo "\n⚠️ Leave Request System needs attention\n";
    echo "Please check the failed tests above and run the necessary setup scripts.\n";
}

echo "\n";
?>
