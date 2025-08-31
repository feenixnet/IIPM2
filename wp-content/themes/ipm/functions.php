<?php
/**
 * IPM functions and definitions - Enhanced for Milestone 2 + Leave Requests
 *
 * @package IPM
 */

if ( ! defined( '_S_VERSION' ) ) {
	define( '_S_VERSION', '1.0.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 */
function ipm_setup() {
	load_theme_textDomain( 'ipm', get_template_directory() . '/languages' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );

	register_nav_menus(
		array(
			'menu-1' => esc_html__( 'Primary', 'ipm' ),
			'footer-menu' => __('Footer Menu'),
			'header-menu' => __('Header Menu'),
		)
	);

	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	add_theme_support(
		'custom-background',
		apply_filters(
			'ipm_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'ipm_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 */
function ipm_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'ipm_content_width', 640 );
}
add_action( 'after_setup_theme', 'ipm_content_width', 0 );

/**
 * Register widget area.
 */
function ipm_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Sidebar', 'ipm' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Add widgets here.', 'ipm' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'ipm_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function ipm_scripts() {
	wp_enqueue_style( 'ipm-style', get_stylesheet_uri(), array(), _S_VERSION );
	wp_style_add_data( 'ipm-style', 'rtl', 'replace' );
	// Ensure main theme CSS is loaded first
	wp_enqueue_style('iipm-main-style', get_template_directory_uri() . '/assets/css/main.min.css', array(), _S_VERSION);

	wp_enqueue_script( 'ipm-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true );
	
	// Don't load main.min.js on admin portal pages to avoid JS errors
	$current_template = get_page_template_slug();
	$admin_templates = array('template-cpd-admin.php', 'template-leave-admin.php');
	
	if (!in_array($current_template, $admin_templates)) {
		wp_enqueue_script( 'ipm-script', get_template_directory_uri() . '/assets/js/main.min.js', array(), _S_VERSION, true );
	}

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'ipm_scripts' );

/**
 * DISABLE WORDPRESS ADMIN BAR FOR ALL USERS ON FRONTEND
 */
// Method 1: Disable admin bar for all users on frontend
add_filter('show_admin_bar', '__return_false');

// Method 2: Remove admin bar for specific user roles (alternative approach)
function iipm_disable_admin_bar() {
    if (!current_user_can('administrator')) {
        show_admin_bar(false);
    }
}
// Uncomment the line below if you want to use Method 2 instead of Method 1
// add_action('after_setup_theme', 'iipm_disable_admin_bar');

// Method 3: Remove admin bar completely for all users (most aggressive)
function iipm_remove_admin_bar() {
    remove_action('wp_head', '_admin_bar_bump_cb');
}
add_action('get_header', 'iipm_remove_admin_bar');

// Include theme files (only if they exist)
$theme_includes = array(
    '/inc/custom-header.php',
    '/inc/template-tags.php',
    '/inc/template-functions.php',
    '/inc/customizer.php',
    '/includes/lia-course-importer.php',
    '/includes/cpd-reporting-functions.php',
    '/includes/enhanced-cpd-search.php',
    '/includes/cpd-compliance-monitoring.php',
    '/includes/cpd-certification-functions.php',
    '/includes/cpd-certification-handlers.php'
);

foreach ($theme_includes as $file) {
    $file_path = get_template_directory() . $file;
    if (file_exists($file_path)) {
        require $file_path;
    }
}

if ( defined( 'JETPACK__VERSION' ) ) {
	$jetpack_file = get_template_directory() . '/inc/jetpack.php';
    if (file_exists($jetpack_file)) {
        require $jetpack_file;
    }
}

/**
 * MODULAR HEADER SYSTEM
 * Function to determine which header to use
 */
function iipm_get_header_type() {
    // Get current page template
    $template = get_page_template_slug();
    
    // Define which templates should use the simple header
    $simple_header_templates = array(
        'template-leave-request.php',
        'template-leave-admin.php',
        'template-cpd-courses.php',
        'template-cpd-portal.php',
        'template-cpd-admin.php',
        'template-member-portal.php'
    );
    
    // Check if current template should use simple header
    if (in_array($template, $simple_header_templates)) {
        return 'simple';
    }
    
    // Check for custom meta field override
    $header_type = get_post_meta(get_the_ID(), '_iipm_header_type', true);
    if ($header_type && in_array($header_type, array('simple', 'default'))) {
        return $header_type;
    }
    
    // Default to original header
    return 'default';
}

/**
 * Ensure header files exist and create fallback
 */
function iipm_ensure_header_files() {
    $simple_header_path = get_template_directory() . '/includes/header-simple.php';
    $original_header_path = get_template_directory() . '/header-original.php';
    
    // If simple header doesn't exist, create the includes directory
    if (!file_exists($simple_header_path)) {
        $includes_dir = get_template_directory() . '/includes';
        if (!file_exists($includes_dir)) {
            wp_mkdir_p($includes_dir);
        }
    }
    
    // If original header doesn't exist, copy from header.php
    if (!file_exists($original_header_path)) {
        $header_php = get_template_directory() . '/header.php';
        if (file_exists($header_php)) {
            copy($header_php, $original_header_path);
        }
    }
}

/**
 * Enhanced custom get_header function that chooses the right header
 */
function iipm_load_header() {
    // Ensure header files exist
    iipm_ensure_header_files();
    
    $header_type = iipm_get_header_type();
    
    if ($header_type === 'simple') {
        $simple_header_path = get_template_directory() . '/includes/header-simple.php';
        if (file_exists($simple_header_path)) {
            include $simple_header_path;
        } else {
            // Fallback to default header
            get_header();
        }
    } else {
        $original_header_path = get_template_directory() . '/header-original.php';
        if (file_exists($original_header_path)) {
            include $original_header_path;
        } else {
            // Fallback to default header
            get_header();
        }
    }
}

/**
 * Add meta box to pages for header selection
 */
function iipm_add_header_meta_box() {
    add_meta_box(
        'iipm_header_type',
        'Header Type',
        'iipm_header_meta_box_callback',
        'page',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'iipm_add_header_meta_box');

function iipm_header_meta_box_callback($post) {
    wp_nonce_field('iipm_header_meta_box', 'iipm_header_meta_box_nonce');
    
    $value = get_post_meta($post->ID, '_iipm_header_type', true);
    
    echo '<label for="iipm_header_type_field">Choose header type:</label>';
    echo '<select id="iipm_header_type_field" name="iipm_header_type_field" style="width: 100%; margin-top: 5px;">';
    echo '<option value="">Auto (based on template)</option>';
    echo '<option value="default"' . selected($value, 'default', false) . '>Original Header</option>';
    echo '<option value="simple"' . selected($value, 'simple', false) . '>Simple Header</option>';
    echo '</select>';
    echo '<p style="margin-top: 10px; font-size: 12px; color: #666;">Leave as "Auto" to use the default header for the page template, or override manually.</p>';
}

function iipm_save_header_meta_box($post_id) {
    if (!isset($_POST['iipm_header_meta_box_nonce'])) {
        return;
    }
    
    if (!wp_verify_nonce($_POST['iipm_header_meta_box_nonce'], 'iipm_header_meta_box')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id)) {
            return;
        }
    } else {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }
    
    if (!isset($_POST['iipm_header_type_field'])) {
        return;
    }
    
    $header_type = sanitize_text_field($_POST['iipm_header_type_field']);
    update_post_meta($post_id, '_iipm_header_type', $header_type);
}
add_action('save_post', 'iipm_save_header_meta_box');

/**
 * FIXED: Alternative email sending method for local development
 */
function iipm_send_email_alternative($null, $atts) {
    // Only use this in local development
    if (defined('WP_DEBUG') && WP_DEBUG) {
        // Extract email data from $atts array
        $to = is_array($atts['to']) ? implode(', ', $atts['to']) : $atts['to'];
        $subject = $atts['subject'];
        $message = $atts['message'];
        $headers = $atts['headers'];
        
        // Log the email instead of sending it
        error_log('IIPM Email Debug:');
        error_log('To: ' . $to);
        error_log('Subject: ' . $subject);
        error_log('Message: ' . $message);
        error_log('Headers: ' . print_r($headers, true));
        
        // Create a file with the email content
        $upload_dir = wp_upload_dir();
        $email_log_dir = $upload_dir['basedir'] . '/email-logs';
        
        if (!file_exists($email_log_dir)) {
            wp_mkdir_p($email_log_dir);
        }
        
        $filename = $email_log_dir . '/email-' . time() . '-' . md5($to . $subject) . '.html';
        
        $email_content = "
        <html>
        <head>
            <title>Email Debug - Local Development</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { background: #f0f0f0; padding: 10px; border-radius: 5px; }
                .content { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>📧 Email Debug - Local Development</h2>
                <p><strong>To:</strong> {$to}</p>
                <p><strong>Subject:</strong> {$subject}</p>
                <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
            </div>
            <div class='content'>
                <h3>Message Content:</h3>
                {$message}
            </div>
        </body>
        </html>
        ";
        
        file_put_contents($filename, $email_content);
        
        // Log to WordPress debug log
        error_log('IIPM: Email logged to file: ' . $filename);
        
        return true; // Return true to indicate "success" and prevent actual sending
    }
    
    // Return null to allow normal wp_mail processing
    return null;
}

// Contact Form 7 customization
function custom_wpcf7_remove_p_tags($form) {
    $form = preg_replace('/<p(.*?)>/i', '', $form); 
    $form = preg_replace('/<\/p>/i', '', $form); 
    return $form;
}
add_filter('wpcf7_form_elements', 'custom_wpcf7_remove_p_tags');

// Custom Post Types
function custom_post_type_events() {
    $labels = array(
        'name'               => 'Events',
        'singular_name'      => 'Event',
        'menu_name'          => 'Events',
        'name_admin_bar'     => 'Event',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Event',
        'new_item'           => 'New Event',
        'edit_item'          => 'Edit Event',
        'view_item'          => 'View Event',
        'all_items'          => 'All Events',
        'search_items'       => 'Search Events',
        'not_found'          => 'No events found',
        'not_found_in_trash' => 'No events found in Trash'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'events'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-calendar',
        'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields')
    );

    register_post_type('events', $args);
}
add_action('init', 'custom_post_type_events');

function add_query_vars_filter($vars) {
    $vars[] = "event_id";
    return $vars;
}
add_filter('query_vars', 'add_query_vars_filter');

function custom_post_type_downloads() {
    $labels = array(
        'name'               => 'Downloads',
        'singular_name'      => 'Download',
        'menu_name'          => 'Downloads',
        'name_admin_bar'     => 'Download',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Download',
        'new_item'           => 'New Download',
        'edit_item'          => 'Edit Download',
        'view_item'          => 'View Download',
        'all_items'          => 'All Downloads',
        'search_items'       => 'Search Downloads',
        'not_found'          => 'No downloads found.',
        'not_found_in_trash' => 'No downloads found in Trash.',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'show_in_menu'       => true,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-download',
        'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'taxonomies'         => array('download_category'),
        'rewrite'            => array('slug' => 'downloads'),
    );

    register_post_type('downloads', $args);
}

function create_download_category_taxonomy() {
    $labels = array(
        'name'              => 'Download Categories',
        'singular_name'     => 'Download Category',
        'search_items'      => 'Search Categories',
        'all_items'         => 'All Categories',
        'parent_item'       => 'Parent Category',
        'parent_item_colon' => 'Parent Category:',
        'edit_item'         => 'Edit Category',
        'update_item'       => 'Update Category',
        'add_new_item'      => 'Add New Category',
        'new_item_name'     => 'New Category Name',
        'menu_name'         => 'Categories',
    );

    $args = array(
        'labels'       => $labels,
        'hierarchical' => true,
        'public'       => true,
        'show_ui'      => true,
        'show_admin_column' => true,
        'rewrite'      => array('slug' => 'download-category'),
    );

    register_taxonomy('download_category', array('downloads'), $args);
}

add_action('init', 'custom_post_type_downloads');
add_action('init', 'create_download_category_taxonomy');

function enqueue_swiper() {
    wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css');
    wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_swiper');

/**
 * Include IIPM custom files - UPDATED FOR MILESTONE 2 + LEAVE REQUESTS
 */
$iipm_includes = array(
    get_template_directory() . '/includes/enhanced-member-registration.php',
    get_template_directory() . '/includes/enhanced-ajax-handlers.php',
    get_template_directory() . '/includes/user-activity-logger.php',
    get_template_directory() . '/includes/database-updates.php',
    get_template_directory() . '/includes/organisation-management-handlers.php',
    get_template_directory() . '/includes/direct-admin-assignment.php',
    get_template_directory() . '/includes/user-management-handlers.php',
    get_template_directory() . '/includes/cpd-database-setup.php',
    get_template_directory() . '/includes/cpd-management-handlers.php',
    get_template_directory() . '/includes/cpd-reporting-functions.php',
    get_template_directory() . '/includes/lia-course-importer.php',
    get_template_directory() . '/includes/enhanced-cpd-search.php',
    get_template_directory() . '/includes/leave-request-functions.php',
    get_template_directory() . '/includes/leave-admin-handlers.php',
    get_template_directory() . '/includes/navigation-manager.php'
);

foreach ($iipm_includes as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

/**
 * IIPM Enhanced Database Tables creation - UPDATED FOR MILESTONE 2 + LEAVE REQUESTS
 */
function iipm_create_enhanced_tables() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	// Organisations table
	$table_organisations = $wpdb->prefix . 'test_iipm_organisations';
	$sql_organisations = "CREATE TABLE $table_organisations (
		id int(11) NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL,
		contact_email varchar(255) NOT NULL,
		contact_phone varchar(50) NULL,
		address_line1 varchar(255) NULL,
		address_line2 varchar(255) NULL,
		city varchar(100) NULL,
		county varchar(100) NULL,
		eircode varchar(20) NULL,
		country varchar(100) DEFAULT 'Ireland',
		admin_user_id bigint(20) NULL,
		billing_contact varchar(255) NULL,
		payment_method enum('invoice','card','bank_transfer') DEFAULT 'invoice',
		is_active tinyint(1) DEFAULT 1,
		created_at timestamp DEFAULT CURRENT_TIMESTAMP,
		updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY admin_user_id (admin_user_id),
		KEY name (name)
	) $charset_collate;";

	// Members table
	$table_members = $wpdb->prefix . 'test_iipm_members';
	$sql_members = "CREATE TABLE $table_members (
		id int(11) NOT NULL AUTO_INCREMENT,
		user_id bigint(20) NOT NULL,
		member_type enum('individual', 'organisation') NOT NULL DEFAULT 'individual',
		membership_level enum('free', 'member', 'associate', 'fellow', 'council') NOT NULL DEFAULT 'free',
		membership_status enum('active', 'inactive', 'pending', 'suspended') NOT NULL DEFAULT 'pending',
		organisation_id int(11) NULL,
		employee_id varchar(50) NULL,
		qualification_date date NULL,
		membership_start_date date NULL,
		membership_end_date date NULL,
		cpd_points_required int(11) DEFAULT 40,
		cpd_points_current int(11) DEFAULT 0,
		communication_preferences text NULL,
		gdpr_consent tinyint(1) DEFAULT 0,
		marketing_consent tinyint(1) DEFAULT 0,
		email_verified tinyint(1) DEFAULT 0,
		profile_completed tinyint(1) DEFAULT 0,
		last_login timestamp NULL,
		created_at timestamp DEFAULT CURRENT_TIMESTAMP,
		updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY user_id (user_id),
		KEY organisation_id (organisation_id),
		KEY membership_status (membership_status),
		KEY membership_level (membership_level)
	) $charset_collate;";

	// Member profiles table
	$table_profiles = $wpdb->prefix . 'test_iipm_member_profiles';
	$sql_profiles = "CREATE TABLE $table_profiles (
		id int(11) NOT NULL AUTO_INCREMENT,
		user_id bigint(20) NOT NULL,
		user_phone varchar(50) NULL,
		work_email varchar(255) NULL,
		user_mobile varchar(50) NULL,
		employer_name varchar(255) NULL,
		other_qualifications text NULL,
		professional_designation varchar(255) NULL,
		emergency_contact_name varchar(255) NULL,
		emergency_contact_phone varchar(50) NULL,
		dietary_requirements text NULL,
		accessibility_requirements text NULL,
		created_at timestamp DEFAULT CURRENT_TIMESTAMP,
		updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY user_id (user_id)
	) $charset_collate;";

	// UPDATED: Invitations table with profile_data column
	$table_invitations = $wpdb->prefix . 'test_iipm_invitations';
	$sql_invitations = "CREATE TABLE $table_invitations (
		id int(11) NOT NULL AUTO_INCREMENT,
		email varchar(255) NOT NULL,
		token varchar(255) NOT NULL,
		invitation_type enum('individual', 'bulk', 'organisation_admin') NOT NULL DEFAULT 'individual',
		invited_by bigint(20) NOT NULL,
		organisation_id int(11) NULL,
		profile_data text NULL,
		expires_at timestamp NOT NULL,
		used_at timestamp NULL,
		created_at timestamp DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY token (token),
		KEY email (email),
		KEY expires_at (expires_at)
	) $charset_collate;";

	// Bulk imports table
	$table_imports = $wpdb->prefix . 'test_iipm_bulk_imports';
	$sql_imports = "CREATE TABLE $table_imports (
		id int(11) NOT NULL AUTO_INCREMENT,
		filename varchar(255) NOT NULL,
		total_records int(11) NOT NULL,
		successful_imports int(11) DEFAULT 0,
		failed_imports int(11) DEFAULT 0,
		import_type enum('members','organisations') NOT NULL,
		imported_by bigint(20) NOT NULL,
		organisation_id int(11) NULL,
		status enum('processing','completed','failed') DEFAULT 'processing',
		error_log text NULL,
		created_at timestamp DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY imported_by (imported_by),
		KEY organisation_id (organisation_id)
	) $charset_collate;";

	// User activity logging table
	$table_activity = $wpdb->prefix . 'test_iipm_user_activity';
	$sql_activity = "CREATE TABLE $table_activity (
		id int(11) NOT NULL AUTO_INCREMENT,
		user_id bigint(20) NOT NULL,
		action varchar(100) NOT NULL,
		description text NULL,
		ip_address varchar(45) NULL,
		user_agent text NULL,
		created_at timestamp DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY user_id (user_id),
		KEY action (action),
		KEY created_at (created_at)
	) $charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql_organisations);
	dbDelta($sql_members);
	dbDelta($sql_profiles);
	dbDelta($sql_invitations);
	dbDelta($sql_imports);
	dbDelta($sql_activity);

	// MILESTONE 2: Create CPD tables
	iipm_create_cpd_tables();

    // Create notifications table
    iipm_create_notifications_table();

    // Remove this line:
    // iipm_create_leave_request_tables();

	// Insert sample organisations
	$existing_orgs = $wpdb->get_var("SELECT COUNT(*) FROM $table_organisations");
	if ($existing_orgs == 0) {
		$wpdb->insert($table_organisations, array(
			'name' => 'Griffith College',
			'contact_email' => 'info@griffith.ie',
			'contact_phone' => '+353 1 415 0400',
			'address_line1' => 'South Circular Road',
			'city' => 'Dublin',
			'county' => 'Dublin',
			'eircode' => 'D08 V04N'
		));

		$wpdb->insert($table_organisations, array(
			'name' => 'Irish Life',
			'contact_email' => 'info@irishlife.ie',
			'contact_phone' => '+353 1 704 2000',
			'address_line1' => 'Irish Life Centre',
			'city' => 'Dublin',
			'county' => 'Dublin',
			'eircode' => 'D01 W5P4'
		));

		$wpdb->insert($table_organisations, array(
			'name' => 'Zurich Insurance',
			'contact_email' => 'info@zurich.ie',
			'contact_phone' => '+353 1 667 9666',
			'address_line1' => 'Zurich House',
			'city' => 'Dublin',
			'county' => 'Dublin',
			'eircode' => 'D02 XK91'
		));
	}
}

