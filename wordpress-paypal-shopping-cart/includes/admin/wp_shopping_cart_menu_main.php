<?php

//Handle the admin dashboard main menu
add_action('admin_menu', 'wspsc_handle_admin_menu');

// Handle the options page display
function wspsc_handle_admin_menu() {

    include_once (WP_CART_PATH . 'includes/admin/wp_shopping_cart_menu_discounts.php');
    include_once (WP_CART_PATH . 'includes/admin/wp_shopping_cart_menu_tools.php');
    include_once (WP_CART_PATH . 'includes/admin/wp_shopping_cart_menu_addons.php');
    
    $menu_icon_url = 'dashicons-cart';
    add_menu_page(__('Simple Cart', 'wordpress-simple-paypal-shopping-cart'), __('Simple Cart', 'wordpress-simple-paypal-shopping-cart'), WP_CART_MANAGEMENT_PERMISSION, WP_CART_MAIN_MENU_SLUG , 'wspsc_settings_interface', $menu_icon_url, 90);
    add_submenu_page(WP_CART_MAIN_MENU_SLUG, __('Settings', 'wordpress-simple-paypal-shopping-cart'),  __('Settings', 'wordpress-simple-paypal-shopping-cart') , WP_CART_MANAGEMENT_PERMISSION, WP_CART_MAIN_MENU_SLUG, 'wspsc_settings_interface');
    add_submenu_page(WP_CART_MAIN_MENU_SLUG, __('Coupons', 'wordpress-simple-paypal-shopping-cart'),  __('Coupons', 'wordpress-simple-paypal-shopping-cart') , WP_CART_MANAGEMENT_PERMISSION, 'wspsc-discounts', 'wspsc_show_coupon_discount_settings_page');
    add_submenu_page(WP_CART_MAIN_MENU_SLUG, __('Tools', 'wordpress-simple-paypal-shopping-cart'),  __('Tools', 'wordpress-simple-paypal-shopping-cart') , WP_CART_MANAGEMENT_PERMISSION, 'wspsc-tools', 'wspsc_show_tools_menu_page');
    add_submenu_page(WP_CART_MAIN_MENU_SLUG, __('Add-ons', 'wordpress-simple-paypal-shopping-cart'),  __('Add-ons', 'wordpress-simple-paypal-shopping-cart') , WP_CART_MANAGEMENT_PERMISSION, 'wspsc-addons', 'wspsc_show_addons_menu_page');
        
    //Can set the "show_in_menu" parameter in the cart orders registration to false then add the menu in here using the following code
    //add_submenu_page(WP_CART_MAIN_MENU_SLUG, __('Orders', 'wordpress-simple-paypal-shopping-cart'),  __('Orders', 'wordpress-simple-paypal-shopping-cart') , WP_CART_MANAGEMENT_PERMISSION, 'edit.php?post_type=wpsc_cart_orders');
    //add_submenu_page(WP_CART_MAIN_MENU_SLUG, __('Add Order', 'wordpress-simple-paypal-shopping-cart'),  __('Add Order', 'wordpress-simple-paypal-shopping-cart') , WP_CART_MANAGEMENT_PERMISSION, 'post-new.php?post_type=wpsc_cart_orders');
    
    //TODO - Remove this at a later version. The purpose of this is to still keep the old setting link that will get redirected to the new settings menu.
    add_options_page(__("WP Paypal Shopping Cart", "wordpress-simple-paypal-shopping-cart"), __("WP Shopping Cart", "wordpress-simple-paypal-shopping-cart"), WP_CART_MANAGEMENT_PERMISSION, 'wordpress-paypal-shopping-cart', 'wspsc_settings_interface');
    
    $menu_parent_slug = WP_CART_MAIN_MENU_SLUG;
    do_action('wspsc_after_main_admin_menu', $menu_parent_slug);
}

/*
 * Main settings menu (it links to all other settings menu tabs). 
 * Only admin user with "manage_options" permission can access this menu page.
 */

function wspsc_settings_interface() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this settings page.');
    }

    $wpspc_plugin_tabs = array(
        'wspsc-menu-main' => __('General Settings', 'wordpress-simple-paypal-shopping-cart'),
        'wspsc-menu-main&action=email-settings' => __('Email Settings', 'wordpress-simple-paypal-shopping-cart'),
        'wspsc-menu-main&action=adv-settings' => __('Advanced Settings', 'wordpress-simple-paypal-shopping-cart'),
    );
    echo '<div class="wrap">';
    echo '<h1>' . (__("WP Paypal Shopping Cart Options", "wordpress-simple-paypal-shopping-cart")) . ' v'.WP_CART_VERSION . '</h1>';

    $current = "";
    if (isset($_GET['page'])) {
        $current = sanitize_text_field($_GET['page']);
        if (isset($_GET['action'])) {
            $current .= "&action=" . sanitize_text_field($_GET['action']);
        }
    }
    $content = '';
    $content .= '<h2 class="nav-tab-wrapper">';
    foreach ($wpspc_plugin_tabs as $location => $tabname) {
        if ($current == $location) {
            $class = ' nav-tab-active';
        } else {
            $class = '';
        }
        $content .= '<a class="nav-tab' . $class . '" href="?page=' . $location . '">' . $tabname . '</a>';
    }
    $content .= '</h2>';
    echo $content;
    echo '<div id="poststuff"><div id="post-body">';
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'email-settings':
                include_once (WP_CART_PATH . 'includes/admin/wp_shopping_cart_menu_email_settings.php');
                show_wp_cart_email_settings_page();
                break;
	    case 'adv-settings':
                include_once (WP_CART_PATH . 'includes/admin/wp_shopping_cart_menu_adv_settings.php');
                show_wp_cart_adv_settings_page();
                break;
        }
    } else {
        include_once (WP_CART_PATH . 'includes/admin/wp_shopping_cart_menu_general_settings.php');
        wspsc_show_general_settings_page();
    }
    echo '</div></div>';
    echo '</div>';
}

