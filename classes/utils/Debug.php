<?php
//
// +----------------------------------------------------------------------+
// | debug.class.php                                                      |
// +----------------------------------------------------------------------+
// | Debug                                                                |
// +----------------------------------------------------------------------+
// | Copyright (c) 2020                                                   |
// +----------------------------------------------------------------------+
// | Authors:  <emnosh.pro@gmail.com.au>                                  |
// +----------------------------------------------------------------------+
//
    namespace EM\DBAL\utils;

    Trait Debug
    {
        static $debug = false;

        /**
         * debug
         *
         * @param  mixed $string
         * @return void
         */
        public static function debug($string)
        {
            if (self::$debug) {
                error_log($string);
            }
        }
    }