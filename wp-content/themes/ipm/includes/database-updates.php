<?php
/**
 * Database Updates and Migrations
 */

/**
 * Update invitations table to add profile_data column
 */
function iipm_update_invitations_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'test_iipm_invitations';
    
    // Check if profile_data column exists
    $column_exists = $wpdb->get_results($wpdb->prepare(
        "SHOW COLUMNS FROM `{$table_name}` LIKE %s",
        'profile_data'
    ));
    
    if (empty($column_exists)) {
        // Add the missing column
        $sql = "ALTER TABLE `{$table_name}` ADD COLUMN `profile_data` TEXT NULL AFTER `organisation_id`";
        
        $result = $wpdb->query($sql);
        
        if ($result === false) {
            error_log('IIPM: Failed to add profile_data column: ' . $wpdb->last_error);
            return false;
        } else {
            error_log('IIPM: Successfully added profile_data column to invitations table');
            return true;
        }
    } else {
        error_log('IIPM: profile_data column already exists');
        return true;
    }
}

/**
 * Run all database updates
 */
function iipm_run_database_updates() {
    // Update invitations table
    iipm_update_invitations_table();
    
    // You can add more database updates here in the future
    
    // Update the database version
    update_option('iipm_db_version', '1.1');
}

/**
 * Check if database updates are needed
 */
function iipm_check_database_updates() {
    $current_version = get_option('iipm_db_version', '1.0');
    $required_version = '1.1';
    
    if (version_compare($current_version, $required_version, '<')) {
        iipm_run_database_updates();
    }
}

// Run database updates on admin init
add_action('admin_init', 'iipm_check_database_updates');

/**
 * Manual database update function (for testing)
 */
function iipm_manual_database_update() {
    if (!current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_portal_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $result = iipm_update_invitations_table();
    
    if ($result) {
        wp_send_json_success('Database updated successfully');
    } else {
        wp_send_json_error('Failed to update database');
    }
}
add_action('wp_ajax_iipm_update_database', 'iipm_manual_database_update');
?>
