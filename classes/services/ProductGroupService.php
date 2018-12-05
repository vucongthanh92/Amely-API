<?php

/**
* 
*/
class ProductGroupService extends Services
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
        $this->table = "amely_product_group";
    }

    public function save($data)
    {
        $productGroup = new ProductGroup();
        foreach ($data as $key => $value) {
        	$productGroup->data->$key = $value;
        }
        $productGroup->data->type = 'user';
        $productGroup->data->currency = 'VND';

        return $productGroup->insert(true);
    }

    public function getProductGroupByType($input, $type = 'id')
	{
		$conditions[] = [
    		'key' => $type,
    		'value' => "= '{$input}'",
    		'operation' => ''
    	];

		$pg = $this->getProductGroup($conditions);
		if (!$pg) return false;
		return $pg;
	}

    public function getProductGroup($conditions)
	{
		$pg = $this->searchObject($conditions, 0, 1);
		if (!$pg) return false;
		$pg = $this->changeStructureInfo($pg);
		return $pg;
	}

	public function getProductGroups($conditions, $offset = 0, $limit = 10)
	{
		$pgs = $this->searchObject($conditions, $offset, $limit);
		if (!$pgs) return false;
		foreach ($pgs as $key => $pg) {
			$pg = $this->changeStructureInfo($pg);
			$pgs[$key] = $pg;
		}
		return array_values($pgs);
	}

	private function changeStructureInfo($pg)
	{
		return $pg;
	}

}