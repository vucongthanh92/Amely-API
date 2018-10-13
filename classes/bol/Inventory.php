<?php

class Inventory extends Object
{
    private $owner_id;
    private $type;
    private $creator_id;
    private $time_created;
    private $salt;
    private $password;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_inventories";
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