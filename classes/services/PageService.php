<?php

/**
* 
*/
class PageService extends Services
{
	protected static $instance = null;

	public static function getInstance()
	{
		if (self::$instance == null) {
			self::$instance =  new self();
		}
		return self::$instance;
	}

	public function __construct() 
	{
        $this->table = "amely_pages";
    }

    public function save()
    {
    	return false;
    }

    public function getPageByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$page = $this->getPage($conditions);
		if (!$page) return false;
		return $page;
    }

    public function getPage($conditions)
	{
		$page = $this->searchObject($conditions, 0, 1);
		if (!$page) return false;
		return $page;
	}

}