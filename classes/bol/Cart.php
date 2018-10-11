<?php

/**
* status
    0 cho thanh toan
    1 da thanh toan
    2 huy
*/
class Cart extends Object
{
    private $owner_id;
    private $type;
    private $time_created;
    private $creator_id;
    private $status;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_cart";
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