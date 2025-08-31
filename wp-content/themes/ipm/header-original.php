<?php
/**
 * Original Header Template
 * This is the original complex header with full navigation
 * 
 * @package IPM
 */
?>

<header class="header">
    <div class="container">
        <div class="header__inner flex align-center space-between">

            <div class="header__burger" id="burger">
                <svg class="opened" xmlns="http://www.w3.org/2000/svg" width="29" height="25" viewBox="0 0 29 25" fill="none">
                    <path
                        d="M0 0H28.3333V3.125H0V0ZM0 10.9375H28.3333V14.0625H0V10.9375ZM0 21.875H28.3333V25H0V21.875Z"
                        fill="white" />
                </svg>
			
			<svg class="closed" viewBox="0 -0.5 21 21" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>close [#ffffff]</title> <desc>Created with Sketch.</desc> <defs> </defs> <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Dribbble-Light-Preview" transform="translate(-419.000000, -240.000000)" fill="#ffffff"> <g id="icons" transform="translate(56.000000, 160.000000)"> <polygon id="close-[#ffffff]" points="375.0183 90 384 98.554 382.48065 100 373.5 91.446 364.5183 100 363 98.554 371.98065 90 363 81.446 364.5183 80 373.5 88.554 382.48065 80 384 81.446"> </polygon> </g> </g> </g> </g></svg>
			
            </div>
		
		<style>
			.header.open .closed{
				display: block;
			}
			
			.header.open .opened{
				display: none;
			}
			
			.header .closed{
				width: 32px;
			}
			
			.header:not(.open) .closed{
				display: none;
			}
			
			/* Header Auth Buttons Styles - High Specificity */
			.header .header__inner .header__auth-buttons {
				display: flex !important;
				flex-direction: row !important;
				align-items: center !important;
				gap: 12px !important;
				justify-content: flex-end !important;
			}
			
			.header .header__inner .header__auth-buttons .login,
			.header .header__inner .header__auth-buttons .register {
				display: inline-flex !important;
				align-items: center !important;
				justify-content: center !important;
				padding: 8px 16px !important;
				border-radius: 6px !important;
				transition: all 0.3s ease !important;
				white-space: nowrap !important;
				min-width: 100px !important;
				text-decoration: none !important;
				font-size: 14px !important;
				font-weight: 500 !important;
				color: white !important;
				margin: 0 !important;
				float: none !important;
				clear: none !important;
				width: auto !important;
				height: auto !important;
			}
			
			.header .header__inner .header__auth-buttons .login {
				background: transparent !important;
				border: 1px solid rgba(255, 255, 255, 0.3) !important;
			}
			
			.header .header__inner .header__auth-buttons .login:hover {
				background: rgba(255, 255, 255, 0.1) !important;
				border-color: rgba(255, 255, 255, 0.5) !important;
			}
			
			.header .header__inner .header__auth-buttons .register {
				background: #ff6b35 !important;
				border: 1px solid #ff6b35 !important;
				color: white !important;
			}
			
			.header .header__inner .header__auth-buttons .register:hover {
				background: #e55a2b !important;
				border-color: #e55a2b !important;
				transform: translateY(-1px) !important;
			}
			
			.header .header__inner .header__auth-buttons svg {
				margin-left: 6px !important;
				flex-shrink: 0 !important;
			}
			
			/* Mobile menu styles - only for the mobile menu, not main header */
			.header__wrapper .header__auth-buttons {
				flex-direction: column !important;
				width: 100% !important;
			}
			
			.header__wrapper .header__auth-buttons .login,
			.header__wrapper .header__auth-buttons .register {
				width: 100% !important;
				justify-content: center !important;
				padding: 12px 16px !important;
				margin-bottom: 8px !important;
			}
			
			/* Ensure no conflicting styles */
			.header .header__inner .header__auth-buttons * {
				box-sizing: border-box !important;
			}
		
			/* Enhanced User Menu Styles */
			.user-menu {
				position: relative;
				display: flex;
				align-items: center;
				margin-top: 8px;
			}

			.user-menu .login {
				background: rgba(255, 255, 255, 0.15) !important;
				border: 2px solid rgba(255, 255, 255, 0.3) !important;
				color: white !important;
				padding: 12px 20px !important;
				border-radius: 50px !important;
				cursor: pointer !important;
				display: flex !important;
				align-items: center !important;
				gap: 12px !important;
				transition: all 0.3s ease !important;
				font-weight: 500 !important;
				text-decoration: none !important;
				font-size: 14px !important;
				min-width: auto !important;
				white-space: nowrap !important;
				margin: 0 !important;
			}

			.user-menu .login:hover {
				background: rgba(255, 255, 255, 0.25) !important;
				border-color: rgba(255, 255, 255, 0.5) !important;
				transform: translateY(-2px) !important;
			}

			.user-menu .login svg {
				width: 20px !important;
				height: 20px !important;
				flex-shrink: 0 !important;
				margin-left: 0 !important;
				margin-right: 0 !important;
			}

			.user-dropdown-menu {
				position: absolute !important;
				top: 100% !important;
				right: 0 !important;
				background: white !important;
				border-radius: 12px !important;
				box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15) !important;
				min-width: 200px !important;
				display: none !important;
				z-index: 1000 !important;
				overflow: hidden !important;
				border: 1px solid #e5e7eb !important;
				animation: dropdownFadeIn 0.3s ease !important;
				margin-top: 2px !important;
				transform: translateY(0) !important;
			}

			.user-menu:hover .user-dropdown-menu {
				display: block !important;
			}

			@keyframes dropdownFadeIn {
				from {
					opacity: 0;
					transform: translateY(-5px);
				}
				to {
					opacity: 1;
					transform: translateY(0);
				}
			}

			.user-dropdown-menu a {
				display: block !important;
				padding: 14px 20px !important;
				color: #374151 !important;
				text-decoration: none !important;
				border-bottom: 1px solid #f3f4f6 !important;
				transition: all 0.2s ease !important;
				font-weight: 500 !important;
				font-size: 14px !important;
				margin: 0 !important;
			}

			.user-dropdown-menu a:last-child {
				border-bottom: none !important;
			}

			.user-dropdown-menu a:hover {
				background: #f8fafc !important;
				color: #8b5a96 !important;
				padding-left: 24px !important;
			}

			/* Arrow indicator */
			.user-menu .login::after {
				content: "▼" !important;
				font-size: 10px !important;
				margin-left: 8px !important;
				transition: transform 0.3s ease !important;
			}

			.user-menu:hover .login::after {
				transform: rotate(180deg) !important;
			}

			/* Override any conflicting styles */
			.user-menu * {
				box-sizing: border-box !important;
			}

			.user-menu .user-dropdown-menu {
				margin: 0 !important;
				padding: 0 !important;
			}
		</style>

            <a href="<?php echo home_url(); ?>" class="logo">
                <?php
                $image = get_field('header_logo', 'option');

                if ($image) {
                    echo '<img src="' . esc_url($image) . '" alt="logo" class="logo__main">';
                }
                ?>
            </a>

            <?php if (is_user_logged_in()): ?>
                <!-- Show user menu for logged in users -->
                <div class="user-menu">
                    <a href="<?php echo home_url('/member-portal/'); ?>" class="login text text-white flex align-center w-max">
                        <?php echo esc_html(wp_get_current_user()->display_name); ?>
                        <svg width="29" height="31" viewBox="0 0 29 31" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M14.5 12.4166C15.1074 12.4166 15.7088 12.297 16.2699 12.0646C16.831 11.8322 17.3409 11.4915 17.7704 11.062C18.1998 10.6325 18.5405 10.1227 18.7729 9.56156C19.0054 9.00043 19.125 8.39901 19.125 7.79165C19.125 7.18428 19.0054 6.58287 18.7729 6.02174C18.5405 5.4606 18.1998 4.95075 17.7704 4.52128C17.3409 4.09181 16.831 3.75113 16.2699 3.5187C15.7088 3.28628 15.1074 3.16665 14.5 3.16665C13.2734 3.16665 12.097 3.65392 11.2296 4.52128C10.3623 5.38863 9.875 6.56502 9.875 7.79165C9.875 9.01827 10.3623 10.1947 11.2296 11.062C12.097 11.9294 13.2734 12.4166 14.5 12.4166ZM14.5 15.5C16.5444 15.5 18.505 14.6879 19.9506 13.2423C21.3962 11.7967 22.2083 9.83602 22.2083 7.79165C22.2083 5.74727 21.3962 3.78662 19.9506 2.34103C18.505 0.895439 16.5444 0.083313 14.5 0.083313C12.4556 0.083313 10.495 0.895439 9.04939 2.34103C7.60379 3.78662 6.79167 5.74727 6.79167 7.79165C6.79167 9.83602 7.60379 11.7967 9.04939 13.2423C10.495 14.6879 12.4556 15.5 14.5 15.5ZM3.15179 20.8156C4.917 18.4754 7.57637 17.0416 10.9757 17.0416H18.0243C21.4236 17.0416 24.083 18.4754 25.8482 20.8156C27.5764 23.1081 28.375 26.1621 28.375 29.375C28.375 29.7839 28.2126 30.176 27.9235 30.4651C27.6343 30.7542 27.2422 30.9166 26.8333 30.9166C26.4245 30.9166 26.0323 30.7542 25.7432 30.4651C25.4541 30.176 25.2917 29.7839 25.2917 29.375C25.2917 26.6277 24.6041 24.2875 23.3877 22.6718C22.2083 21.1086 20.4616 20.125 18.0227 20.125H10.9773C8.53837 20.125 6.79167 21.1086 5.61229 22.6718C4.39438 24.2875 3.70833 26.6277 3.70833 29.375C3.70833 29.7839 3.54591 30.176 3.25679 30.4651C2.96767 30.7542 2.57554 30.9166 2.16667 30.9166C1.75779 30.9166 1.36566 30.7542 1.07654 30.4651C0.787425 30.176 0.625 29.7839 0.625 29.375C0.625 26.1621 1.42358 23.1081 3.15179 20.8156Z"
                                fill="white" />
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M0.625 29.375C0.625 28.9661 0.787425 28.574 1.07654 28.2849C1.36566 27.9957 1.75779 27.8333 2.16667 27.8333H26.7748C27.1836 27.8333 27.5758 27.9957 27.8649 28.2849C28.154 28.574 28.3164 28.9661 28.3164 29.375C28.3164 29.7839 28.154 30.176 27.8649 30.4651C27.5758 30.7542 27.1836 30.9166 26.7748 30.9166H2.16667C1.75779 30.9166 1.36566 30.7542 1.07654 30.4651C0.787425 30.176 0.625 29.7839 0.625 29.375Z"
                                fill="white" />
                        </svg>
                    </a>
                    <div class="user-dropdown-menu">
                        <?php 
                        $current_user = wp_get_current_user();
                        $user_roles = $current_user->roles;
                        $is_admin = in_array('administrator', $user_roles) || in_array('iipm_admin', $user_roles);
                        $is_corporate_admin = in_array('iipm_corporate_admin', $user_roles);
                        
                        // Only show Dashboard link for admins and corporate admins
                        if ($is_admin || $is_corporate_admin): ?>
                            <a href="<?php echo home_url('/dashboard/'); ?>">Dashboard</a>
                        <?php endif; ?>
                        
                        <!-- Member Portal - Always visible for logged-in users -->
                        <a href="<?php echo home_url('/member-portal/'); ?>">Member Portal</a>

                        <!-- Leave Request - Always visible for logged-in users -->
                        <a href="<?php echo home_url('/leave-request/'); ?>">Leave Request</a>
                        
                        <!-- Admin-only menu items -->
                        <?php if (current_user_can('administrator')): ?>
                            <a href="<?php echo home_url('/admin-invitations/'); ?>">Admin Invitations</a>
                            <a href="<?php echo home_url('/bulk-import/'); ?>">Bulk Import</a>
                            <a href="<?php echo home_url('/organisation-management/'); ?>">Organisation Management</a>
                            <a href="<?php echo home_url('/user-management/'); ?>">User Management</a>
                        <?php endif; ?>
                        
                        <!-- Corporate Admin menu items -->
                        <?php if (current_user_can('manage_organisation_members') || in_array('iipm_corporate_admin', wp_get_current_user()->roles)): ?>
                            <a href="<?php echo home_url('/admin-invitations/'); ?>">Admin Invitations</a>
                            <a href="<?php echo home_url('/bulk-import/'); ?>">Bulk Import</a>
                            <a href="<?php echo home_url('/user-management/'); ?>">User Management</a>
                        <?php endif; ?>
                        
                        <!-- Editor-only menu items -->
                        <?php if (current_user_can('editor')): ?>
                            <a href="<?php echo admin_url('edit.php'); ?>">Manage Posts</a>
                        <?php endif; ?>
                        
                        <!-- IPM Administrator role (custom role) -->
                        <?php if (current_user_can('ipm_administrator')): ?>
                            <a href="<?php echo home_url('/ipm-admin/'); ?>">IPM Admin Panel</a>
                        <?php endif; ?>
                        
                        <!-- Profile - Always visible for logged-in users -->
                        <a href="<?php echo home_url('/profile/'); ?>">My Profile</a>
                        
                        <!-- Logout - Always visible for logged-in users -->
                        <a href="<?php echo wp_logout_url(home_url()); ?>">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Show only login button for non-logged in users -->
                <div class="header__auth-buttons">
                    <a href="<?php echo home_url('/login/'); ?>" class="login">
                        Login
                        <svg width="16" height="16" viewBox="0 0 29 31" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M14.5 12.4166C15.1074 12.4166 15.7088 12.297 16.2699 12.0646C16.831 11.8322 17.3409 11.4915 17.7704 11.062C18.1998 10.6325 18.5405 10.1227 18.7729 9.56156C19.0054 9.00043 19.125 8.39901 19.125 7.79165C19.125 7.18428 19.0054 6.58287 18.7729 6.02174C18.5405 5.4606 18.1998 4.95075 17.7704 4.52128C17.3409 4.09181 16.831 3.75113 16.2699 3.5187C15.7088 3.28628 15.1074 3.16665 14.5 3.16665C13.2734 3.16665 12.097 3.65392 11.2296 4.52128C10.3623 5.38863 9.875 6.56502 9.875 7.79165C9.875 9.01827 10.3623 10.1947 11.2296 11.062C12.097 11.9294 13.2734 12.4166 14.5 12.4166ZM14.5 15.5C16.5444 15.5 18.505 14.6879 19.9506 13.2423C21.3962 11.7967 22.2083 9.83602 22.2083 7.79165C22.2083 5.74727 21.3962 3.78662 19.9506 2.34103C18.505 0.895439 16.5444 0.083313 14.5 0.083313C12.4556 0.083313 10.495 0.895439 9.04939 2.34103C7.60379 3.78662 6.79167 5.74727 6.79167 7.79165C6.79167 9.83602 7.60379 11.7967 9.04939 13.2423C10.495 14.6879 12.4556 15.5 14.5 15.5Z"
                                fill="white" />
                        </svg>
                    </a>
                </div>
            <?php endif; ?>

            <div class="header__wrapper">
                <div class="container">
                    <div class="header__menu">
                        <?php
                        wp_nav_menu(array(
                            'theme_location' => 'header-menu',
                            'container' => false,
                            'menu_class' => '',
                            'depth' => 1,
                        ));
                        ?>
                    </div>
                    <div>
                        <?php if (is_user_logged_in()): ?>
                            <!-- Mobile user menu for logged in users -->
                            <?php 
                            $current_user = wp_get_current_user();
                            $user_roles = $current_user->roles;
                            $is_admin = in_array('administrator', $user_roles) || in_array('iipm_admin', $user_roles);
                            $is_corporate_admin = in_array('iipm_corporate_admin', $user_roles);
                            
                            // Show appropriate mobile link based on user role
                            if ($is_admin || $is_corporate_admin): ?>
                                <a href="<?php echo home_url('/dashboard/'); ?>" class="login text text-white flex align-center w-max">
                                    <svg width="29" height="31" viewBox="0 0 29 31" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M14.5 12.4166C15.1074 12.4166 15.7088 12.297 16.2699 12.0646C16.831 11.8322 17.3409 11.4915 17.7704 11.062C18.1998 10.6325 18.5405 10.1227 18.7729 9.56156C19.0054 9.00043 19.125 8.39901 19.125 7.79165C19.125 7.18428 19.0054 6.58287 18.7729 6.02174C18.5405 5.4606 18.1998 4.95075 17.7704 4.52128C17.3409 4.09181 16.831 3.75113 16.2699 3.5187C15.7088 3.28628 15.1074 3.16665 14.5 3.16665C13.2734 3.16665 12.097 3.65392 11.2296 4.52128C10.3623 5.38863 9.875 6.56502 9.875 7.79165C9.875 9.01827 10.3623 10.1947 11.2296 11.062C12.097 11.9294 13.2734 12.4166 14.5 12.4166ZM14.5 15.5C16.5444 15.5 18.505 14.6879 19.9506 13.2423C21.3962 11.7967 22.2083 9.83602 22.2083 7.79165C22.2083 5.74727 21.3962 3.78662 19.9506 2.34103C18.505 0.895439 16.5444 0.083313 14.5 0.083313C12.4556 0.083313 10.495 0.895439 9.04939 2.34103C7.60379 3.78662 6.79167 5.74727 6.79167 7.79165C6.79167 9.83602 7.60379 11.7967 9.04939 13.2423C10.495 14.6879 12.4556 15.5 14.5 15.5ZM3.15179 20.8156C4.917 18.4754 7.57637 17.0416 10.9757 17.0416H18.0243C21.4236 17.0416 24.083 18.4754 25.8482 20.8156C27.5764 23.1081 28.375 26.1621 28.375 29.375C28.375 29.7839 28.2126 30.176 27.9235 30.4651C27.6343 30.7542 27.2422 30.9166 26.8333 30.9166C26.4245 30.9166 26.0323 30.7542 25.7432 30.4651C25.4541 30.176 25.2917 29.7839 25.2917 29.375C25.2917 26.6277 24.6041 24.2875 23.3877 22.6718C22.2083 21.1086 20.4616 20.125 18.0227 20.125H10.9773C8.53837 20.125 6.79167 21.1086 5.61229 22.6718C4.39438 24.2875 3.70833 26.6277 3.70833 29.375C3.70833 29.7839 3.54591 30.176 3.25679 30.4651C2.96767 30.7542 2.57554 30.9166 2.16667 30.9166C1.75779 30.9166 1.36566 30.7542 1.07654 30.4651C0.787425 30.176 0.625 29.7839 0.625 29.375C0.625 26.1621 1.42358 23.1081 3.15179 20.8156Z"
                                            fill="white" />
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M0.625 29.375C0.625 28.9661 0.787425 28.574 1.07654 28.2849C1.36566 27.9957 1.75779 27.8333 2.16667 27.8333H26.7748C27.1836 27.8333 27.5758 27.9957 27.8649 28.2849C28.154 28.574 28.3164 28.9661 28.3164 29.375C28.3164 29.7839 28.154 30.176 27.8649 30.4651C27.5758 30.7542 27.1836 30.9166 26.7748 30.9166H2.16667C1.75779 30.9166 1.36566 30.7542 1.07654 30.4651C0.787425 30.176 0.625 29.7839 0.625 29.375Z"
                                            fill="white" />
                                    </svg>
                                    Dashboard
                                </a>
                            <?php else: ?>
                                <a href="<?php echo home_url('/member-portal/'); ?>" class="login text text-white flex align-center w-max">
                                    <svg width="29" height="31" viewBox="0 0 29 31" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M14.5 12.4166C15.1074 12.4166 15.7088 12.297 16.2699 12.0646C16.831 11.8322 17.3409 11.4915 17.7704 11.062C18.1998 10.6325 18.5405 10.1227 18.7729 9.56156C19.0054 9.00043 19.125 8.39901 19.125 7.79165C19.125 7.18428 19.0054 6.58287 18.7729 6.02174C18.5405 5.4606 18.1998 4.95075 17.7704 4.52128C17.3409 4.09181 16.831 3.75113 16.2699 3.5187C15.7088 3.28628 15.1074 3.16665 14.5 3.16665C13.2734 3.16665 12.097 3.65392 11.2296 4.52128C10.3623 5.38863 9.875 6.56502 9.875 7.79165C9.875 9.01827 10.3623 10.1947 11.2296 11.062C12.097 11.9294 13.2734 12.4166 14.5 12.4166ZM14.5 15.5C16.5444 15.5 18.505 14.6879 19.9506 13.2423C21.3962 11.7967 22.2083 9.83602 22.2083 7.79165C22.2083 5.74727 21.3962 3.78662 19.9506 2.34103C18.505 0.895439 16.5444 0.083313 14.5 0.083313C12.4556 0.083313 10.495 0.895439 9.04939 2.34103C7.60379 3.78662 6.79167 5.74727 6.79167 7.79165C6.79167 9.83602 7.60379 11.7967 9.04939 13.2423C10.495 14.6879 12.4556 15.5 14.5 15.5ZM3.15179 20.8156C4.917 18.4754 7.57637 17.0416 10.9757 17.0416H18.0243C21.4236 17.0416 24.083 18.4754 25.8482 20.8156C27.5764 23.1081 28.375 26.1621 28.375 29.375C28.375 29.7839 28.2126 30.176 27.9235 30.4651C27.6343 30.7542 27.2422 30.9166 26.8333 30.9166C26.4245 30.9166 26.0323 30.7542 25.7432 30.4651C25.4541 30.176 25.2917 29.7839 25.2917 29.375C25.2917 26.6277 24.6041 24.2875 23.3877 22.6718C22.2083 21.1086 20.4616 20.125 18.0227 20.125H10.9773C8.53837 20.125 6.79167 21.1086 5.61229 22.6718C4.39438 24.2875 3.70833 26.6277 3.70833 29.375C3.70833 29.7839 3.54591 30.176 3.25679 30.4651C2.96767 30.7542 2.57554 30.9166 2.16667 30.9166C1.75779 30.9166 1.36566 30.7542 1.07654 30.4651C0.787425 30.176 0.625 29.7839 0.625 29.375C0.625 26.1621 1.42358 23.1081 3.15179 20.8156Z"
                                            fill="white" />
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M0.625 29.375C0.625 28.9661 0.787425 28.574 1.07654 28.2849C1.36566 27.9957 1.75779 27.8333 2.16667 27.8333H26.7748C27.1836 27.8333 27.5758 27.9957 27.8649 28.2849C28.154 28.574 28.3164 28.9661 28.3164 29.375C28.3164 29.7839 28.154 30.176 27.8649 30.4651C27.5758 30.7542 27.1836 30.9166 26.7748 30.9166H2.16667C1.75779 30.9166 1.36566 30.7542 1.07654 30.4651C0.787425 30.176 0.625 29.7839 0.625 29.375Z"
                                            fill="white" />
                                    </svg>
                                    Member Portal
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- Mobile login button only for non-logged in users -->
                            <div class="header__auth-buttons">
                                <a href="<?php echo home_url('/login/'); ?>" class="login">
                                    <svg width="16" height="16" viewBox="0 0 29 31" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M14.5 12.4166C15.1074 12.4166 15.7088 12.297 16.2699 12.0646C16.831 11.8322 17.3409 11.4915 17.7704 11.062C18.1998 10.6325 18.5405 10.1227 18.7729 9.56156C19.0054 9.00043 19.125 8.39901 19.125 7.79165C19.125 7.18428 19.0054 6.58287 18.7729 6.02174C18.5405 5.4606 18.1998 4.95075 17.7704 4.52128C17.3409 4.09181 16.831 3.75113 16.2699 3.5187C15.7088 3.28628 15.1074 3.16665 14.5 3.16665C13.2734 3.16665 12.097 3.65392 11.2296 4.52128C10.3623 5.38863 9.875 6.56502 9.875 7.79165C9.875 9.01827 10.3623 10.1947 11.2296 11.062C12.097 11.9294 13.2734 12.4166 14.5 12.4166ZM14.5 15.5C16.5444 15.5 18.505 14.6879 19.9506 13.2423C21.3962 11.7967 22.2083 9.83602 22.2083 7.79165C22.2083 5.74727 21.3962 3.78662 19.9506 2.34103C18.505 0.895439 16.5444 0.083313 14.5 0.083313C12.4556 0.083313 10.495 0.895439 9.04939 2.34103C7.60379 3.78662 6.79167 5.74727 6.79167 7.79165C6.79167 9.83602 7.60379 11.7967 9.04939 13.2423C10.495 14.6879 12.4556 15.5 14.5 15.5Z"
                                            fill="white" />
                                    </svg>
                                    Login
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
