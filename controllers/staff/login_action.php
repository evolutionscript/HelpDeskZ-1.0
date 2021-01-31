<?php
/**
 * @package HelpDeskZ
 * @website: http://www.helpdeskz.com
 * @community: http://community.helpdeskz.com
 * @author Evolution Script S.A.C.
 * @since 1.0.0
 */
	if($action == 'login'){
		if(verifyToken('login', $input->p['csrfhash']) !== true){
			$error_msg = $LANG['CSRF_ERROR'];	
		}elseif(empty($input->p['username']) || empty($input->p['password'])){
			$error_msg = $LANG['USERNAME_PASSWORD_INCORRECT'];
		}else{
			$attempt = $db->fetchRow("SELECT COUNT(ip) AS total, attempts, date FROM ".TABLE_PREFIX."login_attempt WHERE ip='".$_SERVER['REMOTE_ADDR']."'");
			if($attempt['total'] > 0){
				if($settings['login_attempt'] > 0 && $attempt['attempts'] >= $settings['login_attempt']){
					if(($attempt['date']+(60*$settings['login_attempt_minutes'])) > time()){
						$error_msg = str_replace('%minutes%',$settings['login_attempt_minutes'], $LANG['LOGIN_LOCKED_FOR_X_MINUTES']).'<br>'.str_replace(array('%attempt1%','%attempt2%'), array($settings['login_attempt'],$settings['login_attempt']), $LANG['ATTEMPT_X_OF_Y']);;
					}else{
						$db->delete(TABLE_PREFIX."login_attempt", "ip='".$_SERVER['REMOTE_ADDR']."'");
						$attempt['total'] = 0;
					}
				}elseif(($attempt['date']+300) < time()){
					$db->delete(TABLE_PREFIX."login_attempt", "ip='".$_SERVER['REMOTE_ADDR']."'");
					$attempt['total'] = 0;
				}
			}
			if(!$error_msg){
				$staff = $db->fetchRow("SELECT COUNT(id) AS total, id, username, password, login, fullname, status FROM ".TABLE_PREFIX."staff WHERE username='".$db->real_escape_string($input->p['username'])."' AND password='".sha1($input->p['password'])."' GROUP BY id");
				if($staff['total'] == 0){
					if($settings['login_attempt'] > 0){
						if($attempt['total'] == 0){
							$data = array('ip' => $_SERVER['REMOTE_ADDR'],'attempts' => 1, 'date' => time());
							$db->insert(TABLE_PREFIX."login_attempt", $data);
							$total_attempts = 1;
						}else{
							$total_attempts = $attempt['attempts']+1;
							$data = array('attempts' => $total_attempts, 'date' => time());
							$db->update(TABLE_PREFIX."login_attempt", $data, "ip='".$_SERVER['REMOTE_ADDR']."'");
						}
						$error_msg = $LANG['USERNAME_PASSWORD_INCORRECT'].'<br>'.str_replace(array('%attempt1%','%attempt2%'), array($total_attempts,$settings['login_attempt']), $LANG['ATTEMPT_X_OF_Y']);
					}else{
						$error_msg = $LANG['USERNAME_PASSWORD_INCORRECT'];
					}
				}elseif($staff['status'] != 'Enable'){
					$error_msg = $LANG['ACCOUNT_IS_LOCKED'];
				}else{
					$timenow = time();
					$data = array('login' => $timenow, 'last_login' => $staff['login']);
					$db->update(TABLE_PREFIX."staff", $data, "id={$staff['id']}");
					$data = array('date' => $timenow, 'staff_id' => $staff['id'], 'username' => $staff['username'], 'fullname' => $staff['fullname'], 'ip' => $_SERVER['REMOTE_ADDR'], 'agent' => $_SERVER['HTTP_USER_AGENT']);
					$db->insert(TABLE_PREFIX."login_log", $data);
					$cookie_time = $timenow + (60*60*8);
					$data = array('id' => $staff['id'], 'username' => $staff['username'], 'password' => $staff['password'], 'expires' => $cookie_time);
					$data = serialize($data);
					$data = encrypt($data);
					setcookie('stfhash', $data, $cookie_time, '/');
					$_SESSION['staff']['id'] = $staff['id'];
					$_SESSION['staff']['username'] = $staff['username'];
					$_SESSION['staff']['password'] = $staff['password'];
					header('location: '.getUrl($controller));
					exit;
				}
			}
		}
	}
	
$template_vars['error_msg'] = $error_msg;
$template = $twig->loadTemplate('login.html');
echo $template->render($template_vars);
$db->close();
exit;
?>
