<?php

/**
* 
*/
class AddressService extends Services
{
	protected static $instance = null;
	private $wards;
	private $provinces;
	private $districts;

	public static function getInstance()
	{
		if (self::$instance == null) {
			self::$instance =  new self();
		}
		return self::$instance;
	}

	public function __construct() 
	{
        
    }

    public function getAddress($id, $type)
    {
        $table = "amely_".$type.'s';
        $this->table = $table;

        $conditions[] = null;
        $key = $type."id";
        $conditions[] = [
            'key' => $key,
            'value' => "= '{$id}'",
            'operation' => ''
        ];
        $addr = $this->searchObject($conditions, 0, 1, false);
        return $addr;
    }

    public function getWards($conditions, $offset, $limit)
    {   
        $this->table = "amely_wards";
        
    	$wards = $this->searchObject($conditions, $offset, $limit);
    	if (!$wards) return false;
    	if ($limit == 1) {
    		return $wards[0];
    	}
    	return $wards;
    }

    public function getProvinces($conditions, $offset, $limit)
    {
        $this->table = "amely_provinces";        

    	$provinces = $this->searchObject($conditions, $offset, $limit);
    	if (!$provinces) return false;
    	if ($limit == 1) {
    		return $provinces[0];
    	}
    	return $provinces;
    }

    public function getDistricts($conditions, $offset, $limit)
    {
        $this->table = "amely_districts";

    	$districts = $this->searchObject($conditions, $offset, $limit);
    	if (!$districts) return false;
    	if ($limit == 1) {
    		return $districts[0];
    	}
    	return $districts;
    }
}