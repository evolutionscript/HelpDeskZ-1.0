<?php
namespace Classes;
/**
 * @package HelpDeskZ
 * @website: http://www.helpdeskz.com
 * @community: http://community.helpdeskz.com
 * @author Evolution Script S.A.C.
 * @since 1.0.0
 */
class InputCleaner
{
	var $cleaned_vars = array();
    public function __construct()
	{
		$this->test = 'asas';
		if(function_exists('get_magic_quotes_runtime') && get_magic_quotes_runtime())
			set_magic_quotes_runtime(false);
			
			if(get_magic_quotes_gpc()) {
				$this->array_stripslashes($_POST);
				$this->array_stripslashes($_GET);
				$this->array_stripslashes($_COOKIES);
			}
			$this->frm = $_POST;
			$this->frmg = $_GET;
			$this->cookie = $_COOKIE;
			while (list ($kk, $vv) = each ($this->frm)){
				if (is_array ($vv)){
					$vv_cleaned = $vv;
				}else{
				  $vv = trim ($vv);
				  $vv_cleaned = htmlspecialchars(trim($vv));
				}
				$this->p[$kk] = $vv;
				$this->pc[$kk] = $vv_cleaned;
				$this->r[$kk] = $vv;
				$this->rc[$kk] = $vv_cleaned;
			}
			while (list ($kk, $vv) = each ($this->frmg)){
				if (is_array ($vv)){
					$vv_cleaned = $vv;
				}else{
				  $vv = trim ($vv);
				  $vv_cleaned = htmlspecialchars(trim($vv));
				}
				$this->g[$kk] = $vv;
				$this->gc[$kk] = $vv_cleaned;
				$this->r[$kk] = $vv;
				$this->rc[$kk] = $vv_cleaned;
			}
			while (list ($kk, $vv) = each ($this->cookie)){
				if (is_array ($vv)){
				}else{
				  $vv = trim ($vv);
				  $vv_cleaned = htmlspecialchars(trim($vv));
				}
				$this->c[$kk] = $vv;
				$this->cc[$kk] = $vv_cleaned;
			}
			
	}
	
	function array_stripslashes(&$array) {
		if(is_array($array))
			while(list($key) = each($array))
				if(is_array($array[$key]))
					$this->array_stripslashes($array[$key]);
				else
					$array[$key] = stripslashes($array[$key]);
	}
}
?>