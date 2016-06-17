<?php
/**
* Database class for mysql
*
*/

$G_DB_QUERY_COUNT = 0;		// 資料庫查詢的次數

class DB
{
	var $handle = false;		// random slave db
	var $handle_master = false; // master db
	var $server;
	var $user;
	var $pass;
	var $database;
	var $count = 0;
	var $result = 0;

	function DB()
	{
		global $CORE;
		if (!isset($CORE["db_connection"])) {
			$this->connect();
		}
	}

	function connect()
	{
		global $CORE, $g_dbhost, $g_db_sql_mode;
		//$g_db_sql_mode="SET @@sql_mode = ''";//預設值
		if (!isset($CORE["db_connection"])) {
			if (isset($g_dbhost) && is_array($g_dbhost)) {
				// use multi db server, first one is write db 有使用資料庫主機陣列
				$this->server = $g_dbhost[0];  // 第一個參數：遠端MySQL寫入主機
				$this->user = G_DBUSER;
				$this->pass = G_DBPASS;
				$this->database = G_DBDATABASE;

				$handle = mysql_connect($this->server, $this->user, $this->pass);
				mysql_query($g_db_sql_mode);
				if ($handle==false) {
					die("連接 MASTER 資料庫主機失敗！".mysql_error());
				}
				if (false==mysql_select_db($this->database, $handle)) {
					die("連接 MASTER 資料庫失敗！".mysql_error());
				}
				$CORE["db_connection_master"] = $handle;  // 主控 MASTER MySQL 資料庫

				if (count($g_dbhost)==1) {
					$CORE["db_connection"] = $handle;
				} else {
					$minhost = count($g_dbhost)<3 ? 0 : 1;
					$tries = 0;     // 嘗試連接伺服器次數10次
					$handle = false;
					while ($tries<10 && $handle==false) {
						$slave = rand($minhost, count($g_dbhost) - 1);  // $slave = 1 多台主機用亂數指定一台來讀取
						$handle = mysql_connect($g_dbhost[$slave], $this->user, $this->pass);
						mysql_query($g_db_sql_mode);
						$tries++;
					}
					if ($handle==false) {
						die("連接 SLAVE 資料庫主機失敗！".mysql_error());
					}
					if (false==mysql_select_db($this->database, $handle)) {
						die("連接 SLAVE 資料庫失敗！".mysql_error());
					}
					$CORE["db_connection"] = $handle;  // 本機 SLAVE MySQL 資料庫
				}
			} else {
				$this->server = G_DBSERVER;
				$this->user = G_DBUSER;
				$this->pass = G_DBPASS;
				$this->database = G_DBDATABASE;

				$handle = mysql_connect($this->server, $this->user, $this->pass);
				mysql_query($g_db_sql_mode);
				if ($handle==false) {
					die("連接資料庫主機失敗！".mysql_error());
				}
				if (false==mysql_select_db($this->database, $handle)) {
					die("連接資料庫失敗！".mysql_error());
				}
				$CORE["db_connection"] = $handle;
				$CORE["db_connection_master"] = $handle;
			}
			//echo "<li>DB connect";
		} else {
			//echo "<li>DB cache";
		}
		$this->handle = $CORE["db_connection"];
		$this->handle_master = $CORE["db_connection_master"];
		return $this->handle;
	}

	function queryrow($sql)
	{
		$res = $this->query($sql);
		if (!$res) return false;
		$row = $this->fetch_row($res);
		$this->free_result($res);
		return $row;
	}

	function getrs($sql)
	{
		$res = $this->query($sql);
		if (!$res) return false;
		$rows = array();
		while ($row = $this->fetch_object($res)) {
			$rows[] = $row;
		}
		$this->free_result($res);
		return $rows;
	}

	function getFieldsName($tblName){
		$rows = array();
		if (!$this->connect()) {
			return false;
		}
		$sql = "select * from ".$tblName." LIMIT 0,1";
		$res = $this->query($sql);

		$fields = mysql_num_fields($res);

		for ($i = 0; $i < $fields; $i++) {
			$rows[] = mysql_field_name($res, $i);
		}
		return $rows;
	}

