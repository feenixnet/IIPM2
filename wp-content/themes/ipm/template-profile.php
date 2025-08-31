<?php
/**
 * Template Name: Profile
 * 
 * User profile page with comprehensive member information
 */

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;

// Check if user has IIPM member role
if (!in_array('iipm_member', $user_roles) && 
    !in_array('iipm_council_member', $user_roles) && 
    !in_array('iipm_corporate_admin', $user_roles) &&
    !in_array('administrator', $user_roles)) {
    wp_redirect(home_url());
    exit;
}

$user_id = $current_user->ID;

// Get user data from database
global $wpdb;

// Get member data
$member = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}test_iipm_members WHERE user_id = %d", 
    $user_id
));

// Get profile data
$profile = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}test_iipm_member_profiles WHERE user_id = %d",
    $user_id
));

// Get organisation data if applicable
$organisation = null;
if ($member && $member->organisation_id) {
    $organisation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_organisations WHERE id = %d",
        $member->organisation_id
    ));
}

// Create profile record if it doesn't exist
if (!$profile) {
    $wpdb->insert(
        $wpdb->prefix . 'test_iipm_member_profiles',
        array('user_id' => $user_id),
        array('%d')
    );
    
    $profile = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}test_iipm_member_profiles WHERE user_id = %d",
        $user_id
    ));
}

// Get qualifications (we'll simulate this for now)
$qualifications = array();
if ($member && $member->qualification_date) {
    $qualifications[] = array(
        'qualification' => 'Financial Planning & Investment',
        'awarding_institute' => 'IIPM',
        'date_attained' => date('d/m/Y', strtotime($member->qualification_date)),
        'current_designation' => $profile->professional_designation ?: 'Member'
    );
}

// Get user meta for address
$address_line1 = get_user_meta($user_id, 'address_line1', true) ?: '';
$address_line2 = get_user_meta($user_id, 'address_line2', true) ?: '';
$city = get_user_meta($user_id, 'city', true) ?: '';
$county = get_user_meta($user_id, 'county', true) ?: '';
$eircode = get_user_meta($user_id, 'eircode', true) ?: '';

// Format address
$full_address = array_filter(array($address_line1, $address_line2, $city, $county, $eircode));
$address_display = implode("\n", $full_address);

get_header();
?>

