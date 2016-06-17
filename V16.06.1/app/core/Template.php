<?php
/** 
 * Template
 * 樣板物件
 *
 * @version 1.0
 * @since 1.0
 * @access public
 *
 *
 * @package core
 */
class Template
{
	protected $vars			= array();
	protected $files			= array();
	protected $version		= '0.1.0';
	protected $errortitle		= 'Template Error';
	protected $var_format		= '/\{(\w+)\}/e';
	protected $lang_format		= '/\{\{(\w+)\}\}/e';
	protected $dir			= '.';
  protected $view_name;
	
	function Template( $view = 'default', $dir='default')
	{
		$this->setdir( '../app/views/'. $view . '/'.$dir."/");
	}
    

	
  function setdir($dir)
	{
		if (!is_dir($dir)) {
		    die('Cannot found template folder: '.$dir);
		}
		$this->dir = $dir;
		$this->set('_TPL_PATH', $dir);
		return true;
	}
	
	function LoadFromString($blockname, $blockdata)
	{
		$this->files[$blockname] = $blockdata;
	}
		
	function set($tpl_var, $value = null)
	{
		if (is_array($tpl_var)){
			foreach ($tpl_var as $key => $val) {
				if ($key != '') $this->vars[$key] = $val;
			}
		} else {
			if ($tpl_var != '') $this->vars[$tpl_var] = $value;
		}
	}
	
	function clear($tpl_var)
	{
		$this->vars = array();
	}
	
	// return parse template string
	function parse($block)
	{
		if (!isset($this->files[$block])) {
			die("not vaild block name :" . $block);
		}
		$temp = $this->files[$block];
		$temp = @preg_replace($this->lang_format, "\\1", $temp); 
		$temp = @preg_replace($this->var_format, "\$this->vars['\\1']", $temp); 
		return $temp;
	}
	
	
	function load($tpl_var, $value='')
	{
		if (is_array($tpl_var)){
			foreach ($tpl_var as $key => $val) {
				if ($key != '') {
					if (!file_exists($this->dir.$val)) {
						die("can't find template file :".$this->dir.$val);
					}
					$this->files[$key] = implode("", file($this->dir.$val));
				}
						
			}
		} else {
			if ($tpl_var != '') {
				if (!file_exists($this->dir.$value)) {
					die("can't find template file :".$this->dir.$value);
				}
				$this->files[$tpl_var] = implode("", file($this->dir.$value));
			}
		}
	}
	
	
	function show($block)
	{
		echo $this->parse($block);
	}
}

/** 
 * Template ver 2
 * 延伸 Template 的功能, 預設的 template 檔案都會放在該模組的 tpl 目錄下
 *
 * @version 1.0
 * @since 1.0
 * @access public
 *
 *
 * @package core
 */
class Template2 extends Template
{
	function Template2()
	{
		$this->setdir("/tpl/".G_TEMPLATE.'/');
	}
	
}


/** 
 * Template ver 3
 * 延伸 Template 的功能, 登入頁專用的樣版
 * @package core
 */
class Template3 extends Template
{
	function Template3($title = '')
	{
		$this->setdir("./loginTpl/tpl_".G_LOGINTPL.'/');
		$this->set("sitename", ( $title ? $title : '請登入' ));
	}
}
