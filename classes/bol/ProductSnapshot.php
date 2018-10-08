<?php

class ProductSnapshot extends Object
{
	private $owner_id;
	private $type;
	private $title;
	private $description;
	private $price;
	private $sku;
	private $creator_id;
	private $sale_price;
	private $images;
	private $code;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_product_snapshot";
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