<div class="profile-page">
    <!-- Hero Section -->
    <div class="profile-hero">
        <div class="container">
            <div class="hero-content">
                <div class="page-header">
                    <h1>My Account</h1>
                    <div class="user-welcome">
                        <h2>Hi, <?php echo esc_html($current_user->first_name . ' ' . $current_user->last_name); ?>!</h2>
                        <p class="designation">Your Designation: <?php echo esc_html($profile->professional_designation ?: 'MIIPM'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Validation Error Alert -->
        <div class="validation-alert" id="validation-alert" style="display: none;">
            <div class="alert-icon">⚠</div>
            <div class="alert-message">Cannot save changes. Please fix error(s) in the form.</div>
        </div>

        <div class="profile-layout">

            <div class="profile-content">
                <!-- Left Sidebar Navigation -->
                <div class="profile-sidebar">
                    <nav class="profile-nav">
                        <ul>
                            <li class="nav-item active" data-section="profile">
                                <a href="javascript:void(0)" onclick="showSection('profile')">
                                    Your profile
                                </a>
                            </li>
                            <li class="nav-item" data-section="payment">
                                <a href="javascript:void(0)" onclick="showSection('payment')">
                                    Payment
                                </a>
                            </li>
                            <li class="nav-item" data-section="settings">
                                <a href="javascript:void(0)" onclick="showSection('settings')">
                                    Settings
                                </a>
                            </li>
                            <li class="nav-item" data-section="help">
                                <a href="javascript:void(0)" onclick="showSection('help')">
                                    Help
                                </a>
                            </li>
                            <li class="logout">
                                <a href="<?php echo wp_logout_url(home_url()); ?>">
                                    Log out
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>

                <!-- Main Profile Content -->
                <div class="profile-main" id="profile-section">
                    <!-- Basic Information -->
                    <div class="profile-section">
                        <div class="section-header">
                            <h3>Basic Information</h3>
                            <button class="edit-btn" onclick="editSection('basic-info')" title="Edit">
                                <span class="edit-icon">✎</span>
                            </button>
                        </div>
                        <div class="section-content" id="basic-info">
                            <div class="view-mode">
                                <div class="form-group">
                                    <label>Name*</label>
                                    <div class="form-value" data-field="name"><?php echo esc_html($current_user->first_name . ' ' . $current_user->last_name); ?></div>
                                </div>
                                <div class="form-group">
                                    <label>E-mail*</label>
                                    <div class="form-value" data-field="email"><?php echo esc_html($current_user->user_email); ?></div>
                                </div>
                            </div>
                            <div class="edit-mode" style="display: none;">
                                <div class="form-group">
                                    <label>First Name*</label>
                                    <input type="text" class="form-input" name="first_name" value="<?php echo esc_attr($current_user->first_name); ?>" />
                                </div>
                                <div class="form-group">
                                    <label>Last Name*</label>
                                    <input type="text" class="form-input" name="last_name" value="<?php echo esc_attr($current_user->last_name); ?>" />
                                </div>
                                <div class="form-group">
                                    <label>E-mail*</label>
                                    <input type="email" class="form-input" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Details -->
                    <div class="profile-section">
                        <div class="section-header">
                            <h3>Contact Details</h3>
                            <button class="edit-btn" onclick="editSection('contact-details')" title="Edit">
                                <span class="edit-icon">✎</span>
                            </button>
                        </div>
                        <div class="section-content" id="contact-details">
                            <div class="view-mode">
                                <div class="contact-grid">
                                    <div class="contact-column">
                                        <h4>Personal</h4>
                                        <div class="form-group">
                                            <label>Landline</label>
                                            <div class="form-value" data-field="user_phone">
                                                <?php if ($profile->user_phone): ?>
                                                    <?php echo esc_html($profile->user_phone); ?>
                                                <?php else: ?>
                                                    <span class="placeholder-text">Not provided</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Mobile*</label>
                                            <div class="form-value" data-field="mobile">
                                                <?php if (get_user_meta($user_id, 'phone', true)): ?>
                                                    <?php echo esc_html(get_user_meta($user_id, 'phone', true)); ?>
                                                <?php else: ?>
                                                    <span class="placeholder-text">Not provided</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>E-mail*</label>
                                            <div class="form-value" data-field="personal_email"><?php echo esc_html($current_user->user_email); ?></div>
                                        </div>
                                    </div>
                                    <div class="contact-column">
                                        <h4>Work</h4>
                                        <div class="form-group">
                                            <label>Landline</label>
                                            <div class="form-value" data-field="user_mobile">
                                                <?php if ($profile->user_mobile): ?>
                                                    <?php echo esc_html($profile->user_mobile); ?>
                                                <?php else: ?>
                                                    <span class="placeholder-text">Not provided</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Mobile</label>
                                            <div class="form-value" data-field="work_mobile">
                                                <?php if ($profile->work_mobile): ?>
                                                    <?php echo esc_html($profile->work_mobile); ?>
                                                <?php else: ?>
                                                    <span class="placeholder-text">Not provided</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>E-mail</label>
                                            <div class="form-value" data-field="work_email">
                                                <?php if ($profile->work_email): ?>
                                                    <?php echo esc_html($profile->work_email); ?>
                                                <?php else: ?>
                                                    <span class="placeholder-text">Not provided</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="edit-mode" style="display: none;">
                                <div class="contact-grid">
                                    <div class="contact-column">
                                        <h4>Personal</h4>
                                        <div class="form-group">
                                            <label>Landline</label>
                                            <input type="text" class="form-input" name="user_phone" value="<?php echo esc_attr($profile->user_phone); ?>" />
                                        </div>
                                        <div class="form-group">
                                            <label>Mobile*</label>
                                            <input type="text" class="form-input" name="mobile" value="<?php echo esc_attr(get_user_meta($user_id, 'phone', true)); ?>" />
                                        </div>
                                        <div class="form-group">
                                            <label>E-mail*</label>
                                            <input type="email" class="form-input" name="personal_email" value="<?php echo esc_attr($current_user->user_email); ?>" readonly />
                                        </div>
                                    </div>
                                    <div class="contact-column">
                                        <h4>Work</h4>
                                        <div class="form-group">
                                            <label>Landline</label>
                                            <input type="text" class="form-input" name="user_mobile" value="<?php echo esc_attr($profile->user_mobile); ?>" />
                                        </div>
                                        <div class="form-group">
                                            <label>Mobile</label>
                                            <input type="text" class="form-input" name="work_mobile" value="<?php echo esc_attr($profile->work_mobile); ?>" />
                                        </div>
                                        <div class="form-group">
                                            <label>E-mail</label>
                                            <input type="email" class="form-input" name="work_email" value="<?php echo esc_attr($profile->work_email); ?>" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="profile-section">
                        <div class="section-header">
                            <h3>Address</h3>
                            <button class="edit-btn" onclick="editSection('address')" title="Edit">
                                <span class="edit-icon">✎</span>
                            </button>
                        </div>
                        <div class="section-content" id="address">
                            <div class="view-mode">
                                <div class="form-group">
                                    <label>Address*</label>
                                    <div class="form-value address-value" data-field="address">
                                        <?php if ($address_display): ?>
                                            <?php echo nl2br(esc_html($address_display)); ?>
                                        <?php else: ?>
                                            <span class="placeholder-text">No address information on file</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="edit-mode" style="display: none;">
                                <div class="form-group">
                                    <label>Address Line 1*</label>
                                    <input type="text" class="form-input" name="address_line1" value="<?php echo esc_attr($address_line1); ?>" />
                                </div>
                                <div class="form-group">
                                    <label>Address Line 2</label>
                                    <input type="text" class="form-input" name="address_line2" value="<?php echo esc_attr($address_line2); ?>" />
                                </div>
                                <div class="form-group">
                                    <label>City*</label>
                                    <input type="text" class="form-input" name="city" value="<?php echo esc_attr($city); ?>" />
                                </div>
                                <div class="form-group">
                                    <label>County</label>
                                    <input type="text" class="form-input" name="county" value="<?php echo esc_attr($county); ?>" />
                                </div>
                                <div class="form-group">
                                    <label>Eircode</label>
                                    <input type="text" class="form-input" name="eircode" value="<?php echo esc_attr($eircode); ?>" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Employment Details -->
                    <div class="profile-section">
                        <div class="section-header">
                            <h3>Employment Details</h3>
                            <button class="edit-btn" onclick="editSection('employment')" title="Edit">
                                <span class="edit-icon">✎</span>
                            </button>
                        </div>
                        <div class="section-content" id="employment">
                            <div class="view-mode">
                                <div class="form-group">
                                    <label>Employer Name*</label>
                                    <div class="form-value" data-field="employer_name">
                                        <?php if ($profile->employer_name): ?>
                                            <?php echo esc_html($profile->employer_name); ?>
                                        <?php else: ?>
                                            <span class="placeholder-text">No employer information on file</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Employer Address*</label>
                                    <div class="form-value address-value" data-field="employer_address">
                                        <?php if ($profile->employer_address_line1): ?>
                                            <?php 
                                            $employer_address = array_filter(array(
                                                $profile->employer_address_line1,
                                                $profile->employer_address_line2,
                                                $profile->employer_city,
                                                $profile->employer_county,
                                                $profile->employer_eircode
                                            ));
                                            echo nl2br(esc_html(implode("\n", $employer_address)));
                                            ?>
                                        <?php else: ?>
                                            <span class="placeholder-text">No employer address information on file</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="edit-mode" style="display: none;">
                                <div class="form-group">
                                    <label>Employer Name*</label>
                                    <input type="text" class="form-input" name="employer_name" value="<?php echo esc_attr($profile->employer_name); ?>" />
                                </div>
                                <div class="form-group">
                                    <label>Employer Address Line 1*</label>
                                    <input type="text" class="form-input" name="employer_address_line1" value="<?php echo esc_attr($profile->employer_address_line1); ?>" />
                                </div>
                                <div class="form-group">
                                    <label>Employer Address Line 2</label>
                                    <input type="text" class="form-input" name="employer_address_line2" value="<?php echo esc_attr($profile->employer_address_line2); ?>" />
                                </div>
                                <div class="form-group">
                                    <label>Employer City*</label>
                                    <input type="text" class="form-input" name="employer_city" value="<?php echo esc_attr($profile->employer_city); ?>" />
                                </div>
                                <div class="form-group">
                                    <label>Employer County</label>
                                    <input type="text" class="form-input" name="employer_county" value="<?php echo esc_attr($profile->employer_county); ?>" />
                                </div>
                                <div class="form-group">
                                    <label>Employer Eircode</label>
                                    <input type="text" class="form-input" name="employer_eircode" value="<?php echo esc_attr($profile->employer_eircode); ?>" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Qualifications -->
                    <div class="profile-section">
                        <div class="section-header">
                            <h3>Qualifications</h3>
                            <button class="edit-btn" onclick="editSection('qualifications')" title="Edit">
                                <span class="edit-icon">✎</span>
                            </button>
                        </div>
                        <div class="section-content" id="qualifications">
                            <div class="qualifications-table">
                                <div class="table-header">
                                    <div class="header-cell">Qualifications</div>
                                    <div class="header-cell">Awarding Institute</div>
                                    <div class="header-cell">Date Attained</div>
                                    <div class="header-cell">Current Designation</div>
                                </div>
                                <div class="table-body">
                                    <?php if (!empty($qualifications)): ?>
                                        <?php foreach ($qualifications as $qual): ?>
                                            <div class="table-row">
                                                <div class="table-cell"><?php echo esc_html($qual['qualification']); ?></div>
                                                <div class="table-cell"><?php echo esc_html($qual['awarding_institute']); ?></div>
                                                <div class="table-cell"><?php echo esc_html($qual['date_attained']); ?></div>
                                                <div class="table-cell"><?php echo esc_html($qual['current_designation']); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="table-row">
                                            <div class="table-cell">Financial Planning & Investment</div>
                                            <div class="table-cell">IIPM</div>
                                            <div class="table-cell">05/01/2023</div>
                                            <div class="table-cell">Member</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="profile-actions">
                        <button type="submit" class="save-btn" id="global-save-btn" onclick="saveProfile()" disabled>
                            Save changes
                        </button>
                    </div>
                </div>

                <!-- Payment Section -->
                <div class="payment-main" id="payment-section" style="display: none;">
                    <div class="payment-header">
                        <h2>Your Payments</h2>
                    </div>

                    <div class="payments-table-container">
                        <table class="payments-table">
                            <thead>
                                <tr>
                                    <th>Order No.</th>
                                    <th>Order Details</th>
                                    <th>Order Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><a href="#" class="order-link">2210</a></td>
                                    <td>
                                        <div class="order-details">
                                            <div>1x Membership IIPM</div>
                                            <div>1x Fellowship IIPM</div>
                                        </div>
                                    </td>
                                    <td>05/01/2023</td>
                                    <td><span class="status awaiting">Awaiting Payment</span></td>
                                    <td><button class="action-btn complete-payment" onclick="showOrderDetails('2210')">Complete Payment</button></td>
                                </tr>
                                <tr>
                                    <td><a href="#" class="order-link">2316</a></td>
                                    <td>
                                        <div class="order-details">
                                            <div>1x Membership IIPM</div>
                                        </div>
                                    </td>
                                    <td>04/20/2023</td>
                                    <td><span class="status completed">Completed</span></td>
                                    <td><button class="action-btn see-details" onclick="showOrderDetails('2316')">See Details</button></td>
                                </tr>
                                <tr>
                                    <td><a href="#" class="order-link">2316</a></td>
                                    <td>
                                        <div class="order-details">
                                            <div>1x Membership IIPM</div>
                                        </div>
                                    </td>
                                    <td>04/20/2023</td>
                                    <td><span class="status cancelled">Cancelled</span></td>
                                    <td><button class="action-btn see-details" onclick="showOrderDetails('2316')">See Details</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Order Details Section -->
                <div class="order-details-main" id="order-details-section" style="display: none;">
                    <div class="order-details-header">
                        <div class="back-navigation">
                            <a href="javascript:void(0)" onclick="showSection('payment')" class="back-link">
                                ← Back to order list
                            </a>
                        </div>
                        <h2 id="order-details-title">Order Details - 2210</h2>
                    </div>

                    <div class="order-info-grid">
                        <div class="order-summary-card">
                            <div class="order-meta">
                                <div class="meta-row">
                                    <span class="meta-label">Order No.</span>
                                    <span class="meta-value" id="order-number">2210</span>
                                </div>
                                <div class="meta-row">
                                    <span class="meta-label">Order Date</span>
                                    <span class="meta-value" id="order-date">05/01/2023</span>
                                </div>
                                <div class="meta-row">
                                    <span class="meta-label">Status</span>
                                    <span class="meta-value"><span class="status awaiting" id="order-status">Awaiting Payment</span></span>
                                </div>
                            </div>

                            <div class="billing-info">
                                <h4>Billing Address</h4>
                                <div class="billing-address">
                                    <div>John Smith</div>
                                    <div>123 Turn Road</div>
                                    <div>Dublin</div>
                                    <div>Dublin</div>
                                    <div>1234567890</div>
                                </div>
                            </div>

                            <div class="email-info">
                                <h4>E-mail Address</h4>
                                <div class="email-address">jsmith@iipm.ie</div>
                            </div>
                        </div>

                        <div class="order-items-card">
                            <h4>Order Details</h4>
                            <div class="order-items">
                                <div class="order-item">
                                    <div class="item-icon">🎓</div>
                                    <div class="item-details">
                                        <div class="item-name">IIPM Fellowship</div>
                                        <div class="item-quantity">1x</div>
                                    </div>
                                    <div class="item-price">€270.00</div>
                                </div>
                                <div class="order-item">
                                    <div class="item-icon">🎓</div>
                                    <div class="item-details">
                                        <div class="item-name">IIPM Membership</div>
                                        <div class="item-quantity">1x</div>
                                    </div>
                                    <div class="item-price">€130.00</div>
                                </div>
                            </div>

                            <div class="order-totals">
                                <div class="total-row">
                                    <span>Subtotal</span>
                                    <span>€400.00</span>
                                </div>
                                <div class="total-row">
                                    <span>Tax</span>
                                    <span>€0.00</span>
                                </div>
                                <div class="total-row total-final">
                                    <span>Total</span>
                                    <span>€400.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="payment-section-grid">
                        <div class="payment-notice">
                            <h4>Payment Notice</h4>
                            <p>You haven't completed your IIPM Membership payment. Please complete your payment by <strong>Thursday, May 3, 2025</strong>.</p>
                            <p>Please contact administrator if you encounter any problem with your order. Thank you.</p>
                        </div>

                        <div class="payment-form-card">
                            <h4>Complete Your Payment</h4>
                            
                            <div class="payment-methods">
                                <div class="payment-method active">
                                    <div class="method-icon">💳</div>
                                </div>
                                <div class="payment-method">
                                    <div class="method-icon">📧</div>
                                </div>
                            </div>

                            <form class="payment-form">
                                <div class="form-group">
                                    <label>Cardholder Name</label>
                                    <input type="text" class="payment-input" value="John Smith" />
                                </div>
                                
                                <div class="form-group">
                                    <label>Card Number</label>
                                    <input type="text" class="payment-input" placeholder="XXXX-XXXX-XXXX-XXXX" />
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Expiration Date</label>
                                        <input type="text" class="payment-input" placeholder="06/26" />
                                    </div>
                                    <div class="form-group">
                                        <label>CVV Code</label>
                                        <input type="text" class="payment-input" placeholder="XXX" />
                                    </div>
                                </div>
                                
                                <button type="submit" class="complete-payment-btn">Complete Payment</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Settings Section -->
                <div class="settings-main" id="settings-section" style="display: none;">
                    <div class="settings-content">
                        <h2>Reset Password</h2>
                        
                        <form class="password-reset-form" id="password-reset-form">
                            <div class="form-group">
                                <label for="current-password">Current Password</label>
                                <div class="password-input-container">
                                    <input type="password" id="current-password" class="form-input password-input" placeholder="••••••••••" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="new-password">New Password</label>
                                <div class="password-input-container">
                                    <input type="password" id="new-password" class="form-input password-input" placeholder="••••••••••" required>
                                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility('new-password', this)">
                                        <span class="eye-icon">👁</span>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm-password">Confirm New Password</label>
                                <div class="password-input-container">
                                    <input type="password" id="confirm-password" class="form-input password-input" placeholder="••••••••••" required>
                                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility('confirm-password', this)">
                                        <span class="eye-icon">👁</span>
                                    </button>
                                </div>
                            </div>
                            
                            <button type="submit" class="change-password-btn">Change Password</button>
                        </form>
                        
                        <div class="password-reset-help">
                            <hr class="divider">
                            <div class="forgot-password-section">
                                <p class="forgot-title">Forgot your current password?</p>
                                <a href="javascript:void(0)" class="reset-email-link" onclick="sendResetEmail()">Reset through e-mail</a>
                                <p class="reset-description">We'll send you a reset link to reset your password.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="help-main" id="help-section" style="display: none;">
                    <div class="help-content">
                        <div class="faq-section">
                            <h2>Frequently Asked Questions</h2>
                            
                            <div class="faq-list">
                                <div class="faq-item">
                                    <div class="faq-question" onclick="toggleFAQ(this)">
                                        <span>How to log my CPD hours?</span>
                                        <span class="faq-toggle">▼</span>
                                    </div>
                                    <div class="faq-answer">
                                        <p>You can log your CPD hours by going to the CPD Record section in your profile. Click "Add New Record" and fill in the details of your professional development activity including the date, duration, and description.</p>
                                    </div>
                                </div>
                                
                                <div class="faq-item">
                                    <div class="faq-question" onclick="toggleFAQ(this)">
                                        <span>How do I submit my CPD return?</span>
                                        <span class="faq-toggle">▼</span>
                                    </div>
                                    <div class="faq-answer">
                                        <p>To submit your CPD return, navigate to the CPD Record page and click "Submit CPD Return" button. Ensure you have completed the minimum required hours before submitting.</p>
                                    </div>
                                </div>
                                
                                <div class="faq-item">
                                    <div class="faq-question" onclick="toggleFAQ(this)">
                                        <span>How do I reset my password?</span>
                                        <span class="faq-toggle">▼</span>
                                    </div>
                                    <div class="faq-answer">
                                        <p>You can reset your password by going to Settings in your profile sidebar. Enter your current password and set a new one, or use the "Reset through e-mail" option to receive reset instructions via email.</p>
                                    </div>
                                </div>
                                
                                <div class="faq-item">
                                    <div class="faq-question" onclick="toggleFAQ(this)">
                                        <span>How do I submit a leave request?</span>
                                        <span class="faq-toggle">▼</span>
                                    </div>
                                    <div class="faq-answer">
                                        <p><strong>To submit a leave request:</strong></p>
                                        <ul>
                                            <li><a href="#">Go to Submit Leave Request form homepage</a></li>
                                            <li><a href="#">Fill the Leave Request form</a></li>
                                            <li><a href="#">Click Submit</a></li>
                                        </ul>
                                        <p><strong>Note:</strong> You can apply for leave one year in advance from request date.</p>
                                        <p class="info-text">Your request(s) will have to be approved by the administrator in 2-5 working days. You will be notified if your request has been processed.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-section">
                            <div class="contact-layout">
                                <div class="contact-info">
                                    <h3>Still have questions?</h3>
                                    <p class="contact-description">Fill out the contact form to reach the administrator to get help. We'll do our best to help you as fast as possible.</p>
                                </div>
                                
                                <div class="contact-form-container">
                                    <form class="help-contact-form" id="help-contact-form">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="help-full-name">Full Name*</label>
                                        <input type="text" id="help-full-name" class="form-input" placeholder="John Doe" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="help-email">Email Address*</label>
                                        <input type="email" id="help-email" class="form-input" placeholder="johndoe@iipm.ie" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="help-phone">Phone Number*</label>
                                    <input type="tel" id="help-phone" class="form-input" placeholder="08XXXXXXXX" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="help-message">Message*</label>
                                    <textarea id="help-message" class="form-input message-textarea" placeholder="Write your message here" rows="5" required></textarea>
                                </div>
                                
                                <div class="recaptcha-group">
                                    <label>Verification*</label>
                                    <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="checkbox-groups">
                                        <input type="checkbox" id="privacy-consent" required>
                                    
                                        <span>I consent to my personal data being submitted in accordance to IIPM Privacy Policy.</span>
                                    </label>
                                </div>
                                
                                        <button type="submit" class="send-message-btn">Send Message</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Profile Page Styles */
.profile-page {
    background: #f8fafc;
    min-height: 100vh;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    margin: 0;
    padding: 0;
}

.checkbox-groups {
    display: flex;
    align-items:center;
}

.checkbox-groups input {
    margin-top: 10px;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    padding-bottom: 20px;
}

/* Hero Section */
.profile-hero {
    position: relative;
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
    color: white;
    overflow: hidden;
    margin-top: 0;
}

.profile-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3);
    z-index: 1;
}

