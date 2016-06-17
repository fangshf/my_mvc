<?php
/*
* �]�w�޲z�Ҳ�
*
* �x�s�Ψ��X�t�γ]�w��
*
*/

class Config
{
	var $__version = "1.0";
	var $__classname = "Config";
	var $__sql = array(
		"1.0" => "create table k_config (id varchar(50) not null, ap varchar(50) not null, value varchar(150) not null, primary key(id,ap) );"
		);
	
	var $cache = array('');
	
	/**
	* ���X�]�w��
	*/
	function Get($id, $ap='')
	{
	    if (isset($this->cache[$ap][$id])) {
	        return $this->cache[$ap][$id];
	    } else {
	        $ret = "";
    		$rs = getdb("!!select value from k_config where id='$id' and ap='$ap' LIMIT 0,1");
    		if (!$rs->eof) $ret = $rs->f[0];
    		$this->cache[$ap][$id] = $ret;
    		return $ret;
    	}		
	}
	
	/**
	* �x�s�]�w��
	*/
	function Set($id, $value, $ap='')
	{
		getdb("INSERT INTO `k_config` (`id`,`ap`,`value`) VALUES ('{$id}','{$ap}','{$value}') ON DUPLICATE KEY UPDATE `value`='{$value}'");
		$this->cache[$ap][$id] = $value;
	}
}
?>