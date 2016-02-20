<?php
/**
 * @package HelpDeskZ
 * @website: http://www.helpdeskz.com
 * @community: http://community.helpdeskz.com
 * @author Evolution Script S.A.C.
 * @since 1.0.0
 */
if($params[1] == 'update_general'){
	if(verifyToken('ticket_settings', $input->p['csrfhash']) !== true){
		$error_msg = $LANG['CSRF_ERROR'];	
	}else{
		$db->update(TABLE_PREFIX."settings", array('value' => ($input->p['show_tickets'] == 'DESC'?'DESC':'ASC')), "field='show_tickets'");
		$db->update(TABLE_PREFIX."settings", array('value' => ($input->p['ticket_reopen'] == '1'?'1':'0')), "field='ticket_reopen'");
		$db->update(TABLE_PREFIX."settings", array('value' => (is_numeric($input->p['tickets_page'])?$input->p['tickets_page']:20)), "field='tickets_page'");	
		$db->update(TABLE_PREFIX."settings", array('value' => (is_numeric($input->p['tickets_replies'])?$input->p['tickets_replies']:10)), "field='tickets_replies'");	
		$db->update(TABLE_PREFIX."settings", array('value' => (is_numeric($input->p['overdue_time'])?$input->p['overdue_time']:72)), "field='overdue_time'");
		$db->update(TABLE_PREFIX."settings", array('value' => (is_numeric($input->p['closeticket_time'])?$input->p['closeticket_time']:72)), "field='closeticket_time'");

		$db->update(TABLE_PREFIX."settings", array('value' => $input->p['email_on_new_reply']), "field='email_on_new_reply'");

		$db->update(TABLE_PREFIX."settings", array('value' => ($input->p['ticket_attachment'] == '1'?'1':'0')), "field='ticket_attachment'");

		header('location: '.getUrl($controller,$action, array('tickets','general_updated#ctab1')));
		exit;	
	}
}elseif($params[1] == 'delete_filetype'){
	if(verifyToken('ticket_settings', $input->p['csrfhash']) !== true){
		$error_msg = $LANG['CSRF_ERROR'];	
	}elseif(!is_array($input->p['filetype_id'])){
		$error_msg = $LANG['INVALID_FORM'];	
	}else{
		if($input->p['remove'] == 1){
			foreach($input->p['filetype_id'] as $id){
				$db->delete(TABLE_PREFIX."file_types", "id='".$db->real_escape_string($id)."'");
			}
			header('location: '.getUrl($controller,$action, array('tickets','filetype_removed#ctab2')));
			exit;	
		}
	}	
}elseif($params[1] == 'insert_filetype'){
	if($input->p['do'] != 'insert'){
		$error_msg = $LANG['INVALID_FORM'];	
	}elseif(!ctype_alnum($input->p['type'])){
		$error_msg = $LANG['INVALID_FILE_EXTENSION'];
	}elseif(!is_numeric($input->p['size'])){
		$error_msg = $LANG['INVALID_MAXIMUM_SIZE'];
	}else{
		$filetype = $db->fetchRow("SELECT COUNT(id) AS total, id FROM ".TABLE_PREFIX."file_types WHERE type='".$db->real_escape_string($input->p['type'])."'");
		$data = array('type' => $input->p['type'],
						'size' => ($input->p['size']*1024),
						);
		if($filetype['total'] != 0){
				$db->update(TABLE_PREFIX."file_types", $data, "id={$filetype['id']}");
		}else{
				$db->insert(TABLE_PREFIX."file_types", $data);
		}
		header('location: '.getUrl($controller,$action, array('tickets','file_inserted#ctab2')));
		exit;	
	}
}elseif($params[1] == 'update_filetype'){
	if($input->p['do'] != 'update' || !is_numeric($input->p['filetypeid'])){
		$error_msg = $LANG['INVALID_FORM'];
	}elseif(!ctype_alnum($input->p['type'])){
		$error_msg = $LANG['INVALID_FILE_EXTENSION'];
	}elseif(!is_numeric($input->p['size'])){
		$error_msg = $LANG['INVALID_MAXIMUM_SIZE'];
	}else{
		$filetype = $db->fetchRow("SELECT COUNT(id) AS total, id FROM ".TABLE_PREFIX."file_types WHERE type='".$db->real_escape_string($input->p['type'])."'");
		$data = array('type' => $input->p['type'],
						'size' => ($input->p['size']*1024),
						);
						
		if($filetype['total'] != 0){
				$db->update(TABLE_PREFIX."file_types", $data, "id={$filetype['id']}");
		}else{
				$db->update(TABLE_PREFIX."file_types", $data, "id=".$db->real_escape_string($input->p['filetypeid']));
		}
		header('location: '.getUrl($controller,$action, array('tickets','file_updated#ctab2')));
		exit;	
	}	
}elseif($params[1] == 'getFileTypeForm'){
	if(is_numeric($params[2]) && $params[2] != 0){
		$file_type = $db->fetchRow("SELECT *, COUNT(id) AS total FROM ".TABLE_PREFIX."file_types WHERE id=".$db->real_escape_string($params[2]));
		if($file_type['total'] == 0){
			die($LANG['ERROR_RETRIEVING_DATA']);	
		}
		$form_action = getUrl($controller,$action,array('tickets','update_filetype#ctab2'));
	}else{
		$form_action = getUrl($controller,$action,array('tickets','insert_filetype#ctab2'));
	}
	$template_vars['form_action'] = $form_action;
	$template_vars['file_type'] = $file_type;
	$template = $twig->loadTemplate('admin_filetype_form.html');
	echo $template->render($template_vars);
	$db->close();
	exit;
}elseif($params[1] == 'move_customfield'){
	if(is_numeric($params[3])){
		$custom_field = $db->fetchRow("SELECT COUNT(id) as total, id, display FROM ".TABLE_PREFIX."custom_fields WHERE id=".$db->real_escape_string($params[3]));
		$last_position = $db->fetchOne("SELECT display FROM ".TABLE_PREFIX."custom_fields ORDER BY display DESC LIMIT 1");
		if($custom_field['total'] != 0){
			if($params[2] == 'up' && $custom_field['display'] > 1){
				$old_position = $custom_field['display'];
				$new_position = $old_position - 1;
				$db->query("UPDATE ".TABLE_PREFIX."custom_fields SET display=$old_position WHERE display={$new_position}");
				$db->query("UPDATE ".TABLE_PREFIX."custom_fields SET display=$new_position WHERE id={$custom_field['id']}");
			}elseif($params[2] == 'down' && $custom_field['display'] < $last_position){
				$old_position = $custom_field['display'];
				$new_position = $old_position + 1;
				$db->query("UPDATE ".TABLE_PREFIX."custom_fields SET display=$old_position WHERE display={$new_position}");
				$db->query("UPDATE ".TABLE_PREFIX."custom_fields SET display=$new_position WHERE id={$custom_field['id']}");
			}
		}
		header('location: '.getUrl($controller, $action, array('tickets#ctab3')));
		exit;
	}
}elseif($params[1] == 'getCustomFieldForm'){
	if(is_numeric($params[2]) && $params[2] != 0){
		$custom_field = $db->fetchRow("SELECT *, COUNT(id) AS total FROM ".TABLE_PREFIX."custom_fields WHERE id=".$db->real_escape_string($params[2]));
		if($custom_field['total'] == 0){
			die($LANG['ERROR_RETRIEVING_DATA']);	
		}
		$form_action = getUrl($controller,$action,array('tickets','edit_customfield#ctab3'));
		if($custom_field['type'] == 'radio' || $custom_field['type'] == 'checkbox' || $custom_field['type'] == 'select'){
			$cv = unserialize($custom_field['value']);
			if(is_array($cv)){
				$total = count($cv);				
				foreach($cv as $v){
					$i = $i+1;
					if($v != ''){
						$custom_values .= $v.($i < $total?"\n":"");
					}
				}
			}
		}
	}else{
		$template_vars['new_position'] = $db->fetchOne("SELECT display FROM ".TABLE_PREFIX."custom_fields ORDER BY display DESC LIMIT 1")+1;
		$form_action = getUrl($controller,$action,array('tickets','add_customfield#ctab3'));
	}
	$template_vars['custom_values'] = $custom_values;
	$template_vars['custom_field'] = $custom_field;
	$template_vars['form_action'] = $form_action;
	$template = $twig->loadTemplate('admin_customfield_form.html');
	echo $template->render($template_vars);
	$db->close();
	exit;
}elseif($params[1] == 'add_customfield'){
	$field_types = array('text', 'textarea', 'password', 'checkbox', 'radio', 'select');
	$field_type = $input->p['field_type'];
	if($input->p['do'] != 'new_item'){
		$error_msg = $LANG['INVALID_FORM'];
	}elseif(!in_array($field_type, $field_types)){
		$error_msg = $LANG['SELECT_VALID_CUSTOM_FIELD'];
	}elseif($input->p[$field_type.'_title'] == ''){
		$error_msg = $LANG['ENTER_VALID_FIELD_TITLE'];
	}elseif(!is_numeric($input->p[$field_type.'_order'])){
		$error_msg = $LANG['DISPLAY_ORDER_MUST_BE_NUMBER'];
	}elseif(($field_type == 'checkbox' || $field_type == 'radio' || $field_type == 'select') && ($input->p[$field_type."_value"] == '')){
		$error_msg = $LANG['FIELD_OPTION_CANNOT_BE_BLANK'];
	}else{
		if($field_type == 'checkbox' || $field_type == 'radio' || $field_type == 'select'){
			$valuexplode = explode("\n",$input->p[$field_type.'_value']);
			foreach($valuexplode as $v){
				if($v != "\r" && $v != ""){
					$field_value[] = $v;
				}
			}
			$field_value = serialize($field_value);
		}else{
			$field_value = $input->p[$field_type.'_value'];	
		}
		$data = array(
						'type' => $field_type,
						'title' => $input->p[$field_type.'_title'],
						'value' => $field_value,
						'required' => ($input->p[$field_type.'_required'] == 0?0:1),
						'display' => $input->p[$field_type.'_order'],
					);
		$db->insert(TABLE_PREFIX."custom_fields", $data);
		header('location: '.getUrl($controller,$action, array('tickets','customfield_added#ctab3')));
		exit;	
	}
}elseif($params[1] == 'edit_customfield'){
	$field_types = array('text', 'textarea', 'password', 'checkbox', 'radio', 'select');
	$field_type = $input->p['field_type'];
	if($input->p['do'] != 'edit_item'){
		$error_msg = $LANG['INVALID_FORM'];
	}elseif(!is_numeric($input->p['item_id'])){
		$error_msg = 'Invalid ID.';
	}elseif(!in_array($field_type, $field_types)){
		$error_msg = $LANG['SELECT_VALID_CUSTOM_FIELD'];
	}elseif($input->p[$field_type.'_title'] == ''){
		$error_msg = $LANG['ENTER_VALID_FIELD_TITLE'];
	}elseif(!is_numeric($input->p[$field_type.'_order'])){
		$error_msg = $LANG['DISPLAY_ORDER_MUST_BE_NUMBER'];
	}elseif(($field_type == 'checkbox' || $field_type == 'radio' || $field_type == 'select') && ($input->p[$field_type."_value"] == '')){
		$error_msg = $LANG['FIELD_OPTION_CANNOT_BE_BLANK'];
	}else{
		if($field_type == 'checkbox' || $field_type == 'radio' || $field_type == 'select'){
			$valuexplode = explode("\n",$input->p[$field_type.'_value']);
			foreach($valuexplode as $v){
				if($v != "\r" && $v != ""){
					$field_value[] = $v;
				}
			}
			$field_value = serialize($field_value);
		}else{
			$field_value = $input->p[$field_type.'_value'];	
		}
		$data = array(
						'type' => $field_type,
						'title' => $input->p[$field_type.'_title'],
						'value' => $field_value,
						'required' => ($input->p[$field_type.'_required'] == 0?0:1),
						'display' => $input->p[$field_type.'_order'],
					);
		$db->update(TABLE_PREFIX."custom_fields", $data, "id=".$db->real_escape_string($input->p['item_id']));
		header('location: '.getUrl($controller,$action, array('tickets','customfield_updated#ctab3')));
		exit;	
	}	
}elseif($params[1] == 'delete_customfield'){
	if(is_numeric($params[2])){
		$db->delete(TABLE_PREFIX."custom_fields", "id=".$db->real_escape_string($params[2]));
	}
	header('location:'. getUrl($controller,$action,array('tickets','customfield_deleted#ctab3')));
	exit;
}
$order_list = array('type', 'size');
$orderby = (in_array($params[1],$order_list)?$params[1]:'type');
$sortby = ($params[2] == 'desc'?'desc':'asc');
$q = $db->query("SELECT * FROM ".TABLE_PREFIX."file_types ORDER BY {$orderby} {$sortby}");
while($r = $db->fetch_array($q)){
	$file_types[] = $r;	
}
$q = $db->query("SELECT * FROM ".TABLE_PREFIX."custom_fields ORDER BY display ASC");
while($r = $db->fetch_array($q)){
	$custom_fields[] = $r;	
}
$template_vars['last_position'] = $db->fetchOne("SELECT display FROM ".TABLE_PREFIX."custom_fields ORDER BY display DESC LIMIT 1");
$template_vars['custom_fields'] = $custom_fields;
$template_vars['file_types'] = $file_types;
$template_vars['error_msg'] = $error_msg;
$template = $twig->loadTemplate('admin_tickets.html');
echo $template->render($template_vars);
$db->close();
exit;	
?>