.hero-content {
    position: relative;
    z-index: 2;
    padding: 120px 0 60px 0;
    text-align: left;
}

.page-header{
    text-align:left;
    margin:20px;
}

.page-header h1 {
    color: white;
    font-size: 3rem;
    font-weight: 700;
    margin: 0 0 24px 0;
    line-height: 1.1;
}

.user-welcome h2 {
    color: white;
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0 0 8px 0;
}

.designation {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1rem;
    margin: 0;
    font-weight: 400;
}

.profile-layout {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    margin: 0 0 40px 0;
}

/* Content Layout */
.profile-content {
    display: grid;
    grid-template-columns: 250px 1fr;
    min-height: 600px;
}

/* Sidebar Navigation */
.profile-sidebar {
    background: #f9fafb;
    padding: 20px 16px;
}

.profile-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
    border-radius: 35px;
}

.profile-nav li {
    border: none;
}

.profile-nav li.logout {
    border: none;
    margin-top: 10px;
}

.profile-nav a {
    display: block;
    padding: 16px 20px;
    color: #1f2937;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
    border-radius: 16px;    
    border: 1px solid #e5e7eb;
}

.profile-nav li.active a,
.profile-nav li.nav-item.active a {
    background: #d1d5db;
    color: #1f2937;
}

.profile-nav a:hover:not(.profile-nav li.logout a) {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.profile-nav li.logout a {
    background: none;
    border: none;
    color: #dc2626;
    padding: 8px 0;
    font-weight: 600;
    font-size: 16px;
    text-decoration: underline;
}

.profile-nav li.logout a:hover {
    background: none;
    color: #b91c1c;
    text-decoration: underline;
}

/* Main Content */
.profile-main {
    padding: 40px;
    background-color: #f8fafc
}

/* Profile Sections */
.profile-section {
    margin-bottom: 32px;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
}

.section-header h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.edit-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    border-radius: 6px;
    transition: all 0.2s;
    color: #8b5cf6;
}