	function query($sql='')
	{
		global $G_DB_QUERY_COUNT, $CORE, $g_cache;

		if (!$this->connect()) {
			return false;
		}

		$time_begin = microtime(1);
		// 簡體中文相容
		/*
		if (getvar("c_lang")=="zh-cn") {
		if (!isset($CORE["ccharset"])) {
		include_once "class/ccharset.php";
		$CORE["ccharset"] = new CCharset;
		}
		$sql = $CORE["ccharset"]->Gb_Big5($sql);
		}
		*/
		//echo "<li>".$G_DB_QUERY_COUNT." $sql\n"; //ob_end_flush();
		$this->sql = $sql;

		if (substr($sql,0,2)=="!!") {
			// sql 字串前兩個碼是 !! 則強制用 master db
			$handle = $this->handle_master;
			$sql = substr($sql,2);
		} else {
			// select use slave db, other use master db
			$handle = preg_match("/^select /i", $sql) ? $this->handle : $this->handle_master;
		}

		$res = mysql_query($sql, $handle);
		if (!$res){//資料庫失敗時,記錄到 dblog table
			$errCode = mysql_errno($handle);
			if (G_DEBUG) {
				$r="<p>CODE(".$errCode."):".mysql_error($handle)."</p><p>SQL: ".$sql."</p>";
				echo($r);
			}
			//排除部份錯誤代碼不記錄,2006=系統收盤備份時會產生
			if ($errCode!=2006){
  			if (!eregi("^insert into dblog", $sql) && !eregi("^insert into k_sessions", $sql)){
  			   $buf = "CODE(".$errCode."):".htmlspecialchars($sql, ENT_QUOTES);
  			   $this->query("insert into dblog (logs) values ('{$buf}')");
            if ($errCode==1062 && (eregi("^insert into bet ", $sql) || eregi("^insert into bet_", $sql))){
               //會員注單唯一鍵值錯誤時,自動登出
               if (isset($_SESSION["uid"]) && ($uid=intval($_SESSION["uid"]))>0){
                  $this->query("DELETE FROM k_sessions WHERE uid=".$uid);
                  unset($_SESSION["uid"]);   
               } 
            }
  			}
			}
		}
		$this->result = $res;
		$G_DB_QUERY_COUNT++;

		$time_end = microtime(1);
		/*
		if (!isset($g_cache["db_sqldebug"])) {
		$g_cache["db_sqldebug"] = array();
		}
		$g_cache["db_sqldebug"][] = array($sql, $time_begin, $time_end);
		*/

		return $res;
	}

	function num_rows($res=0)
	{
		if (!$this->connect()) return false;
		if ($res==0) $res = $this->result;
		return mysql_num_rows($res);
	}

	function num_fields($res=0)
	{
		if (!$this->connect()) return false;
		if ($res==0) $res = $this->result;
		return mysql_num_fields($res);
	}

	function fetch_field($res=0)
	{
		if (!$this->connect()) return false;
		if ($res==0) $res = $this->result;
		return mysql_fetch_field($res);
	}

	function fetch_row($res=0)
	{
		if (!$this->connect()) return false;
		if ($res==0) $res = $this->result;
		return mysql_fetch_row($res);
	}

	function fetch_array($res=0)
	{
		if ($this->connect()==false) return false;
		if ($res==0) $res = $this->result;
		return @mysql_fetch_array($res);
	}

	function fetch_object($res=0)
	{
		if (!$this->connect()) return false;
		if ($res==0) $res = $this->result;
		return mysql_fetch_object($res);
	}

	function free_result($res=0)
	{
		if (!$this->connect()) return false;
		if ($res==0) $res = $this->result;
		return mysql_free_result($res);
	}

	function data_seek($res, $count)
	{
		if (!$this->connect()) return false;
		return @mysql_data_seek($res, $count);
	}

	function backup($filename)
	{
		$crlf = "\r\n";
		$h = fopen($filename, "wb");
		fwrite($h, "# 備份日期：".date("Y/m/d")."$crlf");

		$rs = getdb("show tables");
		while (!$rs->eof) {
			$tbname = $rs->f[0];
			fwrite($h, "# Table：".$tbname."$crlf");
			$this->get_table_def($tbname, $h);
			$this->get_table_content($tbname, $h);
			$rs->movenext();
		}
		$rs->close();
		fclose($h);
	}

	// restore backup sql file
	function Restore($filename)
	{

		$fp = fopen($filename, "rb");
		$str = "";
		while (!feof($fp)) $str.=fgets($fp, 1024);

		$SQL = explode(";", $str);

		for ($i=0;$i<count($SQL)-1;$i++) {
			$this->unbuffered_query($SQL[$i]);
		}
	}

	function unbuffered_query($sql)
	{
		// select use slave db, other use master db
		$handle = preg_match("/^select /i", $sql) ? $this->handle : $this->handle_master;

		mysql_unbuffered_query($sql, $handle) or die(mysql_error());
	}

