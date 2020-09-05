<?php
//
// +----------------------------------------------------------------------+
// | model_query_manager.class.php                                        |
// +----------------------------------------------------------------------+
// | ModelQueryManager is a query routing engine basically helps to build |
// | SQL Queries for Models.                                              |
// +----------------------------------------------------------------------+
// | Copyright (c) 2020                                                   |
// +----------------------------------------------------------------------+
// | Authors:  <emnosh.pro@gmail.com.au>                                  |
// +----------------------------------------------------------------------+
//
    namespace EM\DBAL\Model;

    use EM\DBAL\utils\Debug;
    use EM\DBAL\Query\Set;
    /**
     * Manager
     */
    class Manager
    {
        public static $cachedModelInstance = NULL;
        public $cachedQuerySet = NULL;
        public $modelClass = NULL;

        /**
         * __construct
         *
         * @param  mixed $modelClass
         * @return void
         */
        public function __construct($modelClass)
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            $this->modelClass = $modelClass;
        }

        // Basically each function in SQL Builder will be considered here.
        // orderBy/Limit/Offset/Count/ etc
        /**
         * get
         *
         * @param  mixed $modelClass
         * @return void
         */
        public static function get($modelClass)
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            // Each time we call this Manager we return the cached version and not create
            // a new instance.
            if (!isset(self::$cachedModelInstance[$modelClass])) {
                // static is bound to the called class
                self::$cachedModelInstance[$modelClass] = new static($modelClass);
            }
            return self::$cachedModelInstance[$modelClass];
        }

        /**
         * findOne
         *
         * @param  mixed $expression
         * @return void
         */
        public function findOne(...$expression)
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            $argument = array_shift($expression);
            if (!is_array($argument)) {
                // we only have a integer value
                // findOne(model_id)
                $expression  = [$this->modelClass::getIdField(), '=', $argument];
            }
            // we send an expression
            // ['column/field', 'operator', 'value']
            return $this->getQuerySet()->filterAnd($expression);
        }

        /**
         * findAll
         *
         * @return void
         */
        public function findAll()
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            return $this->getQuerySet();
        }

        /**
         * getQuerySet
         *
         * @return void
         */
        public function getQuerySet()
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            // avoid creating multiple objects
            // each filter / method call will create a new QuerySet instance
            if (empty($this->cachedQuerySet)) {
                // static is bound to the called class
                $this->cachedQuerySet = new Set($this->modelClass);
            }
            return $this->cachedQuerySet;
        }

        /**
         * count
         *
         * @return void
         */
        public function count()
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            return $this->getQuerySet($this->modelClass)->count();
        }
    }