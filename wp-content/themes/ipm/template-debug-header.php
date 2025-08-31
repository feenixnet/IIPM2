<?php
/**
 * Template Name: Debug Header System
 * 
 * @package IPM
 */

// Only allow administrators to access this debug page
if (!current_user_can('administrator')) {
    wp_die('Access denied. Administrator privileges required.');
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IIPM Header Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f1f1f1; }
        .debug-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .debug-section h2 { color: #0073aa; margin-top: 0; }
        .status-good { color: #46b450; font-weight: bold; }
        .status-bad { color: #dc3232; font-weight: bold; }
        .status-warning { color: #ffb900; font-weight: bold; }
        .code-block { background: #f8f8f8; padding: 15px; border-left: 4px solid #0073aa; margin: 10px 0; font-family: monospace; }
        .file-content { background: #f0f0f0; padding: 10px; margin: 10px 0; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .test-button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .test-button:hover { background: #005a87; }
    </style>
</head>
<body>

<h1>🔍 IIPM Header System Debug</h1>

<?php
// Get current page info
$current_page_id = get_the_ID();
$current_template = get_page_template_slug();
$current_url = home_url($_SERVER['REQUEST_URI']);

// Debug functions
function debug_status($condition, $good_text, $bad_text) {
    if ($condition) {
        return '<span class="status-good">✅ ' . $good_text . '</span>';
    } else {
        return '<span class="status-bad">❌ ' . $bad_text . '</span>';
    }
}

function debug_file_exists($file_path, $description) {
    $exists = file_exists($file_path);
    $readable = $exists ? is_readable($file_path) : false;
    $size = $exists ? filesize($file_path) : 0;
    
    echo '<tr>';
    echo '<td>' . $description . '</td>';
    echo '<td>' . debug_status($exists, 'Exists', 'Missing') . '</td>';
    echo '<td>' . debug_status($readable, 'Readable', 'Not readable') . '</td>';
    echo '<td>' . ($exists ? number_format($size) . ' bytes' : 'N/A') . '</td>';
    echo '<td><code>' . $file_path . '</code></td>';
    echo '</tr>';
}
?>

<div class="debug-section">
    <h2>📄 Current Page Information</h2>
    <table>
        <tr><th>Property</th><th>Value</th></tr>
        <tr><td>Page ID</td><td><?php echo $current_page_id; ?></td></tr>
        <tr><td>Page Template</td><td><?php echo $current_template ?: 'default'; ?></td></tr>
        <tr><td>Current URL</td><td><?php echo $current_url; ?></td></tr>
        <tr><td>Is Admin</td><td><?php echo current_user_can('administrator') ? 'Yes' : 'No'; ?></td></tr>
        <tr><td>Is User Logged In</td><td><?php echo is_user_logged_in() ? 'Yes' : 'No'; ?></td></tr>
    </table>
</div>

<div class="debug-section">
    <h2>🔧 Function Availability</h2>
    <table>
        <tr><th>Function</th><th>Status</th><th>Description</th></tr>
        <tr>
            <td><code>iipm_get_header_type()</code></td>
            <td><?php echo debug_status(function_exists('iipm_get_header_type'), 'Available', 'Missing'); ?></td>
            <td>Determines which header to use</td>
        </tr>
        <tr>
            <td><code>iipm_load_header()</code></td>
            <td><?php echo debug_status(function_exists('iipm_load_header'), 'Available', 'Missing'); ?></td>
            <td>Loads the appropriate header</td>
        </tr>
        <tr>
            <td><code>iipm_ensure_header_files()</code></td>
            <td><?php echo debug_status(function_exists('iipm_ensure_header_files'), 'Available', 'Missing'); ?></td>
            <td>Creates missing header files</td>
        </tr>
    </table>
</div>

<?php if (function_exists('iipm_get_header_type')): ?>
<div class="debug-section">
    <h2>🎯 Header Type Detection</h2>
    <?php
    $detected_header_type = iipm_get_header_type();
    $meta_header_type = get_post_meta($current_page_id, '_iipm_header_type', true);
    ?>
    <table>
        <tr><th>Detection Method</th><th>Result</th><th>Details</th></tr>
        <tr>
            <td>Detected Header Type</td>
            <td><strong><?php echo $detected_header_type; ?></strong></td>
            <td>Final decision by iipm_get_header_type()</td>
        </tr>
        <tr>
            <td>Meta Field Override</td>
            <td><?php echo $meta_header_type ?: 'None'; ?></td>
            <td>Custom setting from page edit screen</td>
        </tr>
        <tr>
            <td>Template-based Detection</td>
            <td><?php 
            $simple_templates = array('template-leave-request.php', 'template-cpd-courses.php', 'template-cpd-portal.php', 'template-cpd-admin.php', 'template-member-portal.php');
            echo in_array($current_template, $simple_templates) ? 'Simple' : 'Default';
            ?></td>
            <td>Based on page template</td>
        </tr>
    </table>
</div>
<?php endif; ?>

<div class="debug-section">
    <h2>📁 File System Check</h2>
    <table>
        <tr><th>File</th><th>Exists</th><th>Readable</th><th>Size</th><th>Path</th></tr>
        <?php
        $theme_dir = get_template_directory();
        debug_file_exists($theme_dir . '/header.php', 'Default Header');
        debug_file_exists($theme_dir . '/header-original.php', 'Original Header Backup');
        debug_file_exists($theme_dir . '/includes/header-simple.php', 'Simple Header');
        debug_file_exists($theme_dir . '/template-leave-request.php', 'Leave Request Template');
        debug_file_exists($theme_dir . '/functions.php', 'Functions File');
        debug_file_exists($theme_dir . '/includes/', 'Includes Directory');
        ?>
    </table>
</div>

<div class="debug-section">
    <h2>📝 Template Content Check</h2>
    <?php
    $leave_request_template = $theme_dir . '/template-leave-request.php';
    if (file_exists($leave_request_template)):
        $content = file_get_contents($leave_request_template);
        $has_iipm_load_header = strpos($content, 'iipm_load_header()') !== false;
        $has_get_header = strpos($content, 'get_header()') !== false;
        $has_function_check = strpos($content, 'function_exists(\'iipm_load_header\')') !== false;
    ?>
    <table>
        <tr><th>Check</th><th>Status</th><th>Description</th></tr>
        <tr>
            <td>Contains iipm_load_header()</td>
            <td><?php echo debug_status($has_iipm_load_header, 'Yes', 'No'); ?></td>
            <td>Uses custom header loading function</td>
        </tr>
        <tr>
            <td>Contains get_header()</td>
            <td><?php echo debug_status($has_get_header, 'Yes', 'No'); ?></td>
            <td>Uses WordPress default header function</td>
        </tr>
        <tr>
            <td>Has function_exists check</td>
            <td><?php echo debug_status($has_function_check, 'Yes', 'No'); ?></td>
            <td>Checks if custom function exists before using</td>
        </tr>
    </table>
    
    <h3>Template Header Section:</h3>
    <div class="file-content">
        <?php
        $lines = explode("\n", $content);
        $header_section = array_slice($lines, 0, 30); // First 30 lines
        echo htmlspecialchars(implode("\n", $header_section));
        ?>
    </div>
    <?php else: ?>
    <p class="status-bad">❌ Leave Request template file not found!</p>
    <?php endif; ?>
</div>

<div class="debug-section">
    <h2>🔍 Simple Header File Check</h2>
    <?php
    $simple_header_path = $theme_dir . '/includes/header-simple.php';
    if (file_exists($simple_header_path)):
        $simple_content = file_get_contents($simple_header_path);
        $content_length = strlen($simple_content);
    ?>
    <p class="status-good">✅ Simple header file exists (<?php echo number_format($content_length); ?> characters)</p>
    
    <h3>Simple Header Content Preview:</h3>
    <div class="file-content">
        <?php echo htmlspecialchars(substr($simple_content, 0, 1000)) . ($content_length > 1000 ? '...' : ''); ?>
    </div>
    <?php else: ?>
    <p class="status-bad">❌ Simple header file is missing!</p>
    <div class="code-block">
        Expected location: <code><?php echo $simple_header_path; ?></code>
    </div>
    <?php endif; ?>
</div>

<div class="debug-section">
    <h2>🧪 Live Tests</h2>
    <p>Click these buttons to test different scenarios:</p>
    
    <button class="test-button" onclick="testHeaderFunction()">Test iipm_load_header()</button>
    <button class="test-button" onclick="testFileCreation()">Test File Creation</button>
    <button class="test-button" onclick="viewPageSource()">View Page Source</button>
    <button class="test-button" onclick="testLeaveRequestPage()">Test Leave Request Page</button>
    
    <div id="test-results" style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 4px; display: none;">
        <h4>Test Results:</h4>
        <div id="test-output"></div>
    </div>
</div>

<div class="debug-section">
    <h2>🔧 Quick Fixes</h2>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div>
            <h3>Option 1: Create Missing Files</h3>
            <button class="test-button" onclick="createMissingFiles()">Create Missing Header Files</button>
            <p>This will create the missing header files automatically.</p>
        </div>
        <div>
            <h3>Option 2: Force Simple Header</h3>
            <button class="test-button" onclick="forceSimpleHeader()">Force Simple Header for Leave Request</button>
            <p>This will force the Leave Request page to use the simple header.</p>
        </div>
    </div>
</div>

<div class="debug-section">
    <h2>📋 WordPress Environment</h2>
    <table>
        <tr><th>Setting</th><th>Value</th></tr>
        <tr><td>WordPress Version</td><td><?php echo get_bloginfo('version'); ?></td></tr>
        <tr><td>Theme Name</td><td><?php echo wp_get_theme()->get('Name'); ?></td></tr>
        <tr><td>Theme Version</td><td><?php echo wp_get_theme()->get('Version'); ?></td></tr>
        <tr><td>Active Plugins</td><td><?php echo count(get_option('active_plugins')); ?></td></tr>
        <tr><td>Debug Mode</td><td><?php echo defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled'; ?></td></tr>
        <tr><td>Memory Limit</td><td><?php echo ini_get('memory_limit'); ?></td></tr>
    </table>
</div>

<script>
function testHeaderFunction() {
    showTestResults('Testing iipm_load_header() function...');
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=iipm_debug_header_function&nonce=<?php echo wp_create_nonce('iipm_debug'); ?>'
    })
    .then(response => response.json())
    .then(data => {
        showTestResults('Header function test result: ' + JSON.stringify(data, null, 2));
    })
    .catch(error => {
        showTestResults('Error testing header function: ' + error);
    });
}

function testFileCreation() {
    showTestResults('Testing file creation...');
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=iipm_debug_create_files&nonce=<?php echo wp_create_nonce('iipm_debug'); ?>'
    })
    .then(response => response.json())
    .then(data => {
        showTestResults('File creation test result: ' + JSON.stringify(data, null, 2));
        setTimeout(() => location.reload(), 2000); // Reload page after 2 seconds
    })
    .catch(error => {
        showTestResults('Error testing file creation: ' + error);
    });
}

function createMissingFiles() {
    if (confirm('This will create missing header files. Continue?')) {
        testFileCreation();
    }
}

function forceSimpleHeader() {
    showTestResults('Forcing simple header for Leave Request page...');
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=iipm_debug_force_simple_header&nonce=<?php echo wp_create_nonce('iipm_debug'); ?>'
    })
    .then(response => response.json())
    .then(data => {
        showTestResults('Force simple header result: ' + JSON.stringify(data, null, 2));
    })
    .catch(error => {
        showTestResults('Error forcing simple header: ' + error);
    });
}

