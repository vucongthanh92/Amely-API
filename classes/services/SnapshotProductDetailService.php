<?php

/**
* 
*/
class SnapshotProductDetailService extends Services
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
        $this->table = "amely_pdetail_snapshot";
    }

    public function getSnapshotByType($input, $type)
    {
        $conditions = null;
        $conditions[] = [
            'key' => $type,
            'value' => "= '{$input}'",
            'operation' => ''
        ];
        $snapshot = $this->getSnapshot($conditions, 0, 1);
        if (!$snapshot) return false;
        return $snapshot;
    }

    public function getSnapshotsByType($input, $type, $offset = 0, $limit = 10)
    {
        $conditions = null;
        $conditions[] = [
            'key' => $type,
            'value' => "IN ({$input})",
            'operation' => ''
        ];
        $snapshots = $this->getSnapshots($conditions, $offset, $limit);
        if (!$snapshots) return false;
        return $snapshots;
    }
    
    public function getSnapshot($conditions)
    {
    	$snapshot = $this->searchObject($conditions, 0, 1);
		if (!$snapshot) return false;
		$snapshot = $this->changeStructureInfo($snapshot);
		return $snapshot;
    }

    public function getSnapshots($conditions, $offset = 0, $limit = 10)
    {
    	$snapshots = $this->searchObject($conditions, $offset, $limit);
		if (!$snapshots) return false;
		foreach ($snapshots as $key => $snapshot) {
			$snapshot = $this->changeStructureInfo($snapshot);
			$snapshots[$key] = $snapshot;
		}
		return $snapshots;
    }

    private function changeStructureInfo($snapshot)
	{
		
        return $snapshot;
    }

}