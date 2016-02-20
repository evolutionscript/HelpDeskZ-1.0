#!/usr/bin/php -q
<?php

ini_set('error_reporting',E_ALL& ~E_NOTICE);

define('INCLUDES','./');
define('UPLOAD_DIR','../uploads/');
require_once INCLUDES.'parser/MimeMailParser.class.php';
require_once INCLUDES.'classes/classRegistry.php';
require_once INCLUDES.'classes/classMailer.php';
require_once INCLUDES.'functions.php';
// DB Connection
$helpdeskz = new Registry();
if($helpdeskz->config['Database']['type'] == 'mysqli'){
    require_once INCLUDES.'classes/classMysqli.php';
    $db = new MySQLIDB();
}else{
    require_once INCLUDES.'classes/classMysql.php';
    $db = new MySQLDB();
}
$db->connect($helpdeskz->config['Database']['dbname'], $helpdeskz->config['Database']['servername'], $helpdeskz->config['Database']['username'], $helpdeskz->config['Database']['password'], $helpdeskz->config['Database']['tableprefix']);
//Settings
$settings = array();
$q = $db->query("SELECT * FROM ".TABLE_PREFIX."settings");
while($r = $db->fetch_array($q)){
    $settings[$r['field']] = $r['value'];
}
if($settings['email_piping'] == 'no'){
    exit;
}


if (version_compare(PHP_VERSION, '5.3.0', '<')) {
    echo "This script requires PHP version 5.3.0 or higher to work, sorry.";
    exit(1);
}

// because version 5.3.0 is required, we could in theory use the autoloader :)
//require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'autoload.php';

// ... but this project is not so much inteded to be installed through composer etc which is a bit of a pain, but that's okay.
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tomaj' . DIRECTORY_SEPARATOR . 'imap-mail-downloader' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'ProcessAction.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tomaj' . DIRECTORY_SEPARATOR . 'imap-mail-downloader' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Downloader.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tomaj' . DIRECTORY_SEPARATOR . 'imap-mail-downloader' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'MailCriteria.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tomaj' . DIRECTORY_SEPARATOR . 'imap-mail-downloader' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Email.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tomaj' . DIRECTORY_SEPARATOR . 'imap-mail-downloader' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'ImapException.php';


use Tomaj\ImapMailDownloader\ProcessAction;
use Tomaj\ImapMailDownloader\Downloader;
use Tomaj\ImapMailDownloader\MailCriteria;
use Tomaj\ImapMailDownloader\Email;
use Tomaj\ImapMailDownloader\ImapException;

$imapHost = $settings['imap_host'];
$imapPort = intval($settings['imap_port']);
$imapUsername = $settings['imap_username'];
$imapPassword = $settings['imap_password'];

switch ($settings['imap_mail_downloader_processaction']) {
    case ProcessAction::ACTION_DELETE:
        $defaultProcessAction = ProcessAction::delete();
        break;

    case ProcessAction::ACTION_MOVE:
        $folder = 'INBOX/'.$settings['imap_mail_downloader_processaction_folder'];
        $defaultProcessAction = ProcessAction::callback(function($mailbox,$emailIndex)use($folder){
            imap_setflag_full($mailbox,$emailIndex, "\\Seen",0);
            imap_mail_move($mailbox,$emailIndex,$folder,0);
        });
        break;

    default:
        throw new Exception("Invalid process action: {$settings['imap_mail_downloader_processaction']}");
}

//$defaultProcessAction = ProcessAction::move('INBOX/processed');

$downloader = new Downloader($imapHost, $imapPort, $imapUsername, $imapPassword);
$downloader->setProcessedFoldersAutomake(true)
    ->setDefaultProcessAction($defaultProcessAction);


$criteria = new MailCriteria();
$criteria->setUnseen(true);


function pipe($data)
{

    $descriptorspec = array(
        0 => array("pipe", "r"),  // STDIN ist eine Pipe, von der das Child liest
        1 => array("pipe", "w"),  // STDOUT ist eine Pipe, in die das Child schreibt
        2 => array("pipe", "w") // STDERR ist eine Datei,
        // in die geschrieben wird
    );

    $cmd = 'php ' . escapeshellarg(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'pipe.php');

    $cwd = dirname(__FILE__);

    $env = array();

    $process = proc_open($cmd, $descriptorspec, $pipes, $cwd, $env);

    if (is_resource($process)) {

        fwrite($pipes[0], $data);
        fclose($pipes[0]);

        if ($pipes[1] !== null) {
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
        }

        if ($pipes[2] !== null) {
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
        }

        $return_value = proc_close($process);

        if ($return_value != 0) {
            throw new Exception("Unexpected return value {$return_value}.\r\nSTDOUT\r\n{$stdout}\r\nSTDERR\r\n{$stderr}");
        }
    }
}

try {
    $downloader->fetch($criteria, function (Email $email) {

//        echo $email->getFrom() ."\r\n";

        pipe($email->getSource());

        return true;
    }, Downloader::FETCH_SOURCE | Downloader::FETCH_OVERVIEW);

} catch (ImapException $e) {
    // this is an imap exception/error
    echo $e->getMessage() . "\r\n";
    exit(1);

} catch (Exception $e){
    // this is an application exception
    echo $e->getMessage() . "\r\n";
    exit(2);
}
