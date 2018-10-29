<?php
/**
    * type (string)
        feed
        shop
        product

*/

class Like extends Object
{
    private $owner_id;
    private $type;
    private $time_created;
    private $creator_id;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_likes";
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