<?php
/**
 * Create Leave Admin Page
 * 
 * @package IPM
 */

// Create the Leave Admin page
$page_data = array(
    'post_title' => 'Leave Admin',
    'post_name' => 'leave-admin',
    'post_status' => 'publish',
    'post_type' => 'page',
    'post_content' => '[iipm_leave_admin]'
);

$existing_page = get_page_by_path('leave-admin');
if (!$existing_page) {
    $page_id = wp_insert_post($page_data);
    
    if ($page_id) {
        // Set the template
        update_post_meta($page_id, '_wp_page_template', 'template-leave-admin.php');
        echo "✅ Leave Admin page created successfully!\n";
        echo "📍 URL: " . home_url('/leave-admin/') . "\n";
    } else {
        echo "❌ Failed to create Leave Admin page\n";
    }
} else {
    echo "ℹ️ Leave Admin page already exists\n";
    echo "📍 URL: " . home_url('/leave-admin/') . "\n";
}
?>
