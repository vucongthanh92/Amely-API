<?php
/**
* 
*/
class SlimDelete extends SlimDatabase
{
	protected static $instance = null;

	public static function getInstance()
	{
		if (self::$instance == null) {
			self::$instance =  new self();
		}
		return self::$instance;
	}

	public function friend($from, $to)
	{
		$params['from'] = "amely_relationships";
		$params['wheres'][] = "(type = 'friend:request' AND relation_from = '{$from}' AND relation_to = '{$to}')";
		$params['wheres'][] = "OR (type = 'friend:request' AND relation_from = '{$to}' AND relation_to = '{$from}')";

		return $this->delete($params);
	}
}