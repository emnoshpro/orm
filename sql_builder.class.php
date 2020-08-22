<?php
//
// +----------------------------------------------------------------------+
// | sql_builder.class.php                                                |
// +----------------------------------------------------------------------+
// | helper to Build your SQL                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2020                                                   |
// +----------------------------------------------------------------------+
// | Authors:  <emnosh.pro@gmail.com.au>                                  |
// +----------------------------------------------------------------------+
//

/**
 * Sql_Builder class is responsible to dynamically create SQL queries.
 *
 * Important: Verify that every feature you use will work.
 * SQL Query Builder does not attempt to validate the generated SQL at all.
 * INSERT IGNORE was not implemented.
 *
 */
    namespace emnoshpro\Dbal\SQL_Builder;

    class Sql_Builder
    {
        const AND   = 'AND';
        const OR    = 'OR';

        // operations
        const SELECT    = 1;
        const UPDATE    = 2;
        const DELETE    = 3;
        const INSERT    = 4;
        const REPLACE   = 5;

        // join types
        const INNER = 0;
        const OUTER = 1;
        const LEFT  = 2;
        const RIGHT = 3;

        private $_table_name    = '';   // table name
        private $_table_alias   = '';  // table alias
        private $_joins         = [];        // joins
        private $_columns       = [];      // columns efault is all
        private $_wheres        = [];       // wheres
        private $_limits        = [];       // offset, limit
        private $_when          = [];         // case statements
        private $_groupBy       = [];      // group by
        private $_having        = [];      // order by
        private $_orderBy       = [];      // order by

        const FUNCTION_SQL_AGGREGATE = ["AVG", "COUNT", "MAX", "MIN", "SUM"];

        // used internally
        private $_type          = false;      // one has to set it
        private $_join_types    = ['INNER JOIN', 'OUTER JOIN', 'LEFT JOIN', 'RIGHT JOIN'];
        private $_calcFoundRows = false;
        private $_distinct      = false;

        /**
         * __construct
         *
         * @param  mixed $table_name
         * @param  mixed $table_alias
         * @param  mixed $type
         * @return void
         */
        public function __construct($table_name, $table_alias = null, $type = self::SELECT)
        {
            $this->_table_name = $table_name;

            if (!empty($table_alias)) {
                $this->_table_alias = $table_alias;
            }
            $this->_type = $type;
        }

        /**
         * select
         *
         * @param  mixed $table_name
         * @param  mixed $table_alias
         * @return self
         */
        public static function select($table_name, $table_alias = null): self
        {
            if (empty($table_name)) {
                trigger_error(__METHOD__.'()@'.__LINE__.' invalid arguments missing table_name || expression', E_USER_ERROR);
            }

            return new Sql_Builder($table_name, $table_alias);
        }

        /**
         * update
         *
         * @param  mixed $table_name
         * @param  mixed $table_alias
         * @return self
         */
        public static function update($table_name, $table_alias = null):self
        {
            if (empty($table_name)) {
                trigger_error(__METHOD__.'()@'.__LINE__.' invalid arguments missing table_name || expression', E_USER_ERROR);
            }
            return new Sql_Builder($table_name, $table_alias, self::UPDATE);
        }

        /**
         * insert
         *
         * @param  mixed $table_name
         * @param  mixed $table_alias
         * @return self
         */
        public static function insert($table_name, $table_alias = null):self
        {
            if (empty($table_name)) {
                trigger_error(__METHOD__.'()@'.__LINE__.' invalid arguments missing table_name || expression', E_USER_ERROR);
            }
            return new Sql_Builder($table_name, $table_alias, self::INSERT);
        }

        /**
         * delete
         *
         * @param  mixed $table_name
         * @param  mixed $table_alias
         * @return self
         */
        public static function delete($table_name, $table_alias = null):self
        {
            if (empty($table_name)) {
                trigger_error(__METHOD__.'()@'.__LINE__.' invalid arguments missing table_name || expression', E_USER_ERROR);
            }
            return new Sql_Builder($table_name, $table_alias, self::DELETE);
        }

        /**
         * columns
         *
         * @param  mixed $column_data
         * @return self
         */
        public function columns($column_data):self
        {
            // SELECT
            //     id,
            //     action_heading,
            //     CASE
            //         WHEN action_type = 'Income' THEN action_amount
            //         ELSE NULL
            //     END AS income_amt,
            //     CASE
            //         WHEN action_type = 'Expense' THEN action_amount
            //         ELSE NULL
            //     END AS expense_amt
            // FROM tbl_transaction;
            // SELECT
            //   id, action_heading,
            //       IF(action_type='Income',action_amount,0) income,
            //       IF(action_type='Expense', action_amount, 0) expense
            // FROM tbl_transaction
            if (is_string($column_data)) {
                if (strpos($column_data, ',') !== false) {
                    // a,b,c,d,e,f
                    // we explode and add them as array
                    $columns = explode(',', $column_data);
                } else {
                    $function = substr($column_data, 0, strcspn($column_data, '\(.*\)'));
                    // we have aggregate functions
                    if (in_array($function, self::FUNCTION_SQL_AGGREGATE)) {
                        $columns[] = $column_data;
                        // TODO: addGroupBy default?
                    }
                }
            } else if (is_array($column_data) && array_keys($column_data) !== range(0, count($column_data) - 1)) {
                // [a, b, c, d] no aliases
                // [a=>1, b=>2, c=>3] (value is alias)
                // functions avg()/sum()/count()/min()/max()
                // we detect if its a key value pair array
                // keys => value pair
                $columns = array_map(function($key, $value) use ($column_data) {
                    if ($this->_type === self::SELECT) {
                        return $key . ' AS ' . $value;
                    } else if ($this->_type === self::UPDATE || $this->_type === self::INSERT) {
                        $value = trim($value);
                        if (!is_numeric($value)) {
                            $value = '\'' . addcslashes($value, "\'") . '\'';
                        }
                        return $key . ' = ' . $value;
                    }
                }, array_keys($column_data), $column_data);
            }
            $this->_columns[] = $columns;

            return $this;
        }

        // aggregate column functions
        /**
         * avg
         *
         * @param  mixed $column
         * @param  mixed $column_alias
         * @return self
         */
        public function avg($column, $column_alias = null):self
        {
            if (!is_null($column_alias)) {
                $this->columns(['AVG(' . $column . ')' => $column_alias]);
            } else {
                $this->columns('AVG(' . $column . ')');
            }

            return $this;
        }

        /**
         * count
         *
         * @param  mixed $column
         * @param  mixed $column_alias
         * @return self
         */
        public function count($column, $column_alias = null):self
        {
            if (!is_null($column_alias)) {
                $this->columns(['COUNT(' . $column . ')' => $column_alias]);
            } else {
                $this->columns('COUNT(' . $column . ')');
            }

            return $this;
        }

        /**
         * max
         *
         * @param  mixed $column
         * @param  mixed $column_alias
         * @return self
         */
        public function max($column, $column_alias = null):self
        {
            if (!is_null($column_alias)) {
                $this->columns(['MAX(' . $column . ')' => $column_alias]);
            } else {
                $this->columns('MAX(' . $column . ')');
            }

            return $this;
        }

        /**
         * min
         *
         * @param  mixed $column
         * @param  mixed $column_alias
         * @return self
         */
        public function min($column, $column_alias = null):self
        {
            if (!is_null($column_alias)) {
                $this->columns(['MIN(' . $column . ')' => $column_alias]);
            } else {
                $this->columns('MIN(' . $column . ')');
            }

            return $this;
        }

        /**
         * sum
         *
         * @param  mixed $column
         * @param  mixed $column_alias
         * @return self
         */
        public function sum($column, $column_alias = null):self
        {
            if (!is_null($column_alias)) {
                $this->columns(['SUM(' . $column . ')' => $column_alias]);
            } else {
                $this->columns('SUM(' . $column . ')');
            }

            return $this;
        }

        /**
         * if
         *
         * @param  mixed $expression
         * @return self
         */
        public function if(...$expression):self
        {
            if (count($expression[0]) > 3) {
                $alias = array_pop($expression[0]);
            }
            $condition = implode(',', $expression[0]);

            $if = 'IF(' . $condition . ')';
            if (!empty($alias)) {
                $if = [$if => $alias];
            }

            $this->columns($if);
            return $this;
        }

        /**
         * case
         *
         * @param  mixed $expression
         * @return self
         */
        public function case(...$expression):self
        {
// CASE
//     WHEN condition1 THEN result1
//     WHEN condition2 THEN result2
//     WHEN conditionN THEN resultN
//     ELSE result
// END;
// SELECT OrderID, Quantity,
// CASE
//     WHEN Quantity > 30 THEN "The quantity is greater than 30"
//     WHEN Quantity = 30 THEN "The quantity is 30"
//     ELSE "The quantity is under 30"
// END
// FROM OrderDetails;
// SELECT CustomerName, City, Country
// FROM Customers
// ORDER BY
// (CASE
//     WHEN City IS NULL THEN Country
//     ELSE City
// END);
            if (is_array($expression)) {
                $this->_when[] = $expression;
            }

            return $this;
        }

        /**
         * groupBy
         *
         * @param  mixed $expression
         * @return self
         */
        public function groupBy(...$expression):self
        {
            if (is_array($expression)) {
                $this->_groupBy[] = $expression;
            }
            return $this;
        }

        /**
         * having
         *
         * @param  mixed $expression
         * @return self
         */
        public function having(...$expression):self
        {
            // TODO:
            return $this;
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
                $this->_orderBy[] = $expression;
            }
            return $this;
        }

        /**
         * where
         *
         * @param  mixed $expression
         * @return self
         */
        public function where(...$expression):self
        {
            if (isset($expression[1])) {
                // $expression[0][] = $expression[1];
                // unset($expression[1]);
                // we have a condition join
                // we simply unshift such that when iterating we put the conditional criteria first
                array_unshift($this->_wheres, $expression);
            } else {
                if (count($this->_wheres) > 0) {
                    // multiple where clauses we default to AND
                    $expression[1] = self::AND;
                    array_unshift($this->_wheres, $expression);
                } else {
                    $this->_wheres[] = $expression;
                }
            }
            return $this;
        }

        /**
         * andWhere
         *
         * @param  mixed $expression
         * @return self
         */
        public function andWhere($expression):self
        {
            if (count($this->_wheres) > 0) {
                return self::where($expression, self::AND);
            }
            // when there is no where we are treating this simply as a single WHERE
            return self::where($expression);
        }

        /**
         * orWhere
         *
         * @param  mixed $expression
         * @return self
         */
        public function orWhere($expression):self
        {
            if (count($this->_wheres) > 0) {
                return self::where($expression, self::OR);
            }
            // when there is no where we are treating this simply as a single WHERE
            return self::where($expression);
        }

        /**
         * join
         *
         * @param  mixed $table_name
         * @param  mixed $expression
         * @param  mixed $table_alias
         * @param  mixed $type
         * @return self
         */
        public function join($table_name, $expression, $table_alias = null, $type = self::INNER):self
        {
            if (empty($table_name) || empty($expression)) {
                trigger_error(__METHOD__.'()@'.__LINE__.' invalid arguments missing table_name || expression', E_USER_ERROR);
            }

            if (empty($table_alias)) {
                $table_alias = substr($table_name, 0, 1);
            }
            $join_key = sprintf('%s %s.%s', $this->_join_types[$type], trim($table_alias), trim($table_name));

            $this->_joins[] = [$expression, $join_key];
            return $this;
        }

        /**
         * innerJoin
         *
         * @param  mixed $table_name
         * @param  mixed $expression
         * @param  mixed $table_alias
         * @return self
         */
        public function innerJoin($table_name, $expression, $table_alias = null):self
        {
            return self::join($table_name, $expression, $table_alias);
        }

        /**
         * leftJoin
         *
         * @param  mixed $table_name
         * @param  mixed $expression
         * @param  mixed $table_alias
         * @return self
         */
        public function leftJoin($table_name, $expression, $table_alias = null):self
        {
            return self::join($table_name, $expression, $table_alias, self::LEFT);
        }

        /**
         * outerJoin
         *
         * @param  mixed $table_name
         * @param  mixed $expression
         * @param  mixed $table_alias
         * @return self
         */
        public function outerJoin($table_name, $expression, $table_alias = null):self
        {
            return self::join($table_name, $expression, $table_alias, self::OUTER);
        }

        /**
         * rightJoin
         *
         * @param  mixed $table_name
         * @param  mixed $expression
         * @param  mixed $table_alias
         * @return self
         */
        public function rightJoin($table_name, $expression, $table_alias = null):self
        {
            return self::join($table_name, $expression, $table_alias, self::RIGHT);
        }

        /**
         * on
         *
         * @param  mixed $expression
         * @return self
         */
        public function on(...$expression):self
        {
            if (empty($expression[0])) {
                trigger_error(__METHOD__.'()@'.__LINE__.' invalid arguments missing ON expression ', E_USER_ERROR);
            }
            // adding extra expressions on joins
            // we simply get the recent join and append it
            $_join_count = count($this->_joins);
            if ($_join_count > 0) {
                if (isset($expression[1])) {
                    $this->_joins[$_join_count - 1][0][] = $expression[1];
                }
                if (isset($expression[0])) {
                    $this->_joins[$_join_count - 1][0] = array_merge($this->_joins[$_join_count - 1][0], array_values($expression[0]));
                }
            }
            return $this;
        }

        /**
         * andOn
         *
         * @param  mixed $expression
         * @return self
         */
        public function andOn($expression):self
        {
            return self::on($expression, self::AND);
        }

        /**
         * orOn
         *
         * @param  mixed $expression
         * @return self
         */
        public function orOn($expression):self
        {
            return self::on($expression, self::OR);
        }

        /**
         * offsetLimits
         *
         * @param  mixed $offset
         * @param  mixed $limit
         * @return self
         */
        public function offsetLimits($offset, $limit):self
        {
            $this->_limits = [$offset, $limit];
            return $this;
        }

        /**
         * setOffset
         *
         * @param  mixed $offset
         * @return self
         */
        public function setOffset($offset): self
        {
            $this->_limits[0] = $offset;
            return $this;
        }

        /**
         * setLimit
         *
         * @param  mixed $limit
         * @return self
         */
        public function setLimit($limit):self
        {
            $this->_limits[1] = $limit;
            return $this;
        }

        /**
         * calcFoundRows
         *
         * @return self
         */
        public function calcFoundRows():self
        {
            $this->_calcFoundRows = true;
            return $this;
        }

        /**
         * distinct
         *
         * @return self
         */
        public function distinct():self
        {
            $this->_distinct = true;
            return $this;
        }

        /**
         * get
         *
         * @return string
         */
        public function get():string
        {
            if ($this->_type === false) {
                trigger_error(__METHOD__.'()@'.__LINE__.' invalid SQL Operation missing SELECT | UPDATE | INSERT | DELETE statement ', E_USER_ERROR);
            }

            $sql = '';
            switch ($this->_type) {
                case self::DELETE:
                    $sql = sprintf('DELETE FROM %s %s %s', $this->getTableName(), $this->getWheres(), $this->getLimits());
                    break;
                case self::UPDATE:
                    $sql = sprintf('UPDATE %s SET %s %s %s', $this->getTableName(), $this->getColumnNames(), $this->getWheres(), $this->getLimits());
                    break;
                case self::INSERT:
                    // INSERT INTO table (a,b) VALUES (1,2), (2,3), (3,4);
                    $data = $this->getColumnNames();
                    $data = explode(',', $data);

                    $fields = [];
                    $values = [];
                    foreach ($data as $details) {
                        list($field, $value) = explode(' = ', $details);
                        $field = '\'' . addcslashes(trim($field), "\'") . '\'';
                        $fields[] = $field;
                        $values[] = $value;
                    }

                    if ($fields && $values) {
                        if (count($fields) === count($values)) {
                            $fields = implode(',', $fields);
                            $values = implode(',', $values);
                            $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->getTableName(), $fields, $values);
                        }
                    }
                    break;
                case self::REPLACE:
                    break;
                default:
                    // $sql = sprintf('SELECT %s FROM %s %s %s %s', $this->getColumnNames(), $this->getTableName(), $this->getJoin(), $this->getWhere(), $this->getLimits());
                    $sql = 'SELECT';
                    if ($this->_calcFoundRows) {
                        $sql .= ' SQL_CALC_FOUND_ROWS ';
                    }
                    if ($this->_distinct) {
                        $sql .= ' DISTINCT ';
                    }
                    $sql .= $this->getColumnNames();
                    $sql .= $this->getCaseStatements();
                    $sql .= ' FROM ' . $this->getTableName();
                    $sql .= $this->getJoins();
                    $sql .= $this->getWheres();
                    $sql .= $this->getGroupBy();
                    $sql .= $this->getHaving();
                    $sql .= $this->getOrderBy();
                    $sql .= $this->getLimits();
            }
            return $sql;
        }

        /**
         * getColumnNames
         *
         * @return string
         */
        public function getColumnNames():string
        {
            if ($this->_type === false) {
                trigger_error(__METHOD__.'()@'.__LINE__.' invalid SQL Column definition missing column names to select ', E_USER_ERROR);
            }

            if (is_array($this->_columns) && count($this->_columns)) {
                // flatten to a single array
                $columns = call_user_func_array('array_merge', $this->_columns);
                $columns = implode(', ', $columns);
                return sprintf(' %s', $columns);
            }
            return ' *';
        }

        /**
         * getCaseStatements
         *
         * @return string
         */
        public function getCaseStatements(): string
        {
            if ($this->_type === false) {
                trigger_error(__METHOD__.'()@'.__LINE__.' invalid SQL Column definition missing column names to select ', E_USER_ERROR);
            }

            if (count($this->_when) > 0) {
                // we can have multiple when clauses
                // apply quotes to the last value
                $when = array_map(function($when) {
                    $result = array_pop($when[0]);
                    if ($result) {
                        if (!is_numeric($result)) {
                            $result = '\'' . addcslashes($result, "\'") . '\'';
                        }
                    }
                    $column_name = array_shift($when[0]);
                    $condition = implode(' ', $when[0]);

                    return sprintf('WHEN %s %s THEN %s', $column_name, $condition, $result);

                }, $this->_when);
            }
            if (!empty($when)) {
                return sprintf(' CASE (%s) END ', implode(' ', $when));
            }
            return '';
        }

        /**
         * getTableName
         *
         * @return string
         */
        public function getTableName():string
        {
            $table_name = $this->_table_name;
            if ($this->_type === self::SELECT) {
                if (!empty($this->_table_alias)) {
                    $table_name .= ' AS ' . $this->_table_alias;
                }
            }
            return $table_name;
        }

        /**
         * getJoins
         *
         * @return string
         */
        public function getJoins():string
        {
            if (count($this->_joins) > 0) {
                // we can have multiple where clauses
                $joins = array_map(function($join) {
                    if (isset($join[0]) && isset($join[1])) {
                        return $join[1] . ' ON ' . implode(' ', $join[0]);
                    }
                }, $this->_joins);
            }
            if (!empty($joins)) {
                return sprintf(' %s', implode(' ', $joins));
            }
            return '';
        }

        /**
         * getWheres
         *
         * @return string
         */
        public function getWheres():string
        {
            if (count($this->_wheres) > 0) {
                // we can have multiple where clauses
                // apply quotes to the last value
                $where = array_map(function($where) {
                    // column operator 'test' only for string values
                    if (count($where[0]) === 3) {
                        if (is_array($where[0][2])) {
                            // IN_ARRAY / NOT IN ARRAY
                            $where[0][2] = '(' . implode(',', $where[0][2]) . ')';
                        } else if (!is_numeric($where[0][2])) {
                            $where[0][2] = '\'' . addcslashes($where[0][2], "\'") . '\'';
                        }
                    }
                    $_where = implode(' ', $where[0]);
                    if (isset($where[1])) {
                        $_where .= ' ' . $where[1];
                    }
                    return $_where;
                }, $this->_wheres);
            }
            if (!empty($where)) {
                return sprintf(' WHERE %s', implode(' ', $where));
            }
            return '';
        }

        /**
         * getGroupBy
         *
         * @return string
         */
        public function getGroupBy():string
        {
            if (!empty($this->_groupBy)) {
                return sprintf(' GROUP BY %s' . implode(', ', $this->_groupBy));
            }
            return '';
        }

        /**
         * getHaving
         *
         * @return string
         */
        public function getHaving():string
        {
            // TODO:
            return '';
        }

        /**
         * getOrderBy
         *
         * @return string
         */
        public function getOrderBy():string
        {
            if (!empty($this->_orderBy)) {
                return sprintf(' ORDER BY %s' . implode(', ', $this->_orderBy));
            }
            return '';
        }

        /**
         * getLimits
         *
         * @return string
         */
        public function getLimits():string
        {
            $limit = '';
            if (!empty($this->_limits[0])) {
                $limit .= $this->_limits[0] . ', ';
            }

            if (!empty($this->_limits[1])) {
                $limit .= $this->_limits[1];
            }

            if (!empty($limit)) {
                return sprintf(' LIMIT %s', $limit);
            }
            return '';
        }

        /**
         * __toString
         *
         * @return void
         */
        function __toString()
        {
            return $this->get();
        }
    }

    // $sql = Sql_Builder::select('m_user', 'u')
    //         ->where(['a', 'IN', ['a', 'b', 'c', 'd']])
    //         ->join('m_address', ['left column', '=', 'right column'])
    //         ->andOn(['a', '=', 'b'])
    //         // ->orOn(['aeiei', '<=', '200'])
    //         // ->join('m_city', ['2 left column', '=', '2 right column'])
    //         ->columns(['a' => 'hello', 'b' => 'test', 'c' => '123'])
    //         ->avg('text', 'help')
    //         ->if(['a', 'b', 'c', 'd'])
    //         ->case(['jee', '>', '100', 'This is a test'])
    //         ->case(['yee', '<>', '300', 'Three hundred'])
    //         ->case(['dee', '!=', '200', 300])
    //         // ->where(['column', '=', 'test"s'])
    //         // ->where(['test', '>=', '10'])
    //         ->where(['a', 'like', 'teeee'])
    //         ->andWhere(['beee', '>', '100'])
    //         ->offsetLimits(100, 100)
    //         ->orWhere(['b', 'like', '02020202']);
    // print $sql . "\n";

    // $sql = Sql_Builder::update('m_user', 'u')
    //         ->columns(['a' => 'hello ', 'b' => 'test ', 'c' => ' 123 '])
    //         ->where(['a', 'like', 'teeee'])
    //         ->setLimit(10);
    // print $sql . "\n";

    // $sql = Sql_Builder::delete('m_user', 'u')
    //         ->where(['b', '>', 20]);
    //         // ->setLimit(10);
    // print $sql . "\n";

    // $sql = Sql_Builder::insert('m_user', 'u')
    //         ->columns(['a' => 'hello ', 'b' => 'test ', 'c' => ' 123 ']);
    // print $sql . "\n";

    class Database
    {
        private $_connection = [];
        private $type = false;
        private static $_cached_instance = [];

        const COUNT = 1;
        const GETID = 2;
        const RESULTSET = 3;

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

            $this->stats['total_time'] = 0;
            $this->stats['num_queries'] = 0;
            $this->stats['num_rows'] = 0;
            $this->stats['num_changes'] = 0;
        }

        public function __destruct()
        {
            if ($this->isConnected() === true) {
                mysqli_close($this->_connection[$this->type]['id']);
            }
        }

        public function isConnected()
        {
            // echo 'type is ' . $this->type . "\n";
            if (empty($this->_connection[$this->type]['id'])) {
                return false;
            }
            return true;
        }

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

            print $query . "\n";
            // TODO: some stats calculation for each query
            $result = mysqli_query($this->_connection[$this->type]['id'], $query);
            switch($return_type) {
                case self::COUNT:
                    $rows = mysqli_fetch_assoc($result);
                    return $rows['count'];
                case self::GETID:
                    return mysqli_insert_id($this->_connection[$this->type]['id']);
                default:
                    return $result;
            }
        }

        public function error($error_message)
        {
        }
    }

    class QueryResultSet implements \Iterator
    {
        protected $model = false;
        protected $arguments = [];
        protected $results = false;
        protected $position = 0;
        protected $loaded = false;

        public function __construct($model)
        {
            error_log(__METHOD__.'@'.__LINE__);
            $this->model = $model;
        }

        function current()
        {
            // fetch the current row from the database
            error_log(__METHOD__.'@'.__LINE__);
            // this can be different fetch_row() for index values
            $row = $this->results->fetch_assoc();
            $class = $this->model;
            return new $class($row);
        }

        function get($expression)
        {
            error_log(__METHOD__.'@'.__LINE__);
            // print_r($expression);
            $argument = array_shift($expression);
            // print_r($argument);

            $class = $this->model;
            if (!is_array($argument)) {
                // we have a single value so search by primary key ?
                $id_field = $class::getIdField();
                // print_r($id_field);
                $expression = [$id_field, '=', $argument];
            }
            $sql = Sql_builder::select($class::getTableName())
                    ->where($expression);
            // TODO: should get fields depending on the arguments passed
                    // ->columns('firstName, lastName, EmployeeID, City, BirthDate, Title, Address, City');


            // print $sql . "\n";
            $db = $class::getDataBaseConnection();
            $this->results = $db->query($sql);

            $class = $this->model;
            $row = $this->results->fetch_assoc();
            // print_r($row);
            return new $class($row);
        }

        /**
         * Get next position
         */
        function next()
        {
            error_log(__METHOD__.'@'.__LINE__);
            $this->position++;
        }

        /**
         * Get aktual key (=position)
         *
         * @return int
         */
        function key()
        {
            error_log(__METHOD__.'@'.__LINE__);
            return $this->position;
        }

        /**
         * Is actual position valid?
         *
         * @return bool
         */
        function valid()
        {
            error_log(__METHOD__.'@'.__LINE__);
            if ($this->results !== false) {
                if ($this->position < $this->results->num_rows) {
                    return true;
                }
            }
            return false;
        }

        /**
         * Return to first position
         */
        function rewind()
        {
            error_log(__METHOD__.'@'.__LINE__);
            $this->position = 0;

            $class = $this->model;
            $sql = Sql_builder::select($class::getTableName());
            // TODO: should get fields depending on the arguments passed
                    // ->columns('firstName, lastName, EmployeeID, City, BirthDate, Title, Address, City');


            // print $sql . "\n";
            $db = $class::getDataBaseConnection();
            $this->results = $db->query($sql);
        }

        // Countable methods
        public function count()
        {
            error_log(__METHOD__.'@'.__LINE__);
            $class = $this->model;
            $sql = Sql_builder::select($class::getTableName())
                    ->columns(['count(*)' => 'count']);

            // print $sql . "\n";
            $db = $class::getDataBaseConnection();
            $count = $db->query($sql, Database::COUNT);
            return $count;
        }
    }

    class Manager
    {
        protected static $_cached_instance = [];
        protected static $model_class = false;

        public static function get($model_class)
        {
            self::$model_class = $model_class;

            // this basically returns a cached instance object
            if (!isset(self::$_cached_instance[$model_class])) {
                // we do not have a model object here so simply save the class model
                // static is bound to the called class
                self::$_cached_instance[$model_class] = new static($model_class);
            }
            return self::$_cached_instance[$model_class];
        }

        public function find(...$expression)
        {
            error_log(__METHOD__.'@'.__LINE__);
            return $this->getQuerySet(self::$model_class)->get($expression);
        }

        public function getQuerySet($model)
        {
            error_log(__METHOD__.'@'.__LINE__);
            return new QueryResultSet($model);
        }

        public function count()
        {
            error_log(__METHOD__.'@'.__LINE__);
            return $this->getQuerySet(self::$model_class)->count();
        }

        public function findAll()
        {
            error_log(__METHOD__.'@'.__LINE__);
            return $this->getQuerySet(self::$model_class);
        }
    }

    // namespace emnoshpro\Db\Orm_Object;
    abstract class Orm_Object
    {
        // protected $field_values = [];
        // protected $modified_fields = [];

        // all SQL operations (UPDATE/INSERT/DELETE) on Model will be done by the object
        public function __construct()
        {
            // TODO: probably we should support loading objects with both fetch_row()/fetch_assoc()
            // error_log(__METHOD__.'@'.__LINE__);
            $arg = func_get_args();
            $data = array_shift($arg);

            foreach (get_called_class()::fields() as $key => $field) {
                $field_name = $field->getName();
                if ($field->isForeignKey()) {
                    // error_log('foreign key');
                    $model = $field->getClass($data[$field_name]);
                    $model_name = $model::getTableName();
                    $this->$model_name = $model;
                }
                $this->$field_name = $data[$field_name];
            }
        }

        // public function __get($field)
        // {
        //     // error_log(__METHOD__.'@'.__LINE__);
        //     return $this->get($field);
        // }

        // public function get($field)
        // {
        //     // error_log(__METHOD__.'@'.__LINE__);
        //     return $this;
        // }

        // public function __set($field, $value)
        // {
        //     // error_log(__METHOD__.'@'.__LINE__);
        //     return $this->set($field, $value);
        // }

        // public function set($field, $value)
        // {
        //     // error_log(__METHOD__.'@'.__LINE__);
        //     $this->field_values[$field] = $value;
        //     if (!isset($this->modified_fields[$field])) {
        //         $this->modified_fields[] = $field;
        //     }
        //     return $this;
        // }
    }

    trait Shard
    {
        public function getShardId()
        {
            // return shard by id
            return 1;
        }
    }

    class Field
    {
        // protected $field_name = NULL;
        // protected $counter = 0;
        protected $fields;
        const ID = 1;

        public function __construct($field_name, $model_callback = NULL, $type = NULL)
        {
            // $this->field_name = $field_name;
            $this->fields[] = [
                'name' => $field_name,
                'connects_to' => $model_callback,
                'type' => $type
            ];
        }

        public static function addId($field_name, $type = self::ID)
        {
            // error_log(__METHOD__.'@'.__LINE__);
            return new Field($field_name, NULL, $type);
        }

        public static function addVarChar($field_name)
        {
            // error_log(__METHOD__.'@'.__LINE__);
            return new Field($field_name);
        }

        public static function addForeignKey($field_name, $model)
        {
            // error_log(__METHOD__.'@'.__LINE__);
            return new Field($field_name, $model);
        }

        public function getName()
        {
            // error_log(__METHOD__.'@'.__LINE__);
            return $this->fields[0]['name'];
        }

        public function isForeignKey()
        {
            // error_log(__METHOD__.'@'.__LINE__);
            // print_r($this->fields);
            if (!empty($this->fields[0]['connects_to'])) {
                return true;
            }
        }

        public function getClass()
        {
            error_log(__METHOD__.'@'.__LINE__);
            // print_r($this->fields);
            if ($this->isForeignKey() === true) {
                $argument = func_get_args();
                $model_id = array_shift($argument);
                // print_r($model_id);
                // print_r($this->fields[0]['connects_to']);
                $model = $this->fields[0]['connects_to'][0];
                $method = $this->fields[0]['connects_to'][1];
                return call_user_func(array($model, $method))->find($model_id);
            }
        }

        public function getType()
        {
            return $this->fields[0]['type'];
        }
    }

    // namespace emnoshpro\Db\Model;
    abstract class Model extends Orm_Object
    {
        use Shard;
        // use Field;

        // foreign key / primary key constraints
        public static function objects()
        {
            error_log(__METHOD__.'@'.__LINE__);
            return Manager::get(get_called_class());
        }

        public static function getTableName()
        {
            // return get_called_class();
            return str_replace(__NAMESPACE__ . '\\', '', get_called_class());
        }

        public static function getDataBaseConnection()
        {
            $shard_id = self::getShardId();
            // echo 'Shard ID is ' . $shard_id . "\n";
            return Database::connect($shard_id);
        }

        public static function getIdField()
        {
            error_log(__METHOD__.'@'.__LINE__);
            foreach (get_called_class()::fields() as $key => $field) {
                // error_log('field type ' . $field->getType());
                if ($field->getType() === Field::ID) {
                    // error_log('found field type id');
                    return $field->getName();
                }
            }
        }
    }

    class Employees extends Model
    {
        public static function fields()
        {
            return [
                Field::addId('EmployeeID'),
                Field::addVarChar('FirstName'),
                Field::addVarChar('LastName'),
            ];
        }
    }

    class Customers extends Model
    {
        public static function fields()
        {
            return [
                Field::addId('CustomerID'),
                Field::addVarChar('ContactName'),
                Field::addVarChar('CompanyName'),
                Field::addVarChar('ContactTitle'),
            ];
        }
    }

    class Orders extends Model
    {
        public static function fields()
        {
            return [
                Field::addId('OrderID'),
                Field::addVarChar('ShipName'),
                Field::addVarChar('ShipAddress'),
                Field::addVarChar('ShipCity'),
                Field::addVarChar('ShipRegion'),
                Field::addVarChar('ShipPostalCode'),
                Field::addVarChar('ShipCountry'),
                Field::addForeignKey('CustomerID', array(Customers::class, 'objects')),
                Field::addForeignKey('EmployeeID', array(Employees::class, 'objects')),
            ];
        }
    }

    // $user_count = Employees::objects()->count();
    // echo 'User count is ' . $user_count . "\n";
    // if ($user_count > 0) {
    //     $employees = Employees::objects()->findAll();
    //     foreach ($employees as $employee) {
    //         echo "Printing Employee data \n";
    //         print_r($employee);
    //     }
    // }
    // $customer_count = Customers::objects()->count();
    // echo 'Customer count is ' . $customer_count . "\n";
    // if ($customer_count > 0) {
    //     $customers = Customers::objects()->findAll();
    //     foreach ($customers as $customer) {
    //         echo "Printing Customer data \n";
    //         print_r($customer);
    //         echo "real: ".(memory_get_peak_usage(true)/1024/1024)." MiB\n\n";
    //     }
    // }

    $order_count = Orders::objects()->count();
    echo 'Order count is ' . $order_count . "\n";
    if ($order_count > 0) {
        $orders = Orders::objects()->find(11077);
        // $orders = Orders::objects()->findAll();
        print_r($orders);
        // foreach ($orders as $order) {
        //     echo "Printing order data \n";
        //     print_r($order);
        //     echo "real: ".(memory_get_peak_usage(true)/1024/1024)." MiB\n\n";
        // }
    }
