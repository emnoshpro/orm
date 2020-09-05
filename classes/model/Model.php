<?php
//
// +----------------------------------------------------------------------+
// | model.class.php                                                      |
// +----------------------------------------------------------------------+
// | Models                                                               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2020                                                   |
// +----------------------------------------------------------------------+
// | Authors:  <emnosh.pro@gmail.com.au>                                  |
// +----------------------------------------------------------------------+
//
    namespace EM\DBAL\Model;

    use \EM\DBAL\Model\DataMapper;
    use \EM\DBAL\utils\Debug;
    use \EM\DBAL\db\Database;
    use \EM\DBAL\field\Column;

    /**
     * Model
     */
    abstract class Model extends DataMapper
    {
        use \EM\DBAL\db\Shard;

        // foreign key / primary key constraints
        /**
         * objects
         *
         * @return void
         */
        public static function objects()
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            return Manager::get(get_called_class());
        }


        /**
         * getTableName
         *
         * @return string
         */
        public static function getTableName():string
        {
            return str_replace(__NAMESPACE__ . '\\', '', get_called_class());
        }


        /**
         * getDataBaseConnection
         *
         * @return integer
         */
        public static function getDataBaseConnection()
        {
            $shard_server_id = self::getShardServerId();
            return Database::connect($shard_server_id);
        }


        /**
         * getIdField
         *
         * @return string
         */
        public static function getIdField()
        {
            Debug::debug(__METHOD__.'@'.__LINE__);
            foreach (get_called_class()::fields() as $column_name => $column) {
                if ($column->getType() === Column::ID) {
                    return $column_name;
                }
            }
        }
    }