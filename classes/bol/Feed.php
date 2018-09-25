<?php

class Feed extends Object
{
    private $owner_guid;
    private $type;
    private $time_created;
    private $title;
    private $description;
    private $location;
    private $tag;
    private $mood_guid;
    private $poster_guid;
    private $privacy;
    private $item_type;
    private $share_type;
    private $item_guid;
    private $images;

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