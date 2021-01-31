<?php
namespace Classes;

use Classes\MySQLi;
use Classes\Mysql;
use Classes\Registry;

class Db
{
    protected static $db;

    /**
     * get instance of connected database to prevent thousands connections
     * @param $mysql_type
     */
    public static function getInstance()
    {
        // define db prefix
        if(!defined('TABLE_PREFIX') || !self::$db)
        {
            $settings = new Registry();
            if ($settings->config['Database']['type'] == 'mysqli')
                self::$db = new MySQLi();
            elseif ($settings->config['Database']['type'] == 'mysql')
                self::$db = new Mysql();
            else
                die('No Database type found edit config.php');
            self::$db->connect($settings->config['Database']['dbname'], $settings->config['Database']['servername'], $settings->config['Database']['username'], $settings->config['Database']['password'], $settings->config['Database']['tableprefix']);
            // define table prefix
            define('TABLE_PREFIX',$settings->config['Database']['tableprefix']);

        }
        return self::$db;

    }

}