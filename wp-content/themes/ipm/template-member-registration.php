<?php
/*
Template Name: Enhanced Member Registration
*/

// Check for invitation token
$token = sanitize_text_field($_GET['token'] ?? '');
$invitation = null;

if ($token) {
    $invitation = iipm_validate_invitation_token($token);
    if (!$invitation) {
        wp_redirect(home_url('/login/?error=invalid_invitation'));
        exit;
    }
}

// If user is already logged in, redirect
if (is_user_logged_in()) {
    wp_redirect(home_url('/member-portal/'));
    exit;
}

get_header();
?>

<div class="" style="padding-top: 120px; background: linear-gradient(135deg, #8b5a96 0%, #6b4c93 100%); color: white; padding: 60px 0;">
    <div class="container">
        <div class="hero-content" style="text-align: center; margin-top:60px">
            <h1 style="color: white; font-size: 2.5rem; font-weight: 700; margin-bottom: 15px;">IIPM Member Registration</h1>
            <p style="color: rgba(255, 255, 255, 0.9); font-size: 1.2rem; margin-bottom: 20px;">Join the Irish Institute of Pensions Management</p>
            <?php if ($invitation): ?>
                <div class="invitation-info" style="background: rgba(255, 255, 255, 0.1); padding: 15px 25px; border-radius: 10px; display: inline-block; backdrop-filter: blur(10px);">
                    <p style="color: white; margin: 0; font-weight: 500;">You've been invited to join IIPM. Complete your registration below.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<main id="primary" class="site-main">
    <div class="iipm-registration-page">
        <div class="container">
            <div class="registration-content">
                <div class="registration-form-container">
                    <form id="iipm-member-registration-form" class="registration-form" method="POST">
                        <?php wp_nonce_field('iipm_registration_nonce', 'nonce'); ?>
                        <?php if ($token): ?>
                            <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
                        <?php endif; ?>
                        
                        <!-- Add hidden input for member type -->
                        <input type="hidden" name="member_type" value="individual">
                        
                        <!-- Personal Information Section -->
                        <div class="form-section">
                            <h3>Your Information</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">First Name *</label>
                                    <input type="text" name="first_name" id="first_name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="last_name">Last Name *</label>
                                    <input type="text" name="last_name" id="last_name" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="login_name">Login Name</label>
                                <input type="text" name="login_name" id="login_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" name="email" id="email" 
                                       value="<?php echo $invitation ? esc_attr($invitation->email) : ''; ?>" 
                                       <?php echo $invitation ? 'readonly' : ''; ?> required>
                                <small>This will be your login email</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" name="password" id="password" required minlength="8">
                                <div class="password-requirements">
                                    <small class="requirement-text">Password must contain:</small>
                                    <ul class="requirements-list">
                                        <li id="req-length" class="requirement">✗ At least 8 characters</li>
                                        <li id="req-uppercase" class="requirement">✗ One uppercase letter</li>
                                        <li id="req-lowercase" class="requirement">✗ One lowercase letter</li>
                                        <li id="req-number" class="requirement">✗ One number</li>
                                        <li id="req-special" class="requirement">✗ One special character (!@#$%^&*)</li>
                                    </ul>
                                </div>
                                <div class="password-strength">
                                    <div class="strength-bar">
                                        <div class="strength-fill" id="strength-fill"></div>
                                    </div>
                                    <span class="strength-text" id="strength-text">Password strength</span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password *</label>
                                <input type="password" name="confirm_password" id="confirm_password" required>
                            </div>
                        </div>
                        
                        <!-- Contact Information Section -->
                        <div class="form-section">
                            <h3>Contact Information</h3>
                            <div class="form-group">
                                <label for="user_phone">User Phone</label>
                                <input type="tel" name="user_phone" id="user_phone">
                            </div>

                            <div class="form-group">
                                    <label for="user_mobile">User Mobile</label>
                                    <input type="tel" name="user_mobile" id="user_mobile">
                                </div>
                            
                            <div class="form-group">
                                <label for="address">Full Address *</label>
                                <textarea name="address" id="address" rows="3" placeholder="Street Address, Town, County, Eircode" required></textarea>
                                <small>Required for invoicing purposes</small>
                            </div>
                        </div>
                        
                        <!-- Professional Information Section -->
                        <div class="form-section">
                            <h3>Professional Information</h3>
                            
                            <div class="form-group">
                                <label for="user_designation">User Designation</label>
                                <input type="text" name="user_designation" id="user_designation" 
                                       placeholder="e.g., QFA, CPA, etc.">
                            </div>
                            
                            <div class="form-group">
                                <label for="other_qualifications">Other Qualifications</label>
                                <textarea name="other_qualifications" id="other_qualifications" rows="3" 
                                          placeholder="Please include other accreditations and date obtained (e.g., QFA 15.06.2012)"></textarea>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Billing Address</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="postal_address">Postal Address</label>
                                    <input type="address" name="postal_address" id="postal_address">
                                </div>
                                <div class="form-group">
                                    <label for="city_or_town">City or Town</label>
                                    <input type="text" name="city_or_town" id="city_or_town">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="address_line_1">Address Line 1</label>
                                <input type="address" name="address_line_1" id="address_line_1">
                            </div>
                            <div class="form-group">
                                <label for="address_line_2">Address Line 2</label>
                                <input type="address" name="address_line_2" id="address_line_2">
                            </div>
                            <div class="form-group">
                                <label for="address_line_3">Address Line 3</label>
                                <input type="address" name="address_line_3" id="address_line_3">
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Additional Information</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="payment_method">Payment Method</label>
                                    <select name="payment_method" id="payment_method">
                                        <option value="">Select payment method</option>
                                        <option value="Direct Invoiced">Direct Invoiced</option>
                                        <option value="Not Invoiced">Not Invoiced</option>
                                        <option value="NA">NA</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="eircode_p">Personal Eircode</label>
                                    <input type="text" name="eircode_p" id="eircode_p">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="eircode_w">Work Eircode</label>
                                    <input type="text" name="eircode_w" id="eircode_w">
                                </div>
                                <div class="form-group">
                                    <label for="correspondence_email">Correspondence Email</label>
                                    <input type="email" name="correspondence_email" id="correspondence_email">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="user_notes">User Notes</label>
                                <textarea name="user_notes" id="user_notes" rows="3" placeholder="Additional notes about the user"></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>Personal Information (optional)</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email_address_pers">Personal Email Address</label>
                                    <input type="email" name="email_address_pers" id="email_address_pers">
                                </div>
                                <div class="form-group">
                                    <label for="user_phone_pers">Personal Phone</label>
                                    <input type="tel" name="user_phone_pers" id="user_phone_pers">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="user_mobile_pers">Personal Mobile</label>
                                    <input type="tel" name="user_mobile_pers" id="user_mobile_pers">
                                </div>
                                <div class="form-group">
                                    <label for="Address_1_pers">Personal Address Line 1</label>
                                    <input type="address" name="Address_1_pers" id="Address_1_pers">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="Address_2_pers">Personal Address Line 2</label>
                                <input type="address" name="Address_2_pers" id="Address_2_pers">
                            </div>
                            <div class="form-group">
                                <label for="Address_3_pers">Personal Address Line 3</label>
                                <input type="address" name="Address_3_pers" id="Address_3_pers">
                            </div>
                        </div>
                        
                        <!-- Organisation Section (conditional) -->
                        <div class="form-section organisation-section" style="display:none;">
                            <h3>Organisation Information</h3>
                            <div class="form-group">
                                <label for="organisation_name">Organisation</label>
                                <input type="text" name="organisation_name" id="organisation_name" value="Loading..." readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                                <input type="hidden" name="organisation_id" id="organisation_id" value="<?php echo ($invitation && $invitation->organisation_id) ? esc_attr($invitation->organisation_id) : ''; ?>">
                            </div>
                            <small>Organisation information is pre-filled from your invitation</small>
                        </div>
                        
                        <!-- Privacy & Consent Section -->
                        <div class="form-section">
                            <h3>Privacy & Consent</h3>
                            <div class="form-group">
                                <label class="">
                                    <input type="checkbox" name="gdpr_consent" required>
                                    
                                    I consent to IIPM processing my personal data in accordance with the 
                                    <a href="<?php echo home_url('/privacy-policy/'); ?>" target="_blank">Privacy Policy</a> *
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="">
                                    <input type="checkbox" name="marketing_consent">
                                    
                                    I would like to receive marketing communications from IIPM about courses, events, and industry updates
                                </label>
                            </div>
                            
                            <?php if ($invitation && $invitation->invitation_type === 'bulk'): ?>
                            <div class="form-group">
                                <label class="">
                                    <input type="checkbox" name="griffith_consent" required>
                                    
                                    I consent to Griffith College sharing my data with IIPM for the purposes of determining IIPM membership eligibility *
                                </label>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Error Display Area -->
                        <div id="registration-errors" class="error-container" style="display:none;">
                            <div class="error-header">
                                <span class="error-icon">⚠️</span>
                                <span class="error-title">Registration Error</span>
                            </div>
                            <div class="error-content">
                                <ul id="error-list"></ul>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-large">
                                <span class="btn-text">Complete Registration</span>
                                <span class="btn-loading" style="display:none;">Processing...</span>
                            </button>
                        </div>
                    </form>
                    
                    <!-- Enhanced Success Message -->
                    <div id="registration-success" class="enhanced-success-message" style="display:none;">
                        <div class="success-animation">
                            <div class="success-circle">
                                <div class="success-checkmark">
                                    <svg viewBox="0 0 52 52" class="checkmark-svg">
                                        <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                                        <path class="checkmark-check" fill="none" d="m14.1 27.2l7.1 7.2 16.7-16.8"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <div class="success-content">
                            <h2 class="success-title">🎉 Welcome to IIPM!</h2>
                            <p class="success-subtitle">Your account has been created successfully</p>
                            
                            <div class="success-details">
                                <div class="detail-item">
                                    <div class="detail-icon">📧</div>
                                    <div class="detail-text">
                                        <strong>Email Verification</strong>
                                        <span>Check your inbox for verification link</span>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-icon">👤</div>
                                    <div class="detail-text">
                                        <strong>Complete Profile</strong>
                                        <span>Add additional information</span>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-icon">🚀</div>
                                    <div class="detail-text">
                                        <strong>Access Dashboard</strong>
                                        <span>Start using IIPM services</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Local Development Email Verification -->
                            <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                            <div class="local-dev-section">
                                <h4>🔧 Local Development</h4>
                                <p>Since emails aren't configured, use this button to verify your email:</p>
                                <button id="local-verify-email" class="btn btn-secondary">
                                    ✅ Verify Email (Local Dev)
                                </button>
                                <div id="verification-status" class="verification-status"></div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="success-actions">
                                <a href="<?php echo home_url('/login/'); ?>" class="btn btn-primary btn-large">
                                    <span>Continue to Login</span>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="m9 18 6-6-6-6"/>
                                    </svg>
                                </a>
                                
                                <button id="resend-verification-btn" class="btn btn-outline">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                                    </svg>
                                    Resend Verification Email
                                </button>
                            </div>
                            
                            <div class="help-section">
                                <p class="help-text">
                                    <strong>Need help?</strong> Contact us at 
                                    <a href="mailto:info@iipm.ie">info@iipm.ie</a> or 
                                    <a href="tel:+35316130874">+353 (0)1 613 0874</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="registration-info">
                    <div class="info-card">
                        <h3>Membership Benefits</h3>
                        <ul>
                            <li>Access to CPD courses and tracking</li>
                            <li>Professional certification and recognition</li>
                            <li>Industry networking opportunities</li>
                            <li>Exclusive member resources and downloads</li>
                            <li>Annual conference and event access</li>
                            <li>Professional development support</li>
                        </ul>
                    </div>
                    
                    <div class="info-card">
                        <h3>Registration Process</h3>
                        <ol>
                            <li>Complete the registration form</li>
                            <li>Verify your email address</li>
                            <li>Complete your member profile</li>
                            <li>Access your member dashboard</li>
                        </ol>
                    </div>
                    
                    <div class="info-card">
                        <h3>Need Help?</h3>
                        <p>If you have any questions about membership or the registration process, please contact us:</p>
                        <ul class="contact-list">
                            <li>Email: <a href="mailto:info@iipm.ie">info@iipm.ie</a></li>
                            <li>Phone: +353 (0)1 613 0874</li>
                            <li>Address: IIPM, Dublin, Ireland</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
// Enhanced debugging and form handling
console.log("=== IIPM Registration Debug ===");
console.log("jQuery available:", typeof jQuery !== 'undefined');
console.log("iipm_ajax available:", typeof iimp_ajax !== 'undefined');

// Store user ID for local verification
let registeredUserId = null;
let registrationSuccessful = false;

// Wait for document ready
jQuery(document).ready(function($) {
    console.log("jQuery Document Ready");
    
    // Check if iipm_ajax is available
    if (typeof iipm_ajax === 'undefined') {
        console.error("iipm_ajax is not available!");
        
        // Create a fallback iipm_ajax object
        window.iipm_ajax = {
            ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('iipm_portal_nonce'); ?>'
        };
        console.log("Created fallback iipm_ajax:", window.iipm_ajax);
    }
    
    console.log("iipm_ajax object:", iipm_ajax);
    
    // Ensure our form handler is the only one
    var $form = $('#iipm-member-registration-form');
    if ($form.length) {
        // Remove any existing handlers first

        $form.off('submit');
        
        // Prevent any default form submission
        $form.attr('novalidate', 'novalidate');
        
        console.log("Form initialized with custom handler");
    }
    
    // Common weak passwords to check against
    var commonPasswords = [
        'password', 'password123', '123456', '123456789', 'qwerty', 'qwerty123',
        'admin', 'admin123', 'letmein', 'welcome', 'welcome123', 'monkey',
        'dragon', 'master', 'superman', 'batman', 'trustno1', 'hello123',
        'Password1', 'Password123', 'Qwerty123', 'Admin123'
    ];
    
    // Password complexity validation function
    function validatePasswordComplexity(password) {
        var requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
        };
        
        // Check against common passwords
        var isCommon = commonPasswords.includes(password.toLowerCase()) || 
                      commonPasswords.includes(password);
        
        var errors = [];
        if (!requirements.length) errors.push("- At least 8 characters");
        if (!requirements.uppercase) errors.push("- One uppercase letter");
        if (!requirements.lowercase) errors.push("- One lowercase letter");
        if (!requirements.number) errors.push("- One number");
        if (!requirements.special) errors.push("- One special character");
        if (isCommon) errors.push("- Password is too common, choose a more unique password");
        
        var isValid = Object.values(requirements).every(req => req) && !isCommon;
        
        return {
            isValid: isValid,
            requirements: requirements,
            errors: errors,
            isCommon: isCommon,
            score: Object.values(requirements).reduce((score, req) => score + (req ? 1 : 0), 0)
        };
    }
    
    // Update password requirements display
    function updatePasswordRequirements(password) {
        var validation = validatePasswordComplexity(password);
        
        // Update requirement indicators
        $('#req-length').removeClass('met unmet').addClass(validation.requirements.length ? 'met' : 'unmet')
            .html(validation.requirements.length ? '✓ At least 8 characters' : '✗ At least 8 characters');
        
        $('#req-uppercase').removeClass('met unmet').addClass(validation.requirements.uppercase ? 'met' : 'unmet')
            .html(validation.requirements.uppercase ? '✓ One uppercase letter' : '✗ One uppercase letter');
        
        $('#req-lowercase').removeClass('met unmet').addClass(validation.requirements.lowercase ? 'met' : 'unmet')
            .html(validation.requirements.lowercase ? '✓ One lowercase letter' : '✗ One lowercase letter');
        
        $('#req-number').removeClass('met unmet').addClass(validation.requirements.number ? 'met' : 'unmet')
            .html(validation.requirements.number ? '✓ One number' : '✗ One number');
        
        $('#req-special').removeClass('met unmet').addClass(validation.requirements.special ? 'met' : 'unmet')
            .html(validation.requirements.special ? '✓ One special character (!@#$%^&*)' : '✗ One special character (!@#$%^&*)');
        
        // Update strength bar
        var strengthPercentage = (validation.score / 5) * 100;
        var strengthLevel = '';
        var strengthClass = '';
        
        if (validation.isCommon) {
            strengthLevel = 'Too Common';
            strengthClass = 'weak';
            strengthPercentage = 20;
        } else if (validation.score === 0) {
            strengthLevel = 'Enter password';
            strengthClass = 'none';
        } else if (validation.score <= 2) {
            strengthLevel = 'Weak';
            strengthClass = 'weak';
        } else if (validation.score <= 3) {
            strengthLevel = 'Fair';
            strengthClass = 'fair';
        } else if (validation.score <= 4) {
            strengthLevel = 'Good';
            strengthClass = 'good';
        } else {
            strengthLevel = 'Strong';
            strengthClass = 'strong';
        }
        
        $('#strength-fill').css('width', strengthPercentage + '%').removeClass().addClass('strength-fill ' + strengthClass);
        $('#strength-text').text(strengthLevel).removeClass().addClass('strength-text ' + strengthClass);
        
        return validation;
    }
    

    
    // Function to display errors in the error container
    function displayFormErrors(errors) {
        var $errorContainer = $('#registration-errors');
        var $errorList = $('#error-list');
        
        $errorList.empty();
        errors.forEach(function(error) {
            $errorList.append('<li>' + error + '</li>');
        });
        
        $errorContainer.show();
        
        // Scroll to error container
        $('html, body').animate({
            scrollTop: $errorContainer.offset().top - 20
        }, 300);
    }
    
    // Function to hide errors
    function hideFormErrors() {
        $('#registration-errors').hide();
    }
    
    // Comprehensive form validation
    function validateForm($form) {
        var errors = [];
        
        // Clear any existing field-level errors
        $form.find('.error').removeClass('error');
        $form.find('.error-message').remove();
        
        // Validate required fields
        var requiredFields = {
            'first_name': 'First Name',
            'last_name': 'Last Name', 
            'email': 'Email Address',
            'password': 'Password',
            'address': 'Address'
        };
        
        // Validate login_name length if provided
        var loginName = $form.find('input[name="login_name"]').val();
        if (loginName && loginName.trim().length < 2) {
            errors.push('Login Name must be at least 2 characters long');
            $form.find('input[name="login_name"]').addClass('error');
        }
        
        Object.keys(requiredFields).forEach(function(field) {
            var $field = $form.find('[name="' + field + '"]');
            var value = $field.val();
            if (!value || value.trim() === '') {
                errors.push(requiredFields[field] + ' is required');
                $field.addClass('error');
            }
        });
        
        // Validate email format
        var email = $form.find('input[name="email"]').val();
        if (email && !isValidEmail(email)) {
            errors.push('Please enter a valid email address');
            $form.find('input[name="email"]').addClass('error');
        }
        
        // Validate password complexity
        var password = $form.find('input[name="password"]').val();
        if (password) {
            var passwordValidation = validatePasswordComplexity(password);
            if (!passwordValidation.isValid) {
                errors.push('Password requirements not met:');
                passwordValidation.errors.forEach(function(error) {
                    errors.push('  ' + error);
                });
                $form.find('input[name="password"]').addClass('error');
            }
        }
        
        // Validate password confirmation
        var confirmPassword = $form.find('input[name="confirm_password"]').val();
        if (password && confirmPassword && password !== confirmPassword) {
            errors.push('Passwords do not match');
            $form.find('input[name="confirm_password"]').addClass('error');
        }
        
        // Check GDPR consent
        if (!$form.find('input[name="gdpr_consent"]').is(':checked')) {
            errors.push('You must consent to data processing to continue');
        }
        
        // Check Griffith consent if required
        var $griffithConsent = $form.find('input[name="griffith_consent"]');
        if ($griffithConsent.length && $griffithConsent.prop('required') && !$griffithConsent.is(':checked')) {
            errors.push('You must consent to Griffith College data sharing to continue');
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }
    
    // Email validation helper
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Enhanced form submission handler
    $('#iipm-member-registration-form').on('submit', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        console.log("Form submission intercepted by enhanced handler");
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        
        // Hide any previous errors
        hideFormErrors();
        
        // Validate the entire form
        var validation = validateForm($form);
        if (!validation.isValid) {
            displayFormErrors(validation.errors);
            return false;
        }
        
        // If validation passes, prepare form data
        var formData = new FormData(this);
        formData.append('action', 'iipm_register_member');

        // If employer is not selected, do not send employer fields
        var employerIdVal = ($('#employer_select').val() || '').trim();
        if (!employerIdVal) {
            formData.delete('employer_id');
            formData.delete('employer_name');
        }
        
        // Organisation fields are always sent if they exist (from invitation)
        var organisationId = $('#organisation_id').val();
        var organisationName = $('#organisation_name').val();
        if (organisationId && organisationName) {
            formData.set('organisation_id', organisationId);
            formData.set('organisation_name', organisationName);
        }
        
        // Show loading state
        $submitBtn.addClass('loading');
        $submitBtn.find('.btn-text').hide();
        $submitBtn.find('.btn-loading').show();
        $submitBtn.prop('disabled', true);
        
        console.log("Sending AJAX request to:", iipm_ajax.ajax_url);
        console.log("Form data entries:");
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        $.ajax({
            url: iipm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 30000, // 30 second timeout
            success: function(response) {
                console.log("AJAX response received:", response);
                console.log("Response success status:", response ? response.success : 'undefined');
                
                if (response && response.success === true) {
                    console.log("SUCCESS: Registration completed successfully");
                    registrationSuccessful = true;
                    
                    // Store user ID for local verification
                    if (response.data && response.data.user_id) {
                        registeredUserId = response.data.user_id;
                    }
                    
                    // Hide form and show enhanced success message
                    console.log("Hiding form and showing success message");
                    $form.hide();
                    $('#registration-success').show();
                    
                    // Trigger success animation
                    setTimeout(function() {
                        $('.success-animation').addClass('animate');
                    }, 300);
                    
                    // Scroll to success message
                    $('html, body').animate({
                        scrollTop: $('#registration-success').offset().top - 100
                    }, 500);
                    
                } else {
                    console.log("ERROR: Registration failed, response.success is false or missing");
                    // Handle server-side validation errors
                    var errors = [];
                    if (response && response.data) {
                        if (typeof response.data === 'string') {
                            errors.push(response.data);
                        } else if (typeof response.data === 'object' && response.data.message) {
                            errors.push(response.data.message);
                        } else if (Array.isArray(response.data)) {
                            errors = response.data;
                        } else {
                            errors.push('Registration failed');
                        }
                    } else if (response && response.message) {
                        errors.push(response.message);
                    } else {
                        errors.push('Registration failed - unknown error');
                    }
                    
                    console.log("Displaying form errors:", errors);
                    displayFormErrors(errors);
                    console.error("Registration error:", response);
                    
                    // Ensure success message is hidden
                    $('#registration-success').hide();
                    $form.show();
                }
            },
            error: function(xhr, status, error) {
                console.log("AJAX ERROR: Request failed");
                console.error("AJAX error details:");
                console.error("Status:", status);
                console.error("Error:", error);
                console.error("Response Text:", xhr.responseText);
                console.error("Status Code:", xhr.status);
                
                var errors = [];
                if (xhr.status === 0) {
                    errors.push("Network error - please check your internet connection and try again.");
                } else if (xhr.status === 404) {
                    errors.push("Registration service not found. Please contact support.");
                } else if (xhr.status === 500) {
                    errors.push("Server error occurred. Please try again in a moment.");
                } else if (status === 'timeout') {
                    errors.push("Request timed out. Please check your connection and try again.");
                } else {
                    errors.push("Registration failed: " + error);
                }
                
                console.log("Displaying AJAX errors:", errors);
                displayFormErrors(errors);
                
                // Ensure success message is hidden
                $('#registration-success').hide();
                $form.show();
            },
            complete: function() {
                $submitBtn.removeClass('loading');
                $submitBtn.find('.btn-text').show();
                $submitBtn.find('.btn-loading').hide();
                $submitBtn.prop('disabled', false);
            }
        });
        
        return false;
    });
    
    // Local email verification for development
    $('#local-verify-email').on('click', function() {
        if (!registeredUserId) {
            alert('No user ID available. Please register first.');
            return;
        }
        
        var $btn = $(this);
        $btn.text('Verifying...').prop('disabled', true);
        
        $.ajax({
            url: iipm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'iipm_local_verify_email',
                user_id: registeredUserId,
                nonce: iipm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#verification-status').html(
                        '<div class="verification-success">✅ Email verified successfully!</div>'
                    );
                    $btn.text('✅ Email Verified').addClass('verified');
                } else {
                    $('#verification-status').html(
                        '<div class="verification-error">❌ Verification failed: ' + response.data + '</div>'
                    );
                }
            },
            error: function() {
                $('#verification-status').html(
                    '<div class="verification-error">❌ Verification request failed</div>'
                );
            },
            complete: function() {
                if (!$btn.hasClass('verified')) {
                    $btn.text('Verify Email (Local Dev)').prop('disabled', false);
                }
            }
        });
    });
    
    // Initial setup for organization invitations
    var hasOrgInvitation = <?php echo ($invitation && $invitation->organisation_id) ? 'true' : 'false'; ?>;
    if (hasOrgInvitation) {
        $('.organisation-section').show();
    } else {
        $('.organisation-section').hide();
    }
    
    // Real-time validation
    $('input[type="email"]').blur(function() {
        var email = $(this).val();
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            $(this).addClass('error');
            if (!$(this).next('.error-message').length) {
                $(this).after('<div class="error-message">Please enter a valid email address</div>');
            }
        } else {
            $(this).removeClass('error');
            $(this).next('.error-message').remove();
        }
    });
    
    // Password confirmation validation
    function validatePasswordMatch() {
        var password = $('input[name="password"]').val();
        var confirmPassword = $('input[name="confirm_password"]').val();
        var $confirmField = $('input[name="confirm_password"]');
        
        if (confirmPassword && password !== confirmPassword) {
            $confirmField.addClass('error');
            if (!$confirmField.next('.error-message').length) {
                $confirmField.after('<div class="error-message">Passwords do not match</div>');
            }
            return false;
        } else if (confirmPassword && password === confirmPassword) {
            $confirmField.removeClass('error');
            $confirmField.next('.error-message').remove();
            if (!$confirmField.next('.success-message').length) {
                $confirmField.after('<div class="success-message">✓ Passwords match</div>');
            }
            return true;
        } else {
            $confirmField.removeClass('error');
            $confirmField.next('.error-message, .success-message').remove();
            return false;
        }
    }
    
    $('input[name="confirm_password"]').on('input', validatePasswordMatch);
    $('input[name="password"]').on('input', function() {
        updatePasswordRequirements($(this).val());
        // Also check confirm password when main password changes
        if ($('input[name="confirm_password"]').val()) {
            validatePasswordMatch();
        }
    });
    
    // Safety check to prevent success message from showing incorrectly
    // Hide success message by default and keep it hidden unless explicitly successful
    $('#registration-success').hide();
    
    // Add a mutation observer to catch any unwanted attempts to show success
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.target.id === 'registration-success' && 
                    mutation.target.style.display !== 'none' && 
                    !registrationSuccessful) {
                    console.warn("BLOCKED: Unauthorized attempt to show success message");
                    mutation.target.style.display = 'none';
                }
            });
        });
        
        var successElement = document.getElementById('registration-success');
        if (successElement) {
            observer.observe(successElement, { attributes: true, attributeFilter: ['style'] });
        }
    }
});

