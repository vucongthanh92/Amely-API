<?php

/**
* 
*/
class ProgressbarService extends Services
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
      $this->table = "amely_progressbar";
  }

  public function save($data)
  {
  	$progressbar = new Progressbar();
  	foreach ($data as $key => $value) {
  		$progressbar->data->$key = $value;
  	}
  	return $progressbar->insert(true);
  }

  public function updateNumber($id, $inserted, $updated, $error, $number, $status)
  {
    $progressbar = new Progressbar();
    $progressbar->data->id = $id;
    $progressbar->data->inserted = $inserted;
    $progressbar->data->updated = $updated;
    $progressbar->data->error = $error;
    $progressbar->data->number = $number;
    $progressbar->data->status = $status;
    $progressbar->where = "id = {$id}";
    return $progressbar->update(true);
  }

 	public function getInfoByCode($code)
 	{
 		$conditions[] = [
 			'key' => 'code',
 			'value' => "= '{$code}'",
 			'operation' => ''
 		];

 		$progressbar = $this->getInfo($conditions);
 		if (!$progressbar) return false;
 		return $progressbar;
 	}

  public function getInfo($conditions)
	{
		$progressbar = $this->searchObject($conditions, 0, 1);
		if (!$progressbar) return false;
		return $progressbar;
	}

  public function getProgress($conditions, $offset = 0, $limit = 10)
  {
    $progressbars = $this->searchObject($conditions, $offset, $limit);
    if (!$progressbars) return false;
    return $progressbars;
  }
}