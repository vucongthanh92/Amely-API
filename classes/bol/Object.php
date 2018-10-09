<?php

class Object extends SlimDatabase
{
	private $token;
	protected $id;
	protected $table;
    protected $data;
	protected $where;

	public function __construct() 
	{
		$this->data = new stdClass;
        $this->data->time_created = time();
	}

	public function __set($key, $value)
    {
        if (property_exists($this, $key)) {
        	$this->$key = $value;
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

    public function insert($show_id = false)
    {
        $this->checkExistKey();
        if ($this->id) return false;
        return $this->saveTable($this, $this->table, "insert", $show_id);
    }

    public function update($show_id = false)
	{
        if (!$this->where) return false;
        if (array_key_exists("time_created", $this->data)) {
            unset($this->data->time_created);
        }
		return $this->saveTable($this, $this->table, "update", $show_id);
	}

    public function delete()
    {
        if (!$this->where) return false;
        if (array_key_exists("time_created", $this->data)) {
            unset($this->data->time_created);
        }
        return $this->saveTable($this, $this->table, "delete", false);
    }
	
}