// Populate employer select with all employers (single request) and handle selection
jQuery(document).ready(function($){
    // var $sel = $('#employer_select');
    // if (!$sel.length) return;

    // // Show loading placeholder
    // $sel.html('<option value="" disabled selected>Loading...</option>');

    // $.ajax({
    //     url: (typeof iipm_ajax !== 'undefined' ? iipm_ajax.ajax_url : '<?php echo admin_url('admin-ajax.php'); ?>'),
    //     type: 'POST',
    //     dataType: 'json',
    //     data: {
    //         action: 'iipm_search_employers',
    //         nonce: (typeof iipm_ajax !== 'undefined' ? iipm_ajax.nonce : '<?php echo wp_create_nonce('iipm_portal_nonce'); ?>')
    //     }
    // }).done(function(resp){
    //     var items = (resp && resp.results) ? resp.results : [];
    //     var opts = ['<option value="">Select employer</option>'];
    //     items.forEach(function(it){
    //         opts.push('<option value="' + it.id + '">' + $('<div>').text(it.text || '').html() + '</option>');
    //     });
    //     $sel.html(opts.join(''));
    // }).fail(function(){
    //     // keep empty on error
    // });

    // $sel.on('change', function(){
    //     var $opt = $(this).find('option:selected');
    //     var val = ($opt.val() || '').trim();
    //     if (!val) {
    //         $('#employer_name').val('');
    //     } else {
    //         $('#employer_name').val($opt.text() || '');
    //     }
    // });
    
    // Fetch organisation name if invitation has organisation_id
    var invitationOrgId = <?php echo ($invitation && $invitation->organisation_id) ? $invitation->organisation_id : 'null'; ?>;
    console.log("WWWWWWWWWWWWWW", invitationOrgId);
    if (invitationOrgId) {
        $.ajax({
            url: (typeof iipm_ajax !== 'undefined' ? iipm_ajax.ajax_url : '<?php echo admin_url('admin-ajax.php'); ?>'),
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'iipm_get_organisation_name',
                organisation_id: invitationOrgId,
                nonce: (typeof iipm_ajax !== 'undefined' ? iipm_ajax.nonce : '<?php echo wp_create_nonce('iipm_portal_nonce'); ?>')
            }
        }).done(function(resp){
            if (resp && resp.success && resp.data && resp.data.name) {
                $('#organisation_name').val(resp.data.name);
            } else {
                $('#organisation_name').val('Organisation not found');
            }
        }).fail(function(){
            $('#organisation_name').val('Error loading organisation');
        });
    }
});


