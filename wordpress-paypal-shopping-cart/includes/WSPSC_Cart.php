<?php
class WSPSC_Cart {
    private $items = array();
    private $item;
    private $cart_id = 0;
    private $post_type = "";
    protected static $instance = null;
    protected $cart_custom_values = "";

    public function __construct() {
        $this->cart_id = isset($_COOKIE['simple_cart_id']) ? $_COOKIE['simple_cart_id'] : 0;
        $this->post_type = "wpsc_cart_orders";
    }

    public static function get_instance() {
        // If the single instance hasn't been set, set it now.
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Create a new cart order in the database and set a cookie with the cart ID.
     *
     * This method creates a new cart order post in the 'wpsc_cart_orders' post type with the post status set to 'trash',
     * sets a cookie with the cart ID, and updates the cart object with the new ID. It also updates the cart order post
     * with the current cart ID as the post title and the order status as 'In Progress'. If any items have been added to the
     * cart, they will be saved to the database as well.
     */
    public function create_cart() {
        //This is normally used when first time an item is added to the cart. It creates a new cart ID and set the cookie.
        
        //Create a new order
        $wpsc_order = array(
            'post_title'    => 'WPSC Cart Order',
            'post_type'     => $this->post_type,
            'post_content'  => '',
            'post_status'   => 'trash',
        );
        //Insert the post into the database
        $post_id  = wp_insert_post($wpsc_order);

        if ($post_id) {
            $cookie_expiration = time() + (86400 * 30); // 30 days
            setcookie('simple_cart_id', $post_id, $cookie_expiration, '/');
            $this->set_cart_id($post_id);

            //Save the cart ID in the session for the customer input addon backwards compatibility.
            //TODO - Remove this in the next version.
            $_SESSION['simple_cart_id'] = $post_id;

            //Update the post title with the cart ID
            $updated_wpsc_order = array(
                'ID'         => $this->get_cart_id(),
                'post_title' => $this->get_cart_id(),
                'post_type'  => $this->post_type,
            );
            wp_update_post($updated_wpsc_order);

            //Update the order status
            $status = "In Progress";
            update_post_meta($this->get_cart_id(), 'wpsc_order_status', $status);

            //Save cart items (if items were added to cart)
            $this->save_items();
        }
    }

    /**
     * Add / update items to the cart.
     *
     * Set the specified items in the cart.
     *
     * @param array $items An array of items to add to the cart.
     */
    public function add_items($items) {
        $this->set_items($items);
    }


    /**
     * Save the cart items to the database.
     *
     * If the cart ID has been set, update the 'wpsc_cart_items' post meta with
     * the current items in the cart.
     */
    public function save_items() {
        if ($this->get_cart_id()) {
            update_post_meta( $this->get_cart_id(), 'wpsc_cart_items', $this->items );
        }
    }

    /**
     * Resets the cart items and action messages.
     */    
    public function reset_cart() {
        //This function may get called with some items in the cart or 0 items in the cart. It will reset only the cart items.
        //This is to avoid creating multiple cart order objects when calling reset_cart()
        //Thus keeping only one cart order object for one user
        $this->set_items(array());

        $this->clear_cart_action_msg();

        $collection_obj = WPSPSC_Coupons_Collection::get_instance();
        $collection_obj->clear_discount_applied_once($this->get_cart_id());        
        $collection_obj->clear_applied_coupon_code($this->get_cart_id());
    }

    /**
     * It does a full reset by removing the cart id from cookie.
     */
    public function reset_cart_after_txn() {
        //This function will get called after the transaction (from the thank you page).
        //This function doesn't empty the items array in the order post (so that the admin can see the order details).
        $this->items = array();//Set the items to empty array but don't update the order post.

        $this->clear_cart_action_msg();

        $collection_obj = WPSPSC_Coupons_Collection::get_instance();
        $collection_obj->clear_discount_applied_once($this->get_cart_id());        
        $collection_obj->clear_applied_coupon_code($this->get_cart_id());

        //Set cookie in the past to expire it
        setcookie('simple_cart_id', '', time() - 3600, '/');
        $this->set_cart_id(0);
    }

    public function get_total_cart_qty() {
        $total_items = 0;
        if (!$this->get_items()) {
            return $total_items;
        }
        foreach ($this->get_items() as $item) {
            $total_items += $item->get_quantity();
        }
        return $total_items;
    }

    public function get_total_cart_sub_total() {
        $sub_total = 0;
        if (!$this->get_items()) {
            return $sub_total;
        }
        foreach ($this->get_items() as $item) {
            $sub_total += $item->get_price() * $item->get_quantity();
        }
        return $sub_total;
    }

    public function simple_cart_total() {
        $grand_total = 0;
        $total = 0;
        $item_total_shipping = 0;

        if ($this->get_items()) {
            foreach ($this->get_items() as $item) {
                $total             += $item->get_price() * $item->get_quantity();
                $item_total_shipping     += $item->get_shipping() * $item->get_quantity();
            }
            $grand_total = $total + $item_total_shipping;
        }

        return wpspsc_number_format_price($grand_total);
    }

    //Scanerio: User gets back after 2 days, he has cart order id: 123 in his cookie
    //But site admin has deleted all the trash order posts
    //So the cart order id: 123 is basically an orphan, which will not save any cart items and throw errors.
    //Fix: checking if the cart_id is the id of an actual post of `wpsc_cart_orders` post type.
    public function get_cart_id() {
        if ($this->cart_id == 0) {
            return false;
        }
        //Check if cart_id has value, check if that post exists & is of correct post type
        if( get_post_type( $this->cart_id ) === $this->post_type && get_post( $this->cart_id ) ) {
            //Check if this post status is "paid". If it is paid then a new cart ID need to be issued.
            $post_id = $this->cart_id;
            $status = get_post_meta($post_id, 'wpsc_order_status', true);
            if (strcasecmp($status, "paid") == 0) {
                //This cart transaction already completed. Need to create a new one.
                wspsc_log_payment_debug('The transaction status for this cart ID (' . $this->cart_id . ') is paid. Need to create a new cart ID.', true);
                return false;
            }
            //Use the cart ID.
            return $this->cart_id;
        }
        return false;
    }

    public function set_cart_id( $cart_id ) {
        $this->cart_id = $cart_id;
    }

    /**
     * Retrieve the items in the cart from the 'wpsc_cart_items' post meta.
     *
     * This method retrieves the items in the cart from the 'wpsc_cart_items' post meta using the current cart ID. If the
     * cart ID is not set or invalid, the method will return false.
     *
     * @return array|false An array of cart items or false if the cart ID is not set or invalid.
     */
    public function get_items() {
        if ($this->get_cart_id()) {
            $products = get_post_meta($this->get_cart_id(), 'wpsc_cart_items', true);

            if (!is_array($products) || count($products) == 0) {
                return false;
            }

            return $products;
        }

        return false;
    }

    public function cart_not_empty() {
        $count = 0;
        if ($this->get_items()) {
            foreach ($this->get_items() as $item){
                $count++;
            }
            return $count;
        } else
            return 0;
    }

    public function set_items($items) {
        $this->items = $items;
        $this->save_items();
    }

    public function set_cart_custom_values($custom_val_string) {
        $this->cart_custom_values = $custom_val_string;
    }

    public function get_cart_custom_values() {
        return $this->cart_custom_values;
    }

    public function get_cart_action_msg() {
        if ( $this->get_cart_id() ) {
            $transient_key = 'wpspsc_cart_action_msg' . $this->get_cart_id();
            //$expiration = 3600; // 1 hour
            $msg = get_transient($transient_key);
            return $msg;
        }
    }

    public function set_cart_action_msg( $msg ) {
        if ($this->get_cart_id()) {
            $transient_key = 'wpspsc_cart_action_msg' . $this->get_cart_id();
            $expiration = 3600; // 1 hour
            set_transient($transient_key, $msg, $expiration);
        }
    }

    public function clear_cart_action_msg() {
        if ($this->get_cart_id()) {
            $transient_key = 'wpspsc_cart_action_msg' . $this->get_cart_id();
            delete_transient($transient_key);
        }
    }

    /**
     * Calculate the total cart value including item prices and shipping costs.
     *
     * @return string The formatted total cart value.
     */
    public function get_cart_total(){
        $total=0;
        $postage_cost=0;
        $item_total_shipping=0;

        foreach ($this->get_items() as $item) {
			$total               += $item->get_price() * $item->get_quantity();
			$item_total_shipping += $item->get_shipping() * $item->get_quantity();			
		}
		
		
		if (!empty($item_total_shipping)) {
			$baseShipping = get_option('cart_base_shipping_cost');
			$postage_cost = $item_total_shipping + $baseShipping;
		}
		
		$cart_free_shipping_threshold = get_option('cart_free_shipping_threshold');
		if (!empty($cart_free_shipping_threshold) && $total > $cart_free_shipping_threshold) {
			$postage_cost = 0;
		}

        $grand_total=$total + $postage_cost;

        return wpspsc_number_format_price($grand_total);
    }

}
