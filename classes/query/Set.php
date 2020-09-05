<?php
//
// +----------------------------------------------------------------------+
// | queryset.class.php                                                   |
// +----------------------------------------------------------------------+
// | QuerySet class using Iterator and other Array methods.               |
// | Basically is run as a mapper to build Sql Queries for Models.        |
// +----------------------------------------------------------------------+
// | Copyright (c) 2020                                                   |
// +----------------------------------------------------------------------+
// | Authors:  <emnosh.pro@gmail.com.au>                                  |
// +----------------------------------------------------------------------+
//
    namespace EM\DBAL\Query;
    use \EM\DBAL\utils\Debug;
    /**
     * QuerySet
     */
    class Set implements \IteratorAggregate, \Countable, \ArrayAccess
    {
        public $model = false;
        public $clauses = [];
        public $limits = [];
        public $orderBy = [];

        /**
         * __construct
         *
         * @param  mixed $model
         * @return void
         */
        public function __construct($model)
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            $this->model = $model;
        }

        // IteratorAggregate
        /**
         * getIterator
         *
         * @return void
         */
        public function getIterator()
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            return new Result($this);
        }

        /**
         * get
         *
         * @param  mixed $args
         * @return void
         */
        public function get()
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            $clone = clone $this;
            // since this has Countable interface
            // run the count(*) query to get no. of models
            $number_of_records = sizeof($clone);
            if ($number_of_records === 1) {
                // This will try to return the first element
                // offset since the query will have 1 result
                // This triggers offsetGet($offset) which will return Result
                return $clone[0];
            }
            // TODO: Throw Error ?
        }

        /**
         * getClass
         *
         * @return void
         */
        public function getClass()
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            return $this->model->getClass();
        }


        /**
         * filterAnd
         *
         * @param  mixed $expression
         * @return self
         */
        public function filterAnd($expression):self
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            // $clone = clone $this;
            if (is_array($expression) && count($expression)) {
                $this->clauses[] = $expression;
            }
            // We do not have to query yet
            return $this;
        }

        /**
         * limit
         *
         * @param  mixed $expression
         * @return self
         */
        public function limit(...$expression):self
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            // $clone = clone $this;
            if (is_array($expression) && count($expression)) {
                if (!empty($expression[0])) {
                    $this->limits[0] = $expression[0];
                }
                if (!empty($expression[1])) {
                    $this->limits[1] = $expression[1];
                }
            }
            // We do not have to query yet
            return $this;
        }

        /**
         * getClauses
         *
         * @return array
         */
        public function getClauses():array
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            return $this->clauses;
        }

        /**
         * getLimits
         *
         * @return array
         */
        public function getLimits():array
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            return $this->limits;
        }

        public function getOrderBy():array
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            return $this->orderBy;
        }

        /**
         * orderBy
         *
         * @param  mixed $expression
         * @return self
         */
        public function orderBy(...$expression):self
        {
            if (is_array($expression)) {
                $this->orderBy[] = array_shift($expression[0]);
            }
            return $this;
        }

        /**
         * count
         *
         * @return void
         */
        public function count()
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            return sizeof($this->getIterator());
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
            return $this->getIterator()->offsetGet($offset);
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