<?php
/*
Template Name: SMTP Debug
*/

// Only allow administrators to access this page
if (!current_user_can('administrator')) {
    wp_die('You do not have permission to access this page.');
}

// Set up the PHPMailer for testing
function test_smtp_connection() {
    global $phpmailer;
    
    // Make sure PHPMailer is loaded
    if (!is_object($phpmailer) || !is_a($phpmailer, 'PHPMailer\\PHPMailer\\PHPMailer')) {
        require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
        require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
        require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
        $phpmailer = new PHPMailer\PHPMailer\PHPMailer(true);
    }
    
    // Clear any previous settings
    $phpmailer->clearAllRecipients();
    $phpmailer->clearAttachments();
    $phpmailer->clearCustomHeaders();
    $phpmailer->clearReplyTos();
    
    // Set up SMTP
    $phpmailer->isSMTP();
    $phpmailer->Host = SMTP_HOST;
    $phpmailer->SMTPAuth = SMTP_AUTH;
    $phpmailer->Port = SMTP_PORT;
    $phpmailer->Username = SMTP_USER;
    $phpmailer->Password = SMTP_PASS;
    $phpmailer->SMTPSecure = SMTP_SECURE;
    $phpmailer->From = SMTP_FROM;
    $phpmailer->FromName = SMTP_NAME;
    
    // Enable debug output
    $phpmailer->SMTPDebug = 2; // Verbose debug output
    $phpmailer->Debugoutput = function($str, $level) {
        echo "Debug: $str<br>";
    };
    
    // Set up the test email
    $phpmailer->addAddress(SMTP_FROM); // Send to yourself
    $phpmailer->Subject = 'SMTP Test Email';
    $phpmailer->Body = 'This is a test email to verify SMTP configuration.';
    $phpmailer->isHTML(true);
    
    // Try to send the email
    try {
        ob_start();
        $result = $phpmailer->send();
        $debug_output = ob_get_clean();
        
        if ($result) {
            echo '<div style="background-color: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                <strong>Success!</strong> Email was sent successfully.
            </div>';
        } else {
            echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                <strong>Error!</strong> Email could not be sent.
            </div>';
        }
        
        echo '<h3>Debug Output:</h3>';
        echo '<pre style="background-color: #f8f9fa; padding: 15px; border-radius: 4px; overflow: auto;">';
        echo $debug_output;
        echo '</pre>';
        
    } catch (Exception $e) {
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <strong>Exception:</strong> ' . $e->getMessage() . '
        </div>';
    }
}

get_header();
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-12">
            <h1>SMTP Debug Tool</h1>
            <p>This page tests your SMTP configuration and provides detailed debugging information.</p>
            
            <h2>Current SMTP Configuration</h2>
            <table class="table table-bordered">
                <tr>
                    <th>Setting</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>SMTP Host</td>
                    <td><?php echo defined('SMTP_HOST') ? SMTP_HOST : 'Not defined'; ?></td>
                </tr>
                <tr>
                    <td>SMTP Port</td>
                    <td><?php echo defined('SMTP_PORT') ? SMTP_PORT : 'Not defined'; ?></td>
                </tr>
                <tr>
                    <td>SMTP User</td>
                    <td><?php echo defined('SMTP_USER') ? SMTP_USER : 'Not defined'; ?></td>
                </tr>
                <tr>
                    <td>SMTP Password</td>
                    <td><?php echo defined('SMTP_PASS') ? '********' : 'Not defined'; ?></td>
                </tr>
                <tr>
                    <td>SMTP From</td>
                    <td><?php echo defined('SMTP_FROM') ? SMTP_FROM : 'Not defined'; ?></td>
                </tr>
                <tr>
                    <td>SMTP Name</td>
                    <td><?php echo defined('SMTP_NAME') ? SMTP_NAME : 'Not defined'; ?></td>
                </tr>
                <tr>
                    <td>SMTP Secure</td>
                    <td><?php echo defined('SMTP_SECURE') ? SMTP_SECURE : 'Not defined'; ?></td>
                </tr>
                <tr>
                    <td>SMTP Auth</td>
                    <td><?php echo defined('SMTP_AUTH') ? (SMTP_AUTH ? 'true' : 'false') : 'Not defined'; ?></td>
                </tr>
            </table>
            
            <h2>PHP Information</h2>
            <table class="table table-bordered">
                <tr>
                    <td>PHP Version</td>
                    <td><?php echo phpversion(); ?></td>
                </tr>
                <tr>
                    <td>OpenSSL Extension</td>
                    <td><?php echo extension_loaded('openssl') ? 'Loaded' : 'Not loaded'; ?></td>
                </tr>
                <tr>
                    <td>Socket Extension</td>
                    <td><?php echo extension_loaded('sockets') ? 'Loaded' : 'Not loaded'; ?></td>
                </tr>
                <tr>
                    <td>Allow URL fopen</td>
                    <td><?php echo ini_get('allow_url_fopen') ? 'Enabled' : 'Disabled'; ?></td>
                </tr>
            </table>
            
            <h2>Test SMTP Connection</h2>
            <form method="post">
                <button type="submit" name="test_smtp" class="btn btn-primary">Test SMTP Connection</button>
            </form>
            
            <?php
            if (isset($_POST['test_smtp'])) {
                echo '<h3 class="mt-4">Test Results</h3>';
                test_smtp_connection();
            }
            ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
