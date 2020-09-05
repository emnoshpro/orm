<?php
//
// +----------------------------------------------------------------------+
// | loader.class.php                                                     |
// +----------------------------------------------------------------------+
// | Class loader                                                         |
// +----------------------------------------------------------------------+
// | Copyright (c) 2020                                                   |
// +----------------------------------------------------------------------+
// | Authors:  <emnosh.pro@gmail.com.au>                                  |
// +----------------------------------------------------------------------+
//
    /**
     * Loader
     */
    class Loader
    {
        public static $_namespace = NULL;
        /**
         * register
         *
         * @return void
         */
        public static function register($namespace)
        {
            self::$_namespace = $namespace;
            return spl_autoload_register(array('Loader', 'load'));
        }

        /**
         * load
         *
         * @param  mixed $class_name
         * @return void
         */
        public static function load($class_name)
        {
            $file_name = '';
            $namespace = '';

            $class_name = str_replace(self::$_namespace, '', $class_name);
            $last_namespace_position = strripos($class_name, '\\');
            if ($last_namespace_position !== false) {
                $namespace = strtolower(substr($class_name, 0, $last_namespace_position));
                $class_name = substr($class_name, $last_namespace_position + 1);
                $filename = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
            }
            $filename .= $class_name . '.php';
            $filename = CLASS_PATH . DIRECTORY_SEPARATOR . $filename;
            echo 'Loading ' . $class_name . ' Filename ' . $filename . "\n";

            if (file_exists($filename)) {
                require_once($filename);
            }
        }
    }