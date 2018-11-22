<?php

function wspsc_show_coupon_discount_settings_page()
{
    if(!current_user_can('manage_options')){
        wp_die('You do not have permission to access this settings page.');
    }
 
    echo '<div class="wrap">';
    echo '<h1>' . (__("Simple Cart Coupons/Discounts", "wordpress-simple-paypal-shopping-cart")) . '</h1>';
    
    echo '<div id="poststuff"><div id="post-body">';
    
    if (isset($_POST['wpspsc_coupon_settings']))
    {
        $nonce = $_REQUEST['_wpnonce'];
        if ( !wp_verify_nonce($nonce, 'wpspsc_coupon_settings')){
                wp_die('Error! Nonce Security Check Failed! Go back to Coupon/Discount menu and save the settings again.');
        }
        update_option('wpspsc_enable_coupon', (isset($_POST['wpspsc_enable_coupon']) && $_POST['wpspsc_enable_coupon']=='1') ? '1':'');
        echo '<div id="message" class="updated fade"><p><strong>';
        echo 'Coupon Settings Updated!';
        echo '</strong></p></div>';
    }
    if (isset($_POST['wpspsc_save_coupon']))
    {
        $nonce = $_REQUEST['_wpnonce'];
        if ( !wp_verify_nonce($nonce, 'wpspsc_save_coupon')){
                wp_die('Error! Nonce Security Check Failed! Go back to email settings menu and save the settings again.');
        }
        
        $collection_obj = WPSPSC_Coupons_Collection::get_instance();
        $coupon_code = trim(stripslashes(sanitize_text_field($_POST["wpspsc_coupon_code"])));
        $discount_rate = trim(sanitize_text_field($_POST["wpspsc_coupon_rate"]));
        $expiry_date = trim(sanitize_text_field($_POST["wpspsc_coupon_expiry_date"]));
        $coupon_item = new WPSPSC_COUPON_ITEM($coupon_code, $discount_rate, $expiry_date);
        $collection_obj->add_coupon_item($coupon_item);
        WPSPSC_Coupons_Collection::save_object($collection_obj);
        
        echo '<div id="message" class="updated fade"><p><strong>';
        echo 'Coupon Saved!';
        echo '</strong></p></div>';
    }
    
    if(isset($_REQUEST['wpspsc_delete_coupon_id']))
    {
        $coupon_id = $_REQUEST['wpspsc_delete_coupon_id'];
        $collection_obj = WPSPSC_Coupons_Collection::get_instance();
        $collection_obj->delete_coupon_item_by_id($coupon_id);
        echo '<div id="message" class="updated fade"><p>';
        echo 'Coupon successfully deleted!';
        echo '</p></div>';
    }
    $wpspsc_enable_coupon = '';
    if (get_option('wpspsc_enable_coupon') == '1'){
        $wpspsc_enable_coupon = 'checked="checked"';
    }
    ?>
    
    <div class="wspsc_yellow_box">	
    <p><?php _e("For more information, updates, detailed documentation and video tutorial, please visit:", "wordpress-simple-paypal-shopping-cart"); ?><br />
    <a href="https://www.tipsandtricks-hq.com/wordpress-simple-paypal-shopping-cart-plugin-768" target="_blank"><?php _e("WP Simple Cart Homepage", "wordpress-simple-paypal-shopping-cart"); ?></a></p>
    </div>
    
    <form method="post" action="">
    <?php wp_nonce_field('wpspsc_coupon_settings'); ?>
    <input type="hidden" name="coupon_settings_update" id="coupon_settings_update" value="true" />

    <div class="postbox">
    <h3 class="hndle"><label for="title"><?php _e("Coupon/Discount Settings", "wordpress-simple-paypal-shopping-cart");?></label></h3>
    <div class="inside">

    <form method="post" action="">
    <table class="form-table" width="100%" border="0" cellspacing="0" cellpadding="6">
    
    <tr valign="top">
    <th scope="row"><?php _e("Enable Discount Coupon Feature", "wordpress-simple-paypal-shopping-cart");?></th>
    <td>
    <input type="checkbox" name="wpspsc_enable_coupon" value="1" <?php echo $wpspsc_enable_coupon; ?> />
    <span class="description"><?php _e("When checked your customers will be able to enter a coupon code in the shopping cart before checkout.", "wordpress-simple-paypal-shopping-cart");?></span>
    </td>
    </tr>
    
    <tr valign="top">
    <th scope="row">
    <div class="submit">
        <input type="submit" name="wpspsc_coupon_settings" class="button-primary" value="<?php echo (__("Update &raquo;", "wordpress-simple-paypal-shopping-cart")) ?>" />
    </div>
    </th>
    <td></td>
    </tr>

    </table>

    </form>
    </div></div>

    <form method="post" action="">
    <?php wp_nonce_field('wpspsc_save_coupon'); ?>
    <input type="hidden" name="info_update" id="info_update" value="true" />

    <div class="postbox">
    <h3 class="hndle"><label for="title"><?php _e("Add Coupon/Discount", "wordpress-simple-paypal-shopping-cart");?></label></h3>
    <div class="inside">

    <form method="post" action="">
    <table class="form-table" border="0" cellspacing="0" cellpadding="6" style="max-width:650px;">

    <tr valign="top">

    <td width="25%" align="left">
    <?php _e("Coupon Code", 'wordpress-simple-paypal-shopping-cart' ); ?><br />
    <input name="wpspsc_coupon_code" type="text" size="15" value=""/>   
    </td>

    <td width="25%" align="left">
    <?php _e("Discount Rate (%)", 'wordpress-simple-paypal-shopping-cart' ); ?><br />
    <input name="wpspsc_coupon_rate" type="text" size="7" value=""/>            
    </td>
    
    <td width="25%" align="left">
    <?php _e("Expiry Date", 'wordpress-simple-paypal-shopping-cart' ); ?><br />
    <input name="wpspsc_coupon_expiry_date" class="wpspsc_coupon_expiry" type="text" size="15" value=""/>            
    </td>

    <td width="25%" align="left">
    <div class="submit">
        <input type="submit" name="wpspsc_save_coupon" class="button-primary" value="<?php echo (__("Save Coupon &raquo;", "wordpress-simple-paypal-shopping-cart")) ?>" />
    </div>                
    </td> 

    </tr>

    </table>

    </form>
    </div></div>
    
    <?php
    
    //display table
    $output = "";
    $output .= '
    <table class="widefat" style="max-width:800px;">
    <thead><tr>
    <th scope="col">'.(__("Coupon Code", "wordpress-simple-paypal-shopping-cart")).'</th>
    <th scope="col">'.(__("Discount Rate (%)", "wordpress-simple-paypal-shopping-cart")).'</th>
    <th scope="col">'.(__("Expiry Date", "wordpress-simple-paypal-shopping-cart")).'</th>    
    <th scope="col"></th>
    </tr></thead>
    <tbody>';

    $collection_obj = WPSPSC_Coupons_Collection::get_instance();
    if($collection_obj)
    {
        $coupons = $collection_obj->coupon_items; 
        $number_of_coupons = count($coupons);
        if($number_of_coupons > 0)
        {
            $row_count = 0;
            foreach ($coupons as $coupon)
            {
                $output .= '<tr>';
                $output .= '<td><strong>'.$coupon->coupon_code.'</strong></td>';
                $output .= '<td><strong>'.$coupon->discount_rate.'</strong></td>';
                if(empty($coupon->expiry_date)){
                    $output .= '<td><strong>'.__('No Expiry','wordpress-simple-paypal-shopping-cart').'</strong></td>';
                }else{
                    $output .= '<td><strong>'.$coupon->expiry_date.'</strong></td>';
                }
                $output .= '<td>';
                $output .= "<form method=\"post\" action=\"\" onSubmit=\"return confirm('Are you sure you want to delete this entry?');\">";				
                $output .= "<input type=\"hidden\" name=\"wpspsc_delete_coupon_id\" value=".$coupon->id." />";
                $output .= '<input style="border: none; color: red; background-color: transparent; padding: 0; cursor:pointer;" type="submit" name="Delete" value="Delete">';
                $output .= "</form>";
                $output .= '</td>';
                $output .= '</tr>';
                $row_count = $row_count + 1;
            }
        }
        else
        {
            $output .= '<tr><td colspan="5">'.(__("No Coupons Configured.", "wordpress-simple-paypal-shopping-cart")).'</td></tr>';
        }
    }
    else
    {
        $output .= '<tr><td colspan="5">'.(__("No Record found", "wordpress-simple-paypal-shopping-cart")).'</td></tr>';
    }

    $output .= '</tbody>
    </table>';

    echo $output;
    wpspsc_settings_menu_footer();
    
    echo '</div></div>';//End of poststuff and post-body
    echo '</div>';//End of wrap
    
}

