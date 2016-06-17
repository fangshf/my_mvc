<?
/**************************************************************************
 *
 * PHP Session for MySQL Handler
 *
 * ------------------------------------------------------------------------
 * DESCRIPTION:
 * ------------------------------------------------------------------------
 * This library tells the PHP4 session handler to write to a MySQL database
 * instead of creating individual files for each session.
 *
 * Create a new database in MySQL called "k_sessions" like so:
 *
 * CREATE TABLE `k_sessions` (
 * `sid` varchar(32) NOT NULL default '',
 * `createtime` int(11) unsigned NOT NULL default '0',
 * `lastupdate` int(11) unsigned NOT NULL default '0',
 * `value` text NOT NULL,
 * `host` varchar(32) NOT NULL default '',
 * `ip` varchar(32) NOT NULL default '',
 * `proxy` varchar(32) NOT NULL default '',
 * `agent` varchar(255) NOT NULL default '',
 * `uri` varchar(255) NOT NULL default '',
 * PRIMARY KEY (`sid`)
 * );
 *
 * ------------------------------------------------------------------------
 * INSTALLATION:
 * ------------------------------------------------------------------------
 * Make sure you have MySQL support compiled into PHP4.  Then copy this
 * script to a directory that is accessible by the rest of your PHP
 * scripts.
 *
 * ------------------------------------------------------------------------
 * USAGE:
 * ------------------------------------------------------------------------
 * Include this file in your scripts before you call session_start(), you
 * don't have to do anything special after that.
 **************************************************************************/

function sess_open($save_path, $session_name) 
{
	return true;
}


function sess_close() 
{
	return true;
}


function sess_read($key) 
{
	$value = "";
	$sql = "select value from k_sessions where sid='$key'";
	$rs = getdb($sql);
	if(!$rs->eof) {
		$value = $rs->f["value"];
	} else {
	    sess_insert($key);
	}
	return $value;
}


function sess_write($key, $val)
{
	$value = addslashes($val);

	$sql = "select value from k_sessions where sid='$key'";
	$rs = getdb($sql);
	if (!$rs->eof) {
		getdb("update k_sessions set value='$value',lastupdate=".systime()." WHERE sid='$key'");
	} else {
	    sess_insert($key, $val);
	}
	return 1;
}


function sess_destroy($key) 
{
	$sql = "delete from k_sessions where sid='$key'";
	getdb($sql);

	return $qid;
}


function sess_gc($maxlifetime=0) 
{
  if ($maxlifetime==0) {
		$maxlifetime = get_cfg_var("session.gc_maxlifetime");
		$sql = "delete from k_sessions where lastupdate<".(systime()-$maxlifetime);
	} else {
		$maxlifetime = min($maxlifetime, get_cfg_var("session.gc_maxlifetime"));
		$sql = "delete from k_sessions where lastupdate<".(systime()-$maxlifetime);
	}
	
	getdb($sql);
	
	return true;
}



/**
 * Sessin 新增的處理動作，設定該
 *
 * @param string $dirname xxx
 * @param int $mode xxx
 * @return Boolean
 * @access public/private
 * @since 1.0
 */
function sess_insert($sid, $val="")
{
	$proxy = "";
	$ip =  $_SERVER["REMOTE_ADDR"];
	if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		$proxy =  $_SERVER["REMOTE_ADDR"];
		$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
	}
	$host = isset($_SERVER["SERVER_ADDR"]) ? $_SERVER["SERVER_ADDR"] : $_SERVER["HTTP_HOST"];
	$agent = $_SERVER["HTTP_USER_AGENT"];
	$uri = get_uri();
	
	$sql = "insert into k_sessions (proxy,ip,host,agent,uri,sid,value,lastupdate,createtime) values ("
	    ."'$proxy','$ip','$host','$agent','$uri','$sid','$val',".systime().",".systime().")";
	getdb($sql);    
}


session_set_save_handler(
	"sess_open",
	"sess_close",
	"sess_read",
	"sess_write",
	"sess_destroy",
	"sess_gc");

session_start();


sess_update_uri();



function sess_update_uri()
{
    db_update("k_sessions", array("uri" => "'".get_uri()."'"), "sid='".session_id()."'");
}


class Session 
{
	// 使用者網路資訊
	var $proxy = "";
	var $ip = "";
	
	// 以下為瀏覽器資訊
	var $agent;
	var $version;
	var $platform;
	var $language	=	array();
	var $country	=	array();
	
	/**
	* Sessin Initital
	*/
	function Session()
	{
		$this->GetBrowserInfo();
	}
	
	
	/**
	* 取得客戶端 Browser 資訊
	*/
	function GetBrowserInfo()
	{
		$useragent = $_SERVER["HTTP_USER_AGENT"];

		$langs = split(",", $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
		
		foreach ($langs as $lang) {
			$this->language[] = $lang;
		}

		if (ereg('MSIE ([0-9].[0-9]{1,2})',$useragent,$log_version)) {
			$this->version = $log_version[1];
			$this->agent = 'IE';
		} elseif (ereg('Opera ([0-9].[0-9]{1,2})',$useragent,$log_version)) {
			$this->version = $log_version[1];
			$this->agent = 'OPERA';
		} elseif (ereg('Mozilla/([0-9].[0-9]{1,2})',$useragent,$log_version)) {
			$this->version = $log_version[1];
			$this->agent = 'MOZILLA';
		} else {
			$this->version=0;
			$this->agent='OTHER';
		}

		if (strstr($useragent,'Win')) {
			$this->platform='Win';
		} else if (strstr($useragent,'Mac')) {
			$this->platform='Mac';
		} else if (strstr($useragent,'Linux')) {
			$this->platform='Linux';
		} else if (strstr($useragent,'Unix')) {
			$this->platform='Unix';
		} else {
			$this->platform='Other';
		}

		if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$this->proxy =  $_SERVER["REMOTE_ADDR"];
			$this->ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		} else {
			$this->proxy = "";
			$this->ip =  $_SERVER["REMOTE_ADDR"];
		}		
		
	}
}
?>