// Hook to create tables on theme activation
add_action('after_switch_theme', 'iipm_create_enhanced_tables');

/**
 * IIPM Enhanced User Roles - UPDATED FOR MILESTONE 2 + LEAVE REQUESTS
 */
function iipm_create_enhanced_user_roles() {
	// Remove existing roles first
	remove_role('iipm_member');
	remove_role('iipm_corporate_admin');
	remove_role('iipm_council_member');
	remove_role('iipm_admin');

	// IIPM Member Role - UPDATED WITH CPD CAPABILITIES + LEAVE REQUESTS
	add_role('iipm_member', 'IIPM Member', array(
		'read' => true,
		'access_member_portal' => true,
		'view_member_dashboard' => true,
		'manage_own_profile' => true,
		'log_cpd_hours' => true,
		'download_certificates' => true,
		'register_for_events' => true,
		'submit_leave_requests' => true,
		'view_own_invoices' => true,
		'view_cpd_records' => true,
		'submit_cpd_entries' => true,
		'access_cpd_library' => true
	));

	// Corporate Admin Role - UPDATED WITH LEAVE REQUESTS
	add_role('iipm_corporate_admin', 'Corporate Admin', array(
		'read' => true,
		'access_member_portal' => true,
		'view_member_dashboard' => true,
		'manage_own_profile' => true,
		'manage_organisation_members' => true,
		'view_organisation_dashboard' => true,
		'process_bulk_payments' => true,
		'view_employee_reports' => true,
		'bulk_import_members' => true,
		'view_organisation_invoices' => true,
		'submit_leave_requests' => true
	));

	// Council Member Role - UPDATED WITH LEAVE REQUESTS
	add_role('iipm_council_member', 'Council Member', array(
		'read' => true,
		'access_member_portal' => true,
		'view_member_dashboard' => true,
		'manage_own_profile' => true,
		'log_cpd_hours' => true,
		'download_certificates' => true,
		'register_for_events' => true,
		'submit_leave_requests' => true,
		'access_document_repository' => true,
		'access_council_resources' => true,
		'view_own_invoices' => true
	));

	// IIPM Admin Role - UPDATED WITH CPD CAPABILITIES + LEAVE MANAGEMENT
	add_role('iipm_admin', 'IIPM Admin', array(
		'read' => true,
		'manage_iipm_members' => true,
		'manage_organisations' => true,
		'approve_cpd_courses' => true,
		'generate_reports' => true,
		'manage_bulk_imports' => true,
		'send_notifications' => true,
		'manage_invitations' => true,
		'view_user_activity' => true,
		'manage_system_settings' => true,
		'manage_cpd_courses' => true,
		'review_cpd_submissions' => true,
		'generate_cpd_reports' => true,
		'approve_external_cpd' => true,
		'manage_cpd_categories' => true,
		'manage_leave_requests' => true,
		'approve_leave_requests' => true,
		'reject_leave_requests' => true
	));

	// Add capabilities to administrator - UPDATED WITH CPD CAPABILITIES + LEAVE MANAGEMENT
	$admin = get_role('administrator');
	if ($admin) {
		$admin->add_cap('manage_iipm_members');
		$admin->add_cap('manage_organisations');
		$admin->add_cap('approve_cpd_courses');
		$admin->add_cap('generate_reports');
		$admin->add_cap('manage_bulk_imports');
		$admin->add_cap('send_notifications');
		$admin->add_cap('manage_invitations');
		$admin->add_cap('view_user_activity');
		$admin->add_cap('manage_system_settings');
		$admin->add_cap('manage_cpd_courses');
		$admin->add_cap('review_cpd_submissions');
		$admin->add_cap('generate_cpd_reports');
		$admin->add_cap('approve_external_cpd');
		$admin->add_cap('manage_cpd_categories');
		$admin->add_cap('manage_leave_requests');
		$admin->add_cap('approve_leave_requests');
		$admin->add_cap('reject_leave_requests');
	}
}
add_action('init', 'iipm_create_enhanced_user_roles');

/**
 * Helper functions
 */
function iipm_user_can($capability, $user_id = null) {
	if (!$user_id) {
		$user_id = get_current_user_id();
	}
	
	$user = get_user_by('id', $user_id);
	if (!$user) {
		return false;
	}
	
	return user_can($user, $capability);
}

function iipm_get_user_membership_level($user_id = null) {
	if (!$user_id) {
		$user_id = get_current_user_id();
	}
	
	global $wpdb;
	$member = $wpdb->get_row($wpdb->prepare(
		"SELECT membership_level FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d",
		$user_id
	));
	
	return $member ? $member->membership_level : 'free';
}

function iipm_is_organisation_admin($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    // Check if user has the corporate admin role
    $user = get_user_by('id', $user_id);
    if ($user && in_array('iipm_corporate_admin', $user->roles)) {
        return true;
    }
    
    // Check if user is set as admin_user_id in any organization
    global $wpdb;
    $org = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}test_iipm_organisations WHERE admin_user_id = %d",
        $user_id
    ));
    
    return !empty($org);
}

/**
 * FIXED: Bulk Import Functions - Only create invitations, not user accounts
 */
function iipm_get_recent_imports($user_id, $organisation_id = null) {
	global $wpdb;
	
	$where_clause = "WHERE imported_by = %d";
	$params = array($user_id);
	
	if ($organisation_id) {
		$where_clause .= " AND organisation_id = %d";
		$params[] = $organisation_id;
	}
	
	$sql = "SELECT i.*, o.name as organisation_name 
			FROM {$wpdb->prefix}test_iipm_bulk_imports i
			LEFT JOIN {$wpdb->prefix}test_iipm_organisations o ON i.organisation_id = o.id
			{$where_clause}
			ORDER BY i.created_at DESC 
			LIMIT 10";
	
	return $wpdb->get_results($wpdb->prepare($sql, $params));
}

/**
 * FIXED: Process bulk import - Create invitations only, not user accounts
 */
function iipm_process_bulk_import($file_path, $organisation_id, $options = array()) {
	global $wpdb;
	
	$results = array(
		'total' => 0,
		'successful' => 0,
		'failed' => 0,
		'errors' => array(),
		'successful_records' => array(),
		'invitations_sent' => array()
	);
	
	// Parse CSV file
	if (!file_exists($file_path)) {
		return array('success' => false, 'error' => 'File not found');
	}
	
	$csv_data = array();
	if (($handle = fopen($file_path, 'r')) !== FALSE) {
		$header = fgetcsv($handle);
		if (!$header) {
			fclose($handle);
			return array('success' => false, 'error' => 'Invalid CSV file');
		}
		
		// Validate required columns
		$required_columns = array('first_name', 'last_name', 'email');
		$missing_columns = array_diff($required_columns, $header);
		if (!empty($missing_columns)) {
			fclose($handle);
			return array('success' => false, 'error' => 'Missing required columns: ' . implode(', ', $missing_columns));
		}
		
		$row_number = 1;
		while (($row = fgetcsv($handle)) !== FALSE) {
			$row_number++;
			if (count($row) === count($header)) {
				$csv_data[] = array_combine($header, $row);
			} else {
				$results['errors'][] = array(
					'row' => $row_number,
					'email' => isset($row[2]) ? $row[2] : 'N/A',
					'message' => 'Invalid number of columns'
				);
				$results['failed']++;
			}
		}
		fclose($handle);
	} else {
		return array('success' => false, 'error' => 'Could not read CSV file');
	}
	
	$results['total'] = count($csv_data);
	
	// Process each record - CREATE INVITATIONS ONLY
	foreach ($csv_data as $index => $record) {
		try {
			// Validate required fields
			if (empty($record['first_name']) || empty($record['last_name']) || empty($record['email'])) {
				$results['errors'][] = array(
					'row' => $index + 2,
					'email' => $record['email'] ?? 'N/A',
					'message' => 'Missing required fields (first_name, last_name, or email)'
				);
				$results['failed']++;
				continue;
			}
			
			// Validate email
			if (!is_email($record['email'])) {
				$results['errors'][] = array(
					'row' => $index + 2,
					'email' => $record['email'],
					'message' => 'Invalid email address'
				);
				$results['failed']++;
				continue;
			}
			
			// Check if email already exists
			if (email_exists($record['email'])) {
				if (isset($options['skip_existing']) && $options['skip_existing']) {
					$results['errors'][] = array(
						'row' => $index + 2,
						'email' => $record['email'],
						'message' => 'Email already exists (skipped)'
					);
					$results['failed']++;
					continue;
				} else {
					$results['errors'][] = array(
						'row' => $index + 2,
						'email' => $record['email'],
						'message' => 'Email already exists'
					);
					$results['failed']++;
					continue;
				}
			}
			
			// Check if invitation already exists
			$existing_invitation = $wpdb->get_row($wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}test_iipm_invitations 
				 WHERE email = %s AND used_at IS NULL AND expires_at > NOW()",
				$record['email']
			));
			
			if ($existing_invitation) {
				$results['errors'][] = array(
					'row' => $index + 2,
					'email' => $record['email'],
					'message' => 'Invitation already sent to this email'
				);
				$results['failed']++;
				continue;
			}
			
			// Prepare profile data for invitation
			$profile_data = array(
				'first_name' => sanitize_text_field($record['first_name']),
				'last_name' => sanitize_text_field($record['last_name']),
				'email' => sanitize_email($record['email']),
				'member_type' => 'organisation',
				'organisation_id' => $organisation_id,
				'user_phone' => sanitize_text_field($record['user_phone'] ?? ''),
				'work_email' => sanitize_email($record['work_email'] ?? ''),
				'user_mobile' => sanitize_text_field($record['user_mobile'] ?? ''),
				'employer_name' => sanitize_text_field($record['employer_name'] ?? ''),
				'professional_designation' => sanitize_text_field($record['professional_designation'] ?? ''),
				'gdpr_consent' => 1,
				'marketing_consent' => 0
			);
			
			// Send bulk invitation with profile data
			$invitation_result = iipm_send_bulk_invitation(
				$record['email'], 
				$organisation_id, 
				$profile_data
			);
			
			if ($invitation_result['success']) {
				$results['successful_records'][] = array(
					'name' => $record['first_name'] . ' ' . $record['last_name'],
					'email' => $record['email']
				);
				
				$results['invitations_sent'][] = array(
					'name' => $record['first_name'] . ' ' . $record['last_name'],
					'email' => $record['email']
				);
				
				$results['successful']++;
				
				// Log activity
				iipm_log_user_activity(
					get_current_user_id(), 
					'bulk_invitation_sent', 
					"Bulk invitation sent to {$record['email']}"
				);
			} else {
				$results['errors'][] = array(
					'row' => $index + 2,
					'email' => $record['email'],
					'message' => 'Failed to send invitation: ' . $invitation_result['error']
				);
				$results['failed']++;
			}
			
		} catch (Exception $e) {
			$results['errors'][] = array(
				'row' => $index + 2,
				'email' => $record['email'] ?? 'N/A',
				'message' => 'System error: ' . $e->getMessage()
			);
			$results['failed']++;
		}
	}
	
	return array('success' => true, 'data' => $results);
}

/**
 * NEW: Send bulk invitation with profile data
 */
function iipm_send_bulk_invitation($email, $organisation_id, $profile_data) {
	global $wpdb;
	
	try {
		// Get organisation details
		$organisation = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}test_iipm_organisations WHERE id = %d",
			$organisation_id
		));
		
		if (!$organisation) {
			return array('success' => false, 'error' => 'Organisation not found');
		}
		
		// Generate unique token
		$token = wp_generate_password(32, false);
		$expires_at = date('Y-m-d H:i:s', strtotime('+14 days')); // 14 days for bulk invitations
		
		// Insert invitation record with profile data
		$result = $wpdb->insert(
			$wpdb->prefix . 'test_iipm_invitations',
			array(
				'email' => $email,
				'token' => $token,
				'invitation_type' => 'bulk',
				'invited_by' => get_current_user_id(),
				'organisation_id' => $organisation_id,
				'profile_data' => json_encode($profile_data),
				'expires_at' => $expires_at
			),
			array('%s', '%s', '%s', '%d', '%d', '%s', '%s')
		);
		
		if ($result === false) {
			error_log('IIPM: Failed to insert bulk invitation record: ' . $wpdb->last_error);
			return array('success' => false, 'error' => 'Database error: ' . $wpdb->last_error);
		}
		
		// Send welcome invitation email
		$registration_url = home_url('/member-registration/?token=' . $token);
		$subject = 'Welcome to IIPM - Complete Your Registration';
		
		$first_name = $profile_data['first_name'];
		$last_name = $profile_data['last_name'];
		
		$message = "
Dear {$first_name} {$last_name},

You have been invited to join the Irish Institute of Pensions Management (IIPM) as part of {$organisation->name}.

Your organisation has set up an IIPM membership for you. To complete your registration and access your member benefits, please click the link below:

{$registration_url}

As an IIPM member, you'll have access to:
• Professional development courses and CPD tracking
• Industry networking opportunities
• Exclusive member resources and downloads
• Annual conference and event access
• Professional certification and recognition

This invitation will expire in 14 days.

If you have any questions about your membership or the IIPM platform, please contact us at info@iipm.ie or +353 (0)1 613 0874.

Best regards,
IIPM Administration Team

---
Irish Institute of Pensions Management
www.iipm.ie
		";
		
		// Add headers for better email delivery
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: IIPM Portal <' . (defined('SMTP_FROM') ? SMTP_FROM : get_option('admin_email')) . '>'
		);
		
		// Try to send email
		$email_sent = wp_mail($email, $subject, $message, $headers);
		
		if (!$email_sent) {
			error_log('IIPM: Failed to send bulk invitation email to: ' . $email);
			
			// For debugging in local environment
			if (defined('WP_DEBUG') && WP_DEBUG) {
				return array('success' => true, 'token' => $token, 'note' => 'Email logged locally instead of sending');
			}
			
			return array('success' => false, 'error' => 'Failed to send email. Please check email configuration.');
		}
		
		return array('success' => true, 'token' => $token);
		
	} catch (Exception $e) {
		error_log('IIPM: Exception in iipm_send_bulk_invitation: ' . $e->getMessage());
		return array('success' => false, 'error' => 'System error: ' . $e->getMessage());
	}
}

/**
 * User Activity Logging
 */
function iipm_log_user_activity($user_id, $action, $description = '', $additional_data = array()) {
	global $wpdb;
	
	$ip_address = iipm_get_user_ip();
	$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
	
	if (!empty($additional_data)) {
		$description .= ' | Data: ' . json_encode($additional_data);
	}
	
	$wpdb->insert(
		$wpdb->prefix . 'test_iipm_user_activity',
		array(
			'user_id' => $user_id,
			'action' => $action,
			'description' => $description,
			'ip_address' => $ip_address,
			'user_agent' => $user_agent
		),
		array('%d', '%s', '%s', '%s', '%s')
	);
}

function iipm_get_user_ip() {
	$ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
	
	foreach ($ip_keys as $key) {
		if (array_key_exists($key, $_SERVER) === true) {
			foreach (explode(',', $_SERVER[$key]) as $ip) {
				$ip = trim($ip);
				if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
					return $ip;
				}
			}
		}
	}
	
	return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function iipm_get_user_activity($user_id, $limit = 50) {
	global $wpdb;
	
	return $wpdb->get_results($wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}test_iipm_user_activity 
		 WHERE user_id = %d 
		 ORDER BY created_at DESC 
		 LIMIT %d",
		$user_id,
		$limit
	));
}

