<?php

class Snapshot extends Object
{
	private $owner_id;
	private $type;
	private $time_created;
	private $title;
	private $description;
	private $sku;
	private $model;
	private $tax;
	private $weight;
	private $expiry_type;
	private $currency;
	private $origin;
	private $storage_duration;
	private $is_special;
	private $product_group;
	private $creator_id;
	private $custom_attributes;
	private $duration;
	private $begin_day;
	private $end_day;
	private $manufacturer;
	private $price;
	private $sale_price;
	private $unit;
	private $adjourn_price;
	private $code;
	private $parent_id;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_snapshots";
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