</script>

<style>
/* Enhanced Success Message Styles */
.enhanced-success-message {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 20px;
    padding: 40px;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
    max-width: 600px;
    margin: 0 auto;
}

/* Success Animation */
.success-animation {
    margin-bottom: 30px;
}

.success-circle {
    width: 100px;
    height: 100px;
    margin: 0 auto 20px;
    position: relative;
}

.success-checkmark {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    display: block;
    stroke-width: 3;
    stroke: #10b981;
    stroke-miterlimit: 10;
    box-shadow: inset 0px 0px 0px #10b981;
    animation: fill 0.4s ease-in-out 0.4s forwards, scale 0.3s ease-in-out 0.9s both;
    position: relative;
    top: 5px;
    right: 5px;
    margin: 0 auto;
}

.checkmark-svg {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    display: block;
    stroke-width: 3;
    stroke: #10b981;
    stroke-miterlimit: 10;
    box-shadow: inset 0px 0px 0px #10b981;
}

.checkmark-circle {
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    stroke-width: 3;
    stroke-miterlimit: 10;
    stroke: #10b981;
    fill: #fff;
    animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}

.checkmark-check {
    transform-origin: 50% 50%;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
}

.success-animation.animate .checkmark-circle,
.success-animation.animate .checkmark-check {
    animation-play-state: running;
}

