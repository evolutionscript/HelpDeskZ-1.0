<?php
/**
 * @package HelpDeskZ
 * @website: http://www.helpdeskz.com
 * @community: http://community.helpdeskz.com
 * @author Evolution Script S.A.C.
 * @since 1.0.0
 */
$client_languages = array();
$staff_languages = array();
foreach(glob(INCLUDES.'language/*.php') as $filename){
 $client_languages[] = str_replace('.php', '',str_replace(INCLUDES.'language/','',$filename));
}
foreach(glob(INCLUDES.'language/staff/*.php') as $filename){
 $staff_languages[] = str_replace('.php', '',str_replace(INCLUDES.'language/staff/','',$filename));
}
$pagesize = array(5,10,15,20,25,30,35,40,45,50);
if($params[1] == 'update_helpdesk'){
	if(verifyToken('general_settings', $input->p['csrfhash']) !== true){
		$error_msg = $LANG['CSRF_ERROR'];	
	}elseif(empty($input->p['site_name']) || empty($input->p['windows_title']) || empty($input->p['date_format']) || !in_array($input->p['page_size'], $pagesize)){
		$error_msg = $LANG['ONE_REQUIRED_FIELDS_EMPTY'];
	}elseif(validateEmail($input->p['email_ticket']) !== true){
		$error_msg = $LANG['DEFAULT_EMAIL_IS_NOT_VALID'];
	}elseif(!filter_var($input->p['site_url'], FILTER_VALIDATE_URL)){
		$error_msg = $LANG['INCORRECT_HELPDESK_URL'];
	}else{
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['site_name']), "field='site_name'");
		$db->update(TABLE_PREFIX."settings", array('value' => (substr($input->p['site_url'],-1) == '/'?substr($input->p['site_url'],0,-1):$input->p['site_url'])), "field='site_url'");
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['windows_title']), "field='windows_title'");
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['email_ticket']), "field='email_ticket'");
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['page_size']), "field='page_size'");
		$db->update(TABLE_PREFIX."settings", array('value' => (in_array($input->p['timezone'], $timezone)?$input->p['timezone']:'')), "field='timezone'");
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['date_format']), "field='date_format'");
		$db->update(TABLE_PREFIX."settings", array('value' => ($input->p['permalink'] == 1?1:0)), "field='permalink'");
		$db->update(TABLE_PREFIX."settings", array('value' => ($input->p['maintenance'] == 1?1:0)), "field='maintenance'");
		$db->update(TABLE_PREFIX."settings", array('value' => (in_array($input->p['client_language'], $client_languages)?$input->p['client_language']:'english')), "field='client_language'");
		$db->update(TABLE_PREFIX."settings", array('value' => (in_array($input->p['staff_language'], $staff_languages)?$input->p['staff_language']:'english')), "field='staff_language'");
		$db->update(TABLE_PREFIX."settings", array('value' => ($input->p['client_multilanguage'] == 1?1:0)), "field='client_multilanguage'");
		
		$settings['permalink'] = ($input->p['permalink'] == 1?1:0);
		header('location: '.getUrl($controller,$action, array('general','helpdesk_updated')));
		exit;	
	}
}elseif($params[1] == 'update_homepage'){
	if(verifyToken('general_settings', $input->p['csrfhash']) !== true){
		$error_msg = $LANG['CSRF_ERROR'];	
	}else{
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['homepage']), "field='homepage'");
		$data = array('title' => $input->p['home_title'],
						'content' => $input->p['home_content'],
						);
		$db->update(TABLE_PREFIX."pages", $data, "id='home'");
		header('location: '.getUrl($controller,$action, array('general','homepage_updated#ctab2')));
		exit;	
	}
}elseif($params[1] == 'update_knowledgebase'){
	if(verifyToken('general_settings', $input->p['csrfhash']) !== true){
		$error_msg = $LANG['CSRF_ERROR'];	
	}else{
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['knowledgebase']), "field='knowledgebase'");
		$db->update(TABLE_PREFIX."settings", array('value' => (is_numeric($input->p['knowledgebase_columns'])?$input->p['knowledgebase_columns']:2)), "field='knowledgebase_columns'");	
		$db->update(TABLE_PREFIX."settings", array('value' => (is_numeric($input->p['knowledgebase_articlesundercat'])?$input->p['knowledgebase_articlesundercat']:2)), "field='knowledgebase_articlesundercat'");	
		$db->update(TABLE_PREFIX."settings", array('value' => (is_numeric($input->p['knowledgebase_articlemaxchar'])?$input->p['knowledgebase_articlemaxchar']:200)), "field='knowledgebase_articlemaxchar'");	
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['knowledgebase_mostpopular']), "field='knowledgebase_mostpopular'");
		$db->update(TABLE_PREFIX."settings", array('value' => (is_numeric($input->p['knowledgebase_mostpopulartotal'])?$input->p['knowledgebase_mostpopulartotal']:3)), "field='knowledgebase_mostpopulartotal'");	
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['knowledgebase_newest']), "field='knowledgebase_newest'");
		$db->update(TABLE_PREFIX."settings", array('value' => (is_numeric($input->p['knowledgebase_newesttotal'])?$input->p['knowledgebase_newesttotal']:3)), "field='knowledgebase_newesttotal'");	
		header('location: '.getUrl($controller,$action, array('general','knowledgebase_updated#ctab3')));
		exit;	
	}
}elseif($params[1] == 'update_news'){
	if(verifyToken('general_settings', $input->p['csrfhash']) !== true){
		$error_msg = $LANG['CSRF_ERROR'];	
	}else{
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['news']), "field='news'");
		$db->update(TABLE_PREFIX."settings", array('value' => (is_numeric($input->p['news_page'])?$input->p['news_page']:4)), "field='news_page'");	
		header('location: '.getUrl($controller,$action, array('general','news_updated#ctab4')));
		exit;	
	}
}elseif($params[1] == 'update_email'){
	if(verifyToken('general_settings', $input->p['csrfhash']) !== true){
		$error_msg = $LANG['CSRF_ERROR'];	
	}else{
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['smtp']), "field='smtp'");
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['smtp_hostname']), "field='smtp_hostname'");
		$db->update(TABLE_PREFIX."settings", array('value' => (is_numeric($input->p['smtp_port'])?$input->p['smtp_port']:25)), "field='smtp_port'");	
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['smtp_ssl']), "field='smtp_ssl'");
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['smtp_username']), "field='smtp_username'");
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['smtp_password']), "field='smtp_password'");

		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['email_piping']), "field='email_piping'");
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['email_piping_trigger_notification']), "field='email_piping_trigger_notification'");

		// Imap Cron Downloader
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['imap_host']), "field='imap_host'");
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['imap_port']), "field='imap_port'");
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['imap_username']), "field='imap_username'");
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['imap_password']), "field='imap_password'");
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['imap_mail_downloader_processaction']), "field='imap_mail_downloader_processaction'");
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['imap_mail_downloader_processaction_folder']), "field='imap_mail_downloader_processaction_folder'");

		header('location: '.getUrl($controller,$action, array('general','email_updated#ctab5')));
		exit;	
	}
}elseif($params[1] == 'update_security'){
	if(verifyToken('general_settings', $input->p['csrfhash']) !== true){
		$error_msg = $LANG['CSRF_ERROR'];	
	}else{
		$db->update(TABLE_PREFIX."settings", array('value' => ($input->p['use_captcha'] == 1?1:0)), "field='use_captcha'");
		$db->update(TABLE_PREFIX."settings", array('value' => (is_numeric($input->p['login_attempt'])?$input->p['login_attempt']:3)), "field='login_attempt'");	
		$db->update(TABLE_PREFIX."settings", array('value' => (is_numeric($input->p['login_attempt_minutes'])?$input->p['login_attempt_minutes']:5)), "field='login_attempt_minutes'");	
		$db->update(TABLE_PREFIX."settings", array('value' => ($input->p['loginshare'] == 1?1:0)), "field='loginshare'");
		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['loginshare_url']), "field='loginshare_url'");
		header('location: '.getUrl($controller,$action, array('general','security_updated#ctab6')));
		exit;	
	}
}elseif($params[1] == 'update_social'){
    if(verifyToken('general_settings', $input->p['csrfhash']) !== true){
        $error_msg = $LANG['CSRF_ERROR'];
    }else{
        $db->update(TABLE_PREFIX."settings", array('value' => ($input->p['facebookoauth'] == 1?1:0)), "field='facebookoauth'");
        $db->update(TABLE_PREFIX."settings", array('value' => $input->p['facebookappid']), "field='facebookappid'");
        $db->update(TABLE_PREFIX."settings", array('value' => $input->p['facebookappsecret']), "field='facebookappsecret'");
        $db->update(TABLE_PREFIX."settings", array('value' => ($input->p['googleoauth'] == 1?1:0)), "field='googleoauth'");
        $db->update(TABLE_PREFIX."settings", array('value' => $input->p['googleclientid']), "field='googleclientid'");
        $db->update(TABLE_PREFIX."settings", array('value' => $input->p['googleclientsecret']), "field='googleclientsecret'");
        $db->update(TABLE_PREFIX."settings", array('value' => $input->p['socialbuttonnews']), "field='socialbuttonnews'");
        $db->update(TABLE_PREFIX."settings", array('value' => $input->p['socialbuttonkb']), "field='socialbuttonkb'");
        header('location: '.getUrl($controller,$action, array('general','security_social#ctab7')));
        exit;
    }
}
$homepage = $db->fetchRow("SELECT * FROM ".TABLE_PREFIX."pages WHERE id='home'");
$page_title = 'Settings > General';

$template_vars['homepage'] = $homepage;
$template_vars['pagesize'] = $pagesize;
$template_vars['timezone'] = $timezone;
$template_vars['error_msg'] = $error_msg;
$template_vars['staff_languages'] = $staff_languages;
$template_vars['client_languages'] = $client_languages;
$template = $twig->loadTemplate('admin_general.html');
echo $template->render($template_vars);
$db->close();
exit;
?>