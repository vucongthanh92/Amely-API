<?php

class Relationship extends Object
{
	private $relation_from;
	private $relation_to;
    private $type;
	private $time_created;

	public function __construct() 
	{
		parent::__construct();
        $this->table = "amely_relationships";
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