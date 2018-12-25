<?php

/**
* 
*/
class PromotionItemService extends Services
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
        $this->table = "amely_promotion_items";
    }

    public function save($data)
    {
        $promotionItem = new PromotionItem();
        foreach ($data as $key => $value) {
        	$promotionItem->data->$key = $value;
        }
        $promotionItem->data->type = 'promotion';
        $promotionItem->data->currency = 'VND';

        if ($data['id']) {
        	$promotionItem->where = "id = {$data['id']}";
        	$promotionItem->upadte(true);
        } else {
        	return $promotionItem->insert(true);
        }
    }

    public function getPromotionItemById($promotion_item_id)
    {
    	$conditions[] = [
    		'key' => 'id',
    		'value' => "= {$promotion_item_id}",
    		'operation' => ''
    	];

    	$promotion_item = $this->getPromotionItem($conditions);
    	if (!$promotion_item) return false;
    	return $promotion_item;
    }

    public function getPromotionItemsByPromotionId($promotion_id, $offset = 0, $limit = 10)
    {
        $conditions[] = [
            'key' => 'owner_id',
            'value' => "= {$promotion_id}",
            'operation' => ''
        ];
        $promotionItems = $this->getPromotionItems($conditions, $offset, $limit);
        if (!$promotionItems) return false;
        return $promotionItems;

    }

    public function getPromotionItem($conditions)
	{
		$promotionItem = $this->searchObject($conditions, 0, 1);
		if (!$promotionItem) return false;
		return $promotionItem;
	}

	public function getPromotionItems($conditions, $offset = 0, $limit = 10)
	{
		$promotionItems = $this->searchObject($conditions, $offset, $limit);
		if (!$promotionItems) return false;
		return array_values($promotionItems);
	}
}