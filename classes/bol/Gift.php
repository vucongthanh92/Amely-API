<?php

class Gift extends Object
{
    private $time_created;
    private $from_id;
    private $from_type;
    private $to_id;
    private $to_type;
    private $item_id;
    private $status;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_gifts";
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