.edit-btn:hover {
    background: #f3f4f6;
    transform: scale(1.1);
}

.edit-icon {
    font-size: 18px;
    color: #8b5cf6;
    font-weight: bold;
    transition: all 0.2s;
}

.edit-btn.editing {
    background: #8b5cf6;
    color: white;
}

.edit-btn.editing:hover {
    background: #7c3aed;
}

.edit-btn.editing .edit-icon {
    color: white;
}

/* Payment Section Styles */
.payment-main,
.settings-main,
.help-main {
    padding: 40px;
    background-color: #f8fafc;
}

.payment-header h2,
.settings-header h2,
.help-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 30px 0;
}

.payments-table-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.payments-table {
    width: 100%;
    border-collapse: collapse;
}

.payments-table th {
    background: #f9fafb;
    padding: 16px 20px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #e5e7eb;
}

.payments-table td {
    padding: 20px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: top;
}

.payments-table tr:last-child td {
    border-bottom: none;
}

.payments-table tr:hover {
    background: #f9fafb;
}

.order-link {
    color: #8b5cf6;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
}

.order-link:hover {
    text-decoration: underline;
}

.order-details {
    font-size: 14px;
    color: #1f2937;
    line-height: 1.5;
}

.order-details div {
    margin-bottom: 2px;
}

.order-details div:last-child {
    margin-bottom: 0;
}

.status {
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status.awaiting {
    background: #fef3c7;
    color: #92400e;
}

.status.completed {
    background: #d1fae5;
    color: #065f46;
}

.status.cancelled {
    background: #fee2e2;
    color: #991b1b;
}

.action-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.complete-payment {
    background: #8b5cf6;
    color: white;
}

.complete-payment:hover {
    background: #7c3aed;
}

.see-details {
    background: #6b7280;
    color: white;
}

.see-details:hover {
    background: #4b5563;
}

/* Order Details Section Styles */
.order-details-main {
    padding: 40px;
    background-color: #f8fafc;
}

.order-details-header {
    margin-bottom: 30px;
}

.back-navigation {
    margin-bottom: 20px;
}

.back-link {
    color: #8b5cf6;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.back-link:hover {
    text-decoration: underline;
}

.order-details-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.order-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.order-summary-card,
.order-items-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.order-meta {
    margin-bottom: 30px;
}

.meta-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.meta-row:last-child {
    border-bottom: none;
}

.meta-label {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
}

.meta-value {
    font-size: 14px;
    color: #1f2937;
    font-weight: 600;
}

.billing-info,
.email-info {
    margin-bottom: 24px;
}

.billing-info:last-child,
.email-info:last-child {
    margin-bottom: 0;
}

.billing-info h4,
.email-info h4,
.order-items-card h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 12px 0;
}

.billing-address,
.email-address {
    font-size: 14px;
    color: #4b5563;
    line-height: 1.5;
}

.billing-address div {
    margin-bottom: 2px;
}

.order-items {
    margin-bottom: 24px;
}

.order-item {
    display: flex;
    align-items: center;
    padding: 16px 0;
    border-bottom: 1px solid #f1f5f9;
}

.order-item:last-child {
    border-bottom: none;
}

.item-icon {
    width: 40px;
    height: 40px;
    background: #8b5cf6;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    margin-right: 16px;
}

.item-details {
    flex: 1;
}

.item-name {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 2px;
}

.item-quantity {
    font-size: 12px;
    color: #6b7280;
}

.item-price {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
}

.order-totals {
    border-top: 1px solid #e5e7eb;
    padding-top: 16px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    font-size: 14px;
}

.total-row.total-final {
    font-weight: 600;
    font-size: 16px;
    border-top: 1px solid #e5e7eb;
    padding-top: 12px;
    margin-top: 8px;
}

.payment-section-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.payment-notice,
.payment-form-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.payment-notice h4,
.payment-form-card h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 16px 0;
}

.payment-notice p {
    font-size: 14px;
    color: #4b5563;
    line-height: 1.5;
    margin: 0 0 12px 0;
}

.payment-notice p:last-child {
    margin-bottom: 0;
}

.payment-methods {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
}

.payment-method {
    width: 48px;
    height: 48px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.payment-method.active {
    border-color: #8b5cf6;
    background: #f3f4f6;
}

.method-icon {
    font-size: 20px;
}

.payment-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}


.payment-input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    color: #1f2937;
    transition: border-color 0.2s;
}

.payment-input:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.complete-payment-btn {
    background: #8b5cf6;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
    margin-top: 8px;
}

.complete-payment-btn:hover {
    background: #7c3aed;
}

/* Settings Section Styles */
.settings-main {
    padding: 40px;
    background-color: #f8fafc;
}

.settings-content {
    max-width: 500px;
}

.settings-content h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 32px 0;
}

.password-reset-form {
    margin-bottom: 32px;
}

.password-reset-form .form-group {
    display: flex;
    margin-bottom: 20px;
}

.password-reset-form .form-group label {
    min-width: 180px;
    margin-right: 20px;
    margin-bottom: 0;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
}

.password-input-container {
    position: relative;
    display: flex;
    align-items: center;
    flex: 1;
}

.password-input {
    padding-right: 50px;
}

.password-toggle {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    color: #6b7280;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.password-toggle:hover {
    color: #374151;
}

.eye-icon {
    font-size: 16px;
}

.change-password-btn {
    background: #724491;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 16px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
    margin-top: 8px;
}

.change-password-btn:hover {
    background: #7c3aed;
}

.change-password-btn:disabled {
    background: #9ca3af;
    cursor: not-allowed;
}

.password-reset-help {
    margin-top: 32px;
}

.divider {
    border: none;
    border-top: 1px solid #e5e7eb;
    margin: 24px 0;
}

.forgot-password-section {
    text-align: left;
}

.forgot-title {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 8px 0;
}

.reset-email-link {
    color: #724491;
    text-decoration: underline;
    font-size: 14px;
    font-weight: 500;
}

.reset-email-link:hover {
    color: #7c3aed;
}

.reset-description {
    font-size: 14px;
    color: #6b7280;
    margin: 8px 0 0 0;
    line-height: 1.5;
}

/* Help Section Styles */
.help-main {
    padding: 40px;
    background-color: #f8fafc;
}

.help-content {
    max-width: 1000px;
}

.faq-section {
    margin-bottom: 40px;
}

.faq-section h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 24px 0;
}

.faq-list {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}

.faq-item {
    border-bottom: 1px solid #e5e7eb;
}

.faq-item:last-child {
    border-bottom: none;
}

.faq-question {
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #1f2937;
    background: white;
    transition: background-color 0.3s ease;
}

.faq-question:hover {
    background: #f9fafb;
}

.faq-item.expanded .faq-question {
    background: #f3f4f6;
}

.faq-toggle {
    font-size: 12px;
    color: #6b7280;
    transition: transform 0.3s ease;
}

.faq-item.expanded .faq-toggle {
    transform: rotate(180deg);
}

.faq-answer {
    max-height: 0;
    overflow: hidden;
    padding: 0 24px;
    border-top: 1px solid #f1f5f9;
    background: #fafbfc;
    transition: max-height 0.3s ease-out, padding 0.3s ease-out, border-top-color 0.3s ease;
}

.faq-item.expanded .faq-answer {
    max-height: 500px;
    padding: 20px 24px;
    transition: max-height 0.4s ease-in, padding 0.3s ease-in, border-top-color 0.3s ease;
    border-top-color: #e2e8f0;
}

.faq-answer p {
    font-size: 14px;
    color: #4b5563;
    line-height: 1.6;
    margin: 0 0 12px 0;
}

.faq-answer p:last-child {
    margin-bottom: 0;
}

.faq-answer ul {
    margin: 12px 0;
    padding-left: 20px;
}

.faq-answer li {
    font-size: 14px;
    color: #4b5563;
    line-height: 1.6;
    margin-bottom: 8px;
}

.faq-answer li:last-child {
    margin-bottom: 0;
}

.faq-answer a {
    color: #724491;
    text-decoration: none;
}

.faq-answer a:hover {
    text-decoration: underline;
}

.info-text {
    color: #1e40af !important;
    font-style: italic;
}
 


.contact-layout {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 40px;
    align-items: flex-start;
}

.contact-info {
    padding-right: 20px;
}

.contact-info h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 12px 0;
}

.contact-description {
    font-size: 14px;
    color: #6b7280;
    line-height: 1.5;
    margin: 0;
}

.contact-form-container {
    width: 100%;
}


.help-contact-form {
    margin: 0;
}

.help-contact-form .form-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 20px;
}