	function get_table_def($table, $h)
	{
		$db = $this->database;
		$crlf = "\r\n";

		$schema_create = "";
		//$schema_create .= "DROP TABLE IF EXISTS $table;$crlf";
		$schema_create .= "CREATE TABLE $table ($crlf";

		$result = mysql_db_query($db, "SHOW FIELDS FROM $table") or mysql_die();
		while($row = mysql_fetch_array($result))
		{
			$schema_create .= "   $row[Field] $row[Type]";
			if(isset($row["Default"]) && (!empty($row["Default"]) || $row["Default"] == "0"))
			$schema_create .= " DEFAULT '$row[Default]'";
			if($row["Null"] != "YES")
			$schema_create .= " NOT NULL";
			if($row["Extra"] != "")
			$schema_create .= " $row[Extra]";
			$schema_create .= ",$crlf";
		}
		$schema_create = ereg_replace(",".$crlf."$", "", $schema_create);
		$result = mysql_db_query($db, "SHOW KEYS FROM $table") or mysql_die();
		while($row = mysql_fetch_array($result))
		{
			$kname=$row['Key_name'];
			if(($kname != "PRIMARY") && ($row['Non_unique'] == 0))
			$kname="UNIQUE|$kname";
			if(!isset($index[$kname]))
			$index[$kname] = array();
			$index[$kname][] = $row['Column_name'];
		}

		while(list($x, $columns) = @each($index))
		{
			$schema_create .= ",$crlf";
			if($x == "PRIMARY") {
				$schema_create .= "   PRIMARY KEY (".implode($columns, ", ").")";
			} elseif (substr($x,0,6) == "UNIQUE") {
				$schema_create .= "   UNIQUE ".substr($x,7)." (".implode($columns, ", ").")";
			} else {
				$schema_create .= "   KEY $x (".implode($columns, ", ").")";
			}
		}

		$schema_create .= "$crlf)";
		fwrite($h, stripslashes($schema_create).";$crlf$crlf");
	}

	function get_table_content($table, $h)
	{
		$db = $this->database;
		$crlf="\r\n";
		$result = mysql_db_query($db, "SELECT * FROM $table") or mysql_die();
		$i = 0;
		while($row = mysql_fetch_row($result))
		{
			//        set_time_limit(60); // HaRa
			$table_list = "(";

			for($j=0; $j<mysql_num_fields($result);$j++)
			$table_list .= mysql_field_name($result,$j).", ";

			$table_list = substr($table_list,0,-2);
			$table_list .= ")";

			if(isset($GLOBALS["showcolumns"]))
			$schema_insert = "INSERT INTO $table $table_list VALUES (";
			else
			$schema_insert = "INSERT INTO $table VALUES (";

			for($j=0; $j<mysql_num_fields($result);$j++)
			{
				if(!isset($row[$j]))
				$schema_insert .= " NULL,";
				elseif($row[$j] != "")
				$schema_insert .= " '".addslashes($row[$j])."',";
				else
				$schema_insert .= " '',";
			}
			$schema_insert = ereg_replace(",$", "", $schema_insert);
			$schema_insert .= ")";
			fwrite($h, trim($schema_insert).";$crlf");
			$i++;
		}
	}

	function table_exists($tablename, $res=0)
	{
		return $this->is_table($tablename, $res);
	}

	function is_table($tablename, $res=0)
	{
		if (!$this->connect()) {
			return false;
		}
		if ($res==0) {
			$res = $this->result;
		}
		$result = mysql_list_tables($this->database);
		if (!$result) {
			return false;
		}
		while ($row = $this->fetch_array($result)) {
			if ($tablename==$row[0]) {
				$this->free($result);
				return true;
			}
		}
		$this->free($result);
		return false;
	}


	function free($res)
	{
		return mysql_free_result($res);
	}

	/**
	* Log sql string to file
	*/
	function logsql($sql)
	{
		return;//暫時不使用此功能

		if (eregi("^insert into bettotal", $sql)) return;
		elseif (eregi("^delete from bettotal", $sql)) return;
		elseif (eregi("^insert into bet", $sql) || eregi("^delete from bet", $sql)){
			$sql = htmlspecialchars($sql, ENT_QUOTES);
			$this->query("insert into dblog (logs) values ('{$sql}')");
		} else return;
	}

}

/**
* Database RecordSet class
*
*
*/
class RecordSet
{
	var $result = false;
	var $eof = true;
	var $f = array();

	var $db;
	var $page=0;
	var $pagesize=20;
	var $currpos=0;
	var $totalrecords = 0;
	var $totalpages=0;
	/**
	* Class Inititalize
	*
	*/
	function RecordSet()
	{
		global $CORE;
		if (!isset($CORE["db"])) {
			$CORE["db"] = new DB;
		}
		$this->db = $CORE["db"];
	}

	function open($sql, $page=0, $pagesize=20)
	{
		$this->page = $page;
		$this->pagesize = $pagesize;
		$this->result = $this->db->query($sql);
		if ($this->result==false) {
			$this->eof = true;
		} else {
			if ($this->page!=0) {
				// 分頁
				$c = ($this->page-1) * $this->pagesize;
				$this->db->data_seek($this->result, $c);
				$this->totalrecords = $this->db->num_rows($this->result);
				$this->totalpages = ceil($this->totalrecords / $this->pagesize);
				$this->f = $this->db->fetch_array($this->result);
				$this->eof = ($this->f==false);
			} else {
				$this->f = $this->db->fetch_array($this->result);
				$this->eof = ($this->f==false);
			}
		}
		$this->currpos = 0;
	}

