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
        $promotionItemService = PromotionItemService::getInstance();
        $productService = ProductService::getInstance();

        $promotion = new Promotion();
        foreach ($data as $key => $value) {
        	$promotion->data->$key = $value;
        }
        $promotion->data->type = 'shop';

        if ($data['id']) {
            $promotion->data->id = $data['id'];
        	$promotion->where = "id = {$data['id']}";
        	if ($promotion->update(true)) {
                if ($data['status'] == 0) {
                    $promotion_items  = $promotionItemService->getPromotionItemsByPromotionId($data['id'], 0, 99999999);
                    if ($promotion_items) {
                        foreach ($promotion_items as $key => $promotion_item) {
                            $productService->generateSnapshotSalePrice($promotion_item->product_id, 0);
                        }
                    }
                }
            }
        } else {
        	return $promotion->insert(true);
        }
    }

    public function updateStatus($promotion_id, $status)
    {
        $promotionItemService = PromotionItemService::getInstance();
        $productService = ProductService::getInstance();

        switch ($status) {
            case 0:
                $promotion = new Promotion();
                $promotion->data->id = $promotion_id;
                $promotion->data->status = 0;
                $promotion->where = "id = {$promotion_id}";
                if ($promotion->update(true)) {
                    $promotion_items  = $promotionItemService->getPromotionItemsByPromotionId($promotion_id, 0, 99999999);
                    if ($promotion_items) {
                        foreach ($promotion_items as $key => $promotion_item) {
                            $productService->generateSnapshotSalePrice($promotion_item->product_id, 0);
                        }
                    }
                }
                break;
            case 1:
                $promotion = new Promotion();
                $promotion->data->id = $promotion_id;
                $promotion->data->status = 1;
                $promotion->where = "id = {$promotion_id}";
                return $promotion->update(true);
                break;
            case 2:
                $promotion = new Promotion();
                $promotion->data->id = $promotion_id;
                $promotion->data->status = 2;
                $promotion->where = "id = {$promotion_id}";
                return $promotion->update(true);
                break;
            default:
                return false;
                break;
        }
        return false;
    }

    public function getPromotionsRuning()
    {
        $current_time = time();

        $conditions[] = [
            'key' => 'status',
            'value' => "= 1",
            'operation' => ''
        ];
        $conditions[] = [
            'key' => '',
            'value' => "(((DATE_FORMAT(from_unixtime(start_time), '%Y-%m-%d') <= DATE_FORMAT(from_unixtime({$current_time}), '%Y-%m-%d')) AND (DATE_FORMAT(from_unixtime(end_time), '%Y-%m-%d') >= DATE_FORMAT(from_unixtime({$current_time}), '%Y-%m-%d')) AND (DATE_FORMAT(from_unixtime(start_time), '%H:%i:%s') <= DATE_FORMAT(from_unixtime({$current_time}), '%H:%i:%s')) AND (DATE_FORMAT(from_unixtime(end_time), '%H:%i:%s') >= DATE_FORMAT(from_unixtime({$current_time}), '%H:%i:%s')) AND time_type = 1) OR ((start_time < {$current_time}) AND (end_time >= {$current_time}) AND time_type = 0))",
            'operation' => 'AND'
        ];

        $promotions = $this->getPromotions($conditions, 0, 99999999);
        if (!$promotions) return false;
        return $promotions;
    }

    public function approved($promotion_id)
    {
        $promotionItemService = PromotionItemService::getInstance();
        $productService = ProductService::getInstance();

        $time = time();

        $promotion = new Promotion();
        $promotion->data->id = $promotion_id;
        $promotion->data->approved = $time;
        $promotion->where = "id = {$promotion_id}";
        if ($promotion->update(true)) {
            $promotion_items  = $promotionItemService->getPromotionItemsByPromotionId($promotion_id, 0, 99999999);
            if ($promotion_items) {
                foreach ($promotion_items as $key => $promotion_item) {
                    $product = $productService->getProductByType($promotion_item->product_id, 'id');

                    if ($promotion_item->percent) {
                        $sale_price = $product->price - ($product->price * $promotion_item->percent / 100);
                    }
                    if ($promotion_item->price) {
                        $sale_price = $product->price - $promotion_item->price;
                    }
                    $productService->generateSnapshotSalePrice($product->id, $sale_price);
                }
            }

            return true;
        }
        return false;
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