.help-contact-form .form-group label {
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 8px;
}

.message-textarea {
    resize: vertical;
    min-height: 120px;
    font-family: inherit;
}

.recaptcha-group {
    margin-bottom: 20px;
}

.recaptcha-group label {
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 8px;
    display: block;
}

.g-recaptcha {
    margin-top: 8px;
}

/* Responsive reCAPTCHA */
@media (max-width: 480px) {
    .g-recaptcha {
        transform: scale(0.85);
        transform-origin: 0 0;
    }
    
    .validation-error-message {
        font-size: 11px;
    }
    
    .checkbox-groups.error input[type="checkbox"] {
        outline-offset: 1px;
    }
}

.checkbox-group {
    margin-bottom: 24px;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    font-size: 14px;
    color: #4b5563;
    line-height: 1.5;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    display: none;
}

.checkmark {
    width: 18px;
    height: 18px;
    border: 2px solid #d1d5db;
    border-radius: 4px;
    background: white;
    position: relative;
    flex-shrink: 0;
    margin-top: 2px;
    transition: all 0.2s;
}

.checkbox-label input[type="checkbox"]:checked + .checkmark {
    background: #724491;
    border-color: #724491;
}

.checkbox-label input[type="checkbox"]:checked + .checkmark::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.send-message-btn {
    background: #724491;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
}

.send-message-btn:hover {
    background: #5a2d75;
}

.send-message-btn:disabled {
    background: #9ca3af;
    cursor: not-allowed;
}

/* Form Validation Error Styles */
.form-input.error {
    border-color: #ef4444 !important;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
}

.g-recaptcha.error {
    border: 2px solid #ef4444;
    border-radius: 4px;
    padding: 4px;
}

.checkbox-groups.error {
    color: #ef4444;
}

.checkbox-groups.error input[type="checkbox"] {
    outline: 2px solid #ef4444;
    outline-offset: 2px;
}

.validation-error-message {
    color: #ef4444;
    font-size: 12px;
    margin-top: 4px;
    display: none;
    font-weight: 500;
}

.form-group .validation-error-message {
    margin-bottom: 0;
}

.section-content {
    padding: 24px;
}


.form-row:last-child {
    margin-bottom: 0;
}

.form-group {
    display: flex;
    flex-direction: row;
    gap: 20px;
    margin-bottom: 16px;
}

.form-group label {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    min-width: 120px;
    flex-shrink: 0;
}

.form-value {
    font-size: 16px;
    color: #1f2937;
    flex: 1;
}

.address-value {
    line-height: 1.5;
}

.placeholder-text {
    color: #9ca3af;
    font-style: italic;
}

/* Form Inputs */
.form-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 16px;
    color: #1f2937;
    transition: border-color 0.2s;
    background: white;
}

.form-input:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.form-input[readonly] {
    background: #f3f4f6;
    color: #6b7280;
    cursor: not-allowed;
}

.form-input.error {
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
    background-color: #fef2f2;
}

/* Edit Actions */
.edit-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid #e5e7eb;
}

.btn-secondary, .btn-primary {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

.btn-primary {
    background: #8b5cf6;
    color: white;
}

.btn-primary:hover {
    background: #7c3aed;
}

.btn-primary:disabled {
    background: #d1d5db;
    color: #9ca3af;
    cursor: not-allowed;
}

/* Contact Grid */
.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
}

.contact-column h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 20px 0;
}

.contact-column .form-group {
    margin-bottom: 16px;
}

.contact-column .form-group label {
    min-width: 80px;
}

/* Qualifications Table */
.qualifications-table {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    overflow: hidden;
}

