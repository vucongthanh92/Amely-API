<?php

class Notification extends Object
{
    private $id;
    private $owner_id;
    private $type;
    private $title;
    private $description;
    private $time_created;
    private $from_id;
    private $from_type;
    private $subject_id;
    private $item_id;
    private $viewed;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_notifications";
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