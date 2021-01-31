<?php
namespace Classes;
/**
 * @package HelpDeskZ
 * @website: http://www.helpdeskz.com
 * @community: http://community.helpdeskz.com
 * @author Evolution Script S.A.C.
 * @since 1.0.0
 */
class Registry
{
	public $input;
	public $config;
	public function  __construct()
	{
		define('CWD', (($getcwd = getcwd()) ? $getcwd : '.'));
		$config = array();
		include(INCLUDES.'config.php');
		if (sizeof($config) == 0)
		{
			if (file_exists('config.php'))
			{
				// config.php exists, but does not define $config
				die('<br /><br /><strong>Configuration</strong>: includes/config.php exists, but is not in the correct format. Please convert your config file via the new config.php.new.');
			}
			else
			{
				die('<br /><br /><strong>Configuration</strong>: includes/config.php does not exist. Please fill out the data in config.php.new and rename it to config.php');
			}
		}
		
		$this->config =& $config;
		//define('TABLE_PREFIX', trim($this->config['Database']['tableprefix']));
	}
}