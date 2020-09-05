<?php
//
// +----------------------------------------------------------------------+
// | field.class.php                                                      |
// +----------------------------------------------------------------------+
// | Fields for Models                                                    |
// +----------------------------------------------------------------------+
// | Copyright (c) 2020                                                   |
// +----------------------------------------------------------------------+
// | Authors:  <emnosh.pro@gmail.com.au>                                  |
// +----------------------------------------------------------------------+
//

    namespace EM\DBAL\Field;
    use \EM\DBAL\utils\Debug;

    /**
     * Column
     */
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

        /**
         * __construct
         *
         * @param  mixed $data
         * @param  mixed $field_type
         * @return void
         */
        public function __construct(array $data, $field_type = self::INTEGER)
        {
            $this->type = $field_type;
            $this->callback = $data[0];
            $this->options = $data[1];
        }

        /**
         * addInteger
         *
         * @param  mixed $options
         * @return void
         */
        public static function addInteger($options)
        {
            return new self($options);
        }

        /**
         * addString
         *
         * @param  mixed $options
         * @return void
         */
        public static function addString($options)
        {
            return new self($options, self::VARCHAR);
        }

        /**
         * addDateTime
         *
         * @param  mixed $options
         * @return void
         */
        public static function addDateTime($options)
        {
            return new self($options);
        }

        /**
         * addID
         *
         * @param  mixed $options
         * @return void
         */
        public static function addID($options)
        {
            $column = new self($options, self::ID);
            return $column;
        }

        /**
         * addPrimaryKey
         *
         * @param  mixed $options
         * @return void
         */
        public static function addPrimaryKey($options)
        {
            $column = new self($options, self::INTEGER);
            $column->pk = true;
            return $column;
        }

        /**
         * getType
         *
         * @return void
         */
        public function getType()
        {
            return $this->type;
        }

        /**
         * isForeignKey
         *
         * @return void
         */
        public function isForeignKey()
        {
            return !empty($this->pk) && $this->pk === true;
        }

        /**
         * getPrimaryKeyModel
         *
         * @return void
         */
        public function getPrimaryKeyModel()
        {
            if ($this->isForeignKey() === true) {
                if (!empty($this->callback)) {
                    return $this->callback[0];
                }
            }
        }

        /**
         * getRelationalData
         *
         * @return void
         */
        public function getRelationalData(/*model ID*/)
        {
            if ($this->isForeignKey() === true) {
                $argument = func_get_args();
                $model_id = array_shift($argument);

                if (!empty($this->callback)) {
                    $model = $this->callback[0];
                    $method = $this->callback[1];
                    return $model::$method()->findOne($model_id)->get();
                }
            }
        }
    }