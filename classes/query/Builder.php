<?php
//
// +----------------------------------------------------------------------+
// | builder.class.php                                                    |
// +----------------------------------------------------------------------+
// | helper to Build your SQL                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2020                                                   |
// +----------------------------------------------------------------------+
// | Authors:  <emnosh.pro@gmail.com.au>                                  |
// +----------------------------------------------------------------------+
//

/**
 * Builder class is responsible to dynamically create SQL queries.
 *
 * Important: Verify that every feature you use will work.
 * SQL Query Builder does not attempt to validate the generated SQL at all.
 * INSERT IGNORE was not implemented.
 *
 */
    // namespace emnoshpro\Dbal\SQL_Builder;
    namespace EM\DBAL\Query;

    class Builder
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

            return new Builder($table_name, $table_alias);
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
            return new Builder($table_name, $table_alias, self::UPDATE);
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
            return new Builder($table_name, $table_alias, self::INSERT);
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
            return new Builder($table_name, $table_alias, self::DELETE);
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
        public function columnCount($column, $column_alias = null):self
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
                // print_r($expression);
                $this->_orderBy[] = array_shift($expression[0]);
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
        public function getSql():string
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
                return sprintf(' GROUP BY %s', implode(', ', $this->_groupBy));
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
                return sprintf(' ORDER BY %s', implode(', ', $this->_orderBy));
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
            return $this->getSql();
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
    //         ->orderBy(['Column DESC'])
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