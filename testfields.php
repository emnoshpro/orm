<?php

require_once('./init.php');

class Column
{
    const INTEGER = 1;
    const VARCHAR = 2;
    const CHAR = 3;
    const DATE_TIME = 4;
    const ID = 5;

    public $callback = NULL;
    public $type = NULL;
    public $options = NULL;
    public $pk = false;

    public function __construct(array $data, $field_type = self::INTEGER)
    {
        // print_r($data);
        $this->type = $field_type;
        $this->callback = $data[0];
        $this->options = $data[1];
    }

    public static function addInteger($options)
    {
        return new self($options);
    }

    public static function addString($options)
    {
        return new self($options, self::VARCHAR);
    }
    public static function addDateTime($options)
    {
        return new self($options);
    }
    public static function addID($options)
    {
        $column = new self($options, self::ID);
        $column->pk = true;
        return $column;
    }
}

$fields = [
    'OrderID' => Column::addInteger(['callback', 'options']),
    'ShipName' =>  Column::addString(['callback', 'options']),
    'ShipAddress' => Column::addString(['callback', 'options']),
    'ShipCity' => Column::addString(['callback', 'options']),
    'ShipRegion' => Column::addString(['callback', 'options']),
    'ShipPostalCode' => Column::addInteger(['callback', 'options']),
    'ShipCountry' => Column::addInteger(['callback', 'options']),
];

print_r($fields);

// class Fields implements \ArrayAccess, \Countable, \IteratorAggregate
// {
//     public function __construct(array $data)
//     {
//         foreach ($data as $field_name => $field_data) {
//             $this[$field_name] = new Column($field_name, $field_data);
//         }
//         // parent::__construct($data, ArrayObject::ARRAY_AS_PROPS|ArrayObject::STD_PROP_LIST);
//     }

//     public function offsetExists($key)
//     {
//         if (isset($this->data[$key])) {
//             return true;
//         }
//         return false;
//     }

//     public function offsetUnset($key)
//     {
//         unset($this->data[$key]);
//     }

//     public function offsetSet($key, $value)
//     {
//         error_log(__METHOD__.'@'.__LINE__);
//         $this->data[$key] = $value;
//     }

//     public function offsetGet($key)
//     {
//         return $this->data[$key];
//     }
//     public function count()
//     {
//         return count($this->data);
//     }
//     public function getIterator(): ArrayIterator
//     {
//         return new ArrayIterator(array_map(function ($val) {
//             return $this->data[$val];
//         }, $this->data));
//     }
// }

// $model_fields = new Fields($fields);
// print_r($model_fields);