.table-header {
    display: grid;
    grid-template-columns: 2fr 1.5fr 1fr 1fr;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.header-cell {
    padding: 12px 16px;
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-right: 1px solid #e5e7eb;
}

.header-cell:last-child {
    border-right: none;
}

.table-body {
    display: flex;
    flex-direction: column;
}

.table-row {
    display: grid;
    grid-template-columns: 2fr 1.5fr 1fr 1fr;
    border-bottom: 1px solid #f1f5f9;
}

.table-row:last-child {
    border-bottom: none;
}

.table-cell {
    padding: 16px;
    font-size: 14px;
    color: #1f2937;
    border-right: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
}

.table-cell:last-child {
    border-right: none;
}

/* Actions */
.profile-actions {
    text-align: center;
    padding-top: 24px;
    border-top: 1px solid #e5e7eb;
    margin-top: 32px;
}

.save-btn {
    background: #f97316;
    color: white;
    padding: 12px 32px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
}

.save-btn:hover:not(:disabled) {
    background: #ea580c;
}

.save-btn:disabled {
    background: #d1d5db;
    color: #9ca3af;
    cursor: not-allowed;
}

/* Validation Alert */
.validation-alert {
    background: #dc2626;
    color: white;
    padding: 16px 20px;
    border-radius: 8px;
    margin: 40px 0 30px 0;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 500;
    box-shadow: 0 4px 6px rgba(220, 38, 38, 0.1);
    border: 1px solid #b91c1c;
}

.alert-icon {
    font-size: 18px;
    font-weight: bold;
}

.alert-message {
    flex: 1;
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-content {
        padding: 80px 0 40px 0;
    }
    
    .page-header h1 {
        font-size: 2.5rem;
    }
    
    .user-welcome h2 {
        font-size: 1.25rem;
    }
    
    .designation {
        font-size: 0.9rem;
    }
    
    .profile-content {
        grid-template-columns: 1fr;
    }
    
    .profile-sidebar {
        border-right: none;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .profile-sidebar {
        padding: 16px;
    }
    
    .profile-nav ul {
        flex-direction: row;
        overflow-x: auto;
        gap: 8px;
        padding-bottom: 8px;
    }
    
    .profile-nav li {
        white-space: nowrap;
        flex-shrink: 0;
    }
    
    .profile-nav a {
        padding: 12px 16px;
        font-size: 13px;
    }
    
    .profile-nav li.logout {
        margin-top: 0;
        margin-left: 8px;
    }
}
    
    .profile-main {
        padding: 20px;
    }
    
    .section-header {
        padding: 16px 20px;
    }
    
    .section-content {
        padding: 20px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .contact-grid {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    
    
    .table-header,
    .table-row {
        grid-template-columns: 2fr 1fr;
    }
    
    .header-cell:nth-child(3),
    .header-cell:nth-child(4),
    .table-cell:nth-child(3),
    .table-cell:nth-child(4) {
        display: none;
    }
}

@media (max-width: 768px) and (min-width: 481px) {
    .payment-main,
    .settings-main,
    .help-main {
        padding: 30px 20px;
    }
    
    .payments-table-container {
        overflow-x: auto;
    }
    
    .payments-table {
        min-width: 700px;
    }
    
    .order-details-main {
        padding: 30px 20px;
    }
    
    .order-info-grid {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    
    .payment-section-grid {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    
    .help-main {
        padding: 30px 20px;
    }
    
    
    .contact-layout {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    
    .faq-question {
        padding: 18px 22px;
    }
    
    .faq-answer {
        padding: 0 22px;
    }
    
    .faq-item.expanded .faq-answer {
        padding: 18px 22px;
    }
}

@media (max-width: 480px) {
    .container {
        padding: 0 16px;
    }
    
    .hero-content {
        padding: 60px 0 30px 0;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
    
    .user-welcome h2 {
        font-size: 1.1rem;
    }
    
    .designation {
        font-size: 0.85rem;
    }
    
    .profile-main {
        padding: 16px;
    }
    
    .validation-alert {
        padding: 12px 16px;
        margin: 20px 0 20px 0;
        font-size: 14px;
    }
    
    .alert-icon {
        font-size: 16px;
    }
    
    .payment-main,
    .settings-main,
    .help-main {
        padding: 20px 16px;
    }
    
    .payments-table-container {
        overflow-x: auto;
    }
    
    .payments-table {
        min-width: 600px;
    }
    
    .payments-table th,
    .payments-table td {
        padding: 12px 16px;
        font-size: 12px;
    }
    
    .action-btn {
        padding: 6px 12px;
        font-size: 11px;
    }
    
    .order-details-main {
        padding: 20px 16px;
    }
    
    .order-info-grid,
    .payment-section-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .order-summary-card,
    .order-items-card,
    .payment-notice,
    .payment-form-card {
        padding: 20px;
    }
    
    
    .settings-main {
        padding: 20px 16px;
    }
    
    .settings-content {
        max-width: 100%;
    }
    
    .password-reset-form .form-group {
        flex-direction: column;
        margin-bottom: 16px;
    }
    
    .password-reset-form .form-group label {
        min-width: auto;
        margin-right: 0;
        margin-bottom: 8px;
        text-align: left;
    }
    
    .change-password-btn {
        margin-left: 0;
    }
    
    .help-main {
        padding: 20px 16px;
    }
    
    
    .contact-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .contact-section {
        padding: 24px 20px;
    }
    
    .faq-question {
        padding: 16px 20px;
    }
    
    .faq-answer {
        padding: 0 20px;
    }
    
    .faq-item.expanded .faq-answer {
        padding: 16px 20px;
    }
    
    .table-header,
    .table-row {
        grid-template-columns: 1fr;
    }
    
    .header-cell:nth-child(2),
    .table-cell:nth-child(2) {
        display: none;
    }
}
</style>

<script>
// WordPress AJAX URL for frontend
const ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';

// Load reCAPTCHA API
(function() {
    const script = document.createElement('script');
    script.src = 'https://www.google.com/recaptcha/api.js';
    script.async = true;
    script.defer = true;
    document.head.appendChild(script);
})();

// Profile page functionality
let originalData = {};
let editingSections = new Set();

// Store original form data
function storeOriginalData(sectionId) {
    const section = document.getElementById(sectionId);
    const editMode = section.querySelector('.edit-mode');
    const inputs = editMode.querySelectorAll('.form-input:not([readonly])');
    
    originalData[sectionId] = {};
    inputs.forEach(input => {
        originalData[sectionId][input.name] = input.value;
    });
    

}

// Check if any section has changes
function checkForChanges() {
    let hasChanges = false;
    
    editingSections.forEach(sectionId => {
        const section = document.getElementById(sectionId);
        const editMode = section.querySelector('.edit-mode');
        const inputs = editMode.querySelectorAll('.form-input:not([readonly])');
        
        inputs.forEach(input => {
            if (originalData[sectionId] && originalData[sectionId][input.name] !== input.value) {
                hasChanges = true;
            }
        });
    });
    
    return hasChanges;
}

// Update global save button state
function updateSaveButtonState() {
    const saveBtn = document.getElementById('global-save-btn');
    const hasChanges = checkForChanges();
    
    if (hasChanges) {
        saveBtn.disabled = false;
    } else {
        saveBtn.disabled = true;
    }
}

// Validate form inputs
function validateForm() {
    const validationErrors = [];
    
    // Check each editing section
    editingSections.forEach(sectionId => {
        const section = document.getElementById(sectionId);
        const inputs = section.querySelectorAll('.form-input:not([readonly])');
        
        inputs.forEach(input => {
            // Clear previous error styling
            input.classList.remove('error');
            
            // Check required fields
            const label = input.closest('.form-group').querySelector('label');
            const isRequired = label && label.textContent.includes('*');
            
            if (isRequired && (!input.value || input.value.trim() === '')) {
                input.classList.add('error');
                validationErrors.push(`${label.textContent.replace('*', '')} is required`);
            }
            
            // Validate email format
            if (input.type === 'email' && input.value && !isValidEmail(input.value)) {
                input.classList.add('error');
                validationErrors.push('Please enter a valid email address');
            }
        });
    });
    
    return validationErrors;
}

// Email validation helper
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Show validation alert
function showValidationAlert(errors) {
    const alert = document.getElementById('validation-alert');
    const message = alert.querySelector('.alert-message');
    
    if (errors.length > 0) {
        message.textContent = 'Cannot save changes. Please fix error(s) in the form.';
        alert.style.display = 'flex';
        
        // Scroll to the top of the page to show the alert
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            hideValidationAlert();
        }, 5000);
    }
}

// Hide validation alert
function hideValidationAlert() {
    const alert = document.getElementById('validation-alert');
    alert.style.display = 'none';
    
    // Clear all error styling
    document.querySelectorAll('.form-input.error').forEach(input => {
        input.classList.remove('error');
    });
}

// Update edit button appearance based on mode
function updateEditButtonState(sectionId, isEditing) {
    const section = document.getElementById(sectionId);
    const editBtn = section.querySelector('.edit-btn');
    const editIcon = section.querySelector('.edit-icon');
    
    if (!editBtn || !editIcon) {
        console.error('Edit button or icon not found for section:', sectionId);
        return;
    }
    
    if (isEditing) {
        editBtn.classList.add('editing');
        editIcon.textContent = '✓'; // Checkmark when editing
        editBtn.title = 'Exit edit mode';
    } else {
        editBtn.classList.remove('editing');
        editIcon.textContent = '✎'; // Pencil when viewing
        editBtn.title = 'Edit';
    }
}

// Edit section - toggles between edit and view mode
function editSection(sectionId) {

    
    const section = document.getElementById(sectionId);
    const viewMode = section.querySelector('.view-mode');
    const editMode = section.querySelector('.edit-mode');
    
    // Check if already in edit mode
    if (editingSections.has(sectionId)) {

        // Toggle back to view mode
        cancelEditSection(sectionId);
        return;
    }
    
    // Store original data only if not already stored
    if (!originalData[sectionId]) {
        storeOriginalData(sectionId);
    }
    
    // Add to editing sections
    editingSections.add(sectionId);
    
    // Toggle modes
    viewMode.style.display = 'none';
    editMode.style.display = 'block';
    
    // Update edit button appearance
    updateEditButtonState(sectionId, true);
    
    // Setup change listeners if not already setup
    if (!section.hasAttribute('data-listeners-added')) {
        const inputs = editMode.querySelectorAll('.form-input:not([readonly])');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                // Clear error styling when user starts typing
                if (input.classList.contains('error')) {
                    input.classList.remove('error');
                    
                    // Hide alert if no more errors
                    const remainingErrors = document.querySelectorAll('.form-input.error');
                    if (remainingErrors.length === 0) {
                        hideValidationAlert();
                    }
                }
                
                updateSaveButtonState();
            });
        });
        section.setAttribute('data-listeners-added', 'true');
    }
    
    updateSaveButtonState();
}

// Cancel edit for specific section
function cancelEditSection(sectionId) {

    
    const section = document.getElementById(sectionId);
    const viewMode = section.querySelector('.view-mode');
    const editMode = section.querySelector('.edit-mode');
    const inputs = editMode.querySelectorAll('.form-input:not([readonly])');
    
    // Restore original values
    if (originalData[sectionId]) {

        inputs.forEach(input => {
            if (originalData[sectionId][input.name] !== undefined) {
                input.value = originalData[sectionId][input.name];
            }
        });
    }
    
    // Toggle modes
    editMode.style.display = 'none';
    viewMode.style.display = 'block';
    
    // Update edit button appearance
    updateEditButtonState(sectionId, false);
    
    // Remove from editing sections
    editingSections.delete(sectionId);
    
    updateSaveButtonState();

}

// Update view mode with new values
function updateViewMode(sectionId) {
    const section = document.getElementById(sectionId);
    const viewMode = section.querySelector('.view-mode');
    const editMode = section.querySelector('.edit-mode');
    const inputs = editMode.querySelectorAll('.form-input:not([readonly])');
    
    if (sectionId === 'basic-info') {
        const firstNameInput = section.querySelector('input[name="first_name"]');
        const lastNameInput = section.querySelector('input[name="last_name"]');
        const emailInput = section.querySelector('input[name="email"]');
        
        const nameField = viewMode.querySelector('[data-field="name"]');
        const emailField = viewMode.querySelector('[data-field="email"]');
        
        if (nameField && firstNameInput && lastNameInput) {
            nameField.textContent = firstNameInput.value + ' ' + lastNameInput.value;
        }
        if (emailField && emailInput) {
            emailField.textContent = emailInput.value;
        }
    } else if (sectionId === 'contact-details') {
        // Update contact details view
        inputs.forEach(input => {
            const field = viewMode.querySelector(`[data-field="${input.name}"]`);
            if (field) {
                field.textContent = input.value || 'Not provided';
                field.className = 'form-value';
                if (!input.value) {
                    field.innerHTML = '<span class="placeholder-text">Not provided</span>';
                }
            }
        });
    } else if (sectionId === 'address') {
        // Update address view
        const addressLine1 = section.querySelector('input[name="address_line1"]').value;
        const addressLine2 = section.querySelector('input[name="address_line2"]').value;
        const city = section.querySelector('input[name="city"]').value;
        const county = section.querySelector('input[name="county"]').value;
        const eircode = section.querySelector('input[name="eircode"]').value;
        
        const addressField = viewMode.querySelector('[data-field="address"]');
        const addressParts = [addressLine1, addressLine2, city, county, eircode].filter(Boolean);
        
        if (addressParts.length > 0) {
            addressField.innerHTML = addressParts.join('<br>');
        } else {
            addressField.innerHTML = '<span class="placeholder-text">No address information on file</span>';
        }
    } else if (sectionId === 'employment') {
        // Update employment view
        const employerNameInput = section.querySelector('input[name="employer_name"]');
        const employerNameField = viewMode.querySelector('[data-field="employer_name"]');
        
        if (employerNameField && employerNameInput) {
            if (employerNameInput.value) {
                employerNameField.textContent = employerNameInput.value;
            } else {
                employerNameField.innerHTML = '<span class="placeholder-text">No employer information on file</span>';
            }
        }
        
        // Update employer address
        const line1 = section.querySelector('input[name="employer_address_line1"]').value;
        const line2 = section.querySelector('input[name="employer_address_line2"]').value;
        const empCity = section.querySelector('input[name="employer_city"]').value;
        const empCounty = section.querySelector('input[name="employer_county"]').value;
        const empEircode = section.querySelector('input[name="employer_eircode"]').value;
        
        const empAddressField = viewMode.querySelector('[data-field="employer_address"]');
        const empAddressParts = [line1, line2, empCity, empCounty, empEircode].filter(Boolean);
        
        if (empAddressParts.length > 0) {
            empAddressField.innerHTML = empAddressParts.join('<br>');
        } else {
            empAddressField.innerHTML = '<span class="placeholder-text">No employer address information on file</span>';
        }
    }
}

function saveProfile() {
    if (editingSections.size === 0) {
        if (window.notifications) {
            window.notifications.info('No Changes', 'No sections are currently being edited.');
        }
        return;
    }
    
    // Hide any previous validation alerts
    hideValidationAlert();
    
    // Validate form before saving
    const validationErrors = validateForm();
    if (validationErrors.length > 0) {
        showValidationAlert(validationErrors);
        console.log('Validation errors:', validationErrors);
        return;
    }
    
    // Show loading state
    const saveBtn = document.getElementById('global-save-btn');
    const originalText = saveBtn.textContent;
    saveBtn.textContent = 'Saving...';
    saveBtn.disabled = true;
    
    // Save all editing sections
    Promise.all(Array.from(editingSections).map(sectionId => saveSection(sectionId)))
        .then(results => {
            const allSuccessful = results.every(result => result.success);
            
            if (allSuccessful) {
                // Update all view modes and exit edit mode
                editingSections.forEach(sectionId => {
                    updateViewMode(sectionId);
                    
                    const section = document.getElementById(sectionId);
                    const viewMode = section.querySelector('.view-mode');
                    const editMode = section.querySelector('.edit-mode');
                    
                    editMode.style.display = 'none';
                    viewMode.style.display = 'block';
                    
                    // Reset edit button state
                    updateEditButtonState(sectionId, false);
                    
                    // Update original data to the new saved values
                    storeOriginalData(sectionId);
                });
                
                // Clear editing sections
                editingSections.clear();
                updateSaveButtonState();
                
                // Show success notification
                if (window.notifications) {
                    window.notifications.success('Profile Saved', 'Your profile has been updated successfully.');
                }
                
                console.log('All sections saved successfully');
            } else {
                // Show error notification
                if (window.notifications) {
                    window.notifications.error('Save Failed', 'Some sections could not be saved. Please try again.');
                }
                console.error('Some sections failed to save');
            }
        })
        .catch(error => {
            // Show error notification
            if (window.notifications) {
                window.notifications.error('Save Failed', 'An error occurred while saving. Please try again.');
            }
            console.error('Save error:', error);
        })
        .finally(() => {
            // Reset button state
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;
        });
}

// Save individual section (used by global save)
function saveSection(sectionId) {
    return new Promise((resolve, reject) => {
        const section = document.getElementById(sectionId);
        const editMode = section.querySelector('.edit-mode');
        const inputs = editMode.querySelectorAll('.form-input:not([readonly])');
        
        // Collect form data
        const formData = new FormData();
        formData.append('action', 'iipm_update_profile');
        formData.append('section', sectionId);
        formData.append('nonce', '<?php echo wp_create_nonce('iipm_portal_nonce'); ?>');
        
        inputs.forEach(input => {
            formData.append(input.name, input.value);
        });
        
        // Send AJAX request
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            resolve({ success: data.success, sectionId: sectionId, data: data });
        })
        .catch(error => {
            reject({ success: false, sectionId: sectionId, error: error });
        });
    });
}

