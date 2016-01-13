<?php
/**
 * @package HelpDeskZ
 * @website: http://www.helpdeskz.com
 * @community: http://community.helpdeskz.com
 * @author Evolution Script S.A.C.
 * @since 1.0.0
 */
include(INCLUDES.'helpdesk.inc.php');
$template_vars = array();
$emptyvars = array();
if($action == 'displayForm' || $action == 'confirmation'){
	$display_error = 1;
	if($action == 'displayForm'){
		if(is_numeric($input->p['department'])){
			$department_id = $input->p['department'];
			$department = $db->fetchRow("SELECT COUNT(*) AS total, name FROM ".TABLE_PREFIX."departments WHERE id=$department_id AND type=0");
			if($department['total'] != 0){
				$show_step2 = true;
			}
		}
	}elseif($action == 'confirmation'){
	$display_error = 1;
	if(verifyToken('submit_ticket', $input->p['csrfhash']) !== true){
		$display_error = 2;
	}else{
		if(is_numeric($input->p['department'])){
			$department_id = $input->p['department'];
			$department = $db->fetchRow("SELECT COUNT(*) AS total, name FROM ".TABLE_PREFIX."departments WHERE id=$department_id AND type=0");
			if($department['total'] != 0){
				$required_fields = array('fullname', 'email', 'priority', 'subject', 'message');
				if($settings['use_captcha']){
					$required_fields[] = 'captcha';
					if(strtoupper($input->p['captcha']) != $_SESSION['captcha']){
						$show_step2 = true;
						$emptyvars[] = 'captcha';
						$error_msg = $LANG['INVALID_CAPTCHA_CODE'];
						unset($_SESSION['captcha']);			
					}
				}	
				if($client_status == 1){
					unset($required_fields[0]);
					unset($required_fields[1]);			
				}else{
					if(validateEmail($input->p['email']) !== TRUE){
						$show_step2 = true;
						$emptyvars[] = 'email';
						$error_msg = $LANG['INVALID_EMAIL_ADDRESS'];
					}
				}
				if(!is_numeric($input->p['priority'])){
						$show_step2 = true;
						$emptyvars[] = 'priority';
						$error_msg = $LANG['ONE_REQUIRED_FIELD_EMPTY'];
				}else{
					$priorityvar = $db->fetchRow("SELECT COUNT(id) AS total, name FROM ".TABLE_PREFIX."priority WHERE id=".$db->real_escape_string($input->p['priority']));
					if($priorityvar['total'] == 0){
						$show_step2 = true;
						$emptyvars[] = 'priority';
						$error_msg = $LANG['ONE_REQUIRED_FIELD_EMPTY'];
					}
				}
				foreach($required_fields as $v){
					if(empty($input->p[$v])){
						$emptyvars[] = $v;
						$show_step2 = true;
						$error_msg = $LANG['ONE_REQUIRED_FIELD_EMPTY'];
					}
				}
				$q = $db->query("SELECT * FROM ".TABLE_PREFIX."custom_fields ORDER BY display ASC");
				while($r = $db->fetch_array($q)){
					if($r['required'] == 1){
						if($input->p['custom'][$r['id']] != ''){
							if($r['type'] == 'checkbox' || $r['type'] == 'radio' || $r['type'] == 'select'){
								$customvals = unserialize($r['value']);
								$customvals = (is_array($customvals)?$customvals:array());
								if($r['type'] != 'checkbox'){
									if(!array_key_exists($input->p['custom'][$r['id']], $customvals)){
									$show_step2 = true;
									$emptyvars[] = 'custom_'.$r['id'];
									$error_msg = $LANG['ONE_REQUIRED_FIELD_EMPTY'];									
									}else{
										$custom_post[$r['id']] = $input->p['custom'][$r['id']];
									}
								}else{
									foreach($input->p['custom'][$r['id']] as $k => $v){
										if(!array_key_exists($k, $customvals)){
											$show_step2 = true;
											$emptyvars[] = 'custom_'.$r['id'];
											$error_msg = $LANG['ONE_REQUIRED_FIELD_EMPTY'];	
											break;
										}
									}
									if($show_step2 !== true){
										$custom_post[$r['id']] = $input->p['custom'][$r['id']];
									}
								}
							}else{
								$custom_post[$r['id']] = $input->p['custom'][$r['id']];
							}
						}else{
							$show_step2 = true;
							$emptyvars[] = 'custom_'.$r['id'];
							$error_msg = $LANG['ONE_REQUIRED_FIELD_EMPTY'];
						}
					}else{
						if(($r['type'] == 'checkbox' || $r['type'] == 'radio' || $r['type'] == 'select') && $input->p['custom'][$r['id']] != ''){
							$customvals = unserialize($r['value']);
							$customvals = (is_array($customvals)?$customvals:array());
							if($r['type'] != 'checkbox'){
								if(!array_key_exists($input->p['custom'][$r['id']], $customvals)){
								$show_step2 = true;
								$emptyvars[] = 'custom_'.$r['id'];
								$error_msg = $LANG['ONE_REQUIRED_FIELD_EMPTY'];
								}else{
									$custom_post[$r['id']] = $input->p['custom'][$r['id']];
								}
							}else{
								foreach($input->p['custom'][$r['id']] as $k => $v){
									if(!array_key_exists($k, $customvals)){
										$show_step2 = true;
										$emptyvars[] = 'custom_'.$r['id'];
										$error_msg = $LANG['ONE_REQUIRED_FIELD_EMPTY'];	
										break;
									}
								}
								if($show_step2 !== true){
									$custom_post[$r['id']] = $input->p['custom'][$r['id']];
								}
							}
						}else{
							$custom_post[$r['id']] = $input->p['custom'][$r['id']];
						}
					}
				}
	
				if(!isset($error_msg) && $settings['ticket_attachment']==1){
					$uploaddir = UPLOAD_DIR.'tickets/';		
					if($_FILES['attachment']['error'] == 0){
						$ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
						$filename = md5($_FILES['attachment']['name'].time()).".".$ext;
						$fileuploaded[] = array('name' => $_FILES['attachment']['name'], 'enc' => $filename, 'size' => formatBytes($_FILES['attachment']['size']), 'filetype' => $_FILES['attachment']['type']);
						$uploadedfile = $uploaddir.$filename;
						if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadedfile)) {
							$show_step2 = true;
							$error_msg = $LANG['ERROR_UPLOADING_A_FILE'];
						}else{
							$fileverification = verifyAttachment($_FILES['attachment']);
							switch($fileverification['msg_code']){
								case '1':
								$show_step2 = true;
								$error_msg = $LANG['INVALID_FILE_EXTENSION'];
								break;
								case '2':
								$show_step2 = true;
								$error_msg = $LANG['FILE_NOT_ALLOWED'];
								break;
								case '3':
								$show_step2 = true;
								$error_msg = str_replace('%size%',$fileverification['msg_extra'],$LANG['FILE_IS_BIG']);
								break;
							}
						}
					}	
				}
				
				if($show_step2 !== true){
					if($client_status == 1){
						$fullname = $user['fullname'];
						$email = $user['email'];
						$user_id = $user['id'];
					}else{
                        $fullname = $input->p['fullname'];
                        $email = $input->p['email'];
                        $user_id = hdz_registerAccount(array('fullname' => $input->p['fullname'],
                                                            'email' =>$input->p['email']));
					}
					$ticket_id = substr(strtoupper(sha1(time().$email)), 0, 11);
					$ticket_id = substr_replace($ticket_id, '-',3,0);
					$ticket_id = substr_replace($ticket_id, '-',7,0);
					$previewcode = substr((md5(time().$fullname)),2,12);
					$custom_post = serialize($custom_post);
					$data = array(
									'code' => $ticket_id,
									'department_id' => $department_id,
									'priority_id' => $input->p['priority'],
									'user_id' => $user_id,
									'fullname' => $fullname,
									'email' => $email,
									'subject' => $input->p['subject'],
									'date' => time(),
									'last_update' => time(),
									'previewcode' => $previewcode,
									'last_replier' => $fullname,
									'custom_vars' => $custom_post,
								);
					$db->insert(TABLE_PREFIX.'tickets', $data);
					$ticketid = $db->lastInsertId();
					$data = array(
									'ticket_id' => $ticketid,
									'date' => time(),
									'message' => $input->p['message'],
									'ip' => $_SERVER['REMOTE_ADDR'],
									'email' => $email,
								);
					$db->insert(TABLE_PREFIX.'tickets_messages', $data);
					$message_id = $db->lastInsertId();
					if(is_array($fileuploaded)){
						foreach($fileuploaded as $f){
							$data = array('name' => $f['name'], 'enc' => $f['enc'], 'filesize' => $f['size'], 'ticket_id' => $ticketid, 'msg_id' => $message_id, 'filetype' => $f['filetype']);
							$db->insert(TABLE_PREFIX."attachments", $data);
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
									'%ticket_subject%' => $input->p['subject'],
									'%ticket_department%' => $department['name'],
									'%ticket_status%' => $LANG['OPEN'],
									'%ticket_priority%' => $priorityvar['name'],
									),
					);
					$mailer = new Mailer($data_mail);

                    /* New ticket notification for staff */
                    $q = $db->query("SELECT id, fullname, email, department FROM ".TABLE_PREFIX."staff WHERE newticket_notification=1 AND status='Enable'");
                    while($r = $db->fetch_array($q))
                    {
                        $department_list = unserialize($r['department']);
                        $department_list = (is_array($department_list)?$department_list:array());
                        if(in_array($department_id,$department_list))
                        {
                            /* Mailer */
                            $data_mail = array(
                                'id' => 'staff_ticketnotification',
                                'to' => $r['fullname'],
                                'to_mail' => $r['email'],
                                'vars' => array('%staff_name%' => $r['fullname'],
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

					unset($_SESSION['captcha']);
					header('location: '.getUrl('submit_ticket','confirmationMsg',array($ticket_id,$previewcode)));
					exit;
				}
			}
		}	
	}	
	}
	if($show_step2 == true){
		$priority_query = $db->query("SELECT * FROM ".TABLE_PREFIX."priority ORDER BY ID ASC");
		while($r = $db->fetch_array($priority_query)){
			$priority[] = $r;
		}
		$customq = $db->query("SELECT * FROM ".TABLE_PREFIX."custom_fields ORDER BY display ASC");
		while($r = $db->fetch_array($customq)){
			if($r['type'] == 'checkbox' || $r['type'] == 'radio' || $r['type'] == 'select'){
				$r['value'] = unserialize($r['value']);
				$r['value'] = (is_array($r['value'])?$r['value']:array());
			}
			$customfields[] = $r;
		}
		$template_vars['emptyvars'] = $emptyvars;
		$template_vars['error_msg'] = $error_msg;
		$template_vars['department_id'] = $department_id;
		$template_vars['priority'] = $priority;
		$template_vars['customfields'] = $customfields;
		$template_vars['POST'] = $input->p;
		$template = $twig->loadTemplate('submit_ticket_step2.html');
		echo $template->render($template_vars);
		$db->close();
		exit;
	}
}elseif($action == 'confirmationMsg'){
	$ticket_id = $params[0];
	$previewcode = $params[1];
	if(empty($ticket_id) || empty($previewcode)){
		header('location: '.getUrl('submit_ticket'));
		exit;	
	}else{
		$ticket = $db->fetchRow("SELECT COUNT(".TABLE_PREFIX."tickets.id) as total, ".TABLE_PREFIX."tickets.code, ".TABLE_PREFIX."tickets.previewcode, ".TABLE_PREFIX."tickets.priority_id, ".TABLE_PREFIX."tickets.fullname, ".TABLE_PREFIX."tickets.email, ".TABLE_PREFIX."tickets.subject, (SELECT ".TABLE_PREFIX."tickets_messages.message FROM ".TABLE_PREFIX."tickets_messages WHERE ".TABLE_PREFIX."tickets_messages.ticket_id=".TABLE_PREFIX."tickets.id ORDER BY ".TABLE_PREFIX."tickets_messages.date ASC LIMIT 1) as message FROM ".TABLE_PREFIX."tickets WHERE ".TABLE_PREFIX."tickets.code='".$db->real_escape_string($ticket_id)."' AND ".TABLE_PREFIX."tickets.previewcode='".$db->real_escape_string($previewcode)."'");
		if($ticket['total'] == 0){
			header('location: '.getUrl('submit_ticket'));
			exit;	
		}else{
			$ticket['message'] = nl2br($ticket['message']);
			$priority = $db->fetchOne("SELECT name FROM ".TABLE_PREFIX."priority WHERE id={$ticket['priority_id']}");
			$template_vars['priority'] = $priority;
			$template_vars['ticket'] = $ticket;
			$template = $twig->loadTemplate('submit_ticket_confirmation.html');
			echo $template->render($template_vars);
			$db->close();
			exit;
		}
	}
}

$q = $db->query("SELECT * FROM ".TABLE_PREFIX."departments WHERE type=0 ORDER BY dep_order ASC");
while($r = $db->fetch_array($q)){
	$departments[] = $r;
}
$template_vars['departments'] = $departments;
$template_vars['display_error'] = $display_error;
$template_vars['error_msg'] = $error_msg;
$template = $twig->loadTemplate('submit_ticket.html');
echo $template->render($template_vars);
$db->close();
exit;
?>