<?php

class Like extends Object
{
    private $subject_id;
    private $guid;
    private $type;

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