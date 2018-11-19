<?php

class Report extends Object
{
    private $owner_id;
    private $type;
    private $time_created;
    private $creator_id;
    private $message;
    private $status;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_report";
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