// Section navigation
function showSection(sectionName) {
    // Hide all sections
    const sections = ['profile-section', 'payment-section', 'order-details-section', 'settings-section', 'help-section'];
    sections.forEach(id => {
        const section = document.getElementById(id);
        if (section) {
            section.style.display = 'none';
        }
    });
    
    // Show selected section
    const targetSection = document.getElementById(sectionName + '-section');
    if (targetSection) {
        targetSection.style.display = 'block';
    }
    
    // Update sidebar navigation (only for main sections, not order details)
    if (sectionName !== 'order-details') {
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            item.classList.remove('active');
            if (item.getAttribute('data-section') === sectionName) {
                item.classList.add('active');
            }
        });
    }
    
    // Clear any editing modes when switching sections
    if (sectionName !== 'profile' && editingSections.size > 0) {
        const sectionsArray = Array.from(editingSections);
        sectionsArray.forEach(sectionId => {
            cancelEditSection(sectionId);
        });
    }
    
    console.log('Switched to section:', sectionName);
}

// Show order details
function showOrderDetails(orderId) {
    // Sample order data - in real implementation, this would come from backend
    const orderData = {
        '2210': {
            number: '2210',
            date: '05/01/2023',
            status: 'Awaiting Payment',
            statusClass: 'awaiting',
            items: [
                { name: 'IIPM Fellowship', quantity: '1x', price: '€270.00' },
                { name: 'IIPM Membership', quantity: '1x', price: '€130.00' }
            ],
            subtotal: '€400.00',
            tax: '€0.00',
            total: '€400.00',
            showPaymentForm: true
        },
        '2316': {
            number: '2316',
            date: '04/20/2023',
            status: 'Completed',
            statusClass: 'completed',
            items: [
                { name: 'IIPM Membership', quantity: '1x', price: '€130.00' }
            ],
            subtotal: '€130.00',
            tax: '€0.00',
            total: '€130.00',
            showPaymentForm: false
        }
    };
    
    const order = orderData[orderId];
    if (!order) {
        console.error('Order not found:', orderId);
        return;
    }
    
    // Update order details in the page
    document.getElementById('order-details-title').textContent = `Order Details - ${order.number}`;
    document.getElementById('order-number').textContent = order.number;
    document.getElementById('order-date').textContent = order.date;
    
    const statusElement = document.getElementById('order-status');
    statusElement.textContent = order.status;
    statusElement.className = `status ${order.statusClass}`;
    
    // Update order items
    const orderItemsContainer = document.querySelector('.order-items');
    orderItemsContainer.innerHTML = order.items.map(item => `
        <div class="order-item">
            <div class="item-icon">🎓</div>
            <div class="item-details">
                <div class="item-name">${item.name}</div>
                <div class="item-quantity">${item.quantity}</div>
            </div>
            <div class="item-price">${item.price}</div>
        </div>
    `).join('');
    
    // Update totals
    document.querySelector('.order-totals').innerHTML = `
        <div class="total-row">
            <span>Subtotal</span>
            <span>${order.subtotal}</span>
        </div>
        <div class="total-row">
            <span>Tax</span>
            <span>${order.tax}</span>
        </div>
        <div class="total-row total-final">
            <span>Total</span>
            <span>${order.total}</span>
        </div>
    `;
    
    // Show/hide payment form based on order status
    const paymentFormCard = document.querySelector('.payment-form-card');
    const paymentNotice = document.querySelector('.payment-notice');
    
    if (order.showPaymentForm) {
        paymentFormCard.style.display = 'block';
        paymentNotice.innerHTML = `
            <h4>Payment Notice</h4>
            <p>You haven't completed your IIPM Membership payment. Please complete your payment by <strong>Thursday, May 3, 2025</strong>.</p>
            <p>Please contact administrator if you encounter any problem with your order. Thank you.</p>
        `;
    } else {
        paymentFormCard.style.display = 'none';
        paymentNotice.innerHTML = `
            <h4>Order Information</h4>
            <p>This order has been completed. Thank you for your payment.</p>
            <p>If you have any questions about this order, please contact our support team.</p>
        `;
    }
    
    // Show the order details section
    showSection('order-details');
    
    // Add payment form handler if the payment form is visible
    if (order.showPaymentForm) {
        setTimeout(() => {
            const paymentForm = document.querySelector('.payment-form');
            if (paymentForm) {
                paymentForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Show processing notification
                    if (window.notifications) {
                        window.notifications.info('Processing Payment', 'Please wait while we process your payment...');
                    }
                    
                    // Simulate payment processing
                    setTimeout(() => {
                        if (window.notifications) {
                            window.notifications.success('Payment Successful', 'Your payment has been processed successfully!');
                        }
                        
                        // Redirect back to payments list
                        setTimeout(() => {
                            showSection('payment');
                        }, 2000);
                    }, 2000);
                });
            }
        }, 100);
    }
}

// Password visibility toggle
function togglePasswordVisibility(inputId, toggleButton) {
    const input = document.getElementById(inputId);
    const eyeIcon = toggleButton.querySelector('.eye-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        eyeIcon.textContent = '🙈';
    } else {
        input.type = 'password';
        eyeIcon.textContent = '👁';
    }
}

// Send reset email
function sendResetEmail() {
    if (window.notifications) {
        window.notifications.info('Sending Reset Email', 'Please wait...');
    }
    
    // Make AJAX call to send reset email
    const formData = new FormData();
    formData.append('action', 'iipm_send_reset_email');
    
    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (window.notifications) {
                window.notifications.success('Reset Email Sent', data.data.message);
            }
        } else {
            if (window.notifications) {
                window.notifications.error('Error', data.data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (window.notifications) {
            window.notifications.error('Error', 'Failed to send reset email. Please try again.');
        }
    });
}

