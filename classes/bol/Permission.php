<?php

class Permission extends Object
{
    private $owner_id;
    private $type;
    private $time_created;
    private $title;
    private $path;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_permission";
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