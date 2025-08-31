<?php
/**
 * Navigation Manager for IIPM Portal
 * Handles role-based navigation and breadcrumbs
 */

class IIPM_Navigation_Manager {
    
    /**
     * Get navigation menu based on user role
     */
    public static function get_role_based_navigation() {
        if (!is_user_logged_in()) {
            return array();
        }
        
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        $navigation = array();
        
        // Site Administrator Navigation - NO Member Portal
        if (in_array('administrator', $user_roles)) {
            $navigation = array(
                'Dashboard' => home_url('/dashboard/'),
                'Admin Management' => home_url('/super-admin/'),
                'User Management' => home_url('/user-management/'),
                'Organisation Management' => home_url('/organisation-management/'),
                
                'Bulk Import' => home_url('/bulk-import/'),
                'CPD Reports' => home_url('/cpd-reports/'),
                'Course Import' => home_url('/course-import/'),
                'CPD Administration' => home_url('/cpd-admin/'),
                'Course Management' => home_url('/cpd-courses/'),
                'Leave Administration' => home_url('/leave-admin/'),
                'File Dashboard' => home_url('/file-dashboard/'),
                'Logout' => wp_logout_url(home_url())
            );
        }
        // Organization Administrator Navigation - NO Member Portal
        elseif (in_array('iipm_corporate_admin', $user_roles)) {
            $navigation = array(
                'Dashboard' => home_url('/dashboard/'),
                'Employee Management' => home_url('/user-management/'),
                
                'Bulk Import' => home_url('/bulk-import/'),
                'CPD Reports' => home_url('/cpd-reports/'),
                'Leave Management' => home_url('/leave-admin/'),
                'My Leave Requests' => home_url('/leave-request/'),
                'My Profile' => home_url('/profile/'),
                'File Dashboard' => home_url('/file-dashboard/'),
                'Logout' => wp_logout_url(home_url())
            );
        }
        // Council Member Navigation - HAS Member Portal
        elseif (in_array('iipm_council_member', $user_roles)) {
            $navigation = array(
                'Member Portal' => home_url('/member-portal/'),
                'CPD Record' => home_url('/cpd-record/'),
                'CPD Reports' => home_url('/cpd-reports/'),
                'Leave Requests' => home_url('/leave-request/'),
                'My Profile' => home_url('/profile/'),
                'File Dashboard' => home_url('/file-dashboard/'),
                'Logout' => wp_logout_url(home_url())
            );
        }
        // Regular Member Navigation - HAS Member Portal
        else {
            $navigation = array(
                'Member Portal' => home_url('/member-portal/'),
                'CPD Record' => home_url('/cpd-record/'),
                'Leave Requests' => home_url('/leave-request/'),
                'My Profile' => home_url('/profile/'),
                'File Dashboard' => home_url('/file-dashboard/'),
                'Logout' => wp_logout_url(home_url())
            );
        }
        
        return $navigation;
    }
    
    /**
     * Generate breadcrumbs for current page
     */
    public static function get_breadcrumbs() {
        global $post;
        
        $breadcrumbs = array();
        $breadcrumbs[] = array(
            'title' => 'Home',
            'url' => home_url('/')
        );
        
        // If user is logged in, add appropriate portal link
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $user_roles = $current_user->roles;
            
            if (in_array('administrator', $user_roles) || in_array('iipm_corporate_admin', $user_roles)) {
                $breadcrumbs[] = array(
                    'title' => 'Dashboard',
                    'url' => home_url('/dashboard/')
                );
            } else {
                $breadcrumbs[] = array(
                    'title' => 'Member Portal',
                    'url' => home_url('/member-portal/')
                );
            }
        }
        
        // Get current page info
        if (is_page()) {
            $page_template = get_page_template_slug($post->ID);
            $page_title = get_the_title();
            
            // Add specific breadcrumb logic based on page
            $current_url = $_SERVER['REQUEST_URI'];
            
            // CPD-related pages
            if (strpos($current_url, 'cpd') !== false) {
                if (!self::breadcrumb_exists($breadcrumbs, 'CPD Management')) {
                    $breadcrumbs[] = array(
                        'title' => 'CPD Management',
                        'url' => home_url('/cpd-reports/')
                    );
                }
            }
            
            // User management pages
            if (strpos($current_url, 'user-management') !== false || 
                strpos($current_url, 'admin-invitations') !== false || 
                strpos($current_url, 'bulk-import') !== false ||
                strpos($current_url, 'organisation-management') !== false) {
                if (!self::breadcrumb_exists($breadcrumbs, 'User Management')) {
                    $breadcrumbs[] = array(
                        'title' => 'User Management',
                        'url' => home_url('/user-management/')
                    );
                }
            }
            
            // Leave management pages
            if (strpos($current_url, 'leave') !== false) {
                if (!self::breadcrumb_exists($breadcrumbs, 'Leave Management')) {
                    $breadcrumbs[] = array(
                        'title' => 'Leave Management',
                        'url' => home_url('/leave-request/')
                    );
                }
            }
            
            // Add parent pages if they exist
            if ($post->post_parent) {
                $parent_id = $post->post_parent;
                $parents = array();
                while ($parent_id) {
                    $parent = get_post($parent_id);
                    $parents[] = array(
                        'title' => $parent->post_title,
                        'url' => get_permalink($parent->ID)
                    );
                    $parent_id = $parent->post_parent;
                }
                $parents = array_reverse($parents);
                $breadcrumbs = array_merge($breadcrumbs, $parents);
            }
            
            // Add current page
            $breadcrumbs[] = array(
                'title' => $page_title,
                'url' => get_permalink($post->ID),
                'current' => true
            );
        }
        
        return $breadcrumbs;
    }
    
    /**
     * Check if breadcrumb already exists
     */
    private static function breadcrumb_exists($breadcrumbs, $title) {
        foreach ($breadcrumbs as $crumb) {
            if ($crumb['title'] === $title) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Generate page hierarchy suggestions
     */
    public static function get_page_hierarchy() {
        return array(
            'admin' => array(
                'Dashboard' => array(
                    'User Management' => array(
                        'All Users',
                        'Bulk Import',
                        'Admin Invitations',
                        'Organization Management'
                    ),
                    'CPD Management' => array(
                        'CPD Reports',
                        'Course Import',
                        'CPD Administration',
                        'Course Management'
                    ),
                    'Leave Management' => array(
                        'Leave Administration',
                        'My Leave Requests'
                    )
                )
            ),
            'corporate_admin' => array(
                'Dashboard' => array(
                    'Employee Management' => array(
                        'All Employees',
                        'Bulk Import',
                        'Employee Invitations'
                    ),
                    'CPD Oversight' => array(
                        'Employee CPD Reports',
                        'Compliance Monitoring'
                    ),
                    'Leave Management' => array(
                        'Leave Administration',
                        'My Leave Requests'
                    )
                )
            ),
            'member' => array(
                'Member Portal' => array(
                    'CPD Portal' => array(
                        'Browse Courses',
                        'My Learning',
                        'Progress Tracking',
                        'Certificates'
                    ),
                    'Leave Management' => array(
                        'My Leave Requests',
                        'Submit New Request'
                    ),
                    'Profile' => array(
                        'Personal Information',
                        'Professional Details',
                        'Account Settings'
                    )
                )
            )
        );
    }
} 