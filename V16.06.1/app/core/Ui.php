<?php
/*
 * ui.php
 *
 * User Interface class
 *
 *
 */
class UI extends Template
{
	protected $showbodytag = true;	// �O�_�b���Y�[�J <body> tag
	protected $title;
  
	
  function UI($view = 'default', $dir = 'default', $title='Welcome!')
	{
		$this->setdir( '../app/views/'. $view . '/'.$dir."/_core/");
		$this->title = $title;
	}
	
	function header()
	{
		
		if ($this->title!="")
			$this->set("SITENAME", $this->title);
		elseif (defined("L_SITENAME"))
			$this->set("SITENAME", L_SITENAME);
		else
			$this->set("SITENAME", G_SITENAME);
		$this->load("header", "header.html");
    $this->show("header");
	}
  
  function menu()
	{
		$this->load("menu", "menu.html");
    $this->show("menu");
	}
	
	
  /**
  * ���  �L�k�[�ݺֱm3d�e���ɡA����ܪ���T
  * @param string $msg �L�k�[�ݪ���]    
  * */
  function showMsg3D($msg = "STOP USE"){	
    $conf = new Config;
    $this->set("msg", $msg);
    $this->load("systemcheck3d", "systemcheck3d.htm");
	  $this->show("systemcheck3d");
	}
	
	/**
	 * ��ܿ��~�T�������}
	 * �]���Ψ� exit; �ҥH�n�b�o�ഫ�c²	 
	 * @param string $err ���~�T��	 
	 * @param boolean $newsFlag �O�_��ܧY�ɮ��T	 
	 *     */
	function showErrorPage($err, $newsFlag=false){
    global $g_conf,$curruser;
    $acl = $curruser['acl'];
    ob_end_clean();
    ob_start();
    $this->header();
    $this->set("msg", $err);
    $this->load("main", "msg_3d.htm");
    $this->show("main");
    $this->footer();
    $str = ob_get_contents();
    ob_end_clean(); 
    if ($g_conf['language']=="zh-cn") {
    	include_once("ccharset.php");
    	$cc = new CCharset;
    	$str = $cc->Big5_Gb($str);
    	//$str = preg_replace("/big5/i", "gb2312", $str);
    	$str = str_ireplace("big5", "gb2312", $str);//�����j�p�g,str_replace��preg_replace��
    }
    echo $str;
    exit;
  }
  
	function footer()
	{
	/*
  
  	global $G_DB_QUERY_COUNT, $curruser, $g_cache;
    $tempStr ="";
    $msg="";
		
		if (defined("L_COPYRIGHT")) {
			$this->set("copyright", L_COPYRIGHT);
		} else {
			$this->set("copyright", G_COPYRIGHT);
		}
		
		if (G_DEBUG || $curruser['acl']==255) {
			$msg = "<br>Page Time: <b>".substr(getmicrotime() - G_SCRIPTSTART,0,6)."</b>s";
			$msg .= "<br>DB Query Count: <b>".$G_DB_QUERY_COUNT."</b>";
			if ($curruser['account']=="Supervisor")
			   $msg .= "<br>WebServerIP:".$_SERVER["SERVER_ADDR"]."</b>";
			$this->set("debugmsg", $msg);
		}
		
		if (isset($g_cache["db_sqldebug"])) {
		    $tempStr = "<table border=1 cellpadding=2 cellspacing=0>";
		    $total = 0;
		    $i = 1;
		    foreach ($g_cache["db_sqldebug"] as $val) {
		        $tempStr .= "<tr><td align=right>".$i++."<td>".$val[0];
		        $tempStr .= "<td align=right>".substr($val[2] - $val[1],0,8);
		        $total += $val[2] - $val[1];
		    }
		    $tempStr .= "<tr><td colspan=4 align=right>".substr($total,0,8)."</table>";
		}
		
    $this->set("dbsqldebug", $tempStr);
  */
    $this->load("footer", "footer.html");
		$this->show("footer");
		
	}
	
}

