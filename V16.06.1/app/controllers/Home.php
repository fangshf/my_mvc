<?php
/*******************************************************************************
 | Home.php ( 主頁面,首頁 )
 | -----------------------------------------------------------------------------
 | @package		    App
 | @subpackage		config
 | @Maintain:fangshf@gmail.com starts from 2015.03.08  
*******************************************************************************/
class Home extends Controller
{
  
  /**
   | -----------------------------------------------------------------------------
   | 必要!設定基礎view的名稱  
   | -----------------------------------------------------------------------------
  **/ 
  public function __Construct()
  {
    $this->views_name = 'home';      // test
  }
  
  /**
   | -----------------------------------------------------------------------------
   | 預設顯示頁面  
   | -----------------------------------------------------------------------------
  **/ 
  public function index( $name = '' )
  {
    # 不另外處理,直接叫用現有function : 關於網站
    $user = $this->about();
    # $user->name = $name;
    # $this->view('home/index', array('name' => $user->name));
  }
  
  /**
   | -----------------------------------------------------------------------------
   | 關於本站  
   | -----------------------------------------------------------------------------
  **/
  public function about()
  {
     # 設定頁面名稱
     $this->sub_title = '關於本站';
     
     # 存放要在view裡動態顯示的資料陣列
     $dataArr = array();
     # 現在位置
     $dataArr[] = array( '_POSTION', $this->get_postion( $this->views_name ));
     # 存放欲載入之view的html檔資料陣列
     $loadArr = array();
     $loadArr[] = 'about';
     # 因為使用home的共用樣版，故叫直呼叫父類別的基本函式顯示即可
     $this->base_template( $dataArr, $loadArr );
  }
  
  /**
   | -----------------------------------------------------------------------------
   | Jquery應用, 表單驗證  
   | -----------------------------------------------------------------------------
  **/
  public function jquery_form()
  {
     # 設定頁面名稱
     $this->sub_title = 'JQuery 應用, 表單驗證';
     
     # 設定現在位置
     $this->postionArr[] = array( 'JQuery應用範例', '' );
     $this->postionArr[] = array( '表單驗證', '' ); 
     
     # 存放要在view裡動態顯示的資料陣列
     $dataArr = array();
     # 現在位置
     $dataArr[] = array( '_POSTION', $this->get_postion( $this->views_name ));
     $dataArr[] = array( 'BG_TITLE', $this->sub_title);
     
     # 存放欲載入之view的html檔資料陣列
     $loadArr = array();
     $loadArr[] = 'jquery_form';
     # 因為使用home的共用樣版，故叫直呼叫父類別的基本函式顯示即可
     $this->base_template( $dataArr, $loadArr );
  }
  
  
}