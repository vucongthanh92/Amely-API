<?php

class DeliveryOrder extends Object
{
    private $owner_id;
    private $type;
    private $time_created;
    private $title;
    private $description;
    private $so_id;
    private $order_items_snapshot;
    private $status;
    private $shipping_fullname;
    private $shipping_phone;
    private $shipping_address;
    private $shipping_province;
    private $shipping_district;
    private $shipping_ward;
    private $shipping_note;
    private $shipping_method;
    private $shipping_fee;
    
	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_delivery_order";
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