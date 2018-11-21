<?php

class SiteSetting extends Object
{
    private $time_created;
    private $title;
    private $name;
    private $value;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_site_settings";
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