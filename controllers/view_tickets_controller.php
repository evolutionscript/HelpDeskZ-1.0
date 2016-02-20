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
$ticket_status = array(1 => $LANG['OPEN'], 2 => $LANG['ANSWERED'], 3 => $LANG['AWAITING_REPLY'], 4 => $LANG['IN_PROGRESS'], 5 => $LANG['CLOSED']);
$template_vars['ticket_status'] = $ticket_status;

	if($client_status == 1){
		if($action == 'ticket'){
			if(!is_numeric($params[0])){
				header('location: '.getUrl('view_tickets'));
				exit;	
			}
			$ticket = $db->fetchRow("SELECT COUNT(id) AS total, id, code, department_id, fullname, email, date, last_update, status, subject, (SELECT name FROM ".TABLE_PREFIX."departments WHERE id=".TABLE_PREFIX."tickets.department_id) as department, (SELECT name FROM ".TABLE_PREFIX."priority WHERE id=".TABLE_PREFIX."tickets.priority_id) as priority  FROM ".TABLE_PREFIX."tickets WHERE ".TABLE_PREFIX."tickets.id=".$db->real_escape_string($params[0])." AND ".TABLE_PREFIX."tickets.user_id='".$user['id']."'");

			if($ticket['total'] == 0){
				$show_error = true;
				$error_msg = $LANG['TICKET_NOT_FOUND_OR_PERMISSION'];	
			}else{
				if($params[1] == 'reply'){
					if($ticket['status'] == '5' && !($settings['ticket_reopen'])){
						$show_error = true;
						$error_msg = $LANG['TICKET_IS_CLOSED'];
					}elseif(empty($input->p['message'])){
						$show_error = true;
						$error_msg = $LANG['ONE_REQUIRED_FIELD_EMPTY'];
					}else{
						if($settings['ticket_attachment'] == 1){
							$uploaddir = UPLOAD_DIR.'tickets/';		
							if($_FILES['attachment']['error'] == 0){
								$ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
								$filename = md5($_FILES['attachment']['name'].time()).".".$ext;
								$fileuploaded = array('name' => $_FILES['attachment']['name'], 'enc' => $filename, 'size' => formatBytes($_FILES['attachment']['size']), 'filetype' => $_FILES['attachment']['type']);
								$uploadedfile = $uploaddir.$filename;
								if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadedfile)) {
									$show_error = true;
									$error_msg = $LANG['ERROR_UPLOADING_A_FILE'];
								}else{
									$fileverification = verifyAttachment($_FILES['attachment']);
									switch($fileverification['msg_code']){
										case '1':
										$show_error = true;
										$error_msg = $LANG['INVALID_FILE_EXTENSION'];
										break;
										case '2':
										$show_error = true;
										$error_msg = $LANG['FILE_NOT_ALLOWED'];
										break;
										case '3':
										$show_error = true;
										$error_msg = str_replace("%size%", $fileverification['msg_extra'], $LANG['FILE_IS_BIG']);
										break;
									}
								}
							}
						}
					}
					if($show_error !== true){
						$datenow = time();
						$data = array(
										'ticket_id' => $ticket['id'],
										'date' => $datenow,
										'message' => $input->p['message'],
										'ip' => $_SERVER['REMOTE_ADDR'],
										'email' => $user['email'],
									);
						$db->insert(TABLE_PREFIX.'tickets_messages', $data);
						$message_id = $db->lastInsertId();
						if($ticket['status'] == 5 || $ticket['status'] == 2){
							$addquery = ", status=3";
						}
						$db->query("UPDATE ".TABLE_PREFIX."tickets SET last_update=".$datenow.", replies=replies+1, last_replier='{$ticket['fullname']}' {$addquery} WHERE id={$ticket['id']}");
						if(is_array($fileuploaded)){
							$data = array('name' => $fileuploaded['name'], 'enc' => $fileuploaded['enc'], 'filesize' => $fileuploaded['size'], 'ticket_id' => $ticket['id'], 'msg_id' => $message_id, 'filetype' => $fileuploaded['filetype']);
							$db->insert(TABLE_PREFIX."attachments", $data);
						}

						if ($settings['email_on_new_reply'] == 'yes'){

							$status_name = $ticket_status[$ticket['status']];
							$priority_name = $db->fetchOne("SELECT name FROM ".TABLE_PREFIX."priority WHERE id={$ticket['priority_id']}");
							$department_name = $db->fetchOne("SELECT name FROM ".TABLE_PREFIX."departments WHERE id={$ticket['department_id']}");

							/* response  notification for staff */
							$q = $db->query("SELECT id, fullname, email, department FROM ".TABLE_PREFIX."staff WHERE newticket_notification=1 AND status='Enable'");
							while($r = $db->fetch_array($q))
							{
								$department_list = unserialize($r['department']);
								$department_list = (is_array($department_list)?$department_list:array());
								if(in_array($ticket['department_id'],$department_list))
								{
									/* Mailer */
									$data_mail = array(
										'id' => 'staff_ticketupdate_notification',
										'to' => $r['fullname'],
										'to_mail' => $r['email'],
										'vars' => array(
											'%staff_name%' => $r['fullname'],
											'%ticket_id%' => $ticket['code'],
											'%ticket_subject%' => $ticket['subject'],
											'%ticket_department%' => $department_name,
											'%ticket_status%' => $status_name,
											'%ticket_priority%' => $priority_name,
											'%message%' => $input->p['message']
										),
									);
									$mailer = new Mailer($data_mail);
								}
							}
						}

						header('location: '.getUrl('view_tickets','ticket',array($ticket['id'],'sent')));
						exit;
					}
				}elseif($params[1] == 'attachment'){
					if(!is_numeric($params['2'])){
						$filename = CONTROLLERS.'home_controller.php';
						$action = '404notfound';
						include($filename);
						exit;
					}else{
						$attachment = $db->fetchRow("SELECT *, COUNT(id) AS total FROM ".TABLE_PREFIX."attachments WHERE id=".$db->real_escape_string($params[2])." AND ticket_id=".$params[0]." AND msg_id=".$params[3]);
						if($attachment['total'] == 0){
							$filename = CONTROLLERS.'home_controller.php';
							$action = '404notfound';
							include($filename);
							exit;
						}else{
							header("Content-disposition: attachment; filename=".$attachment['name']);
							header("Content-type: ".$attachment['filetype']);
							readfile(UPLOAD_DIR.'tickets/'.$attachment['enc']);	
							exit;
						}
					}
				}
				$page = (is_numeric($params[1])?$params[1]:1);
				$max_results = $settings['tickets_replies'];
				$count = $db->fetchOne("SELECT COUNT(*) AS NUM FROM ".TABLE_PREFIX."tickets_messages WHERE ticket_id={$ticket['id']}");

				$total_pages = ceil($count/$max_results);	
				$page = ($page>$total_pages?$total_pages:$page);
				$from = ($max_results*$page) - $max_results;
				$tickets_query = $db->query("SELECT * FROM ".TABLE_PREFIX."tickets_messages WHERE ticket_id={$ticket['id']} ORDER BY date {$settings['show_tickets']} LIMIT $from, $max_results");
				while($r = $db->fetch_array($tickets_query)){
					$attachments = $db->fetchRow("SELECT *, COUNT(id) AS total FROM ".TABLE_PREFIX."attachments WHERE msg_id={$r['id']}");
					$r['message'] = $r['message'];
					$r['attachments'] = $attachments;
					$messages[] = $r;
				}
				$template_vars['messages'] = $messages;
				$template_vars['POST'] = $input->p;
				$template_vars['ticket'] = $ticket;
				$template_vars['error_msg'] = $error_msg;
				$template_vars['total_pages'] = $total_pages;
				$template_vars['page'] = $page;
				$template = $twig->loadTemplate('show_ticket.html');
				echo $template->render($template_vars);
				$db->close();
				exit;
			}
		}elseif($action == 'search'){
			if(!$input->p['code']){
				$show_error = true;
				$error_msg = $LANG['ONE_REQUIRED_FIELD_EMPTY'];
			}
			if($show_error !== true){
				$code = substr($input->p['code'],0,1);
				if($code == '#'){
					$searchcode = 	substr($input->p['code'],1);
				}else{
					$searchcode = 	$input->p['code'];
				}
				$chk = $db->fetchOne("SELECT COUNT(id) AS total FROM ".TABLE_PREFIX."tickets WHERE code='".$db->real_escape_string($searchcode)."' AND user_id='{$user['id']}'");
				if($chk == 0){
					$show_error = true;
					$error_msg = $LANG['TICKET_NOT_FOUND'];	
				}else{
					$ticketquery = $db->query("SELECT * FROM ".TABLE_PREFIX."tickets WHERE code='".$db->real_escape_string($searchcode)."'");
					while($r = $db->fetch_array($ticketquery)){
						$tickets[] = $r;
					}
					$q = $db->query("SELECT * FROM ".TABLE_PREFIX."priority");
					while($r = $db->fetch_array($q)){
						$priority[$r['id']] = $r;
					}
					$q = $db->query("SELECT * FROM ".TABLE_PREFIX."departments");
					while($r = $db->fetch_array($q)){
						$departments[$r['id']] = $r['name'];
					}
					$template_vars['departments'] = $departments;
					$template_vars['priority'] = $priority;
					$template_vars['tickets'] = $tickets;
					$template = $twig->loadTemplate('tickets.html');
					echo $template->render($template_vars);
					$db->close();
					exit;
				}
			}
		}elseif($action == 'page'){
			if(is_numeric($params[0])){
				$page = $params[0];	
			}else{
				$page = 1;
			}
		}else{
			$page = 1;	
		}
		/* OLD VERSION */
		$db->query("UPDATE ".TABLE_PREFIX."tickets SET user_id={$user['id']} WHERE email='{$user['email']}' AND user_id=0");
		/* END OLD VERSION */
		$page = (!is_numeric($page)?1:$page);
		$max_results = $settings['tickets_page'];
		$count = $db->fetchOne("SELECT COUNT(*) AS NUM FROM ".TABLE_PREFIX."tickets WHERE user_id='{$user['id']}'");
		$total_pages = ceil($count/$max_results);	
		$page = ($page>$total_pages?$total_pages:$page);
		$from = ($max_results*$page) - $max_results;
		$ticketquery = $db->query("SELECT * FROM ".TABLE_PREFIX."tickets WHERE user_id='{$user['id']}' ORDER BY status ASC, last_update DESC LIMIT $from, $max_results");
		while($r = $db->fetch_array($ticketquery)){
			$tickets[] = $r;
		}
		$q = $db->query("SELECT * FROM ".TABLE_PREFIX."priority");
		while($r = $db->fetch_array($q)){
			$priority[$r['id']] = $r;
		}
		$q = $db->query("SELECT * FROM ".TABLE_PREFIX."departments");
		while($r = $db->fetch_array($q)){
			$departments[$r['id']] = $r['name'];
		}
		$template_vars['departments'] = $departments;
		$template_vars['priority'] = $priority;
		$template_vars['tickets'] = $tickets;
		$template_vars['total_pages'] = $total_pages;
		$template_vars['page'] = $page;
		$template_vars['error_msg'] = $error_msg;
		$template = $twig->loadTemplate('tickets.html');
		echo $template->render($template_vars);
		$db->close();
		exit;
	}else{
		include(CONTROLLERS.'home_controller.php');
	}
?>