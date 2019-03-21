<?php

function wspsc_show_addons_menu_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this settings page.');
    }

    echo '<div class="wrap">';
    echo '<h1>' . (__("Simple Cart Add-ons", "wordpress-simple-paypal-shopping-cart")) . '</h1>';
    
    echo '<div id="poststuff"><div id="post-body">';
    ?>

    <div class="wspsc_yellow_box">
        <p><?php _e("For more information, updates, detailed documentation and video tutorial, please visit:", "wordpress-simple-paypal-shopping-cart"); ?><br />
            <a href="https://www.tipsandtricks-hq.com/wordpress-simple-paypal-shopping-cart-plugin-768" target="_blank"><?php _e("WP Simple Cart Homepage", "wordpress-simple-paypal-shopping-cart"); ?></a></p>
    </div>

    <?php
    $addons_data = array();

    $addon_1 = array(
        "name"		 => __( "Collect Customer Input", 'wordpress-simple-paypal-shopping-cart' ),
        "thumbnail"	 => WP_CART_URL . "/includes/admin/images/wspsc-customer-input.png",
        "description"	 => __( "This addon allows you to collect customer input in the shopping cart at the time of checkout.", 'wordpress-simple-paypal-shopping-cart' ),
        "page_url"	 => "https://www.tipsandtricks-hq.com/ecommerce/wp-simple-cart-collect-customer-input-in-the-shopping-cart-4396",
    );
    array_push( $addons_data, $addon_1 );

    $addon_2 = array(
        "name"		 => __( "Mailchimp Integration", 'wordpress-simple-paypal-shopping-cart' ),
        "thumbnail"	 => WP_CART_URL . "/includes/admin/images/wspsc-mailchimp-integration.png",
        "description"	 => __( "This addon allows you to add users to your Mailchimp list after they purchase an item.", 'wordpress-simple-paypal-shopping-cart' ),
        "page_url"	 => "https://www.tipsandtricks-hq.com/ecommerce/wp-shopping-cart-and-mailchimp-integration-3442",
    );
    array_push( $addons_data, $addon_2 );
    
    $addon_3 = array(
        "name"		 => __( "WP Affiliate Plugin", 'wordpress-simple-paypal-shopping-cart' ),
        "thumbnail"	 => WP_CART_URL . "/includes/admin/images/wp-affiliate-plugin-integration.png",
        "description"	 => __( "This plugin allows you to award commission to affiliates for referring customers to your site.", 'wordpress-simple-paypal-shopping-cart' ),
        "page_url"	 => "https://www.tipsandtricks-hq.com/wordpress-affiliate-platform-plugin-simple-affiliate-program-for-wordpress-blogsite-1474",
    );
    array_push( $addons_data, $addon_3 );
    
    /* Show the addons list */
    foreach ( $addons_data as $addon ) {
        $output .= '<div class="wspsc_addon_item_canvas">';

        $output .= '<div class="wspsc_addon_item_thumb">';

        $img_src = $addon[ 'thumbnail' ];
        $output	 .= '<img src="' . $img_src . '" alt="' . $addon[ 'name' ] . '">';
        $output	 .= '</div>'; //end thumbnail

        $output	 .= '<div class="wspsc_addon_item_body">';
        $output	 .= '<div class="wspsc_addon_item_name">';
        $output	 .= '<a href="' . $addon[ 'page_url' ] . '" target="_blank">' . $addon[ 'name' ] . '</a>';
        $output	 .= '</div>'; //end name

        $output	 .= '<div class="wspsc_addon_item_description">';
        $output	 .= $addon[ 'description' ];
        $output	 .= '</div>'; //end description

        $output	 .= '<div class="wspsc_addon_item_details_link">';
        $output	 .= '<a href="' . $addon[ 'page_url' ] . '" class="wspsc_addon_view_details" target="_blank">' . __( 'View Details', 'wordpress-simple-paypal-shopping-cart' ) . '</a>';

        $output	 .= '</div>'; //end detils link
        $output	 .= '</div>'; //end body

        $output .= '</div>'; //end canvas
    }

    echo $output;    
                
    echo '</div></div>';//End of poststuff and post-body
    echo '</div>';//End of wrap
    
}