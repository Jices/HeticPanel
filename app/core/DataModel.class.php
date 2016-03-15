<?php

abstract class DataModel extends Model {

    const DEPENDENCIES = ['Database'];

    const TABLE = null;

    protected $id = 0;
    protected $properties = null;

    public function fromId($id) {
        return $this->fromProperty('id', $id);
    }

    public function fromProperty($key, $value) {
        // Find model with that property
        $rows = $this->database->all($this->getTable(), [
            $key => $value,
        ]);

        if (count($rows) === 1) {
            $this->set($rows[0]);
            $this->id = intval($rows[0]['id']);
            return true;
        }

        return false;
    }

    public function exists() {
        return $this->id > 0;
    }

    public function set($key, $value = null) {
        if (is_array($key)) {

            // Iterate over array
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }

        } else if (isset($this->properties[$key]) and isset($value)) {

            // Set properties
            $this->setProperty($key, $value);

        }
    }

    protected function setProperty($key, $value) {
        if (is_array($this->properties[$key])) {
            $this->properties[$key] = explode(',', $value);
        } else if ($value instanceof DataModel) {
            $this->properties[$key] = $value->getId();
        } else {
            $this->properties[$key] = $value;
        }
    }

    public function equals($other) {
        $otherId = $other instanceof DataModel ? $other->getId() : $other;
        return intval($this->id) === intval($otherId);
    }

    public function getId() {
        // Because id can't be changed
        return $this->id;
    }

    public function get($key) {
        return isset($this->properties[$key]) ? $this->properties[$key] : null;
    }

    public function save() {
        if ($this->exists()) {
            $this->database->update($this->getTable(), $this->serialize(), [
                'id' => $this->getId()
            ]);
        }
    }

    protected function getTable() {
        if (!empty(static::TABLE)) {
            return static::TABLE;
        }

        $class = get_class($this);
        if (lcfirst($class) === strtolower($class)) {
            return strtolower($class) . 's';
        }

        throw new Exception('Don\'t know in what table "' .$class . '" should be stored');
    }

    protected function serialize() {
        $properties = $this->properties;
        foreach ($properties as $key => $value) {
            if (is_array($value)) {
                $first = true;
                $string = '';
                foreach ($value as $k => $v) {
                    if ($first) $first = false;
                    else $string .= ',';

                    if ($v instanceof DataModel) $string .= $v->getId();
                    else $string .= $v;
                }
                $properties[$key] = $string;
                // $properties[$key] = implode(',', $value);
            }
        }
        return $properties;
    }

    protected function make(array $properties) {
        $this->set($properties);
        $this->id = (int) $this->database->insert($this->getTable(), $this->serialize());
        return $this->exists();
    }

    protected function getCollection($class, $id = null) {
        $coll = strtolower($class) . 's';
        if (!class_exists($class)) {
            throw new Exception('Unknown collection class: ' . $class);
        }
        if ($id === null) {
            $ret = [];
            foreach ($this->get($coll) as $value) {
                $obj = Factory::create(new $class);
                $obj->fromId($value);
                $ret[] = $obj;
            }
            return $ret;
        } else {
            if ($id instanceof DataModel) $id = $id->getId();
            foreach ($this->get($coll) as $value) {
                if (intval($value) === intval($id)) {
                    $obj = Factory::create(new $class);
                    $obj->fromId($value);
                    return $obj;
                }
            }
            return null;
        }
    }

    protected function hasCollection($class, $id = null) {
        $coll = strtolower($class) . 's';
        if ($id === null) {
            return !empty($this->get($coll));
        } else {
            if ($id instanceof DataModel) $id = $id->getId();
            foreach ($this->get($coll) as $value) {
                if (intval($value) === intval($id)) {
                    return true;
                }
            }
            return false;
        }
    }

    protected function addCollection($class, $id) {
        $coll = strtolower($class) . 's';
        if ($id instanceof DataModel) $id = $id->getId();
        $this->removeCollection($class, $id);
        $list = $this->get($coll);
        $list[] = $id;
    }

    protected function removeCollection($class, $id) {
        $coll = strtolower($class) . 's';
        if ($id instanceof DataModel) $id = $id->getId();
        $list = $this->get($coll);
        foreach ($list as $key => $value) {
            if (intval($value) === intval($id)) {
                unset($list[$key]);
                return true;
            }
        }
        return false;
    }

    protected function createCollection($class, $list) {
        $coll = [];
        foreach ($list as $item) {
            $obj = Factory::create(new $class);
            $obj->fromId($item['id']);
            $coll[] = $obj;
        }
        return $coll;
    }

}
