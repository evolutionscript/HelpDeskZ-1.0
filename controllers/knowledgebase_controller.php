<?php
/**
 * @package HelpDeskZ
 * @website: http://www.helpdeskz.com
 * @community: http://community.helpdeskz.com
 * @author Evolution Script S.A.C.
 * @since 1.0.0
 */
if($controller == 'knowledgebase'){
include(INCLUDES.'helpdesk.inc.php');
}
if($settings['knowledgebase'] != 'yes'){
	header('location: '.getUrl());
	exit;	
}
$qch = $db->query("SELECT id, parent, public FROM ".TABLE_PREFIX."knowledgebase_category ORDER BY public ASC, parent ASC");
$hiddencategorylist = array();
while($r = $db->fetch_array($qch)){
	if($r['public'] == 0){
		if(!in_array($r['id'],$hiddencategorylist)){
			array_push($hiddencategorylist,$r['id']);
			$hiddencategorylistq .= " AND category!=".$r['id'];
		}
	}elseif(in_array($r['parent'], $hiddencategorylist)){
		array_push($hiddencategorylist,$r['id']);
		$hiddencategorylistq .= " AND category!=".$r['id'];
	}
}
function getCatTitle($cat_id,$indice=0){
	global $db, $controller;
	$cat = $db->fetchRow("SELECT id, name, parent, public FROM ".TABLE_PREFIX."knowledgebase_category WHERE id=".$db->real_escape_string($cat_id));
	$str = '';
	if($cat['parent'] != 0){
		$str = getCatTitle($cat['parent']);
	}
	if($indice == 1){
		$str .= $cat['name'];
	}else{
		$str .= '<a href="'.getUrl($controller,$cat['id'],array(strtourl($cat['name']))).'">'.htmlspecialchars($cat['name']).'</a> &gt; ';
	}
	return $str;
}
if($action == 'search'){
	$template_vars = array();
	$result = 0;
	if($input->p['word'] != '' && strlen($input->p['word']) > 3){
		$q = $db->query("SELECT * FROM ".TABLE_PREFIX."articles WHERE public=1 {$hiddencategorylistq} AND content LIKE '%".$db->real_escape_string(htmlentities($input->p['word']))."%'  ORDER BY date DESC");
		while($r = $db->fetch_array($q)){
			$result = 1;
			$r['url'] = getUrl('knowledgebase',$r['category'],array('article', $r['id'], strtourl($r['title'])));
			$r['content'] = (strlen(strip_tags($r['content'])) > $settings['knowledgebase_articlemaxchar']?substr(strip_tags($r['content']), 0, ($settings['knowledgebase_articlemaxchar']-3)).'...':strip_tags($r['content']));
			$kb[] = $r;
		}
	}
	$template_vars['result'] = $result;
	$template_vars['kb'] = $kb;
	$template = $twig->loadTemplate('knowledgebase_search.html');
	echo $template->render($template_vars);
	$db->close();
	exit;
}
$main_url = getUrl($controller);
if(is_numeric($action)){
	$cat_id = $action;
	if(in_array($cat_id,$hiddencategorylist)){
		header('location: '.getUrl($controller));
		exit;		
	}
	$cat_title = getCatTitle($cat_id,1);
}else{
	$cat_id = 0;
}
if($params[0] == 'article' && is_numeric($params[1])){
	$article = $db->fetchRow("SELECT *, COUNT(id) AS total FROM ".TABLE_PREFIX."articles WHERE id=".$db->real_escape_string($params[1])." AND category=".$cat_id);
	if($article['total'] == 0 || $article['public'] == 0){
		header('location: '. getUrl($controller));
		exit;
	}else{
		if($params[2] == 'attachment' && is_numeric($params[3])){
			$attachment = $db->fetchRow("SELECT *, COUNT(id) AS total FROM ".TABLE_PREFIX."attachments WHERE id=".$db->real_escape_string($params[3])." AND article_id=".$article['id']);

			if($attachment['total'] == 0){
				die($LANG['FILE_NOT_FOUND']);
			}else{
				header("Content-disposition: attachment; filename=".$attachment['name']);
				header("Content-type: ".$attachment['filetype']);
				readfile(UPLOAD_DIR.'articles/'.$attachment['enc']);	
				exit;
			}
		}
		$db->query("UPDATE ".TABLE_PREFIX."articles SET views=views+1 WHERE id={$article['id']}");
		$q = $db->query("SELECT * FROM ".TABLE_PREFIX."attachments WHERE article_id=".$article['id']);
		while($r = $db->fetch_array($q)){
			$attachments[] = $r;
		}
		$template_vars = array();
		$template_vars['cat_id'] = $cat_id;
		$template_vars['cat_title'] = $cat_title;
		$template_vars['article'] = $article;
		$template_vars['attachments'] = $attachments;
		$template_vars['attachment_url'] = getUrl($controller,$action,array('article',$article['id'],'attachment',''));
		$template = $twig->loadTemplate('knowledgebase_article.html');
		echo $template->render($template_vars);
		$db->close();
		exit;
	}
}
$template_vars = array();
$template_vars['cat_id'] = $cat_id;
$template_vars['cat_title'] = $cat_title;
$q = $db->query("SELECT * FROM ".TABLE_PREFIX."knowledgebase_category WHERE parent=".$db->real_escape_string($cat_id)." AND public=1 ORDER BY position ASC");	
while($r = $db->fetch_array($q)){
	$r['total_articles'] = $db->fetchOne("SELECT COUNT(id) AS total FROM ".TABLE_PREFIX."articles WHERE category=".$r['id']." AND public=1");
	$r['url'] = getUrl('knowledgebase',$r['id'],array(strtourl($r['name'])));
	if($r['total_articles'] > 0){
		$aq = $db->query("SELECT id, title FROM ".TABLE_PREFIX."articles WHERE category=".$r['id']." AND public=1 ORDER BY date DESC LIMIT {$settings['knowledgebase_articlesundercat']}");
		while($ka = $db->fetch_array($aq)){
			$ka['url'] = getUrl('knowledgebase',$r['id'],array('article', $ka['id'], strtourl($ka['title'])));
			$r['article'][] = $ka;
		}
	}
	$kb_category[] = $r;	
}
$template_vars['kb_category'] = $kb_category;

