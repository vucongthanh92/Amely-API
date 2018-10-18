<?php

/**
* 
*/
class SiteSettingService extends Services
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
        $this->table = "amely_site_settings";
    }

	public function getSiteSettings($conditions, $offset = 0, $limit = 10)
	{
		$settings = $this->searchObject($conditions, $offset, $limit);
		if (!$settings) return false;
		
		return array_values($settings);
	}
}