	/**
	*	use next()
	*/
	function movenext()
	{
		$this->next();
	}

	/**
	*	移動到下一筆資料
	*/
	function next()
	{
		if ($this->result==false) {
			$this->eof = true;
			return;
		}
		$this->f = $this->db->fetch_array($this->result);
		$this->eof = (!$this->f);
		if (!$this->eof && $this->page!=0) {
			// 分頁
			if (++$this->currpos >= $this->pagesize) {
				$this->eof = true;
			}
		}
	}

	function close()
	{
		if ($this->result) {
			$this->db->free_result($this->result);
		}
		$this->result = false;
		$this->eof = false;
		$this->f = array();
	}

	function num_rows()
	{
		return $this->db->num_rows($this->result);
	}

	/**
	* move db record
	*
	*/
	function move($count)
	{
		for ($i=0; $i<$count; $i++) {
			$this->movenext();
		}
	}

	function pagelink()
	{
		$r = "<div id='y_pg'>";
		//if ($this->page==0) { return ""; } // 沒有分頁

		$url = $_SERVER["REQUEST_URI"];
		$url = preg_replace("/[\?\&]page=(\d+)?/", "", $url);
		if ($this->page > 1) {
			$r .= "<a href='".$url."'>".L_FIRSTPAGE."</a>";
			$r .= "<a href='".$url."&page=".($this->page - 1)."'>".L_PREVPAGE."</a>";
		} else {
			$r .= "<span>" . L_FIRSTPAGE."</span><span>".L_PREVPAGE."</span>";
		}

		if ($this->page < $this->totalpages) {
			$r .= "<a href='".$url."&page=".($this->page+1)."'>".L_NEXTPAGE."</a>";
			$r .= "<a href='".$url."&page=".($this->totalpages)."'>".L_LASTPAGE."</a>";
		} else {
			$r .= "<span>" . L_NEXTPAGE."</span><span>".L_LASTPAGE."</span>";
		}

		$r .= "<span>".L_PAGE." <select onchange=\"location.href='".$url."&page=' + this.value\">";
		for ($i = 1; $i <= $this->totalpages; $i++) {
			$r .= "<option value=".$i;
			if ($this->page == $i) { $r .= " selected"; }
			$r .= ">".$i;
		}
		$r .= "</select>";
		$r .= "，總共 ".$this->totalpages." 頁，".$this->totalrecords." 筆</span>";
		$r .= "</div>";
		return $r;
	}
}

function getrs($sql)
{
	global $CORE;
	if (!isset($CORE["db"])) {
		$CORE["db"] = new DB;
	}
	return $CORE["db"]->getrs($sql);
}

function getdb($sql, $page=0, $pagesize=20)
{
	$rs = new RecordSet;
	$rs->open($sql, $page, $pagesize);
	return $rs;
}

//檢查資料表內某欄位是否已建立
function db_IsExistsField($table,$field)
{
	$result = mysql_query("SHOW COLUMNS FROM ".$table." WHERE Field='".$field."'");
	if (!$result) {
		return false;
	}
	$chk = mysql_num_rows($result);
	mysql_free_result($result);
	return $chk;
}

/**
*   Update table
*/
function db_update($table, $fields, $where = '')
{
	global $CORE;
	if (!isset($CORE["db"])) {
		$CORE["db"] = new DB;
	}
	$sql = "UPDATE $table SET ";
	foreach ($fields as $key => $val) {
		$sql .= $key." = ".$val.",";
	}
	if (substr($sql, -1) == ",") {
		$sql = substr($sql, 0, strlen($sql)-1);
	}
	if ($where != '') {
		$sql .= " WHERE ".$where;
	}
	$CORE["db"]->query($sql);
}

/**
*   Insert table
*/
function db_insert($table, $fields)
{
	global $CORE;
	if (!isset($CORE["db"])) {
		$CORE["db"] = new DB;
	}
	$sql = "INSERT INTO $table (";
	$sql .= implode(",", array_keys($fields));
	$sql .= ") VALUES (";
	$sql .= implode(",", array_values($fields));
	$sql .= ")";
	//$CORE["db"]->query($sql);
	if($CORE["db"]->query($sql))
	return mysql_insert_id($CORE["db"]->handle_master);
	else return false;
}

/**
* 將字串編碼給 sql string 用
*/
function sqlstr($str)
{
	if (!get_magic_quotes_gpc()) {
		$str = addslashes($str);
	}
	return "'".$str."'";
}
//end