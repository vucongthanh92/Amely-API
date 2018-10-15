<?php

class Product extends Object
{
	private $owner_id;
	private $type;
	private $time_created;
	private $title;
	private $description;
	private $sku;
	private $price;
	private $snapshot_id;
	private $model;
	private $tag;
	private $number_sold;
	private $tax;
	private $friendly_url;
	private $weight;
	private $expiry_type;
	private $currency;
	private $origin;
	private $product_order;
	private $duration;
	private $storage_duration;
	private $is_special;
	private $product_group;
	private $creator_id;
	private $custom_attributes;
	private $download;
	private $featured;
	private $begin_day;
	private $end_day;
	private $manufacturer;
	private $sale_price;
	private $unit;
	private $approved;
	private $enabled;
	private $voucher_category;
	private $ticket_category;
	private $shop_category;
	private $market_category;
	private $category;
	private $adjourn_price;
	private $images;
	private $parent_id;


	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_products";
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