function viewPageSource() {
    window.open('view-source:<?php echo home_url('/leave-request/'); ?>', '_blank');
}

function testLeaveRequestPage() {
    window.open('<?php echo home_url('/leave-request/'); ?>', '_blank');
}

function showTestResults(message) {
    const resultsDiv = document.getElementById('test-results');
    const outputDiv = document.getElementById('test-output');
    outputDiv.innerHTML = '<pre>' + message + '</pre>';
    resultsDiv.style.display = 'block';
}
</script>

<?php
// Add AJAX handlers for debug functions
add_action('wp_ajax_iipm_debug_header_function', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_debug') || !current_user_can('administrator')) {
        wp_send_json_error('Access denied');
        return;
    }
    
    $result = array();
    
    // Test if function exists
    $result['function_exists'] = function_exists('iipm_load_header');
    
    if ($result['function_exists']) {
        // Test header type detection
        $result['header_type'] = function_exists('iipm_get_header_type') ? iipm_get_header_type() : 'function_missing';
        
        // Test file paths
        $theme_dir = get_template_directory();
        $result['simple_header_exists'] = file_exists($theme_dir . '/includes/header-simple.php');
        $result['original_header_exists'] = file_exists($theme_dir . '/header-original.php');
    }
    
    wp_send_json_success($result);
});

