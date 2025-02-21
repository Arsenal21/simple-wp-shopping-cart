<?php

//Handle the admin dashboard main menu
add_action('admin_menu', 'wpsc_handle_admin_menu' );

// Handle the options page display
function wpsc_handle_admin_menu() {

    include_once (WP_CART_PATH . 'includes/admin/wp_shopping_cart_menu_addons.php');
    
    $menu_icon_url = 'dashicons-cart';
    add_menu_page(__('Simple Cart', 'wordpress-simple-paypal-shopping-cart'), __('Simple Cart', 'wordpress-simple-paypal-shopping-cart'), WP_CART_MANAGEMENT_PERMISSION, WP_CART_MAIN_MENU_SLUG , 'wpsc_settings_interface', $menu_icon_url, 90);
    add_submenu_page(WP_CART_MAIN_MENU_SLUG, __('Settings', 'wordpress-simple-paypal-shopping-cart'),  __('Settings', 'wordpress-simple-paypal-shopping-cart') , WP_CART_MANAGEMENT_PERMISSION, WP_CART_MAIN_MENU_SLUG, 'wpsc_settings_interface' );
    add_submenu_page(WP_CART_MAIN_MENU_SLUG, __('Coupons', 'wordpress-simple-paypal-shopping-cart'),  __('Coupons', 'wordpress-simple-paypal-shopping-cart') , WP_CART_MANAGEMENT_PERMISSION, 'wspsc-discounts', 'wpsc_show_coupon_discount_settings_page' );
    add_submenu_page(WP_CART_MAIN_MENU_SLUG, __('Tools', 'wordpress-simple-paypal-shopping-cart'),  __('Tools', 'wordpress-simple-paypal-shopping-cart') , WP_CART_MANAGEMENT_PERMISSION, 'wspsc-tools', 'wpsc_show_tools_menu_page' );
    add_submenu_page(WP_CART_MAIN_MENU_SLUG, __('Add-ons', 'wordpress-simple-paypal-shopping-cart'),  __('Add-ons', 'wordpress-simple-paypal-shopping-cart') , WP_CART_MANAGEMENT_PERMISSION, 'wspsc-addons', 'wpsc_show_addons_menu_page' );
        
    //Can set the "show_in_menu" parameter in the cart orders registration to false then add the menu in here using the following code
    //add_submenu_page(WP_CART_MAIN_MENU_SLUG, __('Orders', 'wordpress-simple-paypal-shopping-cart'),  __('Orders', 'wordpress-simple-paypal-shopping-cart') , WP_CART_MANAGEMENT_PERMISSION, 'edit.php?post_type=wpsc_cart_orders');
    //add_submenu_page(WP_CART_MAIN_MENU_SLUG, __('Add Order', 'wordpress-simple-paypal-shopping-cart'),  __('Add Order', 'wordpress-simple-paypal-shopping-cart') , WP_CART_MANAGEMENT_PERMISSION, 'post-new.php?post_type=wpsc_cart_orders');
    
    //TODO - Remove this at a later version. The purpose of this is to still keep the old setting link that will get redirected to the new settings menu.
    add_options_page(__("WP Simple Shopping Cart", "wordpress-simple-paypal-shopping-cart"), __("WP Simple Shopping Cart", "wordpress-simple-paypal-shopping-cart"), WP_CART_MANAGEMENT_PERMISSION, 'wordpress-paypal-shopping-cart', 'wpsc_settings_interface' );
    
    $menu_parent_slug = WP_CART_MAIN_MENU_SLUG;
    do_action('wspsc_after_main_admin_menu', $menu_parent_slug);  // TODO: Old hook. Need to remove this.
    do_action('wpsc_after_main_admin_menu', $menu_parent_slug);
}

/*
 * Main settings menu (it links to all other settings menu tabs). 
 * Only admin user with "manage_options" permission can access this menu page.
 */

function wpsc_settings_interface() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this settings page.');
    }

    $wpspc_plugin_tabs = array(
        'wspsc-menu-main' => __('General Settings', 'wordpress-simple-paypal-shopping-cart'),
        'wspsc-menu-main&action=email-settings' => __('Email Settings', 'wordpress-simple-paypal-shopping-cart'),
        'wspsc-menu-main&action=shipping-settings' => __('Shipping Settings', 'wordpress-simple-paypal-shopping-cart'),
        'wspsc-menu-main&action=ppcp-settings' => __('PayPal PPCP (New API)', 'wordpress-simple-paypal-shopping-cart'),
        'wspsc-menu-main&action=adv-settings' => __('PayPal Smart Checkout', 'wordpress-simple-paypal-shopping-cart'),        
        'wspsc-menu-main&action=stripe-settings' => __('Stripe Settings', 'wordpress-simple-paypal-shopping-cart'),
        'wspsc-menu-main&action=manual-checkout' => __('Manual/Offline Checkout', 'wordpress-simple-paypal-shopping-cart'),
    );
    echo '<div class="wrap">';
    echo '<h1>' . (__("WP Simple Shopping Cart Settings", "wordpress-simple-paypal-shopping-cart")) . ' (v'.WP_CART_VERSION .')' . '</h1>';

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
            case 'shipping-settings':
                include_once (WP_CART_PATH . 'includes/admin/wp_shopping_cart_menu_shipping_settings.php');
                show_wp_cart_shipping_settings_page();
                break;
	        case 'adv-settings':
                include_once (WP_CART_PATH . 'includes/admin/wp_shopping_cart_menu_adv_settings.php');
                show_wp_cart_adv_settings_page();
                break;
            case 'ppcp-settings':
                include_once (WP_CART_PATH . 'includes/admin/wp_shopping_cart_menu_ppcp_settings.php');
                // show_wp_cart_adv_settings_page();
                new WPSC_PPCP_settings_page();
                break;
            case 'stripe-settings':
                include_once (WP_CART_PATH . 'includes/admin/wp_shopping_cart_menu_stripe_settings.php');
                show_wp_cart_stripe_settings_page();
                break;
            case 'manual-checkout':
                include_once (WP_CART_PATH . 'includes/admin/wp_shopping_cart_menu_manual_checkout.php');
                show_wp_cart_manual_checkout_settings_page();
                break;
        }
    } else {
        include_once (WP_CART_PATH . 'includes/admin/wp_shopping_cart_menu_general_settings.php');
        wpsc_show_general_settings_page();
    }
    echo '</div></div>';
    echo '</div>';
}


/*******************************************************************
 * Admin Notice to prompt users to switch to the new PayPal settings
 *******************************************************************/
function wpsc_dashboard_admin_notices(){
	//Smart Checkout
	$enable_smart_checkout = get_option( 'wpspc_enable_pp_smart_checkout' );

	if( $enable_smart_checkout ){
		//The site has the old smart PayPal checkout enabled. Prompt the user to switch to the new PayPal PPCP settings.
		?>
		<div class="notice notice-warning is-dismissible">
			<p><?php _e('You are using the old PayPal Smart Checkout option in the "Simple Shopping Cart" plugin. Please switch to the new PayPal PPCP option for better security and functionaliy.', 'wordpress-simple-paypal-shopping-cart'); ?></p>
			<p><a href="<?php echo admin_url('admin.php?page=wspsc-menu-main&action=ppcp-settings'); ?>"><?php _e('Switch to the new PayPal Commerce Platform API by configuring the API credentials', 'wordpress-simple-paypal-shopping-cart'); ?></a></p>
		</div>
		<?php
	}

}

if( is_admin() ) {
	// Add the admin notices hook
	add_filter('admin_notices', 'wpsc_dashboard_admin_notices');
}
