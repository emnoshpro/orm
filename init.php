<?php
//
// +----------------------------------------------------------------------+
// | init.php                                                             |
// +----------------------------------------------------------------------+
// | init script for setting PATH and loading classes                     |
// +----------------------------------------------------------------------+
// | Copyright (c) 2020                                                   |
// +----------------------------------------------------------------------+
// | Authors:  <emnosh.pro@gmail.com.au>                                  |
// +----------------------------------------------------------------------+
//
    namespace EM\DBAL;

    if (!defined('BASE_PATH')) {
        define('BASE_PATH', dirname(__FILE__));
        define('CLASS_PATH', BASE_PATH . '/classes/');

        require_once(CLASS_PATH . 'Loader.php');
        \Loader::register(__NAMESPACE__);
    }