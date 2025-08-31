<?php
/**
 * Create Debug Page for Header System
 * Run this file once to create the debug page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

function create_iipm_debug_page() {
    // Check if debug page already exists
    $existing_page = get_page_by_path('debug-header');
    
    if (!$existing_page) {
        $page_data = array(
            'post_title' => 'Debug Header System',
            'post_name' => 'debug-header',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => '[iipm_debug_page]',
            'post_author' => 1
        );
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id) {
            // Set the debug template
            update_post_meta($page_id, '_wp_page_template', 'template-debug-header.php');
            
            echo "Debug page created successfully! Visit: " . home_url('/debug-header/') . "\n";
            return $page_id;
        } else {
            echo "Failed to create debug page.\n";
            return false;
        }
    } else {
        echo "Debug page already exists: " . home_url('/debug-header/') . "\n";
        return $existing_page->ID;
    }
}

// Auto-create the debug page when this file is included
if (function_exists('wp_insert_post')) {
    create_iipm_debug_page();
}

// Add shortcode for debug content
function iipm_debug_page_shortcode($atts) {
    return '<div id="iipm-debug-content">Debug content will be loaded by the page template.</div>';
}
add_shortcode('iipm_debug_page', 'iipm_debug_page_shortcode');
?>
