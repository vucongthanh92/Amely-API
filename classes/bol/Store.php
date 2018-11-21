<?php

class Store extends Object
{
	private $owner_id;
	private $type;
	private $time_created;
	private $title;
	private $description;
	private $lat;
	private $lng;
	private $store_phone;
	private $store_address;
	private $store_province;
	private $store_district;
	private $store_ward;
	private $status;
	private $approved;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_stores";
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