$qart = $db->query("SELECT * FROM ".TABLE_PREFIX."articles WHERE category=".$db->real_escape_string($cat_id)." AND public=1 ORDER BY date DESC");
while($r = $db->fetch_array($qart)){
	$r['url'] = getUrl('knowledgebase',$r['category'],array('article', $r['id'], strtourl($r['title'])));
	$r['content'] = (strlen(strip_tags($r['content'])) > $settings['knowledgebase_articlemaxchar']?substr(strip_tags($r['content']), 0, ($settings['knowledgebase_articlemaxchar']-3)).'...':strip_tags($r['content']));
	$articles[] = $r;
}
$template_vars['articles'] = $articles;

//Popular Articles
if($settings['knowledgebase_mostpopular'] == 'yes'){
	$q = $db->query("SELECT id, title, category FROM ".TABLE_PREFIX."articles WHERE public=1 {$hiddencategorylistq} ORDER BY views DESC LIMIT {$settings['knowledgebase_mostpopulartotal']}");
	while($r = $db->fetch_array($q)){
		$r['url'] = getUrl('knowledgebase',$r['category'],array('article', $r['id'], strtourl($r['title'])));
		$kb_popular[] = $r;	
	}
	$template_vars['kb_popular'] = $kb_popular;
}
//Newest Articles
if($settings['knowledgebase_newest'] == 'yes'){
	$q = $db->query("SELECT id, title, category FROM ".TABLE_PREFIX."articles WHERE public=1 {$hiddencategorylistq} ORDER BY date DESC LIMIT {$settings['knowledgebase_newesttotal']}");
	while($r = $db->fetch_array($q)){
		$r['url'] = getUrl('knowledgebase',$r['category'],array('article', $r['id'], strtourl($r['title'])));
		$kb_newest[] = $r;	
	}
	$template_vars['kb_newest'] = $kb_newest;
}
$template_vars['error_msg'] = $error_msg;
$template = $twig->loadTemplate('knowledgebase.html');
echo $template->render($template_vars);
$db->close();
exit;	
?>