/**
 * Login/Logout Activity Logging
 */
function iipm_log_login_activity($user_login, $user) {
	if ($user instanceof WP_User) {
		global $wpdb;
		
		$wpdb->update(
			$wpdb->prefix . 'test_iipm_members',
			array('last_login' => current_time('mysql')),
			array('user_id' => $user->ID),
			array('%s'),
			array('%d')
		);
		
		iipm_log_user_activity($user->ID, 'login', 'User logged in');
	}
}
add_action('wp_login', 'iipm_log_login_activity', 10, 2);

function iipm_log_logout_activity() {
	if (is_user_logged_in()) {
		iipm_log_user_activity(get_current_user_id(), 'logout', 'User logged out');
	}
}
add_action('wp_logout', 'iipm_log_logout_activity');

function iipm_log_profile_update($user_id, $old_user_data) {
	iipm_log_user_activity($user_id, 'profile_update', 'Profile information updated');
}
add_action('profile_update', 'iipm_log_profile_update', 10, 2);

/**
 * Ensure login redirects to dashboard
 */
function iipm_login_redirect($redirect_to, $request, $user) {
    // Only redirect if user login was successful
    if (isset($user->roles) && is_array($user->roles)) {
        // Redirect IIPM users to dashboard
        if (in_array('iipm_member', $user->roles) || 
            in_array('iipm_corporate_admin', $user->roles) || 
            in_array('iipm_council_member', $user->roles) || 
            in_array('iipm_admin', $user->roles)) {
            return home_url('/dashboard/');
        }
    }
    
    return $redirect_to;
}
add_filter('login_redirect', 'iipm_login_redirect', 10, 3);

/**
 * Debug login redirects
 */
function iipm_debug_login_redirect($redirect_to, $request, $user) {
    error_log('IIPM Login Debug:');
    error_log('Original redirect_to: ' . $redirect_to);
    error_log('Request: ' . $request);
    error_log('User roles: ' . print_r($user->roles ?? 'No user', true));
    
    // Check if user login was successful
    if (isset($user->roles) && is_array($user->roles)) {
        // Redirect IIPM users to dashboard
        if (in_array('iipm_member', $user->roles) || 
            in_array('iipm_corporate_admin', $user->roles) || 
            in_array('iipm_council_member', $user->roles) || 
            in_array('iipm_admin', $user->roles)) {
            
            $dashboard_url = home_url('/dashboard/');
            error_log('IIPM: Redirecting to dashboard: ' . $dashboard_url);
            return $dashboard_url;
        }
    }
    
    error_log('IIPM: Using default redirect: ' . $redirect_to);
    return $redirect_to;
}

// Replace the existing filter with higher priority
remove_filter('login_redirect', 'iipm_login_redirect', 10);
add_filter('login_redirect', 'iipm_debug_login_redirect', 999, 3);

/**
 * Alternative login redirect using wp_login hook
 */
function iipm_redirect_after_login($user_login, $user) {
    // Check if this is an IIPM user
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('iipm_member', $user->roles) || 
            in_array('iipm_corporate_admin', $user->roles) || 
            in_array('iipm_council_member', $user->roles) || 
            in_array('iipm_admin', $user->roles)) {
            
            // Only redirect if this is not an AJAX request
            if (!wp_doing_ajax() && !defined('DOING_AJAX')) {
                $dashboard_url = home_url('/dashboard/');
                error_log('IIPM: wp_login hook - redirecting to: ' . $dashboard_url);
                wp_safe_redirect($dashboard_url);
                exit();
            }
        }
    }
}
add_action('wp_login', 'iipm_redirect_after_login', 10, 2);

/**
 * Backfill missing last_login data from user activity logs
 */
function iipm_sync_missing_last_login_data() {
    global $wpdb;
    
    // Get users who have login activities but no last_login timestamp
    $login_activities = $wpdb->get_results("
        SELECT ua.user_id, MAX(ua.created_at) as latest_login 
        FROM {$wpdb->prefix}test_iipm_user_activity ua
        INNER JOIN {$wpdb->prefix}test_iipm_members m ON ua.user_id = m.user_id
        WHERE ua.action = 'login' 
        AND (m.last_login IS NULL OR m.last_login < ua.created_at)
        GROUP BY ua.user_id
    ");
    
    $updated_count = 0;
    foreach ($login_activities as $activity) {
        $result = $wpdb->update(
            $wpdb->prefix . 'test_iipm_members',
            array('last_login' => $activity->latest_login),
            array('user_id' => $activity->user_id),
            array('%s'),
            array('%d')
        );
        if ($result) {
            $updated_count++;
        }
    }
    
    return $updated_count;
}

/**
 * Enhanced Invitation System with Error Handling
 */
function iipm_send_invitation($email, $type = 'individual', $organisation_id = null) {
	global $wpdb;
	
	try {
		// Generate unique token
		$token = wp_generate_password(32, false);
		$expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
		
		// Insert invitation record
		$result = $wpdb->insert(
			$wpdb->prefix . 'test_iipm_invitations',
			array(
				'email' => $email,
				'token' => $token,
				'invitation_type' => $type,
				'invited_by' => get_current_user_id(),
				'organisation_id' => $organisation_id,
				'expires_at' => $expires_at
			),
			array('%s', '%s', '%s', '%d', '%d', '%s')
		);
		
		if ($result === false) {
			error_log('IIPM: Failed to insert invitation record: ' . $wpdb->last_error);
			return array('success' => false, 'error' => 'Database error: ' . $wpdb->last_error);
		}
		
		// Send email
		$registration_url = home_url('/member-registration/?token=' . $token);
		$subject = 'IIPM Membership Invitation';
		
		$message = "
Dear Colleague,

You have been invited to join the Irish Institute of Pensions Management (IIPM).

To complete your registration, please click the link below:
{$registration_url}

This invitation will expire in 7 days.

If you have any questions, please contact us at info@iipm.ie

Best regards,
IIPM Team
		";
		
		// Add headers for better email delivery
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: IIPM Portal <' . (defined('SMTP_FROM') ? SMTP_FROM : get_option('admin_email')) . '>'
        );
		
		// Try to send email
		$email_sent = wp_mail($email, $subject, $message, $headers);
		
		if (!$email_sent) {
			error_log('IIPM: Failed to send invitation email to: ' . $email);
			
			// For debugging in local environment
			if (defined('WP_DEBUG') && WP_DEBUG) {
				// Email will be logged by the alternative method
				return array('success' => true, 'token' => $token, 'note' => 'Email logged locally instead of sending');
			}
			
			return array('success' => false, 'error' => 'Failed to send email. Please check email configuration.');
		}
		
		return array('success' => true, 'token' => $token);
		
	} catch (Exception $e) {
		error_log('IIPM: Exception in iipm_send_invitation: ' . $e->getMessage());
		return array('success' => false, 'error' => 'System error: ' . $e->getMessage());
	}
}

function iipm_validate_invitation_token($token) {
	global $wpdb;
	
	$invitation = $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}test_iipm_invitations 
		 WHERE token = %s AND used_at IS NULL AND expires_at > NOW()",
		$token
	));
	
	return $invitation;
}

/**
 * FIXED: Member Registration Processing - Auto-activate users after successful registration
 */
function iipm_process_member_registration($data, $token = null) {
    try {
        global $wpdb;
        
        $invitation = null;
        if ($token) {
            $invitation = iipm_validate_invitation_token($token);
            if (!$invitation) {
                return array('success' => false, 'error' => 'Invalid or expired invitation');
            }
            
            // For bulk invitations, merge stored profile data with form data
            if ($invitation->invitation_type === 'bulk' && $invitation->profile_data) {
                $stored_profile = json_decode($invitation->profile_data, true);
                if ($stored_profile) {
                    // Merge stored data with form data (form data takes precedence)
                    $data = array_merge($stored_profile, $data);
                }
            }
        }
        
        $email = sanitize_email($data['email']);
        $first_name = sanitize_text_field($data['first_name']);
        $last_name = sanitize_text_field($data['last_name']);
        $postal_address = sanitize_text_field($data['postal_address']);
        $city_or_town = sanitize_text_field($data['city_or_town']);
        $address_line1 = sanitize_text_field($data['address_line_1']);
        $address_line2 = sanitize_text_field($data['address_line_2']);
        $address_line3 = sanitize_text_field($data['address_line_3']);
        $password = $data['password'];
        
        // For invited users, force the member type and organization from the invitation
        if ($invitation) {
            $member_type = $invitation->invitation_type === 'bulk' ? 'organisation' : 'individual';
            $organisation_id = $invitation->organisation_id;
            
            // Fetch organisation name from database if organisation_id exists
            if ($organisation_id) {
                $org = $wpdb->get_row($wpdb->prepare(
                    "SELECT name FROM {$wpdb->prefix}test_iipm_organisations WHERE id = %d",
                    $organisation_id
                ));
                $organisation_name = $org ? $org->name : null;
            } else {
                $organisation_name = null;
            }
        } else {
            $member_type = sanitize_text_field($data['member_type'] ?? 'individual');
            $organisation_id = isset($data['organisation_id']) ? intval($data['organisation_id']) : null;
            $organisation_name = isset($data['organisation_name']) ? sanitize_text_field($data['organisation_name']) : null;
        }
        
        $gdpr_consent = isset($data['gdpr_consent']) ? 1 : 0;
        $marketing_consent = isset($data['marketing_consent']) ? 1 : 0;
        
        // Skip email existence check for invited users since we already validated the email
        if (!$invitation && email_exists($email)) {
            return array('success' => false, 'error' => 'Email already exists');
        }
        
        $user_login = isset($data['login_name']) ? sanitize_user($data['login_name']) : '';
        if ($user_login && strlen($user_login) >= 2) {
            $user_id = wp_create_user($user_login, $password, $email);
        } else {
            $user_id = wp_create_user($email, $password, $email);
        }
        
        if (is_wp_error($user_id)) {
            return array('success' => false, 'error' => $user_id->get_error_message());
        }

        // Validate and set user_login if provided
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
        ));
        
        $role = 'iipm_member';
        if ($organisation_id && iipm_is_organisation_admin_email($email, $organisation_id)) {
            $role = 'iipm_corporate_admin';
        }
        
        $user = new WP_User($user_id);
        $user->set_role($role);
        
        // Set membership status to 'active' for all registrations
        $membership_status = 'active';
        
        $wpdb->insert(
            $wpdb->prefix . 'test_iipm_members',
            array(
                'user_id' => $user_id,
                'member_type' => $member_type,
                'organisation_id' => $organisation_id,
                'membership_status' => $membership_status,
                'membership_level' => 'member',
                'gdpr_consent' => $gdpr_consent,
                'marketing_consent' => $marketing_consent,
                'email_verified' => 1,
                'profile_completed' => 0
            ),
            array('%d', '%s', '%d', '%s', '%s', '%d', '%d', '%d', '%d')
        );

        try {
            $member_result = $wpdb->insert(
                $wpdb->prefix . 'test_iipm_member_profiles',
                array(
                    'user_id' => $user_id,
                    'user_phone' => sanitize_text_field($data['user_phone'] ?? ''),
                    'email_address' => $email,
                    'user_mobile' => sanitize_text_field($data['user_mobile'] ?? ''),
                    'user_employer' => sanitize_text_field($organisation_name ?? ''),
                    'postal_address' => sanitize_text_field($data['postal_address'] ?? ''),
                    'city_or_town' => sanitize_text_field($data['city_or_town'] ?? ''),
                    'Address_1' => sanitize_text_field($data['address_line_1'] ?? ''),
                    'Address_2' => sanitize_text_field($data['address_line_2'] ?? ''),
                    'Address_3' => sanitize_text_field($data['address_line_3'] ?? ''),
                    'user_fullName' => $first_name." ".$last_name,
                    'user_payment_method' => sanitize_text_field($data['payment_method'] ?? ''),
                    'sur_name' => sanitize_text_field($last_name ?? ''),
                    'first_name' => sanitize_text_field($first_name ?? ''),
                    'user_is_admin' => 0,
                    'user_designation' => sanitize_text_field($data['user_designation'] ?? ''),
                    'user_name_login' => sanitize_text_field($data['login_name'] ?? ''),
                    'email_address_pers' => sanitize_email($data['email_address_pers'] ?? ''),
                    'user_phone_pers' => sanitize_text_field($data['user_phone_pers'] ?? ''),
                    'user_mobile_pers' => sanitize_text_field($data['user_mobile_pers'] ?? ''),
                    'Address_1_pers' => sanitize_text_field($data['Address_1_pers'] ?? ''),
                    'Address_2_pers' => sanitize_text_field($data['Address_2_pers'] ?? ''),
                    'Address_3_pers' => sanitize_text_field($data['Address_3_pers'] ?? ''),
                    'eircode_p' => sanitize_text_field($data['eircode_p'] ?? ''),
                    'eircode_w' => sanitize_text_field($data['eircode_w'] ?? ''),
                    'correspondence_email' => sanitize_email($data['correspondence_email'] ?? ''),
                    'user_notes' => sanitize_textarea_field($data['user_notes'] ?? ''),
                    'dateOfUpdatePers' => current_time('mysql'),
                    'dateOfUpdateGen' => current_time('mysql'),
                    'employerDetailsUpdated' => current_time('mysql'),
                    'theUsersStatus' => 'Full Member'
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
            if ($member_result === false) {
                error_log('IIPM: Failed to insert member. Database error: ' . $wpdb->last_error);
                error_log('IIPM: Last query: ' . $wpdb->last_query);
                throw new Exception('Failed to create member record: ' . $wpdb->last_error);
            }
        } catch ( Exception $e ) {
            echo $e; die();
        }
        
        if ($invitation) {
            $wpdb->update(
                $wpdb->prefix . 'test_iipm_invitations',
                array('used_at' => current_time('mysql')),
                array('id' => $invitation->id),
                array('%s'),
                array('%d')
            );
        }
        
        iipm_log_user_activity($user_id, 'registration', 'User registered successfully with active status');
        iipm_send_welcome_email($user_id, $email, $first_name);
        
        // Add welcome notification
        iipm_add_persistent_notification(
            $user_id,
            'success',
            'Welcome to IIPM!',
            'Your account has been successfully created and activated. You can now access all member benefits including CPD courses, resources, and events.',
            array(
                'action_url' => home_url('/member-portal/'),
                'action_text' => 'View Profile',
                'expires_in_days' => 7
            )
        );
        
        return array('success' => true, 'user_id' => $user_id, 'status' => $membership_status);
        
    } catch (Exception $e) {
        // Log the error for debugging
        error_log('IIPM Member Registration Error: ' . $e->getMessage());
        
        // Return a user-friendly error message
        return array('success' => false, 'error' => 'Registration failed due to a system error. Please try again or contact support.');
    }
}

function iipm_is_organisation_admin_email($email, $organisation_id) {
	global $wpdb;
	
	$org = $wpdb->get_row($wpdb->prepare(
		"SELECT contact_email FROM {$wpdb->prefix}test_iipm_organisations WHERE id = %d",
		$organisation_id
	));
	
	return $org && $org->contact_email === $email;
}

function iipm_send_email_verification($user_id) {
	$user = get_user_by('id', $user_id);
	if (!$user) return false;
	
	$token = wp_generate_password(32, false);
	update_user_meta($user_id, 'email_verification_token', $token);
	
	$verification_url = home_url('/verify-email/?token=' . $token . '&user=' . $user_id);
	$subject = 'IIPM - Verify Your Email Address';
	
	$message = "
	Dear {$user->first_name},

	Please verify your email address by clicking the link below:
	{$verification_url}

	Best regards,
	IIPM Team
	";
	
	$headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: IIPM Portal <' . (defined('SMTP_FROM') ? SMTP_FROM : get_option('admin_email')) . '>'
    );
	
	return wp_mail($user->user_email, $subject, $message, $headers);
}

function iipm_verify_email($user_id, $token) {
	$stored_token = get_user_meta($user_id, 'email_verification_token', true);
	
	if ($stored_token && $stored_token === $token) {
		global $wpdb;
		
		$wpdb->update(
			$wpdb->prefix . 'test_iipm_members',
			array('email_verified' => 1),
			array('user_id' => $user_id),
			array('%d'),
			array('%d')
		);
		
		delete_user_meta($user_id, 'email_verification_token');
		iipm_log_user_activity($user_id, 'email_verified', 'Email address verified');
		
		return true;
	}
	
	return false;
}

function iipm_send_welcome_email($user_id, $email, $first_name) {
	$portal_url = home_url('/member-portal/');
	$subject = 'Welcome to IIPM - Your Account is Active!';
	
	$message = "
	Dear {$first_name},

	Welcome to the Irish Institute of Pensions Management!

	Your account has been created successfully and is now ACTIVE. You can immediately access all member benefits:
	
	🎯 Access your member portal: {$portal_url}
	📚 Browse professional development courses
	🎓 Track your CPD points
	📅 Register for events and conferences
	📋 Download member resources
	
	Your membership is now active and ready to use!
	
	If you have any questions, please contact us at info@iipm.ie

	Best regards,
	IIPM Team
	";
	
	$headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: IIPM Portal <' . (defined('SMTP_FROM') ? SMTP_FROM : get_option('admin_email')) . '>'
    );
	
	wp_mail($email, $subject, $message, $headers);
}

/**
 * NEW: Function to manually activate existing pending users (for testing)
 */
function iipm_activate_all_pending_users() {
	global $wpdb;
	
	$updated = $wpdb->query(
		"UPDATE {$wpdb->prefix}test_iipm_members 
		 SET membership_status = 'active', 
		     membership_level = 'member',
		     email_verified = 1
		 WHERE membership_status = 'pending'"
	);
	
	if ($updated > 0) {
		error_log("IIPM: Activated $updated pending users");
		return $updated;
	}
	
	return 0;
}

