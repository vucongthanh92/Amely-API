<?php

/**
* 
*/
class CategoryService extends Services
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
        $this->table = "amely_categories";
    }

    public function getCategoryByType($input, $type ='id')
    {
    	$conditions = null;
		$conditions[] = [
			'key' => $type,
			'value' => "= '{$input}'",
			'operation' => ''
		];
		$category = $this->getCategory($conditions);
		if (!$category) return false;
		return $category;
    }

    public function getCategory($conditions)
	{
		$category = $this->searchObject($conditions, 0, 1);
		if (!$category) return false;
		$category = $this->changeStructureInfo($category);
		return $category;
	}

	public function getCategories($conditions, $offset = 0, $limit = 10)
	{
		$categories = $this->searchObject($conditions, $offset, $limit);
		if (!$categories) return false;
		foreach ($categories as $key => $category) {
			$category = $this->changeStructureInfo($category);
			$categories[$key] = $category;
		}
		return array_values($categories);
	}

	private function changeStructureInfo($category)
	{
		$imageService = ImageService::getInstance();
		$category->logo = $imageService->showImage($category->id, $image, 'category', 'medium');
		return $category;
	}
}