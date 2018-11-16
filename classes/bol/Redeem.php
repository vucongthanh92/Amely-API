<?php

class Redeem extends Object
{
    private $owner_id;
    private $type;
    private $time_created;
    private $item_id;
    private $creator_id;
    private $code;
    private $store_id;
    private $status;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_redeem";
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