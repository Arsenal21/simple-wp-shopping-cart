<?php

//This global variable is used for the cart id to be available on the first page load (when the cookie is set but the server side code doesn't have the cookie value yet).
$wpsc_global_visitor_cart_id = 0;

class WPSC_Cart {
	const POST_TYPE = "wpsc_cart_orders";
	private $items = array();
	private $item;
	private $cart_id = 0;
	private static $instance = null;
	protected $cart_custom_values = "";

    /**
     * Having empty string, means the field was never selected. '-1' means, the field was selected once but not proper value was set.
     * A proper shipping region string value would be like '<location in string>:<type in number>', i.e. 'london:2'.
     *
     * @var string A lookup string for calculating regional shipping cost.
     */
    public $selected_shipping_region = '';

    public $on_page_carts_div_count = 0;
    public static $on_page_cart_div_ids = array();
    public $item_shipping_total = 0;
    public $sub_total = 0;
	public $shipping_cost = 0;
    public $postage_cost = 0;
    public $tax = 0;
    public $grand_total = 0;

	private $post_id_reference = 0;

	private function __construct() {
		$this->cart_id = isset( $_COOKIE['simple_cart_id'] ) ? $_COOKIE['simple_cart_id'] : 0;

		$cart_cpt_id = wpsc_get_cart_cpt_id_by_cart_id( $this->cart_id );
		if ( ! empty( $cart_cpt_id ) ) {
			// Assign all previously saved properties of WPSC_Cart object.
			$saved_wpsc_cart_object = $this->get_cart_from_postmeta( $cart_cpt_id );
			if ( $saved_wpsc_cart_object instanceof self ) {
				foreach ( $saved_wpsc_cart_object as $property => $value ) {
					if ( property_exists( $this, $property ) ) {
						$this->$property = $value;
					}
				}

				$this->set_cart_cpt_id( $cart_cpt_id );
				// Found the card cpt id, and the cart object has loaded.
				return;
			}
		}

		// The saved cart id or the saved cart class object is not valid. So delete that cart and other related values.
		$this->delete_cart_and_related_data( $cart_cpt_id );
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
	 * with the current cart cpt ID as the post title and the order status as 'In Progress'. If any items have been added to the
	 * cart, they will be saved to the database as well.
	 */
	public function create_cart() {
		//This is normally used when first time an item is added to the cart. It creates a new cart ID and set the cookie.

		//Create a new order
		$wpsc_order = array(
			'post_title'   => 'WPSC Cart Order',
			'post_type'    => WPSC_Cart::POST_TYPE,
			'post_content' => '',
			'post_status'  => 'trash',
		);
		//Insert the post into the database
		$cart_cpt_id = wp_insert_post( $wpsc_order );

		if ( $cart_cpt_id ) {
			//Set the cookie with unique cart ID.
			$cart_id           = uniqid();
			$cookie_expiration = time() + ( 86400 * 30 ); // 30 days
			setcookie( 'simple_cart_id', $cart_id, $cookie_expiration, '/' );

			$this->set_cart_id( $cart_id );
			$this->set_cart_cpt_id( $cart_cpt_id );

			//Set the global variable for the cart ID (so for the first page load, addon's can use this cart ID when the cookie is set but the server side code doesn't have the cookie value yet).
			global $wpsc_global_visitor_cart_id;
			$wpsc_global_visitor_cart_id = $cart_id;

			//Update the post title with the cart ID
			$updated_wpsc_order = array(
				'ID'         => $cart_cpt_id,
				'post_title' => $cart_cpt_id,
				'post_type'  => WPSC_Cart::POST_TYPE,
			);
			wp_update_post( $updated_wpsc_order );

			//Update the order status
			$status = "In Progress";
			update_post_meta( $cart_cpt_id, 'wpsc_order_status', $status );
			update_post_meta( $cart_cpt_id, 'wpsc_cart_id', $cart_id );

            //Save cart items (if items were added to cart)
            $this->save_items();

            // Save cart post meta.
            $this->save_cart_to_postmeta();
        }
    }

	/**
	 * Save the cart object to postmeta for persistency.
	 *
	 * @return int|bool
	 */
	public function save_cart_to_postmeta() {
		$serialized_cart_object = serialize( $this );

		return update_post_meta( $this->get_cart_cpt_id(), 'wpsc_cart_object', $serialized_cart_object );
	}

	/**
	 * Retrieve the cart object from postmeta to get the cart related data.
	 * Useful if page reload occurs.
	 *
	 * @return object The WPSC_Cart class object.
	 */
	public static function get_cart_from_postmeta( $cart_cpt_id ) {
		$serialized_cart_object = get_post_meta( $cart_cpt_id, 'wpsc_cart_object', true );

        if ($serialized_cart_object) {
            return unserialize($serialized_cart_object); // Unserialize data to get the object
        }

        return false;
    }

    public function set_selected_shipping_region($region_str){
        $this->selected_shipping_region = $region_str;
    }

    public function get_selected_shipping_region(){
        return $this->selected_shipping_region;
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
	 * @deprecated This method is deprecated since version 5.0.3. Use 'save_cart_to_postmeta' instead where the entire cart class object is being saved.
	 *
	 * Save the cart items to the database.
	 *
	 * If the cart ID has been set, update the 'wpsc_cart_items' post meta with
	 * the current items in the cart.
	 */
	public function save_items() {
		if ( $this->get_cart_id() ) {
			update_post_meta( $this->get_cart_cpt_id(), 'wpsc_cart_items', $this->items );
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
		$collection_obj->clear_discount_applied_once( $this->get_cart_cpt_id() );
		$collection_obj->clear_applied_coupon_code( $this->get_cart_cpt_id() );
	}

	/**
	 * It does a full reset by removing the cart id from cookie.
	 * Recommended to call this function with the $cart_id parameter.
	 */
	public function reset_cart_after_txn( $cart_cpt_id = 0 ) {
		//This function will get called after the transaction (from the thank you page or post payment processing handler).
		//This function doesn't empty the items array in the order post (so that the admin can see the order details).
		$this->items = array();//Set the items to empty array but don't update the order post.

		if ( ! empty( $cart_cpt_id ) ) {
			//After the transaction is completed, the order status will be paid. So we don't want to call the get_cart_id() function.
			//Do these only if a cart ID is passed.
			$collection_obj = WPSPSC_Coupons_Collection::get_instance();
			$collection_obj->clear_discount_applied_once( $cart_cpt_id );
			$collection_obj->clear_applied_coupon_code( $cart_cpt_id );

			//Delete the cart action msg transient
			$transient_key = 'wpspsc_cart_action_msg' . $cart_cpt_id;
			delete_transient( $transient_key );
		}

		//Set cookie in the past to expire it
		setcookie( 'simple_cart_id', '', time() - 3600, '/' );
		$this->set_cart_cpt_id( 0 );
		$this->set_cart_id( 0 );
	}

	public function delete_cart_and_related_data( $cart_cpt_id ) {
		// Delete the corresponding order post.
		wp_delete_post( $cart_cpt_id );
		// Delete the cart and related data.
		$this->clear_cart_action_msg();
		$this->items = array();
		$this->set_cart_cpt_id( 0 );
		$this->set_cart_id( 0 );
		if ( ! headers_sent() ) {
			setcookie( "simple_cart_id", "", time() - 3600, "/" );
		}
	}

	public function get_total_cart_qty() {
		$total_items = 0;
		if ( ! $this->get_items() ) {
			return $total_items;
		}
		foreach ( $this->get_items() as $item ) {
			$total_items += $item->get_quantity();
		}

		return $total_items;
	}

	public function get_total_cart_sub_total() {
		$sub_total = 0;
		if ( ! $this->get_items() ) {
			return $sub_total;
		}
		foreach ( $this->get_items() as $item ) {
			$sub_total += $item->get_price() * $item->get_quantity();
		}

		return $sub_total;
	}

	public function get_sub_total_formatted() {
		//This function will return the sub total of the cart items after calculate_cart_totals_and_postage() is called.
		$sub_total = $this->sub_total;

		return wpsc_number_format_price( $sub_total );
	}

	/**
	 * This includes:
	 * - product item's shipping cost
	 * - base shipping cost
	 * - regional shipping cost
	 */
	public function get_total_shipping_cost() {
		return $this->shipping_cost;
	}

	public function get_postage_cost() {
		//This function will return the postage cost amount of the cart items after calculate_cart_totals_and_postage() is called.
		$postage_cost = $this->postage_cost;

		return $postage_cost;
	}

	public function get_postage_cost_formatted() {
		//This function will return the formatted postage cost of the cart items after calculate_cart_totals_and_postage() is called.
		$postage_cost = $this->postage_cost;

		return wpsc_number_format_price( $postage_cost );
	}

	/**
	 * This function will return the total shipping costs of each cart item.
	 * The total cost is calculated when 'calculate_cart_totals_and_postage' method is called.
	 *
	 * @return float Total shipping costs of each cart item.
	 */
	public function get_item_shipping_total() {
		return $this->item_shipping_total;
	}

	public function get_grand_total_formatted() {
		//This function will return the grand total of the cart items after calculate_cart_totals_and_postage() is called.
		$grand_total = $this->grand_total;

		return wpsc_number_format_price( $grand_total );
	}

    /**
     * Calculates various cart totals and postage cost then sets the values in respective variables. 
     * You can then use getters to get the values.
     * 
     * @return mixed Returns the grand total of the cart.
     */
    public function calculate_cart_totals_and_postage() {
        if (!$this->get_items()) {
            return 0;
        }

        $grand_total = 0;
        $sub_total = 0;
        $postage_cost = 0;
        $item_total_shipping = 0;
        $total_shipping = 0;
        $total_items = 0;

        foreach ( $this->get_items() as $item ) {
			$sub_total += $item->get_price() * $item->get_quantity();
			$item_total_shipping += $item->get_shipping() * $item->get_quantity();
			$total_items += $item->get_quantity();
		}
		if ( ! empty( $item_total_shipping ) ) {
            $baseShipping = get_option( 'cart_base_shipping_cost' );
            // Check if shipping by region is enabled, override base shipping if enabled.
            $regional_shipping_amount = 0;
            $enable_shipping_by_region = get_option('enable_shipping_by_region');
            if ( $enable_shipping_by_region ) {
                //Check the selected region and get the shipping amount for that region.
                $region_str = $this->get_selected_shipping_region();
                $region = check_shipping_region_str($region_str);
                if($region){
                    $regional_shipping_amount = $region['amount'];
                }
            }

			$total_shipping = (float) $item_total_shipping + (float) $baseShipping + (float) $regional_shipping_amount;

			$postage_cost += $total_shipping;
		}

        /**
         * Custom hook to allow modification of the postage cost.
         *
         * This filter allows developers to provide their own custom postage cost calculation routine.
         * It gives access to the WPSC_Cart class instance which can be used for additional context
         * or logic when calculating the postage cost.
         *
         * @param float        $postage_cost The default postage cost calculated by the plugin.
         * @param WPSC_Cart   $this The instance of the WPSC_Cart class, providing
         *                           context and additional methods/properties if needed.
         *
         * @return float The modified postage cost.
         *@since 5.0.7
         *
         */
        $postage_cost = apply_filters('wpsc_custom_postage_cost', $postage_cost, $this);

		$cart_free_shipping_threshold = get_option( 'cart_free_shipping_threshold' );
		if ( ! empty( $cart_free_shipping_threshold ) && $sub_total > $cart_free_shipping_threshold ) {
			$postage_cost = 0;
		}

        $tax = 0;//At the moment we don't have tax calculation. So set it to 0.

        //Calculate the grand total
        $grand_total = $sub_total + $postage_cost + $tax;

        //Set the values in the class variables
        $this->sub_total = $sub_total;
        $this->item_shipping_total = $item_total_shipping;
		$this->shipping_cost = $total_shipping;
        $this->postage_cost = $postage_cost;
        $this->tax = $tax;
        $this->grand_total = $grand_total;

        return $grand_total;
    }

    public function simple_cart_total() {
        //Use calculate_cart_totals_and_postage instead of this function.
        $grand_total = 0;
        $total = 0;
        $item_total_shipping = 0;

        if ($this->get_items()) {
            foreach ($this->get_items() as $item) {
                $total += $item->get_price() * $item->get_quantity();
                $item_total_shipping += $item->get_shipping() * $item->get_quantity();
            }
            $grand_total = $total + $item_total_shipping;
        }

        return wpsc_number_format_price($grand_total);
    }

	public function get_cart_cpt_id() {
		return $this->post_id_reference;

		// TODO: Old code. need to remove

		//	    $cart_cpt_id = wpsc_get_cart_cpt_id_by_cart_id($this->cart_id);
		//		if (empty($cart_cpt_id) && !is_numeric($cart_cpt_id)){
		//			return false;
		//		}

				//Check if cart_id has value, check if that post exists & is of correct post type
		//        if( get_post_type( $this->post_id_reference ) === $this->post_type && get_post( $this->post_id_reference ) ) {
		//            //Check if this post status is "paid". If it is paid then a new cart ID need to be issued.
		//            $post_id = $this->post_id_reference;
		//            $status = get_post_meta($post_id, 'wpsc_order_status', true);
		//            if (strcasecmp($status, "paid") == 0) {
		//                //This cart transaction already completed. Need to create a new one.
		//                wpsc_log_payment_debug( 'The transaction status for this cart ID (' . $this->cart_id . ') & cart cpt ID (' . $this->post_id_reference . ') is paid. Need to create a new cart ID. It will create a new cart cpt ID when an item is added to the cart.', true);
		//                return false;
		//            }
		//            //Use the cart cpt ID.
		//            return $this->post_id_reference;
		//        }
		//        return false;
	}

	public function set_cart_cpt_id( $post_id ) {
		$this->post_id_reference = $post_id;
	}

	public function get_cart_id() {
		return $this->cart_id;
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
		if ( $this->get_cart_cpt_id() ) {
			$products = get_post_meta( $this->get_cart_cpt_id(), 'wpsc_cart_items', true );

            if (!is_array($products) || count($products) == 0) {
                return false;
            }

            return $products;
        }

        return false;
    }

	public function cart_not_empty() {
		$count = 0;
		if ( $this->get_items() ) {
			foreach ( $this->get_items() as $item ) {
				$count ++;
			}

			return $count;
		} else {
			return 0;
		}
	}

    public function set_items($items) {
        $this->items = $items;
        $this->save_items();

        // Save cart post meta.
        $this->save_cart_to_postmeta();
    }

    public function set_cart_custom_values($custom_val_string) {
        $this->cart_custom_values = $custom_val_string;
    }

    public function get_cart_custom_values() {
        return $this->cart_custom_values;
    }


    /* 
     * This is used to keep track of the number of carts divs on the page. Used to generate unique IDs for the cart divs.
     */
    public function increment_on_page_carts_div_count() {
        $this->on_page_carts_div_count++;
    }
        
    public function get_on_page_carts_div_count() {
        return $this->on_page_carts_div_count;
    }

    /* 
     * This function will return the next cart div ID to be used in the page.
     * It will also store all the cart div IDs (used on the page) in a static variable.
     * Usefule when there are multiple carts on the same page.
     */
    public function get_next_on_page_cart_div_id() {
        //Increment the on_page_carts_div_count and return the next cart div ID
        $this->increment_on_page_carts_div_count();
        $next_cart_div_id = 'wpsc_shopping_cart_' . $this->get_on_page_carts_div_count();
        //Store the cart div ID in a static variable
        self::$on_page_cart_div_ids[] = $next_cart_div_id;
        //Return the cart div ID that can be used in the page.
        return $next_cart_div_id;
    }

	public function get_cart_action_msg() {
		if ( $this->get_cart_cpt_id() ) {
			$transient_key = 'wpspsc_cart_action_msg' . $this->get_cart_cpt_id();
			//$expiration = 3600; // 1 hour
			$msg = get_transient( $transient_key );

			return $msg;
		}
	}

	public function set_cart_action_msg( $msg ) {
		if ( $this->get_cart_cpt_id() ) {
			$transient_key = 'wpspsc_cart_action_msg' . $this->get_cart_cpt_id();
			$expiration    = 3600; // 1 hour
			set_transient( $transient_key, $msg, $expiration );
		}
	}

	public function clear_cart_action_msg() {
		if ( $this->get_cart_cpt_id() ) {
			$transient_key = 'wpspsc_cart_action_msg' . $this->get_cart_cpt_id();
			delete_transient( $transient_key );
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

        return wpsc_number_format_price($grand_total);
    }

    /**
     * Checks if all the items in the cart are digital.
     *
     * @return boolean TRUE if all items are digital. FALSE otherwise.
     */
    public function all_cart_items_digital() {
        $cart_items = $this->get_items();
        if( empty( $cart_items ) ) {
            return false;
        }
        //Check if all the items in the cart are digital
        foreach ( $cart_items as $item ) {
            if ( !($item->is_digital_item()) ) {
                //Found a physical item. So return false.
                return false;
            }
		}

        //All items are digital. So return true.
        return true;
    }
}
