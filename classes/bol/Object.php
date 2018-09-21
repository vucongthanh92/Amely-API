<?php

class Object extends SlimDatabase
{
	private $token;
	protected $id;
	protected $table;
	protected $data;

	protected $insert;
	protected $update;
	protected $where;

	public function __construct() 
	{
		parent::__construct();
		$this->data = new stdClass;
		$this->insert = new stdClass;
		$this->update = new stdClass;
	}

	public function __set($key, $value)
    {
        if (property_exists($this, $key)) {
        	$this->insert->$key = $value;
        }
    }

    public function __get($key)
    {
        if (property_exists($this, $key)) {
            return $this->$key;
        }
    }

    public function checkExistKey()
    {
    	$reflection = new ReflectionClass($this);
        $vars = $reflection->getProperties(ReflectionProperty::IS_PRIVATE);
        $vars = array_map(create_function('$o', 'return $o->name;'), $vars);
        foreach ($this->data as $key => $value) {
        	if (!in_array($key, $vars)) unset($this->data->$key);
        }
        return $this;
    }

    public function save($show_id = false)
	{
		$this->checkExistKey();
		
		if ($this->id) {
			$this->where = "id = {$this->id}";
			return $this->db->saveTable($this, $this->table, "update", $show_id);
		} else {
			return $this->db->saveTable($this, $this->table, "insert", $show_id);
		}
	}
	
}