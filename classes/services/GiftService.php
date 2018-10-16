<?php

class GiftService extends Services
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
        $this->table = "amely_gifts";
    }

	public function save($data)
	{
		$gift = new Gift();
		$gift->data->owner_id = $data['owner_id'];
		$gift->data->type = $data['type'];
		$gift->data->from_id = $data['from_id'];
		$gift->data->from_type = $data['from_type'];
		$gift->data->to_id = $data['to_id'];
		$gift->data->to_type = $data['to_type'];
		$gift->data->item_id = $data['item_id'];
		$gift->data->status = 0;
		$gift_id = $gift->insert(true);
		if ($gift_id) {
			$item = new Item();
			$item->data->status = 0;
			$item->where = "id = {$data['item_id']}";
			return $item->update();
		}
		return false;
	}

    public function getGiftByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$gift = $this->getGift($conditions);
		if (!$gift) return false;
		return $gift;
    }

    public function getGift($conditions)
	{
		$offer = $this->searchObject($conditions, 0, 1);
		if (!$offer) return false;
		return $offer;
	}

	public function getGifts($conditions, $offset = 0, $limit = 10)
	{
		$offers = $this->searchObject($conditions, $offset, $limit);
		if (!$offers) return false;
		return array_values($offers);
	}
}