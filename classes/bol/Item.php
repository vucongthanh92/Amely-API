<?php

class Item extends Object
{
    private $owner_id;
    private $type;
    private $time_created;
    private $title;
    private $description;
    private $quantity;
    private $snapshot;
    private $store_id;
    private $price;
    private $expiry_type;
    private $is_special;
    private $stored_end;
    private $end_day;
    private $so_id;
    private $wishlist;
    private $givelist;
    private $status;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_items";
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