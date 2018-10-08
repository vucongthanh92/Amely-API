<?php

class ProductDetailSnapshot extends Object
{
    private $owner_id;
    private $type;
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
    private $unit;
    private $adjourn_price;
    private $code;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_pdetail_snapshot";
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