// Password strength validation
function validatePassword(password) {
    const minLength = 8;
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);
    
    if (password.length < minLength) {
        return { valid: false, message: 'Password must be at least 8 characters long.' };
    }
    
    if (!hasUpperCase || !hasLowerCase) {
        return { valid: false, message: 'Password must contain both uppercase and lowercase letters.' };
    }
    
    if (!hasNumbers) {
        return { valid: false, message: 'Password must contain at least one number.' };
    }
    
    if (!hasSpecialChar) {
        return { valid: false, message: 'Password must contain at least one special character.' };
    }
    
    return { valid: true, message: 'Password is strong.' };
}

// Initialize password reset form
function initializePasswordResetForm() {
    const form = document.getElementById('password-reset-form');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const currentPassword = document.getElementById('current-password').value;
        const newPassword = document.getElementById('new-password').value;
        const confirmPassword = document.getElementById('confirm-password').value;
        
        // Client-side validation
        if (!currentPassword || !newPassword || !confirmPassword) {
            if (window.notifications) {
                window.notifications.error('Missing Information', 'Please fill in all password fields.');
            }
            return;
        }
        
        if (newPassword !== confirmPassword) {
            if (window.notifications) {
                window.notifications.error('Password Mismatch', 'New password and confirmation password do not match.');
            }
            return;
        }
        
        // Show processing state
        const submitBtn = form.querySelector('.change-password-btn');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Changing Password...';
        
        if (window.notifications) {
            window.notifications.info('Updating Password', 'Please wait while we update your password...');
        }
        
        // Prepare form data
        const formData = new FormData();
        formData.append('action', 'iipm_change_password');
        formData.append('current_password', currentPassword);
        formData.append('new_password', newPassword);
        formData.append('confirm_password', confirmPassword);
        formData.append('nonce', '<?php echo wp_create_nonce("iipm_change_password"); ?>');
        
        // Make AJAX call
        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            
            if (data.success) {
                // Success - reset form and show notification
                form.reset();
                if (window.notifications) {
                    window.notifications.success('Password Changed', data.data.message);
                }
            } else {
                // Error - show error message
                if (window.notifications) {
                    window.notifications.error('Error', data.data.message);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            
            if (window.notifications) {
                window.notifications.error('Error', 'Failed to change password. Please try again.');
            }
        });
    });
}

// FAQ functionality
function toggleFAQ(questionElement) {
    const faqItem = questionElement.parentElement;
    const toggle = questionElement.querySelector('.faq-toggle');
    
    const isExpanded = faqItem.classList.contains('expanded');
    
    if (isExpanded) {
        // Collapse
        faqItem.classList.remove('expanded');
        toggle.textContent = '▼';
    } else {
        // Expand
        faqItem.classList.add('expanded');
        toggle.textContent = '▲';
    }
}

// reCAPTCHA functionality
function resetRecaptcha() {
    if (typeof grecaptcha !== 'undefined') {
        grecaptcha.reset();
    }
}

// Clear form validation errors
function clearFormErrors() {
    const errorElements = document.querySelectorAll('.error');
    errorElements.forEach(element => element.classList.remove('error'));
    
    const errorMessages = document.querySelectorAll('.validation-error-message');
    errorMessages.forEach(message => message.style.display = 'none');
}

// Add error styling to field
function addFieldError(fieldId, message = '') {
    const field = document.getElementById(fieldId);
    if (field) {
        field.classList.add('error');
        
        // Add error message if provided
        if (message) {
            let errorMsg = field.parentElement.querySelector('.validation-error-message');
            if (!errorMsg) {
                errorMsg = document.createElement('div');
                errorMsg.className = 'validation-error-message';
                field.parentElement.appendChild(errorMsg);
            }
            errorMsg.textContent = message;
            errorMsg.style.display = 'block';
        }
    }
}

// Add error styling to reCAPTCHA
function addRecaptchaError() {
    const recaptcha = document.querySelector('.g-recaptcha');
    if (recaptcha) {
        recaptcha.classList.add('error');
    }
}

// Add error styling to checkbox
function addCheckboxError() {
    const checkbox = document.querySelector('.checkbox-groups');
    if (checkbox) {
        checkbox.classList.add('error');
    }
}

// Help contact form functionality
function initializeHelpContactForm() {
    const form = document.getElementById('help-contact-form');
    if (!form) return;
    
    // Add event listeners to clear errors when user starts typing
    const fields = ['help-full-name', 'help-email', 'help-phone', 'help-message'];
    fields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function() {
                this.classList.remove('error');
                const errorMsg = this.parentElement.querySelector('.validation-error-message');
                if (errorMsg) {
                    errorMsg.style.display = 'none';
                }
            });
        }
    });
    
    // Clear reCAPTCHA error when user interacts with it
    const recaptcha = document.querySelector('.g-recaptcha');
    if (recaptcha) {
        // Check for reCAPTCHA completion periodically
        setInterval(() => {
            if (grecaptcha && grecaptcha.getResponse && grecaptcha.getResponse()) {
                recaptcha.classList.remove('error');
            }
        }, 1000);
    }
    
    // Clear checkbox error when user checks it
    const privacyCheckbox = document.getElementById('privacy-consent');
    if (privacyCheckbox) {
        privacyCheckbox.addEventListener('change', function() {
            const checkboxGroup = document.querySelector('.checkbox-groups');
            if (checkboxGroup) {
                checkboxGroup.classList.remove('error');
            }
        });
    }
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Clear previous errors
        clearFormErrors();
        
        const fullName = document.getElementById('help-full-name').value.trim();
        const email = document.getElementById('help-email').value.trim();
        const phone = document.getElementById('help-phone').value.trim();
        const message = document.getElementById('help-message').value.trim();
        const privacyConsent = document.getElementById('privacy-consent').checked;
        
        let hasErrors = false;
        
        // Validate required fields and add error styling
        if (!fullName) {
            addFieldError('help-full-name', 'Full name is required');
            hasErrors = true;
        }
        
        if (!email) {
            addFieldError('help-email', 'Email address is required');
            hasErrors = true;
        } else {
            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                addFieldError('help-email', 'Please enter a valid email address');
                hasErrors = true;
            }
        }
        
        if (!phone) {
            addFieldError('help-phone', 'Phone number is required');
            hasErrors = true;
        }
        
        if (!message) {
            addFieldError('help-message', 'Message is required');
            hasErrors = true;
        }
        
        // Validate reCAPTCHA
        const recaptchaResponse = grecaptcha.getResponse();
        if (!recaptchaResponse) {
            addRecaptchaError();
            hasErrors = true;
        }
        
        // Validate privacy consent
        if (!privacyConsent) {
            addCheckboxError();
            hasErrors = true;
        }
        
        // Show error notification if there are validation errors
        if (hasErrors) {
            if (window.notifications) {
                window.notifications.error('Validation Error', 'Please fill in all required fields correctly.');
            }
            
            // Scroll to first error field
            const firstError = document.querySelector('.error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }
        
        // Show processing state
        const submitBtn = form.querySelector('.send-message-btn');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending Message...';
        
        if (window.notifications) {
            window.notifications.info('Sending Message', 'Please wait while we send your message...');
        }
        
        // Prepare form data
        const formData = new FormData();
        formData.append('action', 'iipm_send_help_message');
        formData.append('full_name', fullName);
        formData.append('email', email);
        formData.append('phone', phone);
        formData.append('message', message);
        formData.append('g-recaptcha-response', recaptchaResponse);
        formData.append('nonce', '<?php echo wp_create_nonce("iipm_help_message"); ?>');
        
        // Make AJAX call
        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            
            if (data.success) {
                // Success - reset form and show notification
                form.reset();
                resetRecaptcha();
                clearFormErrors();
                if (window.notifications) {
                    window.notifications.success('Message Sent', data.data.message);
                }
            } else {
                // Error - show error message
                if (window.notifications) {
                    window.notifications.error('Error', data.data.message);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            
            if (window.notifications) {
                window.notifications.error('Error', 'Failed to send message. Please try again.');
            }
        });
    });
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    console.log('Profile page loaded');
    updateSaveButtonState();
    
    // Initialize edit button states
    const sections = ['basic-info', 'contact-details', 'address', 'employment', 'qualifications'];
    sections.forEach(sectionId => {
        updateEditButtonState(sectionId, false);
    });
    
    // Show profile section by default
    showSection('profile');
    
    // Initialize password reset form
    initializePasswordResetForm();
    
    // Initialize help contact form
    initializeHelpContactForm();
    
    // Add escape key handler to exit edit modes
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && editingSections.size > 0) {
            // Cancel all editing sections
            const sectionsArray = Array.from(editingSections);
            sectionsArray.forEach(sectionId => {
                cancelEditSection(sectionId);
            });
        }
    });
});
</script>

<?php get_footer(); ?> 