<?php
/**
    * is_special
        0 binh thuong
        1 voucher
        2 ticket

    * expiry_type
        0 ko han dung
        1 tinh theo luc mua duration
        2 tinh theo begin end

    * featured
    	0 ko noi bat
    	1 noi bat

    * approved
    	0 cho duyet
    	>0 da tuyet (thoi gian phe duyet)

    * enabled
    	0 tat
    	1 mo

    * status
    	0 tat
    	1 mo
    	2 da xoa
*/
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
	private $status;


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