/**
 * Enhanced AJAX Handlers with Better Error Handling
 */
function iipm_handle_member_registration() {
	// Log the AJAX request
	error_log('IIPM: iipm_handle_member_registration called');
	error_log('IIPM: POST data: ' . print_r($_POST, true));
	
	if (!wp_verify_nonce($_POST['nonce'], 'iipm_registration_nonce')) {
		error_log('IIPM: Nonce verification failed');
		wp_send_json_error('Security check failed');
	}
	
	$token = sanitize_text_field($_POST['token'] ?? '');
	$result = iipm_process_member_registration($_POST, $token);
	
	error_log('IIPM: Registration result: ' . print_r($result, true));
	
	if ($result['success']) {
		wp_send_json_success($result);
	} else {
		wp_send_json_error($result['error']);
	}
}
add_action('wp_ajax_iipm_member_registration', 'iipm_handle_member_registration');
add_action('wp_ajax_nopriv_iipm_member_registration', 'iipm_handle_member_registration');

/**
 * NEW: AJAX handler to activate all pending users (for admin testing)
 */
function iipm_handle_activate_pending_users() {
	// Check permissions
	if (!current_user_can('administrator')) {
		wp_send_json_error('Insufficient permissions');
		return;
	}
	
	// Verify nonce
	if (!wp_verify_nonce($_POST['nonce'], 'iipm_portal_nonce')) {
		wp_send_json_error('Security check failed');
		return;
	}
	
	$activated = iipm_activate_all_pending_users();
	
	if ($activated > 0) {
		wp_send_json_success("Successfully activated $activated pending users");
	} else {
		wp_send_json_success("No pending users found to activate");
	}
}
add_action('wp_ajax_iipm_activate_pending_users', 'iipm_handle_activate_pending_users');

/**
 * AJAX handler for submitting leave requests
 */
function iipm_handle_submit_leave_request() {
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_portal_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $user_id = get_current_user_id();
    
    $leave_title = sanitize_text_field($_POST['leave_title'] ?? '');
    $leave_reason = sanitize_text_field($_POST['leave_reason'] ?? '');
    $leave_start_date = sanitize_text_field($_POST['leave_start_date'] ?? '');
    $leave_end_date = sanitize_text_field($_POST['leave_end_date'] ?? '');
    $leave_description = sanitize_textarea_field($_POST['leave_description'] ?? '');
    
    if (empty($leave_title) || empty($leave_reason) || empty($leave_start_date) || empty($leave_end_date)) {
        wp_send_json_error('All required fields must be filled');
        return;
    }
    
    if (strtotime($leave_start_date) >= strtotime($leave_end_date)) {
        wp_send_json_error('End date must be after start date');
        return;
    }
    
    if (strtotime($leave_start_date) < strtotime('today')) {
        wp_send_json_error('Start date cannot be in the past');
        return;
    }
    
    $start = new DateTime($leave_start_date);
    $end = new DateTime($leave_end_date);
    $duration_days = $end->diff($start)->days + 1;
    
    global $wpdb;
    
    $result = $wpdb->insert(
        $wpdb->prefix . 'test_iipm_leave_requests',
        array(
            'user_id' => $user_id,
            'title' => $leave_title,
            'reason' => $leave_reason,
            'leave_start_date' => $leave_start_date,
            'leave_end_date' => $leave_end_date,
            'duration_days' => $duration_days,
            'description' => $leave_description,
            'status' => 'pending'
        ),
        array('%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
    );
    
    if ($result !== false) {
        iipm_log_user_activity(
            $user_id, 
            'leave_request_submitted', 
            "Leave request submitted: {$leave_title}"
        );
        
        iipm_send_leave_request_notification($user_id, $leave_title, $leave_start_date, $leave_end_date);
        
        wp_send_json_success('Leave request submitted successfully');
    } else {
        error_log('IIPM: Failed to insert leave request: ' . $wpdb->last_error);
        wp_send_json_error('Failed to submit leave request. Please try again.');
    }
}

/**
 * Send leave request notification to admins
 */
function iipm_send_leave_request_notification($user_id, $leave_title, $leave_start_date, $leave_end_date) {
    $user = get_user_by('id', $user_id);
    if (!$user) return false;
    
    $admin_emails = array();
    $admins = get_users(array('role__in' => array('administrator', 'iipm_admin')));
    foreach ($admins as $admin) {
        $admin_emails[] = $admin->user_email;
    }
    
    if (empty($admin_emails)) {
        return false;
    }
    
    $subject = 'IIPM - New Leave Request Submitted';
    $message = "
Dear Admin,

A new leave request has been submitted and requires your review.

Request Details:
- Employee: {$user->first_name} {$user->last_name} ({$user->user_email})
- Title: {$leave_title}
- Leave Period: " . date('F j, Y', strtotime($leave_start_date)) . " to " . date('F j, Y', strtotime($leave_end_date)) . "

Please log in to the admin portal to review and approve/reject this request:
" . home_url('/leave-admin/') . "

Best regards,
IIPM System
    ";
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: IIPM Portal <' . (defined('SMTP_FROM') ? SMTP_FROM : get_option('admin_email')) . '>'
    );
    
    return wp_mail($admin_emails, $subject, $message, $headers);
}

/**
 * AJAX handler for cancelling leave requests
 */
function iipm_handle_cancel_leave_request() {
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_portal_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $user_id = get_current_user_id();
    $request_id = intval($_POST['request_id'] ?? 0);
    
    if (!$request_id) {
        wp_send_json_error('Invalid request ID');
        return;
    }
    
    global $wpdb;
    
    $request = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_leave_requests 
         WHERE id = %d AND user_id = %d AND status = 'pending'",
        $request_id,
        $user_id
    ));
    
    if (!$request) {
        wp_send_json_error('Request not found or cannot be cancelled');
        return;
    }
    
    $result = $wpdb->update(
        $wpdb->prefix . 'test_iipm_leave_requests',
        array('status' => 'cancelled'),
        array('id' => $request_id, 'user_id' => $user_id),
        array('%s'),
        array('%d', '%d')
    );
    
    if ($result !== false) {
        iipm_log_user_activity(
            $user_id, 
            'leave_request_cancelled', 
            "Leave request cancelled: {$request->title}"
        );
        
        wp_send_json_success('Leave request cancelled successfully');
    } else {
        wp_send_json_error('Failed to cancel leave request');
    }
}
add_action('wp_ajax_iipm_cancel_leave_request', 'iipm_handle_cancel_leave_request');

/**
 * AJAX handler for creating sample persistent notifications
 */
function iipm_handle_create_sample_notifications() {
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_portal_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $user_id = get_current_user_id();
    
    // Create sample notifications
    $notifications = array(
        array(
            'type' => 'success',
            'title' => 'CPD Course Completed',
            'message' => 'Congratulations! You have successfully completed "Ethics in Financial Planning 2024" and earned 2 CPD points.',
            'options' => array(
                'action_url' => home_url('/cpd-record/'),
                'action_text' => 'View CPD Record',
                'expires_in_days' => 14
            )
        ),
        array(
            'type' => 'info',
            'title' => 'New Course Available',
            'message' => 'A new CPD course "Investment Strategies for 2024" is now available. Enroll today to earn up to 3 CPD points.',
            'options' => array(
                'action_url' => home_url('/cpd-courses/'),
                'action_text' => 'Browse Courses',
                'expires_in_days' => 30
            )
        ),
        array(
            'type' => 'warning',
            'title' => 'CPD Deadline Approaching',
            'message' => 'You have 60 days remaining to complete your annual CPD requirements. You currently have 2 out of 4 required points.',
            'options' => array(
                'action_url' => home_url('/cpd-courses/'),
                'action_text' => 'Find Courses',
                'expires_in_days' => 60
            )
        ),
        array(
            'type' => 'info',
            'title' => 'Profile Update Reminder',
            'message' => 'Please review and update your profile information to ensure you receive important communications.',
            'options' => array(
                'action_url' => home_url('/member-portal/'),
                'action_text' => 'Update Profile',
                'expires_in_days' => 90
            )
        ),
        array(
            'type' => 'success',
            'title' => 'Certificate Generated',
            'message' => 'Your 2024 CPD certificate has been generated and is ready for download.',
            'options' => array(
                'action_url' => home_url('/cpd-certificates/'),
                'action_text' => 'Download Certificate',
                'is_persistent' => 1 // This one doesn't expire
            )
        )
    );
    
    $created_count = 0;
    foreach ($notifications as $notification) {
        $result = iipm_add_persistent_notification(
            $user_id,
            $notification['type'],
            $notification['title'],
            $notification['message'],
            $notification['options']
        );
        
        if ($result) {
            $created_count++;
        }
    }
    
    if ($created_count > 0) {
        wp_send_json_success(array(
            'message' => "Successfully created {$created_count} persistent notifications",
            'count' => $created_count
        ));
    } else {
        wp_send_json_error('Failed to create notifications');
    }
}
add_action('wp_ajax_iipm_create_sample_notifications', 'iipm_handle_create_sample_notifications');

/**
 * AJAX handler for updating user profile
 */
function iipm_handle_update_profile() {
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_portal_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $user_id = get_current_user_id();
    $section = sanitize_text_field($_POST['section']);
    
    global $wpdb;
    
    switch ($section) {
        case 'basic-info':
            $first_name = sanitize_text_field($_POST['first_name']);
            $last_name = sanitize_text_field($_POST['last_name']);
            $email = sanitize_email($_POST['email']);
            
            // Update WordPress user data
            wp_update_user(array(
                'ID' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'user_email' => $email
            ));
            break;
            
        case 'contact-details':
            $user_phone = sanitize_text_field($_POST['user_phone']);
            $mobile = sanitize_text_field($_POST['mobile']);
            $user_mobile = sanitize_text_field($_POST['user_mobile']);
            $work_email = sanitize_email($_POST['work_email']);
            
            // Update user meta
            update_user_meta($user_id, 'phone', $mobile);
            
            // Update profile table
            $wpdb->replace(
                $wpdb->prefix . 'test_iipm_member_profiles',
                array(
                    'user_id' => $user_id,
                    'user_phone' => $user_phone,
                    'user_mobile' => $user_mobile,
                    'work_email' => $work_email
                ),
                array('%d', '%s', '%s', '%s', '%s')
            );
            break;
            
        case 'address':
            $address_line1 = sanitize_text_field($_POST['address_line1']);
            $address_line2 = sanitize_text_field($_POST['address_line2']);
            $city = sanitize_text_field($_POST['city']);
            $county = sanitize_text_field($_POST['county']);
            $eircode = sanitize_text_field($_POST['eircode']);
            
            // Update user meta
            update_user_meta($user_id, 'address_line1', $address_line1);
            update_user_meta($user_id, 'address_line2', $address_line2);
            update_user_meta($user_id, 'city', $city);
            update_user_meta($user_id, 'county', $county);
            update_user_meta($user_id, 'eircode', $eircode);
            break;
            
        case 'employment':
            $employer_name = sanitize_text_field($_POST['employer_name']);
            $employer_address_line1 = sanitize_text_field($_POST['employer_address_line1']);
            $employer_address_line2 = sanitize_text_field($_POST['employer_address_line2']);
            $employer_city = sanitize_text_field($_POST['employer_city']);
            $employer_county = sanitize_text_field($_POST['employer_county']);
            $employer_eircode = sanitize_text_field($_POST['employer_eircode']);
            
            // Update profile table
            $wpdb->replace(
                $wpdb->prefix . 'test_iipm_member_profiles',
                array(
                    'user_id' => $user_id,
                    'employer_name' => $employer_name,
                    'employer_address_line1' => $employer_address_line1,
                    'employer_address_line2' => $employer_address_line2,
                    'employer_city' => $employer_city,
                    'employer_county' => $employer_county,
                    'employer_eircode' => $employer_eircode
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
            );
            break;
            
        default:
            wp_send_json_error('Invalid section');
            return;
    }
    
    // Log activity
    iipm_log_user_activity($user_id, 'profile_updated', "Profile section '{$section}' updated");
    
    // Create notification
    iipm_add_persistent_notification(
        $user_id,
        'success',
        'Profile Updated',
        "Your {$section} information has been successfully updated.",
        array('expires_in_days' => 3)
    );
    
    wp_send_json_success(array(
        'message' => 'Profile updated successfully',
        'section' => $section
    ));
}
add_action('wp_ajax_iipm_update_profile', 'iipm_handle_update_profile');

// AJAX handler for changing user password
function iipm_handle_change_password() {
    // Verify user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to change your password.']);
        return;
    }
    
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_change_password')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
        return;
    }
    
    $user_id = get_current_user_id();
    $current_password = sanitize_text_field($_POST['current_password']);
    $new_password = sanitize_text_field($_POST['new_password']);
    $confirm_password = sanitize_text_field($_POST['confirm_password']);
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        wp_send_json_error(['message' => 'All password fields are required.']);
        return;
    }
    
    // Check if new passwords match
    if ($new_password !== $confirm_password) {
        wp_send_json_error(['message' => 'New password and confirmation password do not match.']);
        return;
    }
    
    // Validate password strength
    if (strlen($new_password) < 8) {
        wp_send_json_error(['message' => 'Password must be at least 8 characters long.']);
        return;
    }
    
    if (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password)) {
        wp_send_json_error(['message' => 'Password must contain both uppercase and lowercase letters.']);
        return;
    }
    
    if (!preg_match('/\d/', $new_password)) {
        wp_send_json_error(['message' => 'Password must contain at least one number.']);
        return;
    }
    
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) {
        wp_send_json_error(['message' => 'Password must contain at least one special character.']);
        return;
    }
    
    // Check if new password is different from current
    if ($current_password === $new_password) {
        wp_send_json_error(['message' => 'New password must be different from your current password.']);
        return;
    }
    
    // Get current user data
    $user = get_user_by('id', $user_id);
    if (!$user) {
        wp_send_json_error(['message' => 'User not found.']);
        return;
    }
    
    // Verify current password
    if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
        wp_send_json_error(['message' => 'Current password is incorrect.']);
        return;
    }
    
    // Update password
    wp_set_password($new_password, $user_id);
    
    // Add success notification
    iipm_add_persistent_notification(
        $user_id,
        'Password Changed',
        'Your password has been successfully updated.',
        'success'
    );
    
    // Log the user back in (since wp_set_password logs them out)
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    
    wp_send_json_success(['message' => 'Password changed successfully!']);
}
add_action('wp_ajax_iipm_change_password', 'iipm_handle_change_password');

// AJAX handler for sending password reset email
function iipm_handle_send_reset_email() {
    // Verify user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to request a password reset.']);
        return;
    }
    
    $user_id = get_current_user_id();
    $user = get_user_by('id', $user_id);
    
    if (!$user) {
        wp_send_json_error(['message' => 'User not found.']);
        return;
    }
    
    // Generate reset key
    $reset_key = get_password_reset_key($user);
    
    if (is_wp_error($reset_key)) {
        wp_send_json_error(['message' => 'Unable to generate reset key.']);
        return;
    }
    
    // Create reset URL
    $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');
    
    // Send email
    $subject = 'Password Reset Request - IIPM';
    $message = "Hello " . $user->display_name . ",\n\n";
    $message .= "You have requested to reset your password for your IIPM account.\n\n";
    $message .= "To reset your password, click the following link:\n";
    $message .= $reset_url . "\n\n";
    $message .= "If you did not request this password reset, please ignore this email.\n\n";
    $message .= "Best regards,\n";
    $message .= "The IIPM Team";
    
    $sent = wp_mail($user->user_email, $subject, $message);
    
    if ($sent) {
        // Add notification
        iipm_add_persistent_notification(
            $user_id,
            'Reset Email Sent',
            'Password reset instructions have been sent to your email address.',
            'info'
        );
        
        wp_send_json_success(['message' => 'Reset email sent successfully!']);
    } else {
        wp_send_json_error(['message' => 'Failed to send reset email. Please try again.']);
    }
}
add_action('wp_ajax_iipm_send_reset_email', 'iipm_handle_send_reset_email');

// AJAX handler for help contact form
function iipm_handle_send_help_message() {
    // Verify user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to send a help message.']);
        return;
    }
    
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_help_message')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
        return;
    }
    
    $user_id = get_current_user_id();
    $full_name = sanitize_text_field($_POST['full_name']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $message = sanitize_textarea_field($_POST['message']);
    $recaptcha_response = sanitize_text_field($_POST['g-recaptcha-response']);
    
    // Validate inputs
    if (empty($full_name) || empty($email) || empty($phone) || empty($message)) {
        wp_send_json_error(['message' => 'All fields are required.']);
        return;
    }
    
    // Validate email
    if (!is_email($email)) {
        wp_send_json_error(['message' => 'Please enter a valid email address.']);
        return;
    }
    
    // Validate reCAPTCHA
    if (empty($recaptcha_response)) {
        wp_send_json_error(['message' => 'Please complete the CAPTCHA verification.']);
        return;
    }
    
    if (!verify_recaptcha($recaptcha_response)) {
        wp_send_json_error(['message' => 'CAPTCHA verification failed. Please try again.']);
        return;
    }
    
    // Get admin email
    $admin_email = get_option('admin_email');
    $site_name = get_bloginfo('name');
    
    // Send email to administrator
    $subject = 'Help Request from ' . $full_name . ' - ' . $site_name;
    $email_message = "Help request received from the IIPM portal:\n\n";
    $email_message .= "Name: " . $full_name . "\n";
    $email_message .= "Email: " . $email . "\n";
    $email_message .= "Phone: " . $phone . "\n";
    $email_message .= "User ID: " . $user_id . "\n\n";
    $email_message .= "Message:\n" . $message . "\n\n";
    $email_message .= "Sent from: " . home_url() . "\n";
    $email_message .= "Date: " . current_time('Y-m-d H:i:s');
    
    $headers = array(
        'From: ' . $site_name . ' <' . $admin_email . '>',
        'Reply-To: ' . $full_name . ' <' . $email . '>'
    );
    
    $sent = wp_mail($admin_email, $subject, $email_message, $headers);
    
    if ($sent) {
        // Add notification for user
        iipm_add_persistent_notification(
            $user_id,
            'Help Request Sent',
            'Your help request has been sent to our support team. We will get back to you soon.',
            'success'
        );
        
        // Send confirmation email to user
        $user_subject = 'Help Request Received - ' . $site_name;
        $user_message = "Dear " . $full_name . ",\n\n";
        $user_message .= "Thank you for contacting our support team. We have received your help request and will respond as soon as possible.\n\n";
        $user_message .= "Your message:\n" . $message . "\n\n";
        $user_message .= "Our support team typically responds within 1-2 business days.\n\n";
        $user_message .= "Best regards,\n";
        $user_message .= "The IIPM Support Team";
        
        wp_mail($email, $user_subject, $user_message);
        
        wp_send_json_success(['message' => 'Your help request has been sent successfully. We will get back to you soon!']);
    } else {
        wp_send_json_error(['message' => 'Failed to send help request. Please try again or contact support directly.']);
    }
}
add_action('wp_ajax_iipm_send_help_message', 'iipm_handle_send_help_message');

