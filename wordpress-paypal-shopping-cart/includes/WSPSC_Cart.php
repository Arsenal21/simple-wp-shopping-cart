<?php
class WSPSC_Cart
{
    private $items = array();
    private $item;
    private $cart_id = 0;

    public function __construct()
    {
        $this->cart_id = isset($_COOKIE['simple_cart_id']) ? $_COOKIE['simple_cart_id'] : 0;
    }

    /**
     * Create a new cart order in the database and set a cookie with the cart ID.
     *
     * This method creates a new cart order post in the 'wpsc_cart_orders' post type with the post status set to 'trash',
     * sets a cookie with the cart ID, and updates the cart object with the new ID. It also updates the cart order post
     * with the current cart ID as the post title and the order status as 'In Progress'. If any items have been added to the
     * cart, they will be saved to the database as well.
     *
     * @return void
     */
    public function create_cart()
    {
        // Create a new order
        $wpsc_order = array(
            'post_title'    => 'WPSC Cart Order',
            'post_type'     => 'wpsc_cart_orders',
            'post_content'  => '',
            'post_status'   => 'trash',
        );
        // Insert the post into the database
        $post_id  = wp_insert_post($wpsc_order);

        if ($post_id) {
            $cookie_expiration = time() + (86400 * 30); // 30 days
            setcookie('simple_cart_id', $post_id, $cookie_expiration, '/');
            $this->set_cart_id($post_id);

            $updated_wpsc_order = array(
                'ID'             => $this->get_cart_id(),
                'post_title'    => $this->get_cart_id(),
                'post_type'     => 'wpsc_cart_orders',
            );
            wp_update_post($updated_wpsc_order);
            $status = "In Progress";

            update_post_meta($this->get_cart_id(), 'wpsc_order_status', $status);

            //save items if added into cart
            $this->save_items();
        }
    }

    /**
     * Add / update items to the cart.
     *
     * Set the specified items in the cart.
     *
     * @param array $items An array of items to add to the cart.
     * @return void
     */
    public function add_items($items)
    {
        $this->set_items($items);
    }


    /**
     * Save the cart items to the database.
     *
     * If the cart ID has been set, update the 'wpsc_cart_items' post meta with
     * the current items in the cart.
     *
     * @return void
     */
    public function save_items()
    {
        if ($this->get_cart_id()) {
            update_post_meta($this->get_cart_id(), 'wpsc_cart_items', $this->items);
        }
    }



    public function reset_cart()
    {
        $collection_obj = WPSPSC_Coupons_Collection::get_instance();

        if (sizeof($this->get_items()) == 0) {
            return;
        }

        $this->set_items(array());

        $this->clear_cart_action_msg();
        $collection_obj->clear_discount_applied_once($this->get_cart_id());        
        $collection_obj->clear_applied_coupon_code($this->get_cart_id());

        //set cookie in past to expire it
        setcookie('simple_cart_id', '', time() - 3600, '/');
        $this->set_cart_id(0);
    }

    public function get_total_cart_qty()
    {
        $total_items = 0;
        if (!$this->get_items()) {
            return $total_items;
        }
        foreach ($this->get_items() as $item) {
            $total_items += $item->get_quantity();
        }
        return $total_items;
    }

    public function get_total_cart_sub_total()
    {
        $sub_total = 0;
        if (!$this->get_items()) {
            return $sub_total;
        }
        foreach ($this->get_items() as $item) {
            $sub_total += $item->get_price() * $item->get_quantity();
        }
        return $sub_total;
    }

    public function simple_cart_total()
    {
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

    public function get_cart_id()
    {
        if ($this->cart_id == 0) {
            return false;
        }

        return $this->cart_id;
    }

    public function set_cart_id($cart_id)
    {
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
    public function get_items()
    {

        if ($this->get_cart_id()) {
            $products = get_post_meta($this->get_cart_id(), 'wpsc_cart_items', true);

            if (!is_array($products) || count($products) == 0) {
                return false;
            }

            return $products;
        }

        return false;
    }

    public function cart_not_empty()
    {
        $count = 0;
        if ($this->get_items()) {
            foreach ($this->get_items() as $item)
                $count++;
            return $count;
        } else
            return 0;
    }

    public function set_items($items)
    {
        $this->items = $items;
        $this->save_items();
    }

    public function get_cart_action_msg()
    {
        if ($this->get_cart_id()) {
            $transient_key = 'wpspsc_cart_action_msg' . $this->get_cart_id();
            $expiration = 3600; // 1 hour
            $msg = get_transient($transient_key);
            return $msg;
        }
    }

    public function set_cart_action_msg($msg)
    {
        if ($this->get_cart_id()) {
            $transient_key = 'wpspsc_cart_action_msg' . $this->get_cart_id();
            $expiration = 3600; // 1 hour
            set_transient($transient_key, $msg, $expiration);
        }
    }

    public function clear_cart_action_msg()
    {
        if ($this->get_cart_id()) {
            $transient_key = 'wpspsc_cart_action_msg' . $this->get_cart_id();
            delete_transient($transient_key);
        }
    }
}
