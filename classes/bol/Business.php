<?php

class Business extends Object
{
    private $owner_guid;
    private $type;
    private $time_created;
    private $title;
    private $description;
    private $subtype;
    private $category;
    private $website;
    private $phone;
    private $address;
    private $inventory_status;
    private $avatar;
    private $cover;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_business_pages";
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