// Add reCAPTCHA configuration - IMPORTANT: UPDATE THESE WITH YOUR ACTUAL KEYS
// Get your keys from: https://www.google.com/recaptcha/admin/create
// These are Google's test keys - replace with your real keys for production
define('RECAPTCHA_SITE_KEY', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'); // Replace with your site key
define('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'); // Replace with your secret key

// Function to verify reCAPTCHA
function verify_recaptcha($recaptcha_response) {
    $secret_key = RECAPTCHA_SECRET_KEY;
    $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
    
    $data = array(
        'secret' => $secret_key,
        'response' => $recaptcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    );
    
    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        )
    );
    
    $context = stream_context_create($options);
    $result = file_get_contents($verify_url, false, $context);
    
    if ($result === FALSE) {
        return false;
    }
    
    $response_data = json_decode($result, true);
    return $response_data['success'];
}

/**
 * Enqueue IIPM Portal Scripts and Styles - UPDATED FOR MILESTONE 2
 */
function iipm_enqueue_portal_scripts() {
	wp_enqueue_style('iipm-portal-style', get_template_directory_uri() . '/css/iipm-portal.css', array(), '1.0.0');
	wp_enqueue_style('iipm-enhanced-style', get_template_directory_uri() . '/css/enhanced-iipm-portal.css', array(), '1.0.0');
	
	wp_enqueue_script('iipm-portal-script', get_template_directory_uri() . '/js/iipm-portal.js', array('jquery'), '1.0.0', true);
	
	wp_localize_script('iipm-portal-script', 'iipm_ajax', array(
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('iipm_portal_nonce'),
		'cpd_nonce' => wp_create_nonce('iipm_cpd_nonce'),
		'cpd_admin_nonce' => wp_create_nonce('iipm_cpd_admin_nonce'),
		'leave_nonce' => wp_create_nonce('iipm_leave_nonce')
	));
}
add_action('wp_enqueue_scripts', 'iipm_enqueue_portal_scripts');

/**
 * Redirect non-logged-in users from protected pages
 */
function iipm_protect_portal_pages() {
    if (is_page_template('template-member-portal.php') || 
        is_page_template('template-dashboard.php') ||
        is_page_template('template-user-management.php') ||
        is_page_template('template-organisation-management.php') ||
        is_page_template('template-bulk-import.php') ||
        is_page_template('template-admin-invitations.php') ||
        is_page_template('template-cpd-courses.php') ||
        is_page_template('template-leave-request.php') ||
        is_page_template('template-leave-admin.php')) {
        
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/login/'));
            exit;
        }
    }
}
add_action('template_redirect', 'iipm_protect_portal_pages');

/**
 * Custom login page redirect
 */
function iipm_custom_login_redirect() {
	global $pagenow;
	
	if ($pagenow == 'wp-login.php' && !isset($_GET['action'])) {
		wp_redirect(home_url('/login/'));
		exit();
	}
}
add_action('init', 'iipm_custom_login_redirect');

/**
 * Handle logout redirect
 */
function iipm_logout_redirect() {
	wp_redirect(home_url('/login/'));
	exit();
}
add_action('wp_logout', 'iipm_logout_redirect');

/**
 * Add custom body classes for IIPM pages
 */
function iipm_custom_body_classes($classes) {
    if (is_page_template('template-member-portal.php')) {
        $classes[] = 'iipm-member-portal';
    }
    if (is_page_template('template-dashboard.php')) {
        $classes[] = 'iipm-dashboard';
    }
    if (is_page_template('template-login.php')) {
        $classes[] = 'iipm-login';
    }
    if (is_page_template('template-member-registration.php')) {
        $classes[] = 'iipm-registration';
    }
    if (is_page_template('template-cpd-courses.php')) {
        $classes[] = 'iipm-cpd-courses';
    }
    if (is_page_template('template-leave-request.php')) {
        $classes[] = 'iipm-leave-request';
    }
    if (is_page_template('template-leave-admin.php')) {
        $classes[] = 'iipm-leave-admin';
    }
    
    return $classes;
}
add_filter('body_class', 'iipm_custom_body_classes');

/**
 * IIPM Menu Management - UPDATED WITH LEAVE ADMIN
 */
function iipm_get_user_menu_items($user_id = null) {
	if (!$user_id) {
		$user_id = get_current_user_id();
	}
	
	$user = get_user_by('id', $user_id);
	if (!$user) {
		return array();
	}
	
	$menu_items = array(
		array(
			'title' => 'Dashboard',
			'url' => home_url('/dashboard/'),
			'icon' => 'dashboard',
			'roles' => array('iipm_member', 'iipm_corporate_admin', 'iipm_council_member', 'iipm_admin', 'administrator')
		),
		array(
			'title' => 'My Profile',
			'url' => home_url('/profile/'),
			'icon' => 'user',
			'roles' => array('iipm_member', 'iipm_corporate_admin', 'iipm_council_member', 'iipm_admin', 'administrator')
		),
		array(
			'title' => 'Notifications',
			'url' => home_url('/notifications/'),
			'icon' => 'bell',
			'roles' => array('iipm_member', 'iipm_corporate_admin', 'iipm_council_member', 'iipm_admin', 'administrator')
		),
		array(
			'title' => 'CPD Courses',
			'url' => home_url('/cpd-courses/'),
			'icon' => 'education',
			'roles' => array('iipm_member', 'iipm_corporate_admin', 'iipm_council_member', 'iipm_admin', 'administrator')
		),
		array(
			'title' => 'Leave Request',
			'url' => home_url('/leave-request/'),
			'icon' => 'calendar',
			'roles' => array('iipm_member', 'iipm_corporate_admin', 'iipm_council_member', 'iipm_admin', 'administrator')
		),
		array(
			'title' => 'Leave Admin',
			'url' => home_url('/leave-admin/'),
			'icon' => 'admin-tools',
			'roles' => array('iipm_admin', 'administrator')
		),
		array(
			'title' => 'User Management',
			'url' => home_url('/user-management/'),
			'icon' => 'users',
			'roles' => array('iipm_admin', 'administrator')
		),
		array(
			'title' => 'Organisation Management',
			'url' => home_url('/organisation-management/'),
			'icon' => 'building',
			'roles' => array('iipm_corporate_admin', 'iipm_admin', 'administrator')
		),
		array(
			'title' => 'Bulk Import',
			'url' => home_url('/bulk-import/'),
			'icon' => 'upload',
			'roles' => array('iipm_corporate_admin', 'iipm_admin', 'administrator')
		),
		array(
			'title' => 'Admin Invitations',
			'url' => home_url('/admin-invitations/'),
			'icon' => 'mail',
			'roles' => array('iipm_admin', 'administrator')
		)
	);
	
	$user_menu = array();
	foreach ($menu_items as $item) {
		$has_access = false;
		foreach ($item['roles'] as $role) {
			if (in_array($role, $user->roles)) {
				$has_access = true;
				break;
			}
		}
		
		if ($has_access) {
			$user_menu[] = $item;
		}
	}
	
	return $user_menu;
}

/**
 * Get organisation for current user
 */
function iipm_get_user_organisation($user_id = null) {
	if (!$user_id) {
		$user_id = get_current_user_id();
	}
	
	global $wpdb;
	
	$member = $wpdb->get_row($wpdb->prepare(
		"SELECT organisation_id FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d",
		$user_id
	));
	
	if ($member && $member->organisation_id) {
		return $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}test_iipm_organisations WHERE id = %d",
			$member->organisation_id
		));
	}
	
	return null;
}

/**
 * Get all organisations
 */
function iipm_get_all_organisations() {
	global $wpdb;
	
	return $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}test_iipm_organisations 
		 WHERE is_active = 1 
		 ORDER BY name ASC"
	);
}

/**
 * Get member profile data
 */
function iipm_get_member_profile($user_id) {
	global $wpdb;
	
	$member = $wpdb->get_row($wpdb->prepare(
		"SELECT m.*, o.name as organisation_name 
		 FROM {$wpdb->prefix}test_iipm_members m
		 LEFT JOIN {$wpdb->prefix}test_iipm_organisations o ON m.organisation_id = o.id
		 WHERE m.user_id = %d",
		$user_id
	));
	
	$profile = $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}test_iipm_member_profiles WHERE user_id = %d",
		$user_id
	));
	
	return array(
		'member' => $member,
		'profile' => $profile
	);
}

/**
 * Update member profile
 */
function iipm_update_member_profile($user_id, $member_data, $profile_data) {
	global $wpdb;
	
	$wpdb->update(
		$wpdb->prefix . 'test_iipm_members',
		$member_data,
		array('user_id' => $user_id)
	);
	
	$existing_profile = $wpdb->get_row($wpdb->prepare(
		"SELECT id FROM {$wpdb->prefix}test_iipm_member_profiles WHERE user_id = %d",
		$user_id
	));
	
	if ($existing_profile) {
		$wpdb->update(
			$wpdb->prefix . 'test_iipm_member_profiles',
			$profile_data,
			array('user_id' => $user_id)
		);
	} else {
		$profile_data['user_id'] = $user_id;
		$wpdb->insert(
			$wpdb->prefix . 'test_iipm_member_profiles',
			$profile_data
		);
	}
	
	iipm_log_user_activity($user_id, 'profile_update', 'Member profile updated');
	
	return true;
}

/**
 * Get dashboard statistics
 */
function iipm_get_dashboard_stats($user_id = null) {
	if (!$user_id) {
		$user_id = get_current_user_id();
	}
	
	global $wpdb;
	
	$user = get_user_by('id', $user_id);
	$stats = array();
	
	if (in_array('iipm_admin', $user->roles) || in_array('administrator', $user->roles)) {
		$stats['total_members'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_members");
		$stats['total_organisations'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_organisations WHERE is_active = 1");
		$stats['pending_invitations'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_invitations WHERE used_at IS NULL AND expires_at > NOW()");
		$stats['recent_registrations'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_members WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
		
		$leave_stats = iipm_get_leave_request_stats();
		$stats['pending_leave_requests'] = $leave_stats->pending_requests ?? 0;
		$stats['total_leave_requests'] = $leave_stats->total_requests ?? 0;
		
	} elseif (in_array('iipm_corporate_admin', $user->roles)) {
		$org = iipm_get_user_organisation($user_id);
		if ($org) {
			$stats['organisation_members'] = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_members WHERE organisation_id = %d",
				$org->id
			));
			$stats['pending_invitations'] = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_invitations WHERE organisation_id = %d AND used_at IS NULL AND expires_at > NOW()",
				$org->id
			));
		}
	}
	
	$user_leave_stats = $wpdb->get_row($wpdb->prepare(
		"SELECT 
			COUNT(*) as total_requests,
			SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
			SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests
		FROM {$wpdb->prefix}test_iipm_leave_requests 
		WHERE user_id = %d",
		$user_id
	));
	
	$stats['my_leave_requests'] = $user_leave_stats->total_requests ?? 0;
	$stats['my_pending_leave'] = $user_leave_stats->pending_requests ?? 0;
	$stats['my_approved_leave'] = $user_leave_stats->approved_requests ?? 0;
	
	return $stats;
}

/**
 * IIPM Portal Page Templates
 */
function iipm_add_portal_pages() {
    $pages = array(
        'member-portal' => 'Member Portal',
        'dashboard' => 'Dashboard',
        'login' => 'Login',
        'member-registration' => 'Member Registration',
        'user-management' => 'User Management',
        'organisation-management' => 'Organisation Management',
        'bulk-import' => 'Bulk Import',
        'admin-invitations' => 'Admin Invitations',
        'cpd-courses' => 'CPD Courses',
        'cpd-record' => 'CPD Record',
        'cpd-certificates' => 'CPD Certificates',
        'profile' => 'Profile',
        'file-dashboard' => 'File Dashboard',
        'leave-request' => 'Leave Request',
        'leave-admin' => 'Leave Admin',
        'notifications' => 'Notifications'
    );
    
    foreach ($pages as $slug => $title) {
        $existing_page = get_page_by_path($slug);
        if (!$existing_page) {
            $page_data = array(
                'post_title' => $title,
                'post_name' => $slug,
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => '[iipm_portal_page]'
            );
            
            $page_id = wp_insert_post($page_data);
            
            // Set specific templates for certain pages
            if ($slug === 'cpd-courses' && $page_id) {
                update_post_meta($page_id, '_wp_page_template', 'template-cpd-courses.php');
            }
            if ($slug === 'cpd-record' && $page_id) {
                update_post_meta($page_id, '_wp_page_template', 'template-cpd-record.php');
            }
            if ($slug === 'cpd-certificates' && $page_id) {
                update_post_meta($page_id, '_wp_page_template', 'template-cpd-certificates.php');
            }
            if ($slug === 'profile' && $page_id) {
                update_post_meta($page_id, '_wp_page_template', 'template-profile.php');
            }
            if ($slug === 'file-dashboard' && $page_id) {
                update_post_meta($page_id, '_wp_page_template', 'template-file-dashboard.php');
            }
            if ($slug === 'leave-request' && $page_id) {
                update_post_meta($page_id, '_wp_page_template', 'template-leave-request.php');
            }
            if ($slug === 'leave-admin' && $page_id) {
                update_post_meta($page_id, '_wp_page_template', 'template-leave-admin.php');
            }
            if ($slug === 'notifications' && $page_id) {
                update_post_meta($page_id, '_wp_page_template', 'template-notifications.php');
            }
        }
    }
}
add_action('after_switch_theme', 'iipm_add_portal_pages');

/**
 * IIPM Portal Shortcode
 */
function iipm_portal_page_shortcode($atts) {
	return '<div id="iipm-portal-content">Portal content will be loaded by the page template.</div>';
}
add_shortcode('iipm_portal_page', 'iipm_portal_page_shortcode');

/**
 * Debug function to check database tables
 */
function iipm_debug_database() {
	if (!current_user_can('administrator')) {
		return;
	}
	
	global $wpdb;
	
	$tables = array(
		'test_iipm_organisations',
		'test_iipm_members',
		'test_iipm_member_profiles',
		'test_iipm_invitations',
		'test_iipm_bulk_imports',
		'test_iipm_user_activity',
		'test_iipm_leave_requests'
	);
	
	echo '<div style="background: white; padding: 20px; margin: 20px; border: 1px solid #ccc;">';
	echo '<h3>IIPM Database Debug</h3>';
	
	foreach ($tables as $table) {
		$full_table_name = $wpdb->prefix . $table;
		$exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
		
		if ($exists) {
			$count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name");
			echo "<p>✅ $table: $count records</p>";
		} else {
			echo "<p>❌ $table: Table does not exist</p>";
		}
	}
	
	echo '</div>';
}

if (defined('WP_DEBUG') && WP_DEBUG) {
	add_action('wp_dashboard_setup', function() {
		wp_add_dashboard_widget('iipm_debug', 'IIPM Debug Info', 'iipm_debug_database');
	});
}

/**
 * IIPM Enhanced Error Logging
 */
function iipm_log_error($message, $context = array()) {
	$log_message = 'IIPM Error: ' . $message;
	if (!empty($context)) {
		$log_message .= ' | Context: ' . json_encode($context);
	}
	error_log($log_message);
}

function iipm_log_success($message, $context = array()) {
	$log_message = 'IIPM Success: ' . $message;
	if (!empty($context)) {
		$log_message .= ' | Context: ' . json_encode($context);
	}
	error_log($log_message);
}

/**
 * IIPM System Health Check
 */
function iipm_system_health_check() {
	$health = array(
		'database' => true,
		'email' => true,
		'permissions' => true,
		'errors' => array()
	);
	
	global $wpdb;
	$required_tables = array(
		'test_iipm_organisations',
		'test_iipm_members',
		'test_iipm_member_profiles',
		'test_iipm_invitations',
		'test_iipm_bulk_imports',
		'test_iipm_user_activity',
		'test_iipm_leave_requests'
	);
	
	foreach ($required_tables as $table) {
		$full_table_name = $wpdb->prefix . $table;
		$exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
		if (!$exists) {
			$health['database'] = false;
			$health['errors'][] = "Missing table: $table";
		}
	}
	
	if (!function_exists('wp_mail')) {
		$health['email'] = false;
		$health['errors'][] = 'wp_mail function not available';
	}
	
	$upload_dir = wp_upload_dir();
	if (!is_writable($upload_dir['basedir'])) {
		$health['permissions'] = false;
		$health['errors'][] = 'Upload directory not writable';
	}
	
	return $health;
}

