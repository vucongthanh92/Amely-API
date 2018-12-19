<?php

class Rule extends Object
{
    private $time_created;
    private $title;
    private $creator_id;
    private $status;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_rule";
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