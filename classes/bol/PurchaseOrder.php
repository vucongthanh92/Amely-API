<?php

class PurchaseOrder extends Object
{
    private $owner_id;
    private $type;
    private $time_created;
    private $title;
    private $description;
    private $payment_method;
    private $shipping_method;
    private $status;
    private $payment_fullname;
    private $payment_phone;
    private $payment_address;
    private $payment_province;
    private $payment_district;
    private $payment_ward;
    private $note;
    private $order_items_snapshot;
    private $total;
    private $quantity;
    private $shipping_fullname;
    private $shipping_phone;
    private $shipping_address;
    private $shipping_province;
    private $shipping_district;
    private $shipping_ward;
    private $shipping_note;
    private $shipping_fee;
    
	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_purchase_order";
	}

	public function __set($key, $value)
    {
        if (property_exists($this, $key)) {
        	$this->$key = $value;
        }
    }

    public function __get($key)
    {
        if (property_exists($this, $key)) {
            return $this->$key;
        }
    }
}