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
        $this->table = "amely_redeem_code";
    }

    public function save(array $data)
    {
    	$redeem = new Redeem();
    	$redeem->data->owner_id = $data['owner_id'];
		$redeem->data->type = 'user';
		$redeem->data->item_id = $data['item_id'];
		$redeem->data->creator_id = $data['creator_id'];
		$redeem->data->code = $data['code'];
		$redeem->data->status = $data['status'];
		return response($redeem->insert(true));
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