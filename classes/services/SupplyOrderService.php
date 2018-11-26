<?php

/**
* 
*/
class SupplyOrderService extends Services
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
        $this->table = "amely_supply_order";
    }

    public function save($data)
    {
    	$so = new SupplyOrder();
    	foreach ($data as $key => $value) {
    		$so->data->$key = $value;
    	}
    	$so_id = $so->insert(true);
    	$userService = UserService::getInstance();
    	$user = $userService->getUserByType($data['store_id'], 'chain_store');
    	
    	

    }

    public function getSOByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$so = $this->getSO($conditions);
		if (!$so) return false;
		return $so;
    }

    public function getSO($conditions)
	{
		$so = $this->searchObject($conditions, 0, 1);
		if (!$so) return false;
		$so = $this->changeStructureInfo($so);
		return $so;
	}

	public function getSOs($conditions, $offset = 0, $limit = 10)
	{
		$sos = $this->searchObject($conditions, $offset, $limit);
		if (!$sos) return false;
		foreach ($sos as $key => $so) {
			$so = $this->changeStructureInfo($so);
			$sos[$key] = $so;
		}
		return array_values($sos);
	}

	private function changeStructureInfo($so)
	{
		$so->display_order = convertPrefixOrder("HD", $so->id, $so->time_created);
		return $so;
	}

}