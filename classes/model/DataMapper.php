<?php
//
// +----------------------------------------------------------------------+
// | object.class.php                                                     |
// +----------------------------------------------------------------------+
// | Loading of Model Objects and performing SQL Queries will be done here|
// +----------------------------------------------------------------------+
// | Copyright (c) 2020                                                   |
// +----------------------------------------------------------------------+
// | Authors:  <emnosh.pro@gmail.com.au>                                  |
// +----------------------------------------------------------------------+
//
    namespace EM\DBAL\Model;
    use \EM\DBAL\utils\Debug;
    use \EM\DBAL\Field\Column;
    /**
     * Orm_Object
     */
    abstract class DataMapper
    {
        /**
         * id
         *
         * @var bool
         */
        protected $id = false;

        // all SQL operations (UPDATE/INSERT/DELETE) on Model will be done by the object
        /**
         * __construct
         *
         * @return void
         */
        public function __construct()
        {
            // TODO: probably we should support loading objects with both fetch_row()/fetch_assoc()
            Debug::debug(__METHOD__.'@'.__LINE__);
            $arg = func_get_args();
            $data = array_shift($arg);

            $model = get_called_class();
            foreach ($model::fields() as $column_name => $column) {

                if ($column->getType() === Column::ID) {
                    $this->id = $data[$column_name];
                }

                if ($column->isForeignKey()) {
                    $modelData = $column->getRelationalData($data[$column_name]);
                    $model_column_name = $column->getPrimaryKeyModel();

                    $this->$model_column_name = $modelData;
                }
                $this->$column_name = $data[$column_name];
            }
        }

        /**
         * delete
         *
         * @return void
         */
        public function delete()
        {
            if ($this->id) {
                print_r($this);
            }
        }
    }