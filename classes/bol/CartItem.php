<?php

class CartItem extends Object
{
    private $owner_id;
    private $type;
    private $time_created;
    private $product_id;
    private $store_id;
    private $quantity;
    private $redeem_quantity;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_cart_items";
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