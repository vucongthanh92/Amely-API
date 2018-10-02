<?php

class Shop extends Object
{
	private $owner_id;
	private $type;
	private $time_created;
	private $title;
	private $description;
	private $shop_bidn;
	private $friendly_url;
	private $shipping_method;
	private $owner_name;
	private $owner_phone;
	private $owner_address;
	private $owner_province;
	private $owner_district;
	private $owner_ward;
	private $owner_ssn;
	private $status;
	private $introduce;
	private $policy;
	private $contact;
	private $avatar;
	private $cover;
	private $files_scan;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_shops";
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