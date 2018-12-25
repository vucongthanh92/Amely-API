<?php

/**
* 
*/
class PromotionService extends Services
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
        $this->table = "amely_promotions";
    }

    public function save($data)
    {
        $promotion = new Promotion();
        foreach ($data as $key => $value) {
        	$promotion->data->$key = $value;
        }
        $promotion->data->type = 'shop';

        if ($data['id']) {
        	$promotion->where = "id = {$data['id']}";
        	return $promotion->upadte(true);
        } else {
        	return $promotion->insert(true);
        }
    }

    public function getPromotionById($promotion_id)
    {
    	$conditions[] = [
    		'key' => 'id',
    		'value' => "= {$promotion_id}",
    		'operation' => ''
    	];
    	$promotion = $this->getPromotion($conditions);
    	if (!$promotion) return false;
    	return $promotion;
    }

    public function getPromotion($conditions)
	{
		$promotion = $this->searchObject($conditions, 0, 1);
		if (!$promotion) return false;
		return $promotion;
	}

	public function getPromotions($conditions, $offset = 0, $limit = 10)
	{
		$promotions = $this->searchObject($conditions, $offset, $limit);
		if (!$promotions) return false;
		return array_values($promotions);
	}
}