<?php

class ProductStore extends Object
{
    private $owner_id;
    private $type;
    private $time_created;
    private $store_id;
    private $product_id;
    private $quantity;
    private $creator_id;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_product_store";
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