/**
 * IIPM Admin Notice for System Health
 */
function iipm_admin_notices() {
	if (!current_user_can('administrator')) {
		return;
	}
	
	$health = iipm_system_health_check();
	
	if (!empty($health['errors'])) {
		echo '<div class="notice notice-error"><p><strong>IIPM System Issues:</strong></p><ul>';
		foreach ($health['errors'] as $error) {
			echo '<li>' . esc_html($error) . '</li>';
		}
		echo '</ul></div>';
	}
}
add_action('admin_notices', 'iipm_admin_notices');

/**
 * IIPM Version and Update Management
 */
define('IIPM_VERSION', '2.1.0');

function iipm_check_version() {
	$current_version = get_option('iipm_version', '1.0.0');
	
	if (version_compare($current_version, IIPM_VERSION, '<')) {
		iipm_run_updates($current_version, IIPM_VERSION);
		update_option('iipm_version', IIPM_VERSION);
	}
}
add_action('admin_init', 'iipm_check_version');

function iipm_run_updates($from_version, $to_version) {
	error_log("IIPM: Updating from version $from_version to $to_version");
	
	iipm_create_enhanced_tables();
	
	if (version_compare($from_version, '1.1.0', '<')) {
		error_log('IIPM: Running 1.1.0 updates');
	}
	
	if (version_compare($from_version, '1.2.0', '<')) {
		error_log('IIPM: Running 1.2.0 updates');
	}
	
	if (version_compare($from_version, '2.0.0', '<')) {
		error_log('IIPM: Running 2.0.0 updates - Adding CPD functionality');
		iipm_create_cpd_tables();
		iipm_create_enhanced_user_roles();
	}
	
	if (version_compare($from_version, '2.1.0', '<')) {
		error_log('IIPM: Running 2.1.0 updates - Adding Leave Request functionality');
		iipm_create_leave_request_tables();
		iipm_create_enhanced_user_roles();
	}
}

/**
 * IIPM Cleanup Functions
 */
function iipm_cleanup_expired_invitations() {
	global $wpdb;
	
	$deleted = $wpdb->query(
		"DELETE FROM {$wpdb->prefix}test_iipm_invitations 
		 WHERE expires_at < NOW() AND used_at IS NULL"
	);
	
	if ($deleted > 0) {
		error_log("IIPM: Cleaned up $deleted expired invitations");
	}
	
	// Also cleanup expired notifications
	iipm_cleanup_expired_notifications();
}

if (!wp_next_scheduled('iipm_daily_cleanup')) {
	wp_schedule_event(time(), 'daily', 'iipm_daily_cleanup');
}
add_action('iipm_daily_cleanup', 'iipm_cleanup_expired_invitations');

/**
 * IIPM Deactivation Cleanup
 */
function iipm_deactivation_cleanup() {
	wp_clear_scheduled_hook('iipm_daily_cleanup');
}
register_deactivation_hook(__FILE__, 'iipm_deactivation_cleanup');

/**
 * IIPM Final Initialization
 */
function iipm_final_init() {
    iipm_create_enhanced_tables();
    
    // Call CPD table creation from the includes file
    if (function_exists('iipm_create_cpd_tables')) {
        if (function_exists('iipm_cpd_tables_exist') && !iipm_cpd_tables_exist()) {
            iipm_create_cpd_tables();
        }
    }
    
    // Initialize CPD certificate table (Milestone 4)
    if (function_exists('iipm_create_cpd_certificates_table')) {
        iipm_create_cpd_certificates_table();
    }
    
    // Initialize CPD returns table
    if (function_exists('iipm_create_cpd_returns_table')) {
        iipm_create_cpd_returns_table();
    }
    
    // Ensure leave admin handlers are loaded and create tables
    if (function_exists('iipm_create_leave_request_tables_if_needed')) {
        iipm_create_leave_request_tables_if_needed();
    }
    
    iipm_create_enhanced_user_roles();
    
    error_log('IIPM: System initialized successfully with CPD and Leave Request functionality');
}
add_action('init', 'iipm_final_init', 999);

/**
 * Enhanced member registration handler
 */
function iipm_handle_enhanced_member_registration_v2() {

    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    error_log('IIPM: Enhanced member registration v2 called');
    error_log('IIPM: POST data: ' . print_r($_POST, true));
    error_log('IIPM: FILES data: ' . print_r($_FILES, true));
    
    if (!defined('DOING_AJAX') || !DOING_AJAX) {
        error_log('IIPM: Not an AJAX request');
        wp_send_json_error('Invalid request method');
        return;
    }
    
    // if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'iipm_registration_nonce')) {
    //     error_log('IIPM: Nonce verification failed');
    //     error_log('IIPM: Received nonce: ' . ($_POST['nonce'] ?? 'none'));
    //     wp_send_json_error('Security check failed');
    //     return;
    // }
    
    $required_fields = ['first_name', 'last_name', 'email', 'password', 'address'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        error_log('IIPM: Missing required fields: ' . implode(', ', $missing_fields));
        wp_send_json_error('Missing required fields: ' . implode(', ', $missing_fields));
        return;
    }
    
    $email = sanitize_email($_POST['email']);
    if (!is_email($email)) {
        error_log('IIPM: Invalid email: ' . $email);
        wp_send_json_error('Invalid email address');
        return;
    }
    
    // Check if this is an invited user
    $token = sanitize_text_field($_POST['token'] ?? '');
    $invitation = null;
    if ($token) {
        $invitation = iipm_validate_invitation_token($token);
        // echo json_encode($invitation); die();
        if (!$invitation) {
            error_log('IIPM: Invalid invitation token');
            wp_send_json_error('Invalid or expired invitation');
            return;
        }
        
        // For invited users, verify the email matches the invitation
        if ($invitation->email !== $email) {
            error_log('IIPM: Email mismatch with invitation');
            wp_send_json_error('Email address does not match invitation');
            return;
        }
    } else {
        // Only check for existing email if this is NOT an invited user
        if (email_exists($email)) {
            error_log('IIPM: Email already exists: ' . $email);
            wp_send_json_error('Email address already exists');
            return;
        }
    }
    
    if (!isset($_POST['gdpr_consent'])) {
        error_log('IIPM: GDPR consent not provided');
        wp_send_json_error('GDPR consent is required');
        return;
    }
    
    try {
        // Check invitation type and route to appropriate handler
        if ($invitation && $invitation->invitation_type === 'organisation_admin') {
            error_log('IIPM: Processing organization admin registration');
            $result = iipm_process_organisation_admin_registration($_POST, $invitation);
        } else {
            error_log('IIPM: Processing regular member registration');
            $result = iipm_process_member_registration($_POST, $token);
        }
        
        error_log('IIPM: Registration result: ' . print_r($result, true));
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
        
    } catch (Exception $e) {
        error_log('IIPM: Exception in registration: ' . $e->getMessage());
        error_log('IIPM: Exception trace: ' . $e->getTraceAsString());
        wp_send_json_error('Registration failed: ' . $e->getMessage());
    }
}

remove_action('wp_ajax_iipm_register_member', 'iipm_handle_member_registration');
remove_action('wp_ajax_nopriv_iipm_register_member', 'iimp_handle_member_registration');
remove_action('wp_ajax_iipm_register_member', 'iipm_handle_enhanced_member_registration');
remove_action('wp_ajax_nopriv_iipm_register_member', 'iipm_handle_enhanced_member_registration');

add_action('wp_ajax_iipm_register_member', 'iipm_handle_enhanced_member_registration_v2');
add_action('wp_ajax_nopriv_iipm_register_member', 'iipm_handle_enhanced_member_registration_v2');

/**
 * Bulk Import AJAX Handler
 */
function iipm_handle_bulk_import() {
	if (!current_user_can('manage_iipm_members') && 
		!current_user_can('administrator') && 
		!current_user_can('bulk_import_members')) {
		wp_send_json_error('Insufficient permissions');
		return;
	}
	
	if (!wp_verify_nonce($_POST['nonce'], 'iipm_bulk_import_nonce')) {
		wp_send_json_error('Security check failed');
		return;
	}
	
	$organisation_id = intval($_POST['organisation_id']);
	if (!$organisation_id) {
		wp_send_json_error('Organisation ID is required');
		return;
	}
	
	if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
		wp_send_json_error('File upload failed');
		return;
	}
	
	$file = $_FILES['csv_file'];
	
	if (!in_array($file['type'], array('text/csv', 'application/csv', 'text/plain'))) {
		wp_send_json_error('Invalid file type. Please upload a CSV file.');
		return;
	}
	
	if ($file['size'] > 5 * 1024 * 1024) {
		wp_send_json_error('File too large. Maximum size is 5MB.');
		return;
	}
	
	$upload_dir = wp_upload_dir();
	$temp_file = $upload_dir['path'] . '/' . uniqid('bulk_import_') . '.csv';
	
	if (!move_uploaded_file($file['tmp_name'], $temp_file)) {
		wp_send_json_error('Failed to process uploaded file');
		return;
	}
	
	$options = array(
		'send_invitations' => isset($_POST['send_invitations']),
		'skip_existing' => isset($_POST['skip_existing'])
	);
	
	global $wpdb;
	$import_id = $wpdb->insert(
		$wpdb->prefix . 'test_iipm_bulk_imports',
		array(
			'filename' => $file['name'],
			'total_records' => 0,
			'import_type' => 'members',
			'imported_by' => get_current_user_id(),
			'organisation_id' => $organisation_id,
			'status' => 'processing'
		),
		array('%s', '%d', '%s', '%d', '%d', '%s')
	);
	
	if (!$import_id) {
		unlink($temp_file);
		wp_send_json_error('Failed to create import record');
		return;
	}
	
	$result = iipm_process_bulk_import($temp_file, $organisation_id, $options);
	
	if ($result['success']) {
		$data = $result['data'];
		$wpdb->update(
			$wpdb->prefix . 'test_iipm_bulk_imports',
			array(
				'total_records' => $data['total'],
				'successful_imports' => $data['successful'],
				'failed_imports' => $data['failed'],
				'status' => 'completed',
				'error_log' => !empty($data['errors']) ? json_encode($data['errors']) : null
			),
			array(
				'id' => $import_id),
			array('%d', '%d', '%d', '%s', '%s'),
			array('%d')
		);
		
		iipm_log_user_activity(
			get_current_user_id(),
			'bulk_import',
			"Bulk import completed: {$data['successful']} successful, {$data['failed']} failed"
		);
		
		wp_send_json_success($data);
	} else {
		$wpdb->update(
			$wpdb->prefix . 'test_iipm_bulk_imports',
			array(
				'status' => 'failed',
				'error_log' => $result['error']
			),
			array('id' => $import_id),
			array('%s', '%s'),
			array('%d')
		);
		
		wp_send_json_error($result['error']);
	}
	
	unlink($temp_file);
}
add_action('wp_ajax_iipm_bulk_import', 'iipm_handle_bulk_import');

/**
 * Local Email Verification for Development
 */
function iipm_handle_local_email_verification() {
	if (!wp_verify_nonce($_POST['nonce'], 'iipm_portal_nonce')) {
		wp_send_json_error('Security check failed');
	}
	
	$user_id = intval($_POST['user_id']);
	
	if (!$user_id) {
		wp_send_json_error('Invalid user ID');
	}
	
	global $wpdb;
	
	$result = $wpdb->update(
		$wpdb->prefix . 'test_iipm_members',
		array('email_verified' => 1),
		array('user_id' => $user_id),
		array('%d'),
		array('%d')
	);
	
	if ($result !== false) {
		iipm_log_user_activity($user_id, 'email_verified_local', 'Email verified via local development method');
		wp_send_json_success('Email verified successfully for local development');
	} else {
		wp_send_json_error('Failed to verify email');
	}
}
add_action('wp_ajax_iipm_local_verify_email', 'iipm_handle_local_email_verification');
add_action('wp_ajax_nopriv_iipm_local_verify_email', 'iipm_handle_local_email_verification');

function iipm_resend_email_verification() {
	if (!is_user_logged_in()) {
		wp_send_json_error('User not logged in');
	}
	
	$user_id = get_current_user_id();
	$result = iipm_send_email_verification($user_id);
	
	if ($result) {
		iipm_log_user_activity($user_id, 'email_verification_resent', 'Email verification resent');
		wp_send_json_success('Verification email sent successfully');
	} else {
		wp_send_json_error('Failed to send verification email');
	}
}
add_action('wp_ajax_iipm_resend_verification', 'iipm_resend_email_verification');

/**
 * AJAX handler for loading invitations
 */
function iipm_load_invitations() {
    // Check permissions
    if (!current_user_can('manage_iipm_members') && 
        !current_user_can('administrator') && 
        !current_user_can('manage_organisation_members')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'iipm_user_management_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    global $wpdb;
    
    // Check if user is org admin and get their organisation
    $current_user_id = get_current_user_id();
    $is_org_admin = iipm_is_organisation_admin($current_user_id);
    $is_site_admin = current_user_can('manage_iipm_members') || current_user_can('administrator');
    
    // Build query based on user permissions
    if ($is_org_admin && !$is_site_admin) {
        // Get the organization ID for org admin
        $user_org = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}test_iipm_organisations WHERE admin_user_id = %d",
            $current_user_id
        ));
        
        if (!$user_org) {
            wp_send_json_error('Organisation not found for current user');
            return;
        }
        
        // Get invitations for specific organization
        $invitations = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, o.name as org_name, u.display_name as invited_by_name
             FROM {$wpdb->prefix}test_iipm_invitations i
             LEFT JOIN {$wpdb->prefix}test_iipm_organisations o ON i.organisation_id = o.id
             LEFT JOIN {$wpdb->users} u ON i.invited_by = u.ID
             WHERE i.organisation_id = %d
             ORDER BY i.created_at DESC",
            $user_org->id
        ));
    } else {
        // Site admin - get all invitations
        $invitations = $wpdb->get_results(
            "SELECT i.*, o.name as org_name, u.display_name as invited_by_name
             FROM {$wpdb->prefix}test_iipm_invitations i
             LEFT JOIN {$wpdb->prefix}test_iipm_organisations o ON i.organisation_id = o.id
             LEFT JOIN {$wpdb->users} u ON i.invited_by = u.ID
             ORDER BY i.created_at DESC"
        );
    }
    
    // Separate invitations by status
    $pending = array();
    $used = array();
    $expired = array();
    
    foreach ($invitations as $invitation) {
        if ($invitation->used_at) {
            $used[] = $invitation;
        } elseif (strtotime($invitation->expires_at) < time()) {
            $expired[] = $invitation;
        } else {
            $pending[] = $invitation;
        }
    }
    
    wp_send_json_success(array(
        'pending' => $pending,
        'used' => $used,
        'expired' => $expired,
        'total' => count($invitations),
        'pending_count' => count($pending),
        'used_count' => count($used),
        'expired_count' => count($expired)
    ));
}
add_action('wp_ajax_iipm_load_invitations', 'iipm_load_invitations');

/**
 * AJAX handler for sending individual invitations
 */
function iipm_send_individual_invitation_enhanced() {
    // Log the start of the function
    error_log('IIPM: iipm_send_individual_invitation_enhanced called');
    
    // Check permissions - Updated to include corporate admins
    if (!current_user_can('manage_iipm_members') && 
        !current_user_can('administrator') && 
        !current_user_can('manage_organisation_members')) {
        error_log('IIPM: Permission denied for user: ' . get_current_user_id());
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'iipm_user_management_nonce')) {
        error_log('IIPM: Nonce verification failed');
        wp_send_json_error('Security check failed');
        return;
    }
    
    // Validate input
    $email = sanitize_email($_POST['email'] ?? '');
    $type = sanitize_text_field($_POST['type'] ?? 'individual');
    $organisation_id = isset($_POST['organisation_id']) ? intval($_POST['organisation_id']) : null;
    
    // Check if user is org admin and get their organisation
    $current_user_id = get_current_user_id();
    $is_org_admin = iipm_is_organisation_admin($current_user_id);
    $is_site_admin = current_user_can('manage_iipm_members') || current_user_can('administrator');
    
    if ($is_org_admin && !$is_site_admin) {
        global $wpdb;
        
        // First try to get organization where user is admin
        $user_org = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}test_iipm_organisations WHERE admin_user_id = %d",
            $current_user_id
        ));
        
        // If not found, try to get organization where user is a member
        if (!$user_org) {
            $user_org = $wpdb->get_row($wpdb->prepare(
                "SELECT o.* 
                 FROM {$wpdb->prefix}test_iipm_organisations o
                 JOIN {$wpdb->prefix}test_iipm_members m ON o.id = m.organisation_id
                 WHERE m.user_id = %d",
                $current_user_id
            ));
        }
        
        if (!$user_org) {
            error_log('IIPM: Organisation not found for org admin user: ' . $current_user_id);
            wp_send_json_error('Organisation not found for current user');
            return;
        }
        
        // Force organisation_id to user's organisation
        $organisation_id = $user_org->id;
    }
    
    error_log('IIPM: Processing invitation for email: ' . $email . ' to org: ' . $organisation_id);
    
    if (!is_email($email)) {
        error_log('IIPM: Invalid email address: ' . $email);
        wp_send_json_error('Invalid email address');
        return;
    }
    
    if (email_exists($email)) {
        error_log('IIPM: Email already exists: ' . $email);
        wp_send_json_error('Email already exists');
        return;
    }
    
    // For org admins, always set type to 'bulk' for organisation members
    if ($is_org_admin && !$is_site_admin) {
        $type = 'bulk';
    }
    
    // Send invitation
    $result = iipm_send_invitation($email, $type, $organisation_id);
    
    if ($result['success']) {
        iipm_log_user_activity(
            get_current_user_id(), 
            'invitation_sent', 
            "Invitation sent to {$email} for organisation ID {$organisation_id}"
        );
        error_log('IIPM: Invitation sent successfully to: ' . $email);
        wp_send_json_success(array('message' => 'Invitation sent successfully!'));
    } else {
        error_log('IIPM: Invitation failed for: ' . $email . ' - Error: ' . $result['error']);
        wp_send_json_error(array('message' => $result['error']));
    }
}

