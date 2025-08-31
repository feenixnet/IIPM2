<?php
/**
 * Create Leave Request Tables
 * 
 * @package IPM
 */

/**
 * Create Leave Request Tables
 */
function iipm_create_leave_request_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Leave requests table
    $table_leave_requests = $wpdb->prefix . 'test_iipm_leave_requests';
    $sql_leave_requests = "CREATE TABLE $table_leave_requests (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        title varchar(255) NOT NULL,
        reason varchar(100) NOT NULL,
        leave_start_date date NOT NULL,
        leave_end_date date NOT NULL,
        duration_days int(11) NOT NULL,
        description text NULL,
        status enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
        approved_by bigint(20) NULL,
        approved_at timestamp NULL,
        rejection_reason text NULL,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY status (status),
        KEY leave_start_date (leave_start_date)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_leave_requests);
    
    // Check if table was created successfully
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_leave_requests'");
    
    if ($table_exists) {
        echo "✅ Leave requests table created successfully!\n";
        
        // Insert sample data for testing
        $sample_data = array(
            array(
                'user_id' => 1,
                'title' => 'Annual Leave - Summer Holiday',
                'reason' => 'vacation',
                'leave_start_date' => date('Y-m-d', strtotime('+30 days')),
                'leave_end_date' => date('Y-m-d', strtotime('+37 days')),
                'duration_days' => 7,
                'description' => 'Family vacation to Spain',
                'status' => 'pending'
            ),
            array(
                'user_id' => 1,
                'title' => 'Sick Leave',
                'reason' => 'sick',
                'leave_start_date' => date('Y-m-d', strtotime('-5 days')),
                'leave_end_date' => date('Y-m-d', strtotime('-3 days')),
                'duration_days' => 2,
                'description' => 'Flu symptoms',
                'status' => 'approved'
            )
        );
        
        foreach ($sample_data as $data) {
            $wpdb->insert($table_leave_requests, $data);
        }
        
        echo "✅ Sample leave request data inserted!\n";
    } else {
        echo "❌ Failed to create leave requests table\n";
    }
}

// Run the function
iipm_create_leave_request_tables();

echo "\n📋 Leave Request System Setup Complete!\n";
echo "🔗 Leave Request Page: " . home_url('/leave-request/') . "\n";
echo "⚖️ Leave Admin Page: " . home_url('/leave-admin/') . "\n";
?>
