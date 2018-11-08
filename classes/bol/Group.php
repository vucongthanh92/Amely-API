<?php

class Group extends Object
{
    private $owner_id;
    private $type;
    private $title;
    private $description;
    private $privacy;
    private $rule;
    private $time_created;
    private $owners_id;
    private $avatar;
    private $cover;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_groups";
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