<?php

class SubProduct extends Object
{
	private $owner_id;
	private $type;
	private $time_created;
	private $title;
	private $description;
	private $price;
	private $quantity;
	private $sku;
	private $creator_id;
	private $number_sold;
	private $sale_price;
	private $current_snapshot;
	private $approved;
	private $enabled;
	private $images;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_sub_products";
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