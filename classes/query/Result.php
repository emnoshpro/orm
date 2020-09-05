<?php
//
// +----------------------------------------------------------------------+
// | query_result.class.php                                               |
// +----------------------------------------------------------------------+
// | Query Result class using Iterator and other Array methods.           |
// | Basically is run as a mysql resultset fetch basedon the offset values|
// +----------------------------------------------------------------------+
// | Copyright (c) 2020                                                   |
// +----------------------------------------------------------------------+
// | Authors:  <emnosh.pro@gmail.com.au>                                  |
// +----------------------------------------------------------------------+
//
    namespace EM\DBAL\Query;
    use EM\DBAL\utils\Debug;
    use EM\DBAL\Query\Builder;
    use EM\DBAL\db\Database;
    /**
     * QueryResultSet
     * ORDER OF METHODS THAT GET EXECUTED
     * REWIND()     One executes the query here only once
     * NEXT()
     * VALID()
     * CURRENT()    Fetches the current row
     * key()        will only be called if accessed in the loop
     */
    class Result implements \Iterator, \Countable, \ArrayAccess
    {
        /**
         * parent
         *
         * @var bool
         */
        public $parent = false;
        /**
         * results
         *
         * @var bool
         */
        protected $results = false;
        /**
         * position
         *
         * @var int
         */
        protected $position = 0;

        /**
         * __construct
         *
         * @param  mixed $parent
         * @return void
         */
        public function __construct($parent)
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            $this->parent = $parent;
        }

        /**
         * __destruct
         *
         * @return void
         */
        public function __destruct()
        {
            if ($this->results) {
                $this->results->free();
                $this->results = false;
            }
        }

        /**
         * current
         *
         * @return void
         */
        public function current()
        {
            // fetch the current row from the database
            Debug::debug(__METHOD__.'@'.__LINE__);
            if ($this->results) {
                // this can be different fetch_row() for index values
                $row = $this->results->fetch_assoc();
                $class = $this->parent->model;
                // echo 'new class is ' . $class . "\n";
                if (!empty($row) && is_array($row) && count($row)) {
                    // we have a database row now load the object based on that data
                    return new $class($row);
                }
            }
        }

        /**
         * next
         * Get next position
         */
        function next()
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            // fetch the next record()
            $this->position++;
        }

        /**
         * key
         * Get aktual key (=position)
         * When iterating mysqli resultset this makes no sense.
         *
         * @return int
         */
        function key()
        {
            Debug::debug(__METHOD__.'@'.__LINE__ . ' Position ' . $this->position);
            return $this->position;
        }

        /**
         * valid
         * Is actual position valid?
         *
         * @return bool
         */
        function valid()
        {
            // valid false means no records in the resultset()
            Debug::debug(__METHOD__.'@'.__LINE__);
            if ($this->results !== false) {
                if ($this->position < $this->results->num_rows) {
                    return true;
                }
            }
            // $this->results->free();
            $this->results = false;
            return false;
        }

        /**
         * rewind
         * only called once
         * Ideally execute the query here and return to first position
         */
        function rewind()
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            // this is the first method that gets called in Iterator
            // we thus execute the query here instead of executing it on next()
            $this->position = 0;

            $class = $this->parent->model;
            // TODO: We need to build the Query here
            // TODO: probably we can move this to the parent class
            // We know what mappings we have for the $class
            $sql = Builder::select($class::getTableName());
            // TODO: should get fields depending on the arguments passed
                    // ->columns('firstName, lastName, EmployeeID, City, BirthDate, Title, Address, City');
            $wheres = $this->parent->getClauses();
            if (is_array($wheres) && count($wheres)) {
                // TODO: Loop
                $sql->where($wheres[0]);
            }

            $limit = $this->parent->getLimits();
            if (is_array($limit) && count($limit)) {
                $sql->offsetLimits($limit[0], $limit[1]);
            }

            $order = $this->parent->getOrderBy();
            if (is_array($order) && count($order)) {
                $sql->orderBy($order);
            }

            $db = $class::getDataBaseConnection();
            $this->results = $db->query($sql);
        }

        // Countable methods
        /**
         * count
         * Returns count of a given model
         * @return void
         */
        public function count()
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            $class = $this->parent->model;
            // print_r($class);
            $sql = Builder::select($class::getTableName())
                    ->columns(['count(*)' => 'count']);

            $wheres = $this->parent->getClauses();
            if (is_array($wheres) && count($wheres)) {
                $sql->where($wheres[0]);
            }

            $db = $class::getDataBaseConnection();
            $count = $db->query($sql, Database::COUNT);
            return $count;
        }

        // ArrayAccess
        /**
         * offsetExists
         *
         * @param  mixed $offset
         * @return void
         */
        public function offsetExists($offset)
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
        }

        /**
         * offsetGet
         *
         * @param  mixed $offset
         * @return void
         */
        public function offsetGet($offset)
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            $this->rewind();
            if ($offset) {
                $this->position = $offset;
                if ($this->results) {
                    if ($this->valid()) {
                        $this->results->data_seek($offset);
                        $row = $this->results->fetch_assoc();
                        $class = $this->parent->model;
                        if (!empty($row) && is_array($row) && count($row)) {
                            // die('here');
                            // we have a database row now load the object based on that data
                            return new $class($row);
                        }
                    } else {
                        // TODO: invalid range?
                    }
                }
            }

            return $this->current();
        }

        /**
         * offsetSet
         *
         * @param  mixed $offset
         * @param  mixed $value
         * @return void
         */
        public function offsetSet($offset, $value)
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
        }

        /**
         * offsetUnset
         *
         * @param  mixed $offset
         * @return void
         */
        public function offsetUnset($offset)
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
        }
    }