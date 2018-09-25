<?php

class Annotation extends Object
{
    private $owner_guid;
    private $subject_guid;
    private $type;
    private $time_created;
    private $content;
    private $images;

	public function __construct() 
	{	
		parent::__construct();
		$this->table = "amely_annotations";
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