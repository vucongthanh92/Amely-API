<?php

class ReportService extends Services
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
        $this->table = "amely_report";
    }

    public function save($data)
    {
        $report = new Report();
        foreach ($data as $key => $value) {
            $report->data->$key = $value;
        }
        return $report->insert(true);
    }

    public function getReportByType($input, $type = 'user')
    {
        $conditions = null;
        $conditions[] = [
            'key' => 'type',
            'value' => "= '{$type}'",
            'operation' => ''
        ];
        $conditions[] = [
            'key' => 'owner_id',
            'value' => "= '{$input}'",
            'operation' => 'AND'
        ];
        $report = $this->getReport($conditions);
        if (!$report) return false;
        return $report;
    }

    public function getReport($conditions)
    {
        $report = $this->searchObject($conditions, 0, 1);
        if (!$report) return false;
        return $report;
    }

    public function getReports($conditions, $offset = 0, $limit = 10)
    {
        $reports = $this->searchObject($conditions, $offset, $limit);
        if (!$reports) return false;
        return $reports;
    }

}