// Replace the existing handler
remove_action('wp_ajax_iipm_send_invitation', 'iipm_send_individual_invitation');
add_action('wp_ajax_iipm_send_invitation', 'iipm_send_individual_invitation_enhanced');

/**
 * Email configuration test
 */
function iipm_test_email_configuration() {
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_test_email')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $test_email = defined('SMTP_FROM') ? SMTP_FROM : get_option('admin_email');
    $subject = 'IIPM Email Configuration Test - Brevo SMTP';
    $message = 'This is a test email sent via Brevo SMTP to verify that email sending is working correctly.';
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: IIPM Portal <' . $test_email . '>'
    );
    
    $result = wp_mail($test_email, $subject, $message, $headers);
    
    if ($result) {
        wp_send_json_success('Test email sent successfully to ' . $test_email . ' via Brevo SMTP');
    } else {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            wp_send_json_success('Test email logged locally (check wp-content/uploads/email-logs/)');
        } else {
            wp_send_json_error('Failed to send test email via Brevo SMTP. Please check your configuration.');
        }
    }
}
add_action('wp_ajax_iipm_test_email', 'iipm_test_email_configuration');

/**
 * Resend invitation
 */
function iipm_resend_invitation() {
    if (!current_user_can('manage_iipm_members') && !current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_user_management_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $email = sanitize_email($_POST['email']);
    
    if (!is_email($email)) {
        wp_send_json_error('Invalid email address');
        return;
    }
    
    global $wpdb;
    $invitation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_invitations 
         WHERE email = %s AND used_at IS NULL 
         ORDER BY created_at DESC LIMIT 1",
        $email
    ));
    
    if (!$invitation) {
        wp_send_json_error('No pending invitation found for this email');
        return;
    }
    
    $new_expiry = date('Y-m-d H:i:s', strtotime('+7 days'));
    $wpdb->update(
        $wpdb->prefix . 'test_iipm_invitations',
        array('expires_at' => $new_expiry),
        array('id' => $invitation->id),
        array('%s'),
        array('%d')
    );
    
    $registration_url = home_url('/member-registration/?token=' . $invitation->token);
    $subject = 'IIPM Membership Invitation (Resent)';
    
    $message = "
Dear Colleague,

This is a reminder that you have been invited to join the Irish Institute of Pensions Management (IIPM).

To complete your registration, please click the link below:
{$registration_url}

This invitation will expire in 7 days.

If you have any questions, please contact us at info@iipm.ie

Best regards,
IIPM Team
    ";
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: IIPM Portal <' . (defined('SMTP_FROM') ? SMTP_FROM : get_option('admin_email')) . '>'
    );
    
    $result = wp_mail($email, $subject, $message, $headers);
    
    if ($result) {
        iipm_log_user_activity(
            get_current_user_id(), 
            'invitation_resent', 
            "Invitation resent to {$email}"
        );
        wp_send_json_success('Invitation resent successfully');
    } else {
        wp_send_json_error('Failed to resend invitation email');
    }
}
add_action('wp_ajax_iipm_resend_invitation', 'iipm_resend_invitation');

/**
 * Enhanced portal scripts
 */
function iipm_enqueue_enhanced_portal_scripts() {
	if (is_page_template('template-member-portal.php') || 
		is_page_template('template-member-registration.php') ||
		is_page_template('template-bulk-import.php') ||
		is_page_template('template-admin-invitations.php') ||
        is_page_template('template-organisation-management.php') ||
        is_page_template('template-cpd-courses.php') ||
        is_page_template('template-leave-request.php') ||
        is_page_template('template-leave-admin.php') ||
        is_page_template('smtp-debug.php')) {
		
		wp_enqueue_script('jquery');
		
		wp_enqueue_script('iipm-portal-js', get_template_directory_uri() . '/js/iipm-portal.js', array('jquery'), '2.1.0', true);
		
		wp_enqueue_style('iipm-portal-css', get_template_directory_uri() . '/css/iipm-portal.css', array(), '2.1.0');
		
		wp_localize_script('iipm-portal-js', 'iipm_ajax', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('iipm_portal_nonce'),
			'debug' => defined('WP_DEBUG') && WP_DEBUG
		));
		
		if (defined('WP_DEBUG') && WP_DEBUG) {
			wp_add_inline_script('jquery', '
				console.log("=== IIPM Debug Info ===");
				console.log("jQuery version:", jQuery.fn.jquery);
				console.log("AJAX URL:", "' . admin_url('admin-ajax.php') . '");
				console.log("Current page:", window.location.href);
			');
		}
	}
}
add_action('wp_enqueue_scripts', 'iipm_enqueue_enhanced_portal_scripts', 5);

function iipm_add_image_sizes() {
	add_image_size('hero-background', 1920, 1080, true);
}
add_action('after_setup_theme', 'iipm_add_image_sizes');

/**
 * Helper functions for portal
 */
function iipm_calculate_profile_completion($user_id) {
	global $wpdb;
	
	$user = get_user_by('id', $user_id);
	$member = $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d", 
		$user_id
	));
	$profile = $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}test_iipm_member_profiles WHERE user_id = %d",
		$user_id
	));
	
	$completion = 0;
	$total_fields = 8;
	
	if ($user && $user->first_name && $user->last_name) $completion += 2;
	if ($profile && $profile->user_phone) $completion += 1;
	if ($profile && $profile->work_email) $completion += 1;
	if ($profile && $profile->employer_name) $completion += 2;
	if ($member && $member->email_verified) $completion += 2;
	
	return min(100, round(($completion / $total_fields) * 100));
}

/**
 * Create notifications table for persistent notifications
 */
function iipm_create_notifications_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'test_iipm_notifications';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        type enum('success','error','warning','info') NOT NULL DEFAULT 'info',
        title varchar(255) NOT NULL,
        message text NOT NULL,
        action_url varchar(255) NULL,
        action_text varchar(100) NULL,
        is_read tinyint(1) DEFAULT 0,
        is_persistent tinyint(1) DEFAULT 0,
        expires_at timestamp NULL,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY type (type),
        KEY is_read (is_read),
        KEY created_at (created_at),
        KEY expires_at (expires_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Add persistent notification to database
 */
function iipm_add_persistent_notification($user_id, $type, $title, $message, $options = array()) {
    global $wpdb;
    
    $defaults = array(
        'action_url' => null,
        'action_text' => null,
        'is_persistent' => 0,
        'expires_in_days' => 30
    );
    
    $options = array_merge($defaults, $options);
    
    $expires_at = null;
    if (!$options['is_persistent'] && $options['expires_in_days'] > 0) {
        $expires_at = date('Y-m-d H:i:s', strtotime('+' . $options['expires_in_days'] . ' days'));
    }
    
    $result = $wpdb->insert(
        $wpdb->prefix . 'test_iipm_notifications',
        array(
            'user_id' => $user_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $options['action_url'],
            'action_text' => $options['action_text'],
            'is_persistent' => $options['is_persistent'],
            'expires_at' => $expires_at
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
    );
    
    if ($result) {
        $notification_id = $wpdb->insert_id;
        
        // Log activity
        iipm_log_user_activity($user_id, 'notification_created', "Notification created: {$title}");
        
        return $notification_id;
    }
    
    return false;
}

/**
 * Get user notifications from database
 */
function iipm_get_user_notifications($user_id, $limit = 50, $offset = 0, $filters = array()) {
    global $wpdb;
    
    $where_conditions = array('user_id = %d');
    $where_params = array($user_id);
    
    // Add expiration filter
    $where_conditions[] = '(expires_at IS NULL OR expires_at > NOW())';
    
    // Add type filter
    if (!empty($filters['type'])) {
        $where_conditions[] = 'type = %s';
        $where_params[] = $filters['type'];
    }
    
    // Add read status filter
    if (isset($filters['is_read'])) {
        $where_conditions[] = 'is_read = %d';
        $where_params[] = intval($filters['is_read']);
    }
    
    // Add search filter
    if (!empty($filters['search'])) {
        $where_conditions[] = '(title LIKE %s OR message LIKE %s)';
        $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
        $where_params[] = $search_term;
        $where_params[] = $search_term;
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    $sql = "SELECT * FROM {$wpdb->prefix}test_iipm_notifications 
            {$where_clause}
            ORDER BY created_at DESC 
            LIMIT %d OFFSET %d";
    
    $where_params[] = $limit;
    $where_params[] = $offset;
    
    return $wpdb->get_results($wpdb->prepare($sql, $where_params));
}

/**
 * Get notification count for user
 */
function iipm_get_user_notification_count($user_id, $filters = array()) {
    global $wpdb;
    
    $where_conditions = array('user_id = %d');
    $where_params = array($user_id);
    
    // Add expiration filter
    $where_conditions[] = '(expires_at IS NULL OR expires_at > NOW())';
    
    // Add type filter
    if (!empty($filters['type'])) {
        $where_conditions[] = 'type = %s';
        $where_params[] = $filters['type'];
    }
    
    // Add read status filter
    if (isset($filters['is_read'])) {
        $where_conditions[] = 'is_read = %d';
        $where_params[] = intval($filters['is_read']);
    }
    
    // Add search filter
    if (!empty($filters['search'])) {
        $where_conditions[] = '(title LIKE %s OR message LIKE %s)';
        $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
        $where_params[] = $search_term;
        $where_params[] = $search_term;
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_notifications {$where_clause}";
    
    return $wpdb->get_var($wpdb->prepare($sql, $where_params));
}

/**
 * Mark notification as read
 */
function iipm_mark_notification_read($notification_id, $user_id) {
    global $wpdb;
    
    return $wpdb->update(
        $wpdb->prefix . 'test_iipm_notifications',
        array('is_read' => 1, 'updated_at' => current_time('mysql')),
        array('id' => $notification_id, 'user_id' => $user_id),
        array('%d', '%s'),
        array('%d', '%d')
    );
}

/**
 * Mark all notifications as read for user
 */
function iipm_mark_all_notifications_read($user_id) {
    global $wpdb;
    
    return $wpdb->update(
        $wpdb->prefix . 'test_iipm_notifications',
        array('is_read' => 1, 'updated_at' => current_time('mysql')),
        array('user_id' => $user_id, 'is_read' => 0),
        array('%d', '%s'),
        array('%d', '%d')
    );
}

/**
 * Delete notification
 */
function iipm_delete_notification($notification_id, $user_id) {
    global $wpdb;
    
    return $wpdb->delete(
        $wpdb->prefix . 'test_iipm_notifications',
        array('id' => $notification_id, 'user_id' => $user_id),
        array('%d', '%d')
    );
}

/**
 * Delete all notifications for user
 */
function iipm_delete_all_notifications($user_id) {
    global $wpdb;
    
    return $wpdb->delete(
        $wpdb->prefix . 'test_iipm_notifications',
        array('user_id' => $user_id),
        array('%d')
    );
}

/**
 * Clean up expired notifications
 */
function iipm_cleanup_expired_notifications() {
    global $wpdb;
    
    $deleted = $wpdb->query(
        "DELETE FROM {$wpdb->prefix}test_iipm_notifications 
         WHERE expires_at IS NOT NULL AND expires_at < NOW()"
    );
    
    if ($deleted > 0) {
        error_log("IIPM: Cleaned up $deleted expired notifications");
    }
    
    return $deleted;
}

/**
 * NOTIFICATION SYSTEM INTEGRATION AND EXAMPLES
 * Demonstrates how to use the global notification system
 */

// Start session for notifications
if (!session_id()) {
    session_start();
}

/**
 * Example AJAX handler for CPD record operations with notifications
 */
add_action('wp_ajax_iipm_update_cpd_record', 'handle_cpd_record_update_with_notifications');
add_action('wp_ajax_nopriv_iipm_update_cpd_record', 'handle_cpd_record_update_with_notifications');

function handle_cpd_record_update_with_notifications() {
    check_ajax_referer('iipm_cpd_nonce', 'nonce');
    
    $user_id = intval($_POST['user_id']);
    $year = intval($_POST['year']);
    
    // Simulate processing
    sleep(1);
    
    // Example of adding server-side notifications
    if (rand(1, 10) > 7) {
        // Simulate an error scenario
        add_error_notification('Update Failed', 'Unable to update CPD record. Please try again.');
        wp_send_json_error('Failed to update record');
    } else {
        // Success scenario
        add_success_notification('Record Updated', 'Your CPD record has been successfully updated.');
        wp_send_json_success([
            'message' => 'CPD record updated successfully',
            'records' => 5,
            'total_minutes' => 330
        ]);
    }
}

/**
 * Example AJAX handler for certificate download with notifications
 */
add_action('wp_ajax_iipm_download_certificate', 'handle_certificate_download_with_notifications');
add_action('wp_ajax_nopriv_iipm_download_certificate', 'handle_certificate_download_with_notifications');

function handle_certificate_download_with_notifications() {
    check_ajax_referer('iipm_cpd_nonce', 'nonce');
    
    $user_id = intval($_POST['user_id']);
    $year = intval($_POST['year']);
    
    // Check if user has permission to download
    if (!current_user_can('read') || get_current_user_id() !== $user_id) {
        add_error_notification('Access Denied', 'You do not have permission to download this certificate.');
        wp_send_json_error('Access denied');
        return;
    }
    
    // Simulate certificate generation
    $certificate_path = generate_certificate($user_id, $year);
    
    if ($certificate_path) {
        add_success_notification('Certificate Ready', 'Your certificate has been generated and is ready for download.');
        
        // In a real implementation, you would serve the file here
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="CPD_Certificate_' . $year . '.pdf"');
        header('Content-Length: ' . filesize($certificate_path));
        
        // readfile($certificate_path);
        exit;
    } else {
        add_error_notification('Generation Failed', 'Unable to generate certificate. Please contact support.');
        wp_send_json_error('Certificate generation failed');
    }
}

/**
 * Simulate certificate generation
 */
function generate_certificate($user_id, $year) {
    // Simulate processing time
    sleep(2);
    
    // Simulate success/failure
    if (rand(1, 10) > 2) {
        return '/path/to/generated/certificate.pdf';
    }
    
    return false;
}

/**
 * Example usage in form processing
 */
if ($_POST && isset($_POST['demo_action'])) {
    switch ($_POST['demo_action']) {
        case 'success_demo':
            add_success_notification('Success!', 'This is a success notification demo.');
            break;
            
        case 'error_demo':
            add_error_notification('Error!', 'This is an error notification demo.');
            break;
            
        case 'warning_demo':
            add_warning_notification('Warning!', 'This is a warning notification demo.');
            break;
            
        case 'info_demo':
            add_info_notification('Information', 'This is an info notification demo.');
            break;
    }
    
    // Redirect to prevent form resubmission
    wp_redirect($_SERVER['REQUEST_URI']);
    exit;
}



function iipm_get_organisation_member_count($organisation_id) {
	global $wpdb;
	
	return $wpdb->get_var($wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_members WHERE organisation_id = %d",
		$organisation_id
	));
}

function iipm_get_organisation_active_members($organisation_id) {
	global $wpdb;
	
	return $wpdb->get_var($wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->prefix}test_iipm_members 
		 WHERE organisation_id = %d AND membership_status = 'active'",
		$organisation_id
	));
}

function iipm_get_activity_icon($action) {
	$icons = array(
		'login' => '🔐',
		'logout' => '🚪',
		'registration' => '📝',
		'profile_update' => '👤',
		'email_verified' => '✅',
		'bulk_import' => '📊',
		'cpd_logged' => '📚',
		'event_registered' => '📅',
		'payment_made' => '💳',
		'leave_request_submitted' => '📋',
		'leave_request_approved' => '✅',
		'leave_request_rejected' => '❌',
		'leave_request_cancelled' => '🚫'
	);
	
	return $icons[$action] ?? '📋';
}

if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}

add_filter('pre_wp_mail', 'iipm_send_email_alternative', 10, 2);

/**
 * Debug all login redirect filters
 */
function iipm_debug_all_login_filters() {
    global $wp_filter;
    
    if (isset($wp_filter['login_redirect'])) {
        error_log('IIPM: All login_redirect filters:');
        foreach ($wp_filter['login_redirect']->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $function_name = 'Unknown';
                if (is_string($callback['function'])) {
                    $function_name = $callback['function'];
                } elseif (is_array($callback['function'])) {
                    $function_name = get_class($callback['function'][0]) . '::' . $callback['function'][1];
                }
                error_log("Priority {$priority}: {$function_name}");
            }
        }
    }
}
add_action('init', 'iipm_debug_all_login_filters');

/**
 * CPD Management Functions
 */
function iipm_get_user_cpd_summary($user_id, $year = null) {
    if (!$year) {
        $year = date('Y');
    }
    
    global $wpdb;
    
    $summary = $wpdb->get_row($wpdb->prepare(
        "SELECT 
            SUM(CASE WHEN status = 'approved' THEN cpd_points ELSE 0 END) as total_points,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as completed_activities,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_activities,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_activities
        FROM {$wpdb->prefix}test_iipm_cpd_records 
        WHERE user_id = %d AND cpd_year = %d",
        $user_id,
        $year
    ));
    
    $member = $wpdb->get_row($wpdb->prepare(
        "SELECT cpd_points_required FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d",
        $user_id
    ));
    
    $required_points = $member ? $member->cpd_points_required : 40;
    
    return array(
        'total_points' => $summary ? $summary->total_points : 0,
        'required_points' => $required_points,
        'completed_activities' => $summary ? $summary->completed_activities : 0,
        'pending_activities' => $summary ? $summary->pending_activities : 0,
        'rejected_activities' => $summary ? $summary->rejected_activities : 0,
        'progress_percentage' => $required_points > 0 ? min(100, ($summary->total_points / $required_points) * 100) : 0
    );
}

