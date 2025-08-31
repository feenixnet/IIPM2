<?php
/*
Template Name: Login Page
*/

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    // Attempt to authenticate user
    $user = wp_authenticate($email, $password);
    
    if (is_wp_error($user)) {
        $login_error = $user->get_error_message();
    } else {
        // Login successful
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember);
        
        // Redirect to dashboard instead of member portal
        wp_redirect(home_url('/dashboard/'));
        exit;
    }
}

// If user is already logged in, redirect them to dashboard
if (is_user_logged_in()) {
    wp_redirect(home_url('/dashboard/'));
    exit;
}

get_header();
?>

<main class="login" style="padding-top: 120px;">
    <div class="banner">
        <?php
        $image = get_field('image');
        if ($image): ?>
            <img src="<?php echo esc_url($image); ?>" alt="" class="w-full">
        <?php endif; ?>

        <div class="container">
            <h1 class="title-lg"><?php echo get_field('title'); ?></h1>
        </div>
    </div>
    
    <div class="login-content">
        <div class="container">
            <div class="login-content__wrapper">
                <div class="login-content__left">
                    <div class="title-md text-purple"><?php echo get_field('login_title'); ?></div>
                    <div class="text"><?php echo get_field('login_text'); ?></div>
                </div>
                
                <div class="login-content__right" style="z-index:200">
                    <div class="title-md text-purple" style="font-size:50px">Log in</div>
                    
                    <?php if (isset($login_error)): ?>
                        <div class="login-error">
                            <p><?php echo esc_html($login_error); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="" class="login-form">
                        <?php wp_nonce_field('iipm_login_nonce', 'login_nonce'); ?>
                        
                        <label>
                            E-mail Address
                            <input type="email" name="email" placeholder="johndoe@iipm.ie" 
                                   value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>" 
                                   required>
                        </label>

                        <label>
                            Password
                            <input type="password" name="password" placeholder="Enter your password" required>
                        </label>
                        
                        <div class="iipm-custom-checkbox-wrapper">
                            <div class="iipm-checkbox-container">
                                <input type="checkbox" name="remember" value="1" class="iipm-checkbox-input">
                                <span class="iipm-checkbox-checkmark"></span>
                                <span class="iipm-checkbox-text">Remember me</span>
                            </div>
                        </div>

                        <button type="submit" name="login_submit" class="login-btn">Log In</button>

                        <a href="<?php echo wp_lostpassword_url(); ?>" class="forgot-password">I forgot my password</a>

                        <p class="register-text">
                            Don't have an account? <a href="<?php echo home_url('/member-registration/'); ?>">Register here</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>
