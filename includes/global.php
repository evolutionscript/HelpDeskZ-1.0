<?php
/**
 * @package HelpDeskZ
 * @website: http://www.helpdeskz.com
 * @community: http://community.helpdeskz.com
 * @author Evolution Script S.A.C.
 * @since 1.0.0
 */
require_once(dirname(__FILE__).'/definces.php');
// add autoload
require_once(dirname(__FILE__).'/vendor/autoload.php');
require_once INCLUDES.'functions.php';
require_once INCLUDES.'timezone.inc.php';
use Classes\Db;
use Classes\InputCleaner;
// DB Connection
$input = new InputCleaner();
$db = Db::getInstance();
$settings = array();
// get all settings
$q = $db->query("SELECT * FROM ".TABLE_PREFIX."settings");
while($r = $db->fetch_array($q)){
	$settings[$r['field']] = $r['value'];
}
if(in_array($settings['timezone'], $timezone)){
	date_default_timezone_set($settings['timezone']);
}