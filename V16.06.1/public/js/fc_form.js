/*******************************************************************************
 | fc_form.js ( 常用的表單檢查模組.建議不要每頁載入!需要的頁面再載入即可. )
 | -----------------------------------------------------------------------------
 | @package		    js
 | @Maintain:fangshf@gmail.com starts from 2015.03.12
 | 說明: 使用 jquery-1.11.2.min.js
 | JS 自已寫有個好處就是不用載入太多的套件, 只專注於需要部份, 如此一來可增加效能
*******************************************************************************/

var fc_form = {
  /* 顯示提示字元 ----------------------------------------------------------- */
  show_msg : function(oThis){
    var fc_msg = $(oThis).attr('fc_msg');
    if( fc_msg === undefined ) return true;
    if( fc_msg != '' )
    {
      var fc_tootip = this.create_tootip(oThis,fc_msg );
      if( fc_tootip != '' )
      {
        $(oThis).after( fc_tootip );
      }
    }
  },
  
  /* 產生tooltip ----------------------------------------------------------- */
  create_tootip : function(oTarget, sMsg){
      var tar_left = $(oTarget).position().left + $(oTarget).width() + 6;
      var tar_top = $(oTarget).position().top;
      var oFc_id = 'fc_' + tar_left + tar_top;
      var oFc = $('div[fc_id="' + oFc_id + '"]'); 
      // 已存在時只切換class顯示
      if( oFc.hasClass('tooltip') )
      {
        oFc.removeClass("in").addClass("in")
        return '';
      }
      
      var oBj_tooltip = '';
       oBj_tooltip = '<div class="tooltip right in" style="left:'+tar_left+'px; top:'+tar_top+'px;" fc_id="' + oFc_id + '"   >'
                    + '<div class="tooltip-arrow"></div>'
                    + '<div class="tooltip-inner">'
                    + sMsg
                    + '</div>'
                    + '</div>';
      return oBj_tooltip;
  },
  
  /* 隱藏提示字元 ----------------------------------------------------------- */
  hidden_msg : function(oThis){
    var tar_left = $(oThis).position().left + $(oThis).width() + 6;
    var tar_top = $(oThis).position().top;
    var oFc_id = 'fc_' + tar_left + tar_top;
    var oFc = $('div[fc_id="' + oFc_id + '"]'); 
    // 已存在時只切換class顯示
    if( oFc.hasClass('tooltip') )
    {
      oFc.removeClass("in");
      return '';
    }
  }
  
 

};

