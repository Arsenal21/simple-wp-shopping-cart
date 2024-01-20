<?php
class WSPSC_Cart_Item {

    protected $name;
    protected $price;
    protected $price_orig;
    protected $quantity;
    protected $shipping;
    protected $cart_link;
    protected $item_number;
    protected $file_url;
    protected $thumbnail;
    protected $stamp_pdf;

    function __construct() {
        
    }

    public function set_name($name) {
        $this->name = $name;
    }

    public function get_name() {
        return $this->name;
    }

    public function set_price($price) {
        $this->price = $price;
    }

    public function get_price() {
        return $this->price;
    }

    public function set_price_orig($price_orig) {
        $this->price_orig = $price_orig;
    }

    public function get_price_orig() {
        return $this->price_orig;
    }

    public function set_quantity($quantity) {
        $this->quantity = $quantity;
    }

    public function get_quantity() {
        return $this->quantity;
    }

    public function set_shipping($shipping) {
        $this->shipping = $shipping;
    }

    public function get_shipping() {
        return $this->shipping;
    }

    public function set_cart_link($cart_link) {
        $this->cart_link = $cart_link;
    }

    public function get_cart_link() {
        return $this->cart_link;
    }

    public function set_item_number($item_number) {
        $this->item_number = $item_number;
    }

    public function get_item_number() {
        return $this->item_number;
    }

    public function set_file_url($file_url) {
        $this->file_url = $file_url;
    }

    public function get_file_url() {
        return $this->file_url;
    }

    public function set_thumbnail($thumbnail) {
        $this->thumbnail = $thumbnail;
    }

    public function get_thumbnail() {
        return $this->thumbnail;
    }

    public function set_stamp_pdf($stamp_pdf) {
        $this->stamp_pdf = $stamp_pdf;
    }

    public function get_stamp_pdf() {
        return $this->stamp_pdf;
    }

}
