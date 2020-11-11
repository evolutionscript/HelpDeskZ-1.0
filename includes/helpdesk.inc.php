<?php
/**
 * @package HelpDeskZ
 * @website: http://www.helpdeskz.com
 * @community: http://community.helpdeskz.com
 * @author Evolution Script S.A.C.
 * @since 1.0.0
 */
if(is_array($_SESSION['user'])){
	$user = $db->fetchRow("SELECT * FROM ".TABLE_PREFIX."users WHERE id=".$db->real_escape_string($_SESSION['user']['id']));
	if(is_array($user) && $user['email'] == $_SESSION['user']['email'] && $user['password'] == $_SESSION['user']['password']){
		$client_status = 1;
	}else{
		clientLogout();
	}
}elseif(isset($_COOKIE['usrhash'])){
	$data = decrypt($_COOKIE['usrhash']);
	$data = unserialize($data);
	if(is_array($data) && is_numeric($data['expires']) && $data['expires'] > time() && is_numeric($data['id'])){
		$user = $db->fetchRow("SELECT * FROM ".TABLE_PREFIX."users WHERE id=".$db->real_escape_string($data['id']));
		if(is_array($user) && $user['email'] == $data['email'] && $user['password'] == $data['password']){
			$_SESSION['user']['id'] = $user['id'];
			$_SESSION['user']['email'] = $user['email'];
			$_SESSION['user']['password'] = $user['password'];
			$client_status = 1;
		}else{
			clientLogout();
		}
	}else{
		clientLogout();
	}
}		
if($client_status == 1){
	if(in_array($user['timezone'], $timezone)){
		date_default_timezone_set($user['timezone']);
	}	
}
//Language
$client_languages = array();
if($settings['client_multilanguage'] == 1){
	foreach(glob(INCLUDES.'language/*.php') as $filename){
	 $client_languages[] = str_replace('.php', '',str_replace(INCLUDES.'language/','',$filename));
	}
}else{
	$client_languages[] = 'english';
}
if($input->g['lang']){
	if(in_array($input->g['lang'], $client_languages)){
		setcookie('lang',$input->g['lang'], time()+604800);
	}
	header('location: '.$settings['site_url']);
	exit;
}
if(isset($_COOKIE['lang'])){
	if(in_array($_COOKIE['lang'], $client_languages)){
		$default_language = $_COOKIE['lang'];	
	}else{
		$default_language = $settings['client_language'];
		setcookie('lang',$default_language, time()+604800);		
	}
}else{
	$default_language = $settings['client_language'];
	setcookie('lang',$default_language, time()+604800);
}

include(INCLUDES.'language/'.$default_language.'.php');
/* Template Loader */

include(INCLUDES.'Twig/Autoloader.php');
Twig_Autoloader::register();
$loader = new Twig_Loader_Filesystem(ROOTPATH.'views/client');
$twig = new Twig_Environment($loader);
$twig->addGlobal('controller', $controller);
$twig->addGlobal('action', $action);
$twig->addGlobal('params', $params);
$twig->addGlobal('settings', $settings);
$twig->addGlobal('LANG', $LANG);
$twig->addGlobal('input', $input);
$twig->addGlobal('client_status', $client_status);
$twig->addGlobal('error_msg', $error_msg);
$twig->addGlobal('client_languages', $client_languages);
$twig->addGlobal('default_language', $default_language);
$twig->addFunction('success_message', new Twig_Function_Function('success_message'));
$twig->addFunction('getUrl', new Twig_Function_Function('getUrl'));
$twig->addFunction('getToken', new Twig_Function_Function('getToken'));
$twig->addFunction('error_message', new Twig_Function_Function('error_message'));
$twig->addFunction('displayDate', new Twig_Function_Function('displayDate'));
$twig->addFunction('ticketpaginator', new Twig_Function_Function('ticketpaginator'));

$twig->addFilter('is_array', new Twig_Filter_Function('is_array'));
$twig->addFilter('print_r', new Twig_Filter_Function('print_r'));

if($settings['maintenance'] == 1){
	if($client_status == 1 && !is_array($_SESSION['staff'])){
		clientLogout();
	}
	if(!is_array($_SESSION['staff'])){
		$template_vars['error_msg'] = $error_msg;
		$template = $twig->loadTemplate('maintenance.html');
		echo $template->render($template_vars);
		$db->close();
		exit;	
	}
}
?>
