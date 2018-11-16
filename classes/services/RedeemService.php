<?php

/**
* 
*/
class RedeemService extends Services
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
        $this->table = "amely_redeem";
    }

    public function save(array $data)
    {
    	$redeem = new Redeem();
    	foreach ($data as $key => $value) {
    		$redeem->data->$key = $value;
    	}
		$redeem->data->type = 'user';
		$redeem_id = $redeem->insert(true);
		if ($redeem_id) {
			$itemService = ItemService::getInstance();
			$itemService->updateStatus($data['item_id'], 2);
			return $redeem_id;
		}
		return false;
    }

    public function getRedeemByType($input, $type = 'id')
    {
    	$conditions[] = [
    		'key' => $type,
    		'value' => "= {$input}",
    		'operation' => ''
    	];
    	$redeem = $this->getRedeem($conditions);
    	return $redeem;
    }

    public function getRedeem($conditions)
	{
		$redeem = $this->searchObject($conditions, 0, 1);
		if (!$redeem) return false;
		return $redeem;
	}

	public function getRedeems($conditions, $offset = 0, $limit = 10)
	{
		$redeems = $this->searchObject($conditions, $offset, $limit);
		if (!$redeems) return false;
		return array_values($redeems);
	}
}