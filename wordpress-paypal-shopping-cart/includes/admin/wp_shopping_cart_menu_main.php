<?php

//Handle the admin dashboard main menu
add_action('admin_menu', 'wp_cart_options_page');

// Handle the options page display
function wp_cart_options_page() {
    include_once(WP_CART_PATH . 'wp_shopping_cart_settings.php');
    add_options_page(__("WP Paypal Shopping Cart", "wordpress-simple-paypal-shopping-cart"), __("WP Shopping Cart", "wordpress-simple-paypal-shopping-cart"), WP_CART_MANAGEMENT_PERMISSION, 'wordpress-paypal-shopping-cart', 'wp_cart_options');

    //Main menu - Complete this when the dashboard menu is ready
    //$menu_icon_url = '';//TODO - use 
    //add_menu_page(__('Simple Cart', 'wordpress-simple-paypal-shopping-cart'), __('Simple Cart', 'wordpress-simple-paypal-shopping-cart'), WP_CART_MANAGEMENT_PERMISSION, WP_CART_MAIN_MENU_SLUG , 'wp_cart_options', $menu_icon_url);
    //add_submenu_page(WP_CART_MAIN_MENU_SLUG, __('Settings', 'wordpress-simple-paypal-shopping-cart'),  __('Settings', 'wordpress-simple-paypal-shopping-cart') , WP_CART_MANAGEMENT_PERMISSION, WP_CART_MAIN_MENU_SLUG, 'wp_cart_options');
    //add_submenu_page(WP_CART_MAIN_MENU_SLUG, __('Bla', 'wordpress-simple-paypal-shopping-cart'),  __('Bla', 'wordpress-simple-paypal-shopping-cart') , WP_CART_MANAGEMENT_PERMISSION, 'wspsc-bla', 'wp_cart_options');
}
