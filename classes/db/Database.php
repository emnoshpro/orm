<?php
//
// +----------------------------------------------------------------------+
// | database.class.php                                                   |
// +----------------------------------------------------------------------+
// | Database Connection class                                            |
// +----------------------------------------------------------------------+
// | Copyright (c) 2020                                                   |
// +----------------------------------------------------------------------+
// | Authors:  <emnosh.pro@gmail.com.au>                                  |
// +----------------------------------------------------------------------+
//
    namespace EM\DBAL\db;

    /**
     * Database
     */
    class Database
    {
        /**
         * _connection
         *
         * @var array
         */
        private $_connection = [];
        /**
         * type
         *
         * @var bool
         */
        private $type = false;
        private static $_cached_instance = [];

        const COUNT = 1;
        const GETID = 2;
        const RESULTSET = 3;

        /**
         * __construct
         *
         * @param  mixed $type
         * @return void
         */
        protected function __construct($type)
        {
            // class correct method as database driver selected in config file
            // call_user_func_array([$this, \config()], [null]);
            // TODO: $type will be a integer value
            $this->type = $type;
            $this->_connection[1] = [
                'host' => 'localhost',
                'user' => 'emnoshpro',
                'pass' => '3mn05hpr0',
                'port' => '3306',
                'database' => 'northwind',
                // 'id' => false
            ];
        }

        /**
         * __destruct
         *
         * @return void
         */
        public function __destruct()
        {
            if ($this->isConnected() === true) {
                $this->getStats();
                mysqli_close($this->_connection[$this->type]['id']);
            }
        }

        /**
         * isConnected
         *
         * @return void
         */
        public function isConnected()
        {
            // echo 'type is ' . $this->type . "\n";
            if (empty($this->_connection[$this->type]['id'])) {
                return false;
            }
            return true;
        }

        /**
         * connect
         *
         * @param  mixed $type
         * @return void
         */
        public static function connect($type)
        {
            // this basically returns a cached instance object
            if (!isset(self::$_cached_instance[$type])) {
                // we do not have a model object here so simply save the class model
                // static is bound to the called class
                self::$_cached_instance[$type] = new Database($type);
            }
            return self::$_cached_instance[$type];
        }

        /**
         * query
         *
         * @param  mixed $query
         * @param  mixed $return_type
         * @return void
         */
        public function query($query, $return_type = self::RESULTSET)
        {
            if ($this->isConnected() === false) {
                try {
                    $this->_connection[$this->type]['id'] = mysqli_connect(
                        $this->_connection[$this->type]['host'],
                        $this->_connection[$this->type]['user'],
                        $this->_connection[$this->type]['pass'],
                        $this->_connection[$this->type]['database'],
                        $this->_connection[$this->type]['port']
                    );
                } catch (Exception $ex) {
                    $this->error('Failed to connect');
                    throw new Exception('Could not connect to MySQL database.');
                }
            }

            // some stats calculation for each query
            $query_time = microtime(true);
            $result = mysqli_query($this->_connection[$this->type]['id'], $query);
            $time = microtime(true) - $query_time;

            switch($return_type) {
                case self::COUNT:
                    $rows = mysqli_fetch_assoc($result);

                    // add some stats
                    $this->stats['queries'][] = [
                        'query' => $query->getSql(),
                        'time' => $time,
                        'rows' => $rows['count'],
                        'changes' => 0
                    ];

                    return $rows['count'];
                case self::GETID:
                    // add some stats
                    $this->stats['queries'][] = [
                        'query' => $query->getSql(),
                        'time' => $time,
                        'rows' => 1,
                        'changes' => 0
                    ];

                    return mysqli_insert_id($this->_connection[$this->type]['id']);
                default:

                    $num_rows = $result->num_rows;
                    $changes = mysqli_affected_rows($this->_connection[$this->type]['id']);

                    $this->stats['queries'][] = [
                        'query' => $query->getSql(),
                        'time' => $time,
                        'rows' => $num_rows,
                        'changes' => $changes
                    ];
                    return $result;
            }
        }

        /**
         * getStats
         *
         * @return void
         */
        public function getStats()
        {

            $this->stats['total_time'] = 0;
            $this->stats['num_queries'] = 0;
            $this->stats['num_rows'] = 0;
            $this->stats['num_changes'] = 0;

            if (isset($this->stats['queries'])) {
                foreach ($this->stats['queries'] as $query) {
                    $this->stats['total_time'] += $query['time'];
                    $this->stats['num_queries'] += 1;
                    $this->stats['num_rows'] += $query['rows'];
                    $this->stats['num_changes'] += $query['changes'];
                }
            }

            $this->stats['avg_query_time'] =
                $this->stats['total_time'] /
                (float)(($this->stats['num_queries'] > 0) ? $this->stats['num_queries'] : 1);

            print_r($this->stats);
        }

        /**
         * error
         *
         * @param  mixed $error_message
         * @return void
         */
        public function error($error_message)
        {
        }
    }