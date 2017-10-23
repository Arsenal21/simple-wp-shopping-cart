<?php
class WPSPSC_Coupons_Collection
{
    var $coupon_items = array();
    
    function __construct()
    {
        
    }
    
    function add_coupon_item($coupon_item)
    {
        array_push($this->coupon_items, $coupon_item);
    }
    
    function find_coupon_by_code($coupon_code)
    {
        if(empty($this->coupon_items)){
            echo "<br />".(__("Admin needs to configure some discount coupons before it can be used", "wordpress-simple-paypal-shopping-cart"));
            return new stdClass();
        }
        foreach($this->coupon_items as $key => $coupon)
        {
            if(strtolower($coupon->coupon_code) == strtolower($coupon_code)){
                return $coupon;
            }
        }
        return new stdClass();
    }
    
    function delete_coupon_item_by_id($coupon_id)
    {
        $coupon_deleted = false;
        foreach($this->coupon_items as $key => $coupon)
        {
            if($coupon->id == $coupon_id){
                $coupon_deleted = true;
                unset($this->coupon_items[$key]);
            }
        }
        if($coupon_deleted){
            $this->coupon_items = array_values($this->coupon_items);
            WPSPSC_Coupons_Collection::save_object($this);
        }
    }
    
    function print_coupons_collection()
    {
        foreach ($this->coupon_items as $item){
            $item->print_coupon_item_details();
        }
    }
       
    static function save_object($obj_to_save)
    {
        update_option('wpspsc_coupons_collection', $obj_to_save);
    }
    
    static function get_instance()
    {
        $obj = get_option('wpspsc_coupons_collection');
        if($obj){
            return $obj;
        }else{
            return new WPSPSC_Coupons_Collection();
        }
    }
}

class WPSPSC_COUPON_ITEM
{
    var $id;
    var $coupon_code;
    var $discount_rate;
    var $expiry_date;
    function __construct($coupon_code, $discount_rate, $expiry_date)
    {
        $this->id = uniqid();
        $this->coupon_code = $coupon_code;
        $this->discount_rate = $discount_rate;
        $this->expiry_date = $expiry_date;
    }
    
    function print_coupon_item_details()
    {
        echo "<br />".(__("Coupon ID: ", "wordpress-simple-paypal-shopping-cart")).$this->id;
        echo "<br />".(__("Coupon Code: ", "wordpress-simple-paypal-shopping-cart")).$this->coupon_code;
        echo "<br />".(__("Discount Amt: ", "wordpress-simple-paypal-shopping-cart")).$this->discount_rate;
        echo "<br />".(__("Expiry date: ", "wordpress-simple-paypal-shopping-cart")).$this->expiry_date;
    }
}

function wpspsc_apply_cart_discount($coupon_code)
{
    $collection_obj = WPSPSC_Coupons_Collection::get_instance();
    $coupon_item = $collection_obj->find_coupon_by_code($coupon_code);
    if(!isset($coupon_item->id)){
        $_SESSION['wpspsc_cart_action_msg'] = '<div class="wpspsc_error_message">'.__("Coupon code used does not exist!", "wordpress-simple-paypal-shopping-cart").'</div>';
        return;
    }
    $coupon_expiry_date = $coupon_item->expiry_date;
    if(!empty($coupon_expiry_date)){
        $current_date = date("Y-m-d");
        if($current_date > $coupon_expiry_date){
            $_SESSION['wpspsc_cart_action_msg'] = '<div class="wpspsc_error_message">'.__("Coupon code expired!", "wordpress-simple-paypal-shopping-cart").'</div>';
            return;
        }
    }
    if (isset($_SESSION['wpspsc_discount_applied_once']) && $_SESSION['wpspsc_discount_applied_once'] == '1'){
        $_SESSION['wpspsc_cart_action_msg'] = '<div class="wpspsc_error_message">'.__("Discount can only be applied once per checkout!", "wordpress-simple-paypal-shopping-cart").'</div>';
        return;
    }
    
    //Apply the discount
    $curr_symbol = WP_CART_CURRENCY_SYMBOL;
    $discount_rate = $coupon_item->discount_rate;
    $products = $_SESSION['simpleCart'];
    $discount_total = 0;
    foreach ($products as $key => $item)
    {
        if ($item['price'] > 0)
        {
            $item_discount = (($item['price_orig']*$discount_rate)/100);	            			
            $discount_total = $discount_total + $item_discount*$item['quantity'];
            $item['price'] = $item['price_orig'] - $item_discount;
            unset($products[$key]);
            array_push($products, $item);
        }
    }
    $_SESSION['simpleCart'] = $products;
    $disct_amt_msg = print_payment_currency($discount_total, $curr_symbol);
    $_SESSION['wpspsc_cart_action_msg'] = '<div class="wpspsc_success_message">'.__("Discount applied successfully! Total Discount: ", "wordpress-simple-paypal-shopping-cart").$disct_amt_msg.'</div>';
    $_SESSION['wpspsc_discount_applied_once'] = '1';
    $_SESSION['wpspsc_applied_coupon_code'] = $coupon_code;
}

function wpspsc_reapply_discount_coupon_if_needed()
{
    //Re-apply coupon to the cart if necessary (meaning a coupon was already applied to the cart when this item was modified.
    if (isset($_SESSION['wpspsc_discount_applied_once']) && $_SESSION['wpspsc_discount_applied_once'] == '1'){
        $coupon_code = $_SESSION['wpspsc_applied_coupon_code'];
        unset($_SESSION['wpspsc_discount_applied_once']);
        wpspsc_apply_cart_discount($coupon_code);
    }    
}