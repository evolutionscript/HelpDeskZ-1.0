<?php
/**
 * @package HelpDeskZ
 * @website: http://www.helpdeskz.com
 * @community: http://community.helpdeskz.com
 * @author Evolution Script S.A.C.
 * @since 1.0.0
 */
$department = $db->fetchRow("SELECT id, name FROM ".TABLE_PREFIX."departments WHERE autoassign=1 LIMIT 1");
if($text != '' && is_array($department)){
	$user = $db->fetchRow("SELECT COUNT(id) AS total, id FROM ".TABLE_PREFIX."users WHERE email='".$db->real_escape_string($from_email)."'");
	$fullname = $from_name;
	$email = $from_email;	
	if($user['total'] == 0){
		$password = substr((md5(time().$fullname)),5,7);
		$data = array('fullname' => $fullname,
						'email' => $email,
						'password' => sha1($password),
					);
		$db->insert(TABLE_PREFIX."users", $data);
		$user_id = $db->lastInsertId();
		/* Mailer */
		$data_mail = array(
		'id' => 'new_user',
		'to' => $fullname,
		'to_mail' => $email,
		'vars' => array('%client_name%' => $fullname, '%client_email%' => $email, '%client_password%' => $password),
		);
		$mailer = new Mailer($data_mail);
	}else{
		$user_id = $user['id'];	
	}
	$ticket_id = substr(strtoupper(sha1(time().$email)), 0, 11);
	$ticket_id = substr_replace($ticket_id, '-',3,0);
	$ticket_id = substr_replace($ticket_id, '-',7,0);
	$previewcode = substr((md5(time().$fullname)),2,12);
	$data = array(
					'code' => $ticket_id,
					'department_id' => $department['id'],
					'priority_id' => 1,
					'user_id' => $user_id,
					'fullname' => $fullname,
					'email' => $email,
					'subject' => $subject,
					'date' => $datenow,
					'last_update' => $datenow,
					'previewcode' => $previewcode,
					'last_replier' => $fullname,
				);
	$db->insert(TABLE_PREFIX.'tickets', $data);
	$ticketid = $db->lastInsertId();
	$data = array(
					'ticket_id' => $ticketid,
					'date' => time(),
					'message' => ($text),
					'ip' => $_SERVER['REMOTE_ADDR'],
					'email' => $email,
				);
	$db->insert(TABLE_PREFIX.'tickets_messages', $data);
	$message_id = $db->lastInsertId();
	if(is_array($attachments)){
		$save_dir = UPLOAD_DIR;
		foreach($attachments as $attachment) {
		  // get the attachment name
		  $filename = $attachment->filename;
		  // write the file to the directory you want to save it in
		  if ($fp = fopen($save_dir.$filename, 'w')) {
			while($bytes = $attachment->read()) {
			  fwrite($fp, $bytes);
			}
			fclose($fp);
		  }
			
		  $filesize = @filesize(UPLOAD_DIR.$filename);
		  if($filesize){
			  $fileinfo = array('name' => $filename, 'size' => $filesize);
			  $fileverification = verifyAttachment($fileinfo);
			  if($fileverification['msg_code'] == 0){
				$ext = pathinfo($filename, PATHINFO_EXTENSION);
				$filename_encoded = md5($filename.time()).".".$ext;
				$data = array('name' => $filename, 'enc' => $filename_encoded, 'filesize' => $filesize, 'ticket_id' => $ticketid, 'msg_id' => $message_id, 'filetype' => $attachment->content_type);
				$db->insert(TABLE_PREFIX."attachments", $data);
				rename(UPLOAD_DIR.$filename, UPLOAD_DIR.'tickets/'.$filename_encoded);
			  }else{
				unlink(UPLOAD_DIR.$filename);
			  }
		  }
		}
	}
	/* Mailer */
	$data_mail = array(
	'id' => 'new_ticket',
	'to' => $fullname,
	'to_mail' => $email,
	'vars' => array('%client_name%' => $fullname, 
					'%client_email%' => $email, 
					'%ticket_id%' => $ticket_id,
					'%ticket_subject%' => $subject,
					'%ticket_department%' => $department['name'],
					'%ticket_status%' => $LANG['OPEN'],
					'%ticket_priority%' => 'Low',
					),
	);
	$mailer = new Mailer($data_mail);

	if ($settings['email_piping_trigger_notification']){
		/* New ticket notification for staff */
		$q = $db->query("SELECT id, fullname, email, department FROM ".TABLE_PREFIX."staff WHERE newticket_notification=1 AND status='Enable'");
		while($r = $db->fetch_array($q))
		{
			$department_list = unserialize($r['department']);
			$department_list = (is_array($department_list)?$department_list:array());
			if(in_array($department['id'],$department_list))
			{
				/* Mailer */
				$data_mail = array(
					'id' => 'staff_ticketnotification',
					'to' => $r['fullname'],
					'to_mail' => $r['email'],
					'vars' => array('%staff_name%' => $r['fullname'],
						'%staff_name%' => $r['fullname'],
						'%ticket_id%' => $ticket_id,
						'%ticket_subject%' => $input->p['subject'],
						'%ticket_department%' => $department['name'],
						'%ticket_status%' => $LANG['OPEN'],
						'%ticket_priority%' => $priorityvar['name'],
					),
				);
				$mailer = new Mailer($data_mail);
			}
		}
	}
}
?>