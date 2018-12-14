<?php

/**
* 
*/
class ProductStoreService extends Services
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
        $this->table = "amely_product_store";
    }

    public function save($data)
    {
        $data['type'] = 'shop';
        $ps = $this->checkQuantityInStore($data['product_id'], $data['store_id']);
        if ($ps) {
            $productStore = new ProductStore();
            $productStore->data->quantity = $data['quantity'];
            $productStore->data->id = $ps->id;
            $productStore->where = "store_id = {$data['store_id']} AND product_id = {$data['product_id']}";
            return $productStore->update(true);
        } else {
            $productStore = new ProductStore();
            foreach ($data as $key => $value) {
                $productStore->data->$key = $value;
            }
            return $productStore->insert(true);
        }
        return true;
    }

    public function updateQuantity($product_id, $store_id, $quantity)
    {
        $store_quantity = $this->checkQuantityInStore($product_id, $store_id);
        if (!$store_quantity) return false;
        $update_quantity = $store_quantity->quantity - $quantity;
        if ($update_quantity < 0) return false;
        $store_quantity = object_cast("ProductStore", $store_quantity);
        $store_quantity->data->quantity = $update_quantity;
        $store_quantity->data->id = $store_quantity->id;
        $store_quantity->where = "id = {$store_quantity->id}";
        return $store_quantity->update(true);
    }

    public function showProduct($product_id) 
    {
        $conditions = null;
        $conditions[] = [
            'key' => 'product_id',
            'value' => "= '{$product_id}'",
            'operation' => ''
        ];
        $conditions[] = [
            'key' => 'quantity',
            'value' => "> 0",
            'operation' => 'AND'
        ];
        $store_quantity = $this->getQuantityProduct($conditions);
        if (!$store_quantity) return false;
        return $store_quantity;
    }

    public function checkQuantityInStore($product_id, $store_id)
    {
    	$conditions = null;
    	$conditions[] = [
    		'key' => 'product_id',
    		'value' => "= '{$product_id}'",
    		'operation' => ''
    	];
    	$conditions[] = [
    		'key' => 'store_id',
    		'value' => "= '{$store_id}'",
    		'operation' => 'AND'
    	];
    	$store_quantity = $this->getQuantityProduct($conditions);
		if (!$store_quantity) return false;
		return $store_quantity;
    }

    public function getQuantityByType($input, $type = 'id', $offset = 0, $limit = 10)
    {
    	$conditions = null;
    	$conditions[] = [
    		'key' => $type,
    		'value' => "= '{$input}'",
    		'operation' => ''
    	];
    	$store_quantity = $this->getQuantityProducts($conditions, $offset, $limit);
		if (!$store_quantity) return false;
		return $store_quantity;
    }

    public function getQuantityProduct($conditions)
	{
		$product = $this->searchObject($conditions, 0, 1);
		if (!$product) return false;
		return $product;
	}

	public function getQuantityProducts($conditions, $offset = 0, $limit = 10)
	{
		$products = $this->searchObject($conditions, $offset, $limit);
		if (!$products) return false;
		return array_values($products);
	}
}
