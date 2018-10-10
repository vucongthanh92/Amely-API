<?php

class SupplyOrder extends Object
{
    private $owner_id;
    private $type;
    private $time_created;
    private $title;
    private $description;
    private $status;
    private $store_id;
    private $shipping_fee;
    private $order_item_snapshot;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_supply_order";
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