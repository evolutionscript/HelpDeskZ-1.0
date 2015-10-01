#!/usr/bin/php -q
<?php
/**
 * @package HelpDeskZ
 * @website: http://www.helpdeskz.com
 * @community: http://community.helpdeskz.com
 * @author Evolution Script S.A.C.
 * @since 1.0.0
 */
define('ROOTPATH', dirname(dirname(__FILE__)).'/');
define('INCLUDES', ROOTPATH . 'includes/');
define('UPLOAD_DIR',ROOTPATH.'uploads/');

require_once INCLUDES.'global.php';
require_once INCLUDES.'parser/MimeMailParser.class.php';

if($settings['email_piping'] == 'no'){
	exit;	
}
include(INCLUDES.'language/'.$settings['client_language'].'.php');
$text = file_get_contents('php://stdin');
$Parser = new MimeMailParser();
$Parser->setText($text);
$to = $Parser->getHeader('to');
$from = $Parser->getHeader('from');
$text = $Parser->getMessageBody('text');
$attachments = $Parser->getAttachments();

$subject = $Parser->getHeader('subject');
$subject2 = imap_mime_header_decode($subject);
for ($i=0; $i<count($subject2); $i++) {
	$subjectdecoded .= $subject2[$i]->text;
}
$subject = $subjectdecoded;

if(strpos ($from, '<') !== false)
{
    $from2 = explode ('<', $from);
    $from3 = explode ('>', $from2[1]);
	$from_name = trim($from2[0]);
    $from_email = trim($from3[0]);
}else{
	$from_name = $from;
	$from_email = $from;
}

$datenow = time();
if($subject){
	if(preg_match ("/\#[[a-zA-Z0-9_]+\-[a-zA-Z0-9_]+\-[a-zA-Z0-9_]+\]/",$subject,$regs)) {
		include(INCLUDES.'parser/reply_ticket.php');
	}else{
		//New Ticket
		include(INCLUDES.'parser/new_ticket.php');
	}
}
?>
