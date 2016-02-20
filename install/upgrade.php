<?php
/**
 * @package HelpDeskZ
 * @website: http://www.helpdeskz.com
 * @community: http://community.helpdeskz.com
 * @author Evolution Script S.A.C.
 * @since 1.0.2
 */
error_reporting(E_ALL & ~E_NOTICE);
session_start();
require_once __DIR__.'/functions.php';
include('../includes/global.php');
include(INCLUDES.'helpdesk.inc.php');

/**
 * Retrieves current version from database. Serves as an authorative source for the current version, and step
 * verification.
 * @return bool
 */
function helpdeskz_getCurrentVersion(){
    global $db;
    $q = $db->query("SELECT `value` as version FROM ".TABLE_PREFIX."settings WHERE `field`='helpdeskz_version'");
    $r = $db->fetch_array($q);
    if ($r){
        return $r['version'];
    }
    return FALSE;
}

function helpdeskz_notneedupdate()
{
    helpdeskz_header();
    echo 'You are using the lastest version of HelpDeskZ, you do not need to make an upgrade.';
    helpdeskz_footer();
}
function helpdeskz_upgradecompleted()
{
    helpdeskz_header();
    echo 'Your helpdesk has been successfully upgraded to version '.HELPDESKZ_VERSION.'.';
    helpdeskz_footer();
}
function helpdeskz_upgradefailed($reasons = array())
{
    helpdeskz_header();
    echo 'Upgrade to HelpDeskZ version <strong>'.HELPDESKZ_VERSION.'</strong> could <i>NOT</i> be successfully completed:';
    if (empty($reasons)) {
        echo 'Reasons unknown';
    } else {
        echo '<ul><li>'.implode('</li><li>', $reasons).'</li></ul>';
    }

    echo '<div align="center"><button onclick="location.href=\'./upgrade.php?process=start\';">Attempt again to upgrade HelpDeskZ</button></div>';
    helpdeskz_footer();
}
function helpdeskz_startupgrade()
{
    helpdeskz_header();
    echo 'Your HelpDeskZ will be updated to version <strong>'.HELPDESKZ_VERSION.'</strong>, please click in the button below to start with this process.';
    echo '<div align="center"><button onclick="location.href=\'./upgrade.php?process=start\';">Upgrade my HelpDeskZ</button></div>';
    helpdeskz_footer();
}

function helpdeskz_102(){
    global $db;
    $db->query("INSERT INTO `".TABLE_PREFIX."settings` (`field` ,`value`)VALUES ('facebookoauth', '0');");
    $db->query("INSERT INTO `".TABLE_PREFIX."settings` (`field` ,`value`)VALUES ('facebookappid', NULL), ('facebookappsecret', NULL);");
    $db->query("INSERT INTO `".TABLE_PREFIX."settings` (`field` ,`value`)VALUES ('googleoauth', '0');");
    $db->query("INSERT INTO `".TABLE_PREFIX."settings` (`field` ,`value`)VALUES ('googleclientid', NULL), ('googleclientsecret', NULL);");
    $db->query("INSERT INTO `".TABLE_PREFIX."settings` (`field` ,`value`)VALUES ('socialbuttonnews', '0');");
    $db->query("INSERT INTO `".TABLE_PREFIX."emails` (`id`, `orderlist`, `name`, `subject`, `message`) VALUES
    ('staff_ticketnotification', 6, 'New ticket notification to staff', 'New ticket notification', 'Dear %staff_name%,\r\n\r\nA new ticket has been created in department assigned for you, please login to staff panel to answer it.\r\n\r\n\r\nTicket Details\r\n---------------\r\n\r\nTicket ID: %ticket_id%\r\nDepartment: %ticket_department%\r\nStatus: %ticket_status%\r\nPriority: %ticket_priority%\r\n\r\n\r\nHelpdesk: %helpdesk_url%');");
    $db->query("INSERT INTO `".TABLE_PREFIX."settings` (`field` ,`value`)VALUES ('socialbuttonkb', '0');");
    $db->query("ALTER TABLE `".TABLE_PREFIX."staff` ADD `newticket_notification` SMALLINT( 1 ) NOT NULL DEFAULT '0';");
    $db->update(TABLE_PREFIX."settings", array('value' => '1.0.2'), "field='helpdeskz_version'");
}

function helpdeskz_103(){
    global $db;
    $db->query("INSERT INTO `".TABLE_PREFIX."settings` (`field` ,`value`)VALUES ('imap_host', '0');");
    $db->query("INSERT INTO `".TABLE_PREFIX."settings` (`field` ,`value`)VALUES ('imap_port', '143');");
    $db->query("INSERT INTO `".TABLE_PREFIX."settings` (`field` ,`value`)VALUES ('imap_username', '');");
    $db->query("INSERT INTO `".TABLE_PREFIX."settings` (`field` ,`value`)VALUES ('imap_password', '');");
    $db->query("INSERT INTO `".TABLE_PREFIX."settings` (`field` ,`value`)VALUES ('imap_mail_downloader_processaction', 'move');");
    $db->query("INSERT INTO `".TABLE_PREFIX."settings` (`field` ,`value`)VALUES ('imap_mail_downloader_processaction_folder', 'processed');");
    $db->query("INSERT INTO `".TABLE_PREFIX."settings` (`field` ,`value`)VALUES ('email_piping_trigger_notification', 'no');");
    $db->query("INSERT INTO `".TABLE_PREFIX."settings` (`field` ,`value`)VALUES ('email_on_new_reply', 'no');");

    $db->query("INSERT INTO `".TABLE_PREFIX."emails` (`id`, `orderlist`, `name`, `subject`, `message`) VALUES
    ('staff_ticketupdate_notification', 7, 'Ticket update notification to staff', '[#%ticket_id%] %ticket_subject% (Update)', 'Dear %staff_name%,\r\n\r\nA ticket has been updated in department assigned for you, please login to staff panel to answer it.\r\n\r\n\r\nTicket Details\r\n---------------\r\n\r\nTicket ID: %ticket_id%\r\nDepartment: %ticket_department%\r\nStatus: %ticket_status%\r\nPriority: %ticket_priority%\r\n\r\n\r\nHelpdesk: %helpdesk_url%\r\n\r\n%message%');");

    $db->query("ALTER TABLE `".TABLE_PREFIX."departments` ADD COLUMN `autoassign_web` INT( 1 ) NOT NULL DEFAULT  '0';");

    $db->update(TABLE_PREFIX."settings", array('value' => '1.0.3'), "field='helpdeskz_version'");
}


if(helpdeskz_getCurrentVersion() >= HELPDESKZ_VERSION)
{
    if($_GET['process'] == 'completed')
    {
        helpdeskz_upgradecompleted();
    }else{
        helpdeskz_notneedupdate();
    }
}else{
    if($_GET['process'] == 'start')
    {
        if(helpdeskz_getCurrentVersion() == '1.0')
        {
            helpdeskz_102();
        }
        if (helpdeskz_getCurrentVersion() == '1.0.2') {
            helpdeskz_103();
        }

        if (helpdeskz_getCurrentVersion() == HELPDESKZ_VERSION){
            header('Location: upgrade.php?process=completed');
            exit;
        } else {
            helpdeskz_upgradeFailed();
        }
    }else{
        helpdeskz_startupgrade();
    }
}