add_action('wp_ajax_iipm_debug_create_files', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_debug') || !current_user_can('administrator')) {
        wp_send_json_error('Access denied');
        return;
    }
    
    $result = array();
    $theme_dir = get_template_directory();
    
    // Create includes directory if it doesn't exist
    $includes_dir = $theme_dir . '/includes';
    if (!file_exists($includes_dir)) {
        $created = wp_mkdir_p($includes_dir);
        $result['includes_dir_created'] = $created;
    } else {
        $result['includes_dir_exists'] = true;
    }
    
    // Create simple header if it doesn't exist
    $simple_header_path = $includes_dir . '/header-simple.php';
    if (!file_exists($simple_header_path)) {
        $simple_header_content = file_get_contents($theme_dir . '/includes/header-simple.php');
        if (!$simple_header_content) {
            // Create a basic simple header
            $simple_header_content = '<?php
/**
 * Simple Header Template
 * Used for portal pages like Leave Request, CPD, etc.
 * 
 * @package IPM
 */
?>

<header class="header-simple">
    <div class="header-simple__container">
        <div class="header-simple__inner">
            <div class="header-simple__logo">
                <a href="' . home_url() . '">
                    <div class="logo-text">IIPM</div>
                </a>
            </div>
            <div class="header-simple__actions">
                <?php if (is_user_logged_in()): ?>
                    <a href="' . home_url('/member-portal/') . '">Member Portal</a>
                    <a href="' . wp_logout_url(home_url()) . '">Logout</a>
                <?php else: ?>
                    <a href="' . home_url('/login/') . '">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<style>
.header-simple {
    background: #8b5a96;
    padding: 15px 0;
    color: white;
}
.header-simple__container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}
.header-simple__inner {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.logo-text {
    font-size: 24px;
    font-weight: bold;
    color: white;
    text-decoration: none;
}
.header-simple__actions a {
    color: white;
    text-decoration: none;
    margin-left: 20px;
}
</style>';
        }
        
        $created = file_put_contents($simple_header_path, $simple_header_content);
        $result['simple_header_created'] = $created !== false;
    } else {
        $result['simple_header_exists'] = true;
    }
    
    // Create original header backup if it doesn't exist
    $original_header_path = $theme_dir . '/header-original.php';
    if (!file_exists($original_header_path)) {
        $header_content = file_get_contents($theme_dir . '/header.php');
        if ($header_content) {
            $created = file_put_contents($original_header_path, $header_content);
            $result['original_header_created'] = $created !== false;
        }
    } else {
        $result['original_header_exists'] = true;
    }
    
    wp_send_json_success($result);
});

add_action('wp_ajax_iipm_debug_force_simple_header', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'iipm_debug') || !current_user_can('administrator')) {
        wp_send_json_error('Access denied');
        return;
    }
    
    // Find the Leave Request page
    $leave_request_page = get_page_by_path('leave-request');
    
    if ($leave_request_page) {
        // Force simple header
        $updated = update_post_meta($leave_request_page->ID, '_iipm_header_type', 'simple');
        wp_send_json_success(array(
            'page_id' => $leave_request_page->ID,
            'meta_updated' => $updated,
            'current_meta' => get_post_meta($leave_request_page->ID, '_iipm_header_type', true)
        ));
    } else {
        wp_send_json_error('Leave Request page not found');
    }
});
?>

</body>
</html>