function iipm_get_user_cpd_records($user_id, $limit = 20, $year = null) {
    global $wpdb;
    
    $where_clause = "WHERE cr.user_id = %d";
    $params = array($user_id);
    
    if ($year) {
        $where_clause .= " AND cr.cpd.cpd_year = %d";
        $params[] = $year;
    }
    
    $sql = "SELECT cr.*, cc.name as category_name, c.title as course_title, c.provider
            FROM {$wpdb->prefix}test_iipm_cpd_records cr
            LEFT JOIN {$wpdb->prefix}test_iipm_cpd_categories cc ON cr.category_id = cc.id
            LEFT JOIN {$wpdb->prefix}test_iipm_cpd_courses c ON cr.course_id = c.id
            {$where_clause}
            ORDER BY cr.completion_date DESC
            LIMIT %d";
    
    $params[] = $limit;
    
    return $wpdb->get_results($wpdb->prepare($sql, $params));
}

function iipm_handle_cpd_file_upload($file, $upload_dir = 'cpd-certificates') {
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }
    
    $upload_overrides = array(
        'test_form' => false,
        'upload_path' => wp_upload_dir()['basedir'] . '/' . $upload_dir
    );
    
    if (!file_exists($upload_overrides['upload_path'])) {
        wp_mkdir_p($upload_overrides['upload_path']);
    }
    
    $movefile = wp_handle_upload($file, $upload_overrides);
    
    if ($movefile && !isset($movefile['error'])) {
        return array(
            'success' => true,
            'url' => $movefile['url'],
            'file' => $movefile['file']
        );
    } else {
        return array(
            'success' => false,
            'error' => $movefile['error']
        );
    }
}

function iipm_add_cpd_body_classes($classes) {
    if (is_page_template('template-cpd-portal.php')) {
        $classes[] = 'iipm-cpd-portal';
    } elseif (is_page_template('template-cpd-admin.php')) {
        $classes[] = 'iipm-cpd-admin';
    }
    
    return $classes;
}
add_filter('body_class', 'iipm_add_cpd_body_classes');

/**
 * AJAX handler for deleting training records
 */
function iipm_delete_training_record() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_nonce')) {
        wp_die(json_encode(array('success' => false, 'data' => 'Security check failed')));
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_die(json_encode(array('success' => false, 'data' => 'User not logged in')));
    }
    
    $current_user_id = get_current_user_id();
    $record_id = intval($_POST['record_id']);
    $user_id = intval($_POST['user_id']);
    
    // Security check: user can only delete their own records
    if ($current_user_id !== $user_id && !current_user_can('administrator')) {
        wp_die(json_encode(array('success' => false, 'data' => 'Permission denied')));
    }
    
    global $wpdb;
    
    // First, verify the record exists and belongs to the user
    $record = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_cpd_records WHERE id = %d AND user_id = %d",
        $record_id,
        $user_id
    ));
    
    if (!$record) {
        wp_die(json_encode(array('success' => false, 'data' => 'Record not found')));
    }
    
    // Delete the record
    $deleted = $wpdb->delete(
        $wpdb->prefix . 'test_iipm_cpd_records',
        array(
            'id' => $record_id,
            'user_id' => $user_id
        ),
        array('%d', '%d')
    );
    
    if ($deleted === false) {
        wp_die(json_encode(array('success' => false, 'data' => 'Failed to delete record')));
    }
    
    // Log the activity
    iipm_log_user_activity($user_id, 'cpd_deleted', 'CPD training record deleted', array(
        'record_id' => $record_id,
        'course_title' => $record->activity_title ?: $record->course_title,
        'cpd_points' => $record->cpd_points
    ));
    
    // Get updated CPD summary for the user
    $updated_summary = iipm_get_user_cpd_summary_with_categories($user_id);
    
    wp_die(json_encode(array(
        'success' => true, 
        'data' => array(
            'message' => 'Training record deleted successfully',
            'summary' => $updated_summary
        )
    )));
}
add_action('wp_ajax_iipm_delete_training_record', 'iipm_delete_training_record');

/**
 * AJAX handler for restoring training records (undo delete)
 */
function iipm_restore_training_record() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_nonce')) {
        wp_die(json_encode(array('success' => false, 'data' => 'Security check failed')));
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_die(json_encode(array('success' => false, 'data' => 'User not logged in')));
    }
    
    $current_user_id = get_current_user_id();
    $user_id = intval($_POST['user_id']);
    $record_data = json_decode(stripslashes($_POST['record_data']), true);
    
    // Security check: user can only restore their own records
    if ($current_user_id !== $user_id && !current_user_can('administrator')) {
        wp_die(json_encode(array('success' => false, 'data' => 'Permission denied')));
    }
    
    if (!$record_data || !isset($record_data['id'])) {
        wp_die(json_encode(array('success' => false, 'data' => 'Invalid record data')));
    }
    
    global $wpdb;
    
    // Prepare record data for insertion (remove the id for re-insertion)
    $insert_data = array(
        'user_id' => $user_id,
        'course_id' => $record_data['course_id'],
        'category_id' => $record_data['category_id'],
        'activity_title' => $record_data['activity_title'],
        'external_provider' => $record_data['external_provider'],
        'cpd_points' => $record_data['cpd_points'],
        'completion_date' => $record_data['completion_date'],
        'status' => $record_data['status'],
        'cpd_year' => $record_data['cpd_year'],
        'certificate_url' => $record_data['certificate_url'],
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    );
    
    // Remove null values
    $insert_data = array_filter($insert_data, function($value) {
        return $value !== null;
    });
    
    // Insert the record back
    $inserted = $wpdb->insert(
        $wpdb->prefix . 'test_iipm_cpd_records',
        $insert_data
    );
    
    if ($inserted === false) {
        wp_die(json_encode(array('success' => false, 'data' => 'Failed to restore record')));
    }
    
    // Log the activity
    iipm_log_user_activity($user_id, 'cpd_restored', 'CPD training record restored', array(
        'original_record_id' => $record_data['id'],
        'new_record_id' => $wpdb->insert_id,
        'course_title' => $record_data['activity_title'] ?: $record_data['course_title'],
        'cpd_points' => $record_data['cpd_points']
    ));
    
    // Get updated CPD summary for the user
    $updated_summary = iipm_get_user_cpd_summary_with_categories($user_id);
    
    wp_die(json_encode(array(
        'success' => true, 
        'data' => array(
            'message' => 'Training record restored successfully',
            'summary' => $updated_summary,
            'new_record_id' => $wpdb->insert_id
        )
    )));
}
add_action('wp_ajax_iipm_restore_training_record', 'iipm_restore_training_record');

/**
 * Get CPD summary with category breakdown
 */
function iipm_get_user_cpd_summary_with_categories($user_id, $year = null) {
    if (!$year) {
        $year = date('Y');
    }
    
    global $wpdb;
    
    // Get category-based CPD progress
    $categories = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            cc.id,
            cc.name,
            cc.min_points_required as required_points,
            COALESCE(SUM(CASE WHEN cr.status = 'approved' THEN cr.cpd_points ELSE 0 END), 0) as earned_points
        FROM {$wpdb->prefix}test_iipm_cpd_categories cc
        LEFT JOIN {$wpdb->prefix}test_iipm_cpd_records cr ON cc.id = cr.category_id 
            AND cr.user_id = %d AND cr.cpd_year = %d
        WHERE cc.is_active = 1 AND cc.is_mandatory = 1
        GROUP BY cc.id, cc.name, cc.min_points_required
        ORDER BY cc.sort_order ASC",
        $user_id,
        $year
    ));
    
    $summary = array('categories' => array());
    
    foreach ($categories as $category) {
        $summary['categories'][$category->name] = array(
            'points' => $category->earned_points,
            'required' => $category->required_points
        );
    }
    
    return $summary;
}

/**
 * CPD Return Submission Functions
 */

/**
 * Check if user can submit CPD return
 */
function iipm_can_submit_cpd_return($user_id, $year = null) {
    if (!$year) {
        $year = date('Y');
    }
    
    global $wpdb;
    
    // Check if user has already submitted for this year
    $existing_return = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}test_iipm_cpd_returns WHERE user_id = %d AND cpd_year = %d",
        $user_id,
        $year
    ));
    
    if ($existing_return) {
        return false; // Already submitted
    }
    
    // Get user's CPD progress for mandatory categories
    $cpd_categories = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            cc.id,
            cc.name,
            cc.min_points_required as required_points,
            COALESCE(SUM(CASE WHEN cr.status = 'approved' THEN cr.cpd_points ELSE 0 END), 0) as earned_points
        FROM {$wpdb->prefix}test_iipm_cpd_categories cc
        LEFT JOIN {$wpdb->prefix}test_iipm_cpd_records cr ON cc.id = cr.category_id 
            AND cr.user_id = %d AND cr.cpd_year = %d
        WHERE cc.is_active = 1 AND cc.is_mandatory = 1
        GROUP BY cc.id, cc.name, cc.min_points_required
        ORDER BY cc.sort_order ASC",
        $user_id,
        $year
    ));
    
    // Check if all mandatory categories meet minimum requirements
    foreach ($cpd_categories as $category) {
        if ($category->earned_points < $category->required_points) {
            return false; // Not all categories completed
        }
    }
    
    return true; // All requirements met
}

/**
 * Get CPD return submission eligibility status
 */
function iipm_get_cpd_return_status($user_id, $year = null) {
    if (!$year) {
        $year = date('Y');
    }
    
    global $wpdb;
    
    // Check if already submitted
    $existing_return = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_cpd_returns WHERE user_id = %d AND cpd_year = %d",
        $user_id,
        $year
    ));
    
    if ($existing_return) {
        return array(
            'can_submit' => false,
            'status' => 'submitted',
            'submission_date' => $existing_return->submission_date,
            'submission_status' => $existing_return->submission_status,
            'certificate_issued' => $existing_return->certificate_issued
        );
    }
    
    // Get user's CPD progress for mandatory categories
    $cpd_categories = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            cc.id,
            cc.name,
            cc.min_points_required as required_points,
            COALESCE(SUM(CASE WHEN cr.status = 'approved' THEN cr.cpd_points ELSE 0 END), 0) as earned_points
        FROM {$wpdb->prefix}test_iipm_cpd_categories cc
        LEFT JOIN {$wpdb->prefix}test_iipm_cpd_records cr ON cc.id = cr.category_id 
            AND cr.user_id = %d AND cr.cpd_year = %d
        WHERE cc.is_active = 1 AND cc.is_mandatory = 1
        GROUP BY cc.id, cc.name, cc.min_points_required
        ORDER BY cc.sort_order ASC",
        $user_id,
        $year
    ));
    
    $total_earned = 0;
    $total_required = 0;
    $categories_met = 0;
    $category_details = array();
    
    foreach ($cpd_categories as $category) {
        $total_earned += $category->earned_points;
        $total_required += $category->required_points;
        
        $is_met = $category->earned_points >= $category->required_points;
        if ($is_met) {
            $categories_met++;
        }
        
        $category_details[] = array(
            'name' => $category->name,
            'earned' => $category->earned_points,
            'required' => $category->required_points,
            'met' => $is_met
        );
    }
    
    $can_submit = $categories_met === count($cpd_categories) && $total_earned >= $total_required;
    
    return array(
        'can_submit' => $can_submit,
        'status' => 'not_submitted',
        'total_earned' => $total_earned,
        'total_required' => $total_required,
        'categories_met' => $categories_met,
        'total_categories' => count($cpd_categories),
        'category_details' => $category_details
    );
}

/**
 * Submit CPD return
 */
function iipm_submit_cpd_return($user_id, $year = null) {
    if (!$year) {
        $year = date('Y');
    }
    
    global $wpdb;
    
    // Check if user can submit
    $status = iipm_get_cpd_return_status($user_id, $year);
    if (!$status['can_submit']) {
        return array(
            'success' => false,
            'message' => 'CPD requirements not met or already submitted'
        );
    }
    
    // Get member data for required points
    $member = $wpdb->get_row($wpdb->prepare(
        "SELECT cpd_points_required, cpd_prorata_adjustment FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d",
        $user_id
    ));
    
    if (!$member) {
        return array(
            'success' => false,
            'message' => 'Member record not found'
        );
    }
    
    $required_points = $member->cpd_points_required - ($member->cpd_prorata_adjustment ?: 0);
    
    // Create submission record
    $submission_data = array(
        'user_id' => $user_id,
        'cpd_year' => $year,
        'total_cpd_points' => $status['total_earned'],
        'required_points' => $required_points,
        'category_breakdown' => json_encode($status['category_details']),
        'compliance_status' => 'compliant',
        'submission_status' => 'submitted'
    );
    
    $result = $wpdb->insert(
        $wpdb->prefix . 'test_iipm_cpd_returns',
        $submission_data,
        array('%d', '%d', '%f', '%f', '%s', '%s', '%s')
    );
    
    if ($result === false) {
        return array(
            'success' => false,
            'message' => 'Failed to submit CPD return'
        );
    }
    
    // Log the activity
    if (function_exists('iipm_log_user_activity')) {
        iipm_log_user_activity($user_id, 'cpd_return_submitted', "Submitted CPD return for year {$year}");
    }
    
    return array(
        'success' => true,
        'message' => 'CPD return submitted successfully',
        'submission_id' => $wpdb->insert_id
    );
}

/**
 * AJAX handler for CPD return submission
 */
function iipm_ajax_submit_cpd_return() {
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!current_user_can('view_cpd_records')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    $user_id = get_current_user_id();
    $year = intval($_POST['year'] ?? date('Y'));
    
    $result = iipm_submit_cpd_return($user_id, $year);
    
    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result['message']);
    }
}
add_action('wp_ajax_iipm_submit_cpd_return', 'iipm_ajax_submit_cpd_return');

/**
 * AJAX handler for checking CPD return status
 */
function iipm_ajax_check_cpd_return_status() {
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_cpd_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!current_user_can('view_cpd_records')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    $user_id = get_current_user_id();
    $year = intval($_POST['year'] ?? date('Y'));
    
    $status = iipm_get_cpd_return_status($user_id, $year);
    wp_send_json_success($status);
}
add_action('wp_ajax_iipm_check_cpd_return_status', 'iipm_ajax_check_cpd_return_status');

/**
 * Leave Request Functions
 */

/**
 * Check if CPD tables exist
 */

// AJAX: Search employers from wp_employers (emp_ID, emp_Name)
function iipm_search_employers_ajax() {
    global $wpdb;

    $table = $wpdb->prefix . 'employers';

    // Optional filter by q; no pagination
    $term = isset($_REQUEST['q']) ? trim(wp_unslash($_REQUEST['q'])) : '';
    $where = '';
    if ($term !== '') {
        $like = '%' . $wpdb->esc_like($term) . '%';
        $where = $wpdb->prepare('WHERE emp_Name LIKE %s', $like);
    }

    $sql = "SELECT emp_ID, emp_Name FROM {$table} {$where} ORDER BY emp_Name ASC";
    $rows = $wpdb->get_results($sql);

    $results = array();
    foreach ((array)$rows as $row) {
        $results[] = array(
            'id' => (string) $row->emp_ID,
            'text' => $row->emp_Name,
        );
    }

    wp_send_json(array(
        'results' => $results
    ));
}
add_action('wp_ajax_iipm_search_employers', 'iipm_search_employers_ajax');
add_action('wp_ajax_nopriv_iipm_search_employers', 'iipm_search_employers_ajax');

// AJAX: Search organisations for organisation select
function iipm_search_organisations_ajax() {
    global $wpdb;

    $table = $wpdb->prefix . 'test_iipm_organisations';

    // Optional filter by q; no pagination
    $term = isset($_REQUEST['q']) ? trim(wp_unslash($_REQUEST['q'])) : '';
    $where = '';
    if ($term !== '') {
        $like = '%' . $wpdb->esc_like($term) . '%';
        $where = $wpdb->prepare('WHERE name LIKE %s', $like);
    }

    $sql = "SELECT id, name FROM {$table} {$where} ORDER BY name ASC";
    $rows = $wpdb->get_results($sql);

    $results = array();
    foreach ((array)$rows as $row) {
        $results[] = array(
            'id' => (string) $row->id,
            'text' => $row->name,
        );
    }

    wp_send_json(array(
        'results' => $results
    ));
}
add_action('wp_ajax_iipm_search_organisations', 'iipm_search_organisations_ajax');
add_action('wp_ajax_nopriv_iipm_search_organisations', 'iipm_search_organisations_ajax');

// AJAX: Get organisation name by ID
function iipm_get_organisation_name_ajax() {
    global $wpdb;
    
    $organisation_id = isset($_REQUEST['organisation_id']) ? intval($_REQUEST['organisation_id']) : 0;
    
    if (!$organisation_id) {
        wp_send_json_error('Organisation ID is required');
        return;
    }
    
    $table = $wpdb->prefix . 'test_iipm_organisations';
    $org = $wpdb->get_row($wpdb->prepare(
        "SELECT id, name FROM {$table} WHERE id = %d",
        $organisation_id
    ));
    
    if ($org) {
        wp_send_json_success(array(
            'id' => $org->id,
            'name' => $org->name
        ));
    } else {
        wp_send_json_error('Organisation not found');
    }
}
add_action('wp_ajax_iipm_get_organisation_name', 'iipm_get_organisation_name_ajax');
add_action('wp_ajax_nopriv_iipm_get_organisation_name', 'iipm_get_organisation_name_ajax');