@keyframes stroke {
    100% {
        stroke-dashoffset: 0;
    }
}

@keyframes scale {
    0%, 100% {
        transform: none;
    }
    50% {
        transform: scale3d(1.1, 1.1, 1);
    }
}

@keyframes fill {
    100% {
        box-shadow: inset 0px 0px 0px 30px #10b981;
    }
}

/* Success Content */
.success-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 10px;
}

.success-subtitle {
    font-size: 1.2rem;
    color: #6b7280;
    margin-bottom: 30px;
}

/* Success Details */
.success-details {
    display: grid;
    gap: 20px;
    margin: 30px 0;
    text-align: left;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: white;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
}

.detail-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.detail-icon {
    font-size: 2rem;
    flex-shrink: 0;
}

.detail-text {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.detail-text strong {
    font-weight: 600;
    color: #1f2937;
}

.detail-text span {
    color: #6b7280;
    font-size: 0.9rem;
}

/* Local Development Section */
.local-dev-section {
    background: #fef3c7;
    border: 1px solid #f59e0b;
    border-radius: 12px;
    padding: 20px;
    margin: 30px 0;
}

.local-dev-section h4 {
    color: #92400e;
    margin-bottom: 10px;
}

.local-dev-section p {
    color: #92400e;
    margin-bottom: 15px;
}

.verification-status {
    margin-top: 15px;
    padding: 10px;
    border-radius: 6px;
}

.verification-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.verification-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

/* Success Actions */
.success-actions {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin: 30px 0;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 28px;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-size: 1rem;
}

.btn-primary {
    background: linear-gradient(135deg, #8b5a96 0%, #6b4c93 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(139, 90, 150, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(139, 90, 150, 0.4);
}

.btn-outline {
    background: transparent;
    border: 2px solid #d1d5db;
    color: #374151;
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #8b5a96;
    color: #8b5a96;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
    transform: translateY(-2px);
}

.btn-secondary.verified {
    background: #10b981;
    color: white;
}

/* Help Section */
.help-section {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.help-text {
    color: #6b7280;
    font-size: 0.9rem;
    margin: 0;
}

.help-text a {
    color: #8b5a96;
    text-decoration: none;
    font-weight: 500;
}

.help-text a:hover {
    text-decoration: underline;
}

/* Password Requirements and Strength Indicator */
.password-requirements {
    margin-top: 8px;
    padding: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
}

.requirement-text {
    display: block;
    font-weight: 500;
    color: #374151;
    margin-bottom: 8px;
}

.requirements-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.requirement {
    font-size: 13px;
    padding: 2px 0;
    transition: all 0.3s ease;
}

.requirement.met {
    color: #10b981;
    font-weight: 500;
}

.requirement.unmet {
    color: #ef4444;
}

.password-strength {
    margin-top: 10px;
}

.strength-bar {
    width: 100%;
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 5px;
}

.strength-fill {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 3px;
}

.strength-fill.none {
    background: #e5e7eb;
}

.strength-fill.weak {
    background: #ef4444;
}

.strength-fill.fair {
    background: #f59e0b;
}

.strength-fill.good {
    background: #3b82f6;
}

.strength-fill.strong {
    background: #10b981;
}

.strength-text {
    font-size: 13px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.strength-text.none {
    color: #6b7280;
}

.strength-text.weak {
    color: #ef4444;
}

.strength-text.fair {
    color: #f59e0b;
}

.strength-text.good {
    color: #3b82f6;
}

.strength-text.strong {
    color: #10b981;
}

/* Form Error States */
.form-group input.error {
    border-color: #ef4444;
    background-color: #fef2f2;
}

.error-message {
    display: block;
    margin-top: 5px;
    font-size: 13px;
    color: #ef4444;
    font-weight: 500;
}

.success-message {
    display: block;
    margin-top: 5px;
    font-size: 13px;
    color: #10b981;
    font-weight: 500;
}

/* Error Container */
.error-container {
    margin: 20px 0;
    padding: 16px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    color: #991b1b;
}

.error-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}

.error-icon {
    font-size: 18px;
}

.error-title {
    font-weight: 600;
    font-size: 16px;
}

.error-content ul {
    margin: 0;
    padding-left: 20px;
}

.error-content li {
    margin-bottom: 4px;
    line-height: 1.4;
}

/* Enhanced Submit Button States */
.btn.loading {
    opacity: 0.7;
    cursor: not-allowed;
    position: relative;
}

.btn-loading {
    display: none;
}

.btn.loading .btn-loading {
    display: inline-block;
}

/* Responsive Design */
@media (max-width: 768px) {
    .enhanced-success-message {
        padding: 30px 20px;
    }
    
    .success-title {
        font-size: 2rem;
    }
    
    .success-actions {
        gap: 12px;
    }
    
    .btn {
        padding: 12px 24px;
        font-size: 0.9rem;
    }
    
    .password-requirements {
        padding: 10px;
    }
    
    .requirement {
        font-size: 12px;
    }
}
</style>
</div>
</main>

<?php get_footer(); ?>
