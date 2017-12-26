<?php

/*
 * General settings menu page
 */
function wspsc_show_general_settings_page () 
{
    if(!current_user_can('manage_options')){
        wp_die('You do not have permission to access this settings page.');
    }
    
    if(isset($_POST['wspsc_reset_logfile'])) {
        // Reset the debug log file
        if(wspsc_reset_logfile()){
            echo '<div id="message" class="updated fade"><p><strong>Debug log file has been reset!</strong></p></div>';
        }
        else{
            echo '<div id="message" class="updated fade"><p><strong>Debug log file could not be reset!</strong></p></div>';
        }
    }
    if (isset($_POST['info_update']))
    {
    	$nonce = $_REQUEST['_wpnonce'];
        if ( !wp_verify_nonce($nonce, 'wp_simple_cart_settings_update')){
                wp_die('Error! Nonce Security Check Failed! Go back to settings menu and save the settings again.');
        }

        $currency_code = sanitize_text_field($_POST["cart_payment_currency"]);
        $currency_code = trim(strtoupper($currency_code));//Currency code must be uppercase.
        update_option('cart_payment_currency', $currency_code);
        update_option('cart_currency_symbol', sanitize_text_field($_POST["cart_currency_symbol"]));
        update_option('cart_base_shipping_cost', sanitize_text_field($_POST["cart_base_shipping_cost"]));
        update_option('cart_free_shipping_threshold', sanitize_text_field($_POST["cart_free_shipping_threshold"]));   
        update_option('wp_shopping_cart_collect_address', (isset($_POST['wp_shopping_cart_collect_address']) && $_POST['wp_shopping_cart_collect_address']!='') ? 'checked="checked"':'' );    
        update_option('wp_shopping_cart_use_profile_shipping', (isset($_POST['wp_shopping_cart_use_profile_shipping']) && $_POST['wp_shopping_cart_use_profile_shipping']!='') ? 'checked="checked"':'' );
                
        update_option('cart_paypal_email', sanitize_email($_POST["cart_paypal_email"]));
        update_option('addToCartButtonName', sanitize_text_field($_POST["addToCartButtonName"]));
        update_option('wp_cart_title', sanitize_text_field($_POST["wp_cart_title"]));
        update_option('wp_cart_empty_text', sanitize_text_field($_POST["wp_cart_empty_text"]));
        update_option('cart_return_from_paypal_url', sanitize_text_field($_POST["cart_return_from_paypal_url"]));
        update_option('cart_cancel_from_paypal_url', sanitize_text_field($_POST["cart_cancel_from_paypal_url"]));
        update_option('cart_products_page_url', sanitize_text_field($_POST["cart_products_page_url"]));

        update_option('wp_shopping_cart_auto_redirect_to_checkout_page', (isset($_POST['wp_shopping_cart_auto_redirect_to_checkout_page']) && $_POST['wp_shopping_cart_auto_redirect_to_checkout_page']!='') ? 'checked="checked"':'' );
        update_option('cart_checkout_page_url', sanitize_text_field($_POST["cart_checkout_page_url"]));
        update_option('wspsc_open_pp_checkout_in_new_tab', (isset($_POST['wspsc_open_pp_checkout_in_new_tab']) && $_POST['wspsc_open_pp_checkout_in_new_tab']!='') ? 'checked="checked"':'' );
        update_option('wp_shopping_cart_reset_after_redirection_to_return_page', (isset($_POST['wp_shopping_cart_reset_after_redirection_to_return_page']) && $_POST['wp_shopping_cart_reset_after_redirection_to_return_page']!='') ? 'checked="checked"':'' );        

        update_option('wp_shopping_cart_image_hide', (isset($_POST['wp_shopping_cart_image_hide']) && $_POST['wp_shopping_cart_image_hide']!='') ? 'checked="checked"':'' );
        update_option('wp_cart_paypal_co_page_style', sanitize_text_field($_POST["wp_cart_paypal_co_page_style"]));
        update_option('wp_shopping_cart_strict_email_check', (isset($_POST['wp_shopping_cart_strict_email_check']) && $_POST['wp_shopping_cart_strict_email_check']!='') ? 'checked="checked"':'' );
        update_option('wspsc_disable_nonce_add_cart', (isset($_POST['wspsc_disable_nonce_add_cart']) && $_POST['wspsc_disable_nonce_add_cart']!='') ? 'checked="checked"':'' );
        update_option('wp_use_aff_platform', (isset($_POST['wp_use_aff_platform']) && $_POST['wp_use_aff_platform']!='') ? 'checked="checked"':'' );
        
        update_option('wp_shopping_cart_enable_sandbox', (isset($_POST['wp_shopping_cart_enable_sandbox']) && $_POST['wp_shopping_cart_enable_sandbox']!='') ? 'checked="checked"':'' );
        update_option('wp_shopping_cart_enable_debug', (isset($_POST['wp_shopping_cart_enable_debug']) && $_POST['wp_shopping_cart_enable_debug']!='') ? 'checked="checked"':'' );
        
        echo '<div id="message" class="updated fade">';
        echo '<p><strong>'.(__("Options Updated!", "wordpress-simple-paypal-shopping-cart")).'</strong></p></div>';
    }	
	
    $defaultCurrency = get_option('cart_payment_currency');    
    if (empty($defaultCurrency)) $defaultCurrency = __("USD", "wordpress-simple-paypal-shopping-cart");
    
    $defaultSymbol = get_option('cart_currency_symbol');
    if (empty($defaultSymbol)) $defaultSymbol = __("$", "wordpress-simple-paypal-shopping-cart");

    $baseShipping = get_option('cart_base_shipping_cost');
    if (empty($baseShipping)) $baseShipping = 0;
    
    $cart_free_shipping_threshold = get_option('cart_free_shipping_threshold');

    $defaultEmail = get_option('cart_paypal_email');
    if (empty($defaultEmail)) $defaultEmail = get_bloginfo('admin_email');
    
    $return_url =  get_option('cart_return_from_paypal_url');
    $cancel_url = get_option('cart_cancel_from_paypal_url');
    $addcart = get_option('addToCartButtonName');
    if (empty($addcart)) $addcart = __("Add to Cart", "wordpress-simple-paypal-shopping-cart");           

    $title = get_option('wp_cart_title');
    $emptyCartText = get_option('wp_cart_empty_text');
    $cart_products_page_url = get_option('cart_products_page_url');	  
    $cart_checkout_page_url = get_option('cart_checkout_page_url');
    
    if (get_option('wp_shopping_cart_auto_redirect_to_checkout_page'))
        $wp_shopping_cart_auto_redirect_to_checkout_page = 'checked="checked"';
    else
        $wp_shopping_cart_auto_redirect_to_checkout_page = '';	
        
    if (get_option('wspsc_open_pp_checkout_in_new_tab'))
        $wspsc_open_pp_checkout_in_new_tab = 'checked="checked"';
    else
        $wspsc_open_pp_checkout_in_new_tab = '';
    
    if (get_option('wp_shopping_cart_reset_after_redirection_to_return_page'))
        $wp_shopping_cart_reset_after_redirection_to_return_page = 'checked="checked"';
    else
        $wp_shopping_cart_reset_after_redirection_to_return_page = '';	
                	    
    if (get_option('wp_shopping_cart_collect_address'))
        $wp_shopping_cart_collect_address = 'checked="checked"';
    else
        $wp_shopping_cart_collect_address = '';
        
    if (get_option('wp_shopping_cart_use_profile_shipping')){
        $wp_shopping_cart_use_profile_shipping = 'checked="checked"';
    }
    else {
        $wp_shopping_cart_use_profile_shipping = '';
    }
                	
    if (get_option('wp_shopping_cart_image_hide')){
        $wp_cart_image_hide = 'checked="checked"';
    }
    else{
        $wp_cart_image_hide = '';
    }

    $wp_cart_paypal_co_page_style = get_option('wp_cart_paypal_co_page_style');

    $wp_shopping_cart_strict_email_check = '';
    if (get_option('wp_shopping_cart_strict_email_check')){
        $wp_shopping_cart_strict_email_check = 'checked="checked"';
    }
    
    $wspsc_disable_nonce_add_cart = '';
    if (get_option('wspsc_disable_nonce_add_cart')){
        $wspsc_disable_nonce_add_cart = 'checked="checked"';
    }
    
    if (get_option('wp_use_aff_platform')){
        $wp_use_aff_platform = 'checked="checked"';
    }
    else{
        $wp_use_aff_platform = '';
    }
                              
	//$wp_shopping_cart_enable_sandbox = get_option('wp_shopping_cart_enable_sandbox');
    if (get_option('wp_shopping_cart_enable_sandbox'))
        $wp_shopping_cart_enable_sandbox = 'checked="checked"';
    else
        $wp_shopping_cart_enable_sandbox = '';	
    
    $wp_shopping_cart_enable_debug = '';
    if (get_option('wp_shopping_cart_enable_debug')){
        $wp_shopping_cart_enable_debug = 'checked="checked"';
    }    
    ?> 	
 	
    <div class="wspsc_yellow_box">
    <p><?php _e("For more information, updates, detailed documentation and video tutorial, please visit:", "wordpress-simple-paypal-shopping-cart"); ?><br />
    <a href="https://www.tipsandtricks-hq.com/wordpress-simple-paypal-shopping-cart-plugin-768" target="_blank"><?php _e("WP Simple Cart Homepage", "wordpress-simple-paypal-shopping-cart"); ?></a></p>
    </div>
    
    <div class="postbox">
    <h3 class="hndle"><label for="title"><?php _e("Quick Usage Guide", "wordpress-simple-paypal-shopping-cart"); ?></label></h3>
    <div class="inside">
	
        <p><strong><?php _e("Step 1) ","wordpress-simple-paypal-shopping-cart"); ?></strong><?php _e("To add an 'Add to Cart' button for a product simply add the shortcode", "wordpress-simple-paypal-shopping-cart"); ?> [wp_cart_button name="<?php _e("PRODUCT-NAME", "wordpress-simple-paypal-shopping-cart"); ?>" price="<?php _e("PRODUCT-PRICE", "wordpress-simple-paypal-shopping-cart"); ?>"] <?php _e("to a post or page next to the product. Replace PRODUCT-NAME and PRODUCT-PRICE with the actual name and price of your product.", "wordpress-simple-paypal-shopping-cart"); ?></p>
        <p><?php _e("Example add to cart button shortcode usage:", "wordpress-simple-paypal-shopping-cart");?> <p style="background-color: #DDDDDD; padding: 5px; display: inline;">[wp_cart_button name="Test Product" price="29.95"]</p></p>
	<p><strong><?php _e("Step 2) ","wordpress-simple-paypal-shopping-cart"); ?></strong><?php _e("To add the shopping cart to a post or page (example: a checkout page) simply add the shortcode", "wordpress-simple-paypal-shopping-cart"); ?> [show_wp_shopping_cart] <?php _e("to a post or page or use the sidebar widget to add the shopping cart to the sidebar.", "wordpress-simple-paypal-shopping-cart"); ?></p>
        <p><?php _e("Example shopping cart shortcode usage:", "wordpress-simple-paypal-shopping-cart");?> <p style="background-color: #DDDDDD; padding: 5px; display: inline;">[show_wp_shopping_cart]</p></p>
    </div></div>

    <form method="post" action="">
    <?php wp_nonce_field('wp_simple_cart_settings_update'); ?>
    <input type="hidden" name="info_update" id="info_update" value="true" />    
<?php
echo '
	<div class="postbox">
	<h3 class="hndle"><label for="title">'.(__("PayPal and Shopping Cart Settings", "wordpress-simple-paypal-shopping-cart")).'</label></h3>
	<div class="inside">';

echo '
<table class="form-table">
<tr valign="top">
<th scope="row">'.(__("Paypal Email Address", "wordpress-simple-paypal-shopping-cart")).'</th>
<td><input type="text" name="cart_paypal_email" value="'.esc_attr($defaultEmail).'" size="40" /></td>
</tr>
<tr valign="top">
<th scope="row">'.(__("Shopping Cart title", "wordpress-simple-paypal-shopping-cart")).'</th>
<td><input type="text" name="wp_cart_title" value="'.esc_attr($title).'" size="40" /></td>
</tr>
<tr valign="top">
<th scope="row">'.(__("Text/Image to Show When Cart Empty", "wordpress-simple-paypal-shopping-cart")).'</th>
<td><input type="text" name="wp_cart_empty_text" value="'.esc_attr($emptyCartText).'" size="100" /><br />'.(__("You can either enter plain text or the URL of an image that you want to show when the shopping cart is empty", "wordpress-simple-paypal-shopping-cart")).'</td>
</tr>';

?>
<tr valign="top">
    <th scope="row"><?php _e("Currency", "wordpress-simple-paypal-shopping-cart"); ?></th>
    <td>
        <select id="cart_payment_currency" name="cart_payment_currency">
            <option value="USD" <?php echo ($defaultCurrency == 'USD') ? 'selected="selected"' : ''; ?>>US Dollars (USD)</option>
            <option value="EUR" <?php echo ($defaultCurrency == 'EUR') ? 'selected="selected"' : ''; ?>>Euros (EUR)</option>
            <option value="GBP" <?php echo ($defaultCurrency == 'GBP') ? 'selected="selected"' : ''; ?>>Pounds Sterling (GBP)</option>
            <option value="AUD" <?php echo ($defaultCurrency == 'AUD') ? 'selected="selected"' : ''; ?>>Australian Dollars (AUD)</option>
            <option value="BRL" <?php echo ($defaultCurrency == 'BRL') ? 'selected="selected"' : ''; ?>>Brazilian Real (BRL)</option>
            <option value="CAD" <?php echo ($defaultCurrency == 'CAD') ? 'selected="selected"' : ''; ?>>Canadian Dollars (CAD)</option>
            <option value="CNY" <?php echo ($defaultCurrency == 'CNY') ? 'selected="selected"' : ''; ?>>Chinese Yuan (CNY)</option>
            <option value="CZK" <?php echo ($defaultCurrency == 'CZK') ? 'selected="selected"' : ''; ?>>Czech Koruna (CZK)</option>
            <option value="DKK" <?php echo ($defaultCurrency == 'DKK') ? 'selected="selected"' : ''; ?>>Danish Krone (DKK)</option>
            <option value="HKD" <?php echo ($defaultCurrency == 'HKD') ? 'selected="selected"' : ''; ?>>Hong Kong Dollar (HKD)</option>
            <option value="HUF" <?php echo ($defaultCurrency == 'HUF') ? 'selected="selected"' : ''; ?>>Hungarian Forint (HUF)</option>
            <option value="INR" <?php echo ($defaultCurrency == 'INR') ? 'selected="selected"' : ''; ?>>Indian Rupee (INR)</option>
            <option value="IDR" <?php echo ($defaultCurrency == 'IDR') ? 'selected="selected"' : ''; ?>>Indonesia Rupiah (IDR)</option>
            <option value="ILS" <?php echo ($defaultCurrency == 'ILS') ? 'selected="selected"' : ''; ?>>Israeli Shekel (ILS)</option>
            <option value="JPY" <?php echo ($defaultCurrency == 'JPY') ? 'selected="selected"' : ''; ?>>Japanese Yen (JPY)</option>
            <option value="MYR" <?php echo ($defaultCurrency == 'MYR') ? 'selected="selected"' : ''; ?>>Malaysian Ringgits (MYR)</option>
            <option value="MXN" <?php echo ($defaultCurrency == 'MXN') ? 'selected="selected"' : ''; ?>>Mexican Peso (MXN)</option>
            <option value="NZD" <?php echo ($defaultCurrency == 'NZD') ? 'selected="selected"' : ''; ?>>New Zealand Dollar (NZD)</option>
            <option value="NOK" <?php echo ($defaultCurrency == 'NOK') ? 'selected="selected"' : ''; ?>>Norwegian Krone (NOK)</option>
            <option value="PHP" <?php echo ($defaultCurrency == 'PHP') ? 'selected="selected"' : ''; ?>>Philippine Pesos (PHP)</option>
            <option value="PLN" <?php echo ($defaultCurrency == 'PLN') ? 'selected="selected"' : ''; ?>>Polish Zloty (PLN)</option>
            <option value="SGD" <?php echo ($defaultCurrency == 'SGD') ? 'selected="selected"' : ''; ?>>Singapore Dollar (SGD)</option>
            <option value="ZAR" <?php echo ($defaultCurrency == 'ZAR') ? 'selected="selected"' : ''; ?>>South African Rand (ZAR)</option>
            <option value="KRW" <?php echo ($defaultCurrency == 'KRW') ? 'selected="selected"' : ''; ?>>South Korean Won (KRW)</option>
            <option value="SEK" <?php echo ($defaultCurrency == 'SEK') ? 'selected="selected"' : ''; ?>>Swedish Krona (SEK)</option>
            <option value="CHF" <?php echo ($defaultCurrency == 'CHF') ? 'selected="selected"' : ''; ?>>Swiss Franc (CHF)</option>
            <option value="TWD" <?php echo ($defaultCurrency == 'TWD') ? 'selected="selected"' : ''; ?>>Taiwan New Dollars (TWD)</option>
            <option value="THB" <?php echo ($defaultCurrency == 'THB') ? 'selected="selected"' : ''; ?>>Thai Baht (THB)</option>
            <option value="TRY" <?php echo ($defaultCurrency == 'TRY') ? 'selected="selected"' : ''; ?>>Turkish Lira (TRY)</option>
            <option value="VND" <?php echo ($defaultCurrency == 'VND') ? 'selected="selected"' : ''; ?>>Vietnamese Dong (VND)</option>
            <option value="RUB" <?php echo ($defaultCurrency == 'RUB') ? 'selected="selected"' : ''; ?>>Russian Ruble (RUB)</option>
        </select>
    </td>
</tr>
<?php

echo '<tr valign="top">
<th scope="row">'.(__("Currency Symbol", "wordpress-simple-paypal-shopping-cart")).'</th>
<td><input type="text" name="cart_currency_symbol" value="'.esc_attr($defaultSymbol).'" size="3" style="width: 2em;" /> ('.(__("e.g.", "wordpress-simple-paypal-shopping-cart")).' $, &#163;, &#8364;) 
</td>
</tr>

<tr valign="top">
<th scope="row">'.(__("Base Shipping Cost", "wordpress-simple-paypal-shopping-cart")).'</th>
<td><input type="text" name="cart_base_shipping_cost" value="'.esc_attr($baseShipping).'" size="5" /> <br />'.(__("This is the base shipping cost that will be added to the total of individual products shipping cost. Put 0 if you do not want to charge shipping cost or use base shipping cost.", "wordpress-simple-paypal-shopping-cart")).' <a href="http://www.tipsandtricks-hq.com/ecommerce/?p=297" target="_blank">'.(__("Learn More on Shipping Calculation", "wordpress-simple-paypal-shopping-cart")).'</a></td>
</tr>

<tr valign="top">
<th scope="row">'.(__("Free Shipping for Orders Over", "wordpress-simple-paypal-shopping-cart")).'</th>
<td><input type="text" name="cart_free_shipping_threshold" value="'.esc_attr($cart_free_shipping_threshold).'" size="5" /> <br />'.(__("When a customer orders more than this amount he/she will get free shipping. Leave empty if you do not want to use it.", "wordpress-simple-paypal-shopping-cart")).'</td>
</tr>

<tr valign="top">
<th scope="row">'.(__("Must Collect Shipping Address on PayPal", "wordpress-simple-paypal-shopping-cart")).'</th>
<td><input type="checkbox" name="wp_shopping_cart_collect_address" value="1" '.$wp_shopping_cart_collect_address.' /><br />'.(__("If checked the customer will be forced to enter a shipping address on PayPal when checking out.", "wordpress-simple-paypal-shopping-cart")).'</td>
</tr>

<tr valign="top">
<th scope="row">'.(__("Use PayPal Profile Based Shipping", "wordpress-simple-paypal-shopping-cart")).'</th>
<td><input type="checkbox" name="wp_shopping_cart_use_profile_shipping" value="1" '.$wp_shopping_cart_use_profile_shipping.' /><br />'.(__("Check this if you want to use", "wordpress-simple-paypal-shopping-cart")).' <a href="https://www.tipsandtricks-hq.com/setup-paypal-profile-based-shipping-5865" target="_blank">'.(__("PayPal profile based shipping", "wordpress-simple-paypal-shopping-cart")).'</a>. '.(__("Using this will ignore any other shipping options that you have specified in this plugin.", "wordpress-simple-paypal-shopping-cart")).'</td>
</tr>
		
<tr valign="top">
<th scope="row">'.(__("Add to Cart button text or Image", "wordpress-simple-paypal-shopping-cart")).'</th>
<td><input type="text" name="addToCartButtonName" value="'.esc_attr($addcart).'" size="100" />
<br />'.(__("To use a customized image as the button simply enter the URL of the image file.", "wordpress-simple-paypal-shopping-cart")).' '.(__("e.g.", "wordpress-simple-paypal-shopping-cart")).' http://www.your-domain.com/wp-content/plugins/wordpress-paypal-shopping-cart/images/buy_now_button.png
<br />You can download nice add to cart button images from <a href="https://www.tipsandtricks-hq.com/ecommerce/add-to-cart-button-images-for-shopping-cart-631" target="_blank">this page</a>.
</td>
</tr>

<tr valign="top">
<th scope="row">'.(__("Return URL", "wordpress-simple-paypal-shopping-cart")).'</th>
<td><input type="text" name="cart_return_from_paypal_url" value="'.esc_attr($return_url).'" size="100" /><br />'.(__("This is the URL the customer will be redirected to after a successful payment", "wordpress-simple-paypal-shopping-cart")).'</td>
</tr>

<tr valign="top">
<th scope="row">'.(__("Cancel URL", "wordpress-simple-paypal-shopping-cart")).'</th>
<td><input type="text" name="cart_cancel_from_paypal_url" value="'.esc_attr($cancel_url).'" size="100" /><br />'.(__("The customer will be sent to the above page if the cancel link is clicked on the PayPal checkout page.", "wordpress-simple-paypal-shopping-cart")).'</td>
</tr>
		
<tr valign="top">
<th scope="row">'.(__("Products Page URL", "wordpress-simple-paypal-shopping-cart")).'</th>
<td><input type="text" name="cart_products_page_url" value="'.esc_attr($cart_products_page_url).'" size="100" /><br />'.(__("This is the URL of your products page if you have any. If used, the shopping cart widget will display a link to this page when cart is empty", "wordpress-simple-paypal-shopping-cart")).'</td>
</tr>

<tr valign="top">
<th scope="row">'.(__("Automatic redirection to checkout page", "wordpress-simple-paypal-shopping-cart")).'</th>
<td><input type="checkbox" name="wp_shopping_cart_auto_redirect_to_checkout_page" value="1" '.$wp_shopping_cart_auto_redirect_to_checkout_page.' />
 '.(__("Checkout Page URL", "wordpress-simple-paypal-shopping-cart")).': <input type="text" name="cart_checkout_page_url" value="'.$cart_checkout_page_url.'" size="60" />
<br />'.(__("If checked the visitor will be redirected to the Checkout page after a product is added to the cart. You must enter a URL in the Checkout Page URL field for this to work.", "wordpress-simple-paypal-shopping-cart")).'</td>
</tr>

<tr valign="top">
<th scope="row">'.(__("Open PayPal Checkout Page in a New Tab", "wordpress-simple-paypal-shopping-cart")).'</th>
<td><input type="checkbox" name="wspsc_open_pp_checkout_in_new_tab" value="1" '.$wspsc_open_pp_checkout_in_new_tab.' />
<br />'.(__("If checked the PayPal checkout page will be opened in a new tab/window when the user clicks the checkout button.", "wordpress-simple-paypal-shopping-cart")).'</td>
</tr>

<tr valign="top">
<th scope="row">'.(__("Reset Cart After Redirection to Return Page", "wordpress-simple-paypal-shopping-cart")).'</th>
<td><input type="checkbox" name="wp_shopping_cart_reset_after_redirection_to_return_page" value="1" '.$wp_shopping_cart_reset_after_redirection_to_return_page.' />
<br />'.(__("If checked the shopping cart will be reset when the customer lands on the return URL (Thank You) page.", "wordpress-simple-paypal-shopping-cart")).'</td>
</tr>
</table>


<table class="form-table">
<tr valign="top">
<th scope="row">'.(__("Hide Shopping Cart Image", "wordpress-simple-paypal-shopping-cart")).'</th>
<td><input type="checkbox" name="wp_shopping_cart_image_hide" value="1" '.$wp_cart_image_hide.' /><br />'.(__("If ticked the shopping cart image will not be shown.", "wordpress-simple-paypal-shopping-cart")).'</td>
</tr>
</table>

<table class="form-table">
<tr valign="top">
<th scope="row">'.(__("Custom Checkout Page Logo Image", "wordpress-simple-paypal-shopping-cart")).'</th>
<td><input type="text" name="wp_cart_paypal_co_page_style" value="'.esc_attr($wp_cart_paypal_co_page_style).'" size="100" />
<br />'.(__("Specify an image URL if you want to customize the paypal checkout page with a custom logo/image. The image URL must be a 'https' URL otherwise PayPal will ignore it.", "wordpress-simple-paypal-shopping-cart")).'</td>
</tr>
</table>

<table class="form-table">
<tr valign="top">
<th scope="row">'.(__("Use Strict PayPal Email Address Checking", "wordpress-simple-paypal-shopping-cart")).'</th>
<td><input type="checkbox" name="wp_shopping_cart_strict_email_check" value="1" '.$wp_shopping_cart_strict_email_check.' /><br />'.(__("If checked the script will check to make sure that the PayPal email address specified is the same as the account where the payment was deposited (Usage of PayPal Email Alias will fail too).", "wordpress-simple-paypal-shopping-cart")).'</td>
</tr>
</table>

<table class="form-table">
<tr valign="top">
<th scope="row">'.(__("Disable Nonce Check for Add to Cart", "wordpress-simple-paypal-shopping-cart")).'</th>
<td><input type="checkbox" name="wspsc_disable_nonce_add_cart" value="1" '.$wspsc_disable_nonce_add_cart.' />
<br />'.(__("Check this option if you are using a caching solution on your site. This will bypass the nonce check on the add to cart buttons.", "wordpress-simple-paypal-shopping-cart")).'</td>
</tr>
</table>

<table class="form-table">
<tr valign="top">
<th scope="row">'.(__("Customize the Note to Seller Text", "wordpress-simple-paypal-shopping-cart")).'</th>
<td>'.(__("PayPal has removed this feature. We have created an addon so you can still collect instructions from customers at the time of checking out. ", "wordpress-simple-paypal-shopping-cart"))
. '<a href="https://www.tipsandtricks-hq.com/ecommerce/wp-simple-cart-collect-customer-input-in-the-shopping-cart-4396" target="_blank">'.__("View the addon details", "wordpress-simple-paypal-shopping-cart").'</a>'.'</td>
</tr>
</table>

<table class="form-table">
<tr valign="top">
<th scope="row">'.(__("Use WP Affiliate Platform", "wordpress-simple-paypal-shopping-cart")).'</th>
<td><input type="checkbox" name="wp_use_aff_platform" value="1" '.$wp_use_aff_platform.' />
<br />'.(__("Check this if using with the", "wordpress-simple-paypal-shopping-cart")).' <a href="https://www.tipsandtricks-hq.com/wordpress-affiliate-platform-plugin-simple-affiliate-program-for-wordpress-blogsite-1474" target="_blank">WP Affiliate Platform plugin</a>. '.(__("This plugin lets you run your own affiliate campaign/program and allows you to reward (pay commission) your affiliates for referred sales", "wordpress-simple-paypal-shopping-cart")).'</td>
</tr>
</table>
</div></div>

<div class="postbox">
    <h3 class="hndle"><label for="title">'.(__("Testing and Debugging Settings", "wordpress-simple-paypal-shopping-cart")).'</label></h3>
    <div class="inside">
    
    <table class="form-table"> 
    
    <tr valign="top">
    <th scope="row">'.(__("Enable Debug", "wordpress-simple-paypal-shopping-cart")).'</th>
    <td><input type="checkbox" name="wp_shopping_cart_enable_debug" value="1" '.$wp_shopping_cart_enable_debug.' />
    <br />'.(__("If checked, debug output will be written to the log file. This is useful for troubleshooting post payment failures", "wordpress-simple-paypal-shopping-cart")).'
        <p><i>You can check the debug log file by clicking on the link below (The log file can be viewed using any text editor):</i>
        <ul>
            <li><a href="'.WP_CART_URL.'/ipn_handle_debug.txt" target="_blank">ipn_handle_debug.txt</a></li>
        </ul>
        </p>
        <input type="submit" name="wspsc_reset_logfile" class="button" style="font-weight:bold; color:red" value="Reset Debug Log file"/> 
        <p class="description">It will reset the debug log file and timestamp it with a log file reset message.</a>
    </td></tr>

    <tr valign="top">
    <th scope="row">'.(__("Enable Sandbox Testing", "wordpress-simple-paypal-shopping-cart")).'</th>
    <td><input type="checkbox" name="wp_shopping_cart_enable_sandbox" value="1" '.$wp_shopping_cart_enable_sandbox.' />
    <br />'.(__("Check this option if you want to do PayPal sandbox testing. You will need to create a PayPal sandbox account from PayPal Developer site", "wordpress-simple-paypal-shopping-cart")).'</td>
    </tr>
    
    </table>
    
    </div>
</div>

    <div class="submit">
        <input type="submit" class="button-primary" name="info_update" value="'.(__("Update Options &raquo;", "wordpress-simple-paypal-shopping-cart")).'" />
    </div>						
 </form>
 ';
    echo (__("Like the Simple WordPress Shopping Cart Plugin?", "wordpress-simple-paypal-shopping-cart")).' <a href="https://wordpress.org/support/plugin/wordpress-simple-paypal-shopping-cart/reviews/?filter=5" target="_blank">'.(__("Give it a good rating", "wordpress-simple-paypal-shopping-cart")).'</a>';
    wpspsc_settings_menu_footer();
}
