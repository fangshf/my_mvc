/*******************************************************************************
 | fc_char.js ( 圖表模組.建議不要每頁載入!需要的頁面再載入即可. )
 | -----------------------------------------------------------------------------
 | @package		    js
 | @Maintain:fangshf@gmail.com starts from 2015.03.18
 | 說明: 使用 jquery-1.11.2.min.js
 | JS 自已寫有個好處就是不用載入太多的套件, 只專注於需要部份, 如此一來可增加效能
*******************************************************************************/

/* -----------------------------------------------------------------------------
 | 物件本身 參照用
 | @param	sCanID 製元素的ID
 | @return this
 ---------------------------------------------------------------------------- */ 
function fc_char( sCanID )    // sCanID
{
  /*  物件本身 -------------------------------------------------------------- */
  var oThis = this;
  
  /* 要處理的繪圖區及相關物件 ----------------------------------------------- */
  this.obj_canvas = document.getElementById(sCanID);
  this.obj_context = this.obj_canvas.getContext("2d");
  this.obj_context.font = "thin 15px sans-serif";
  
  
  /* 設定 X 軸和 Y軸 起始繪圖單位 至少要大於0.5 ----------------------------- */
  this.obj_fSX = 40;
  this.obj_fSY = 20;
  
  /* 設定 X 軸和 Y軸間距 ---------------------------------------------------- */
  this.obj_iX = 10;
  this.obj_iY = 10;
  
  /* 設定 X 軸和 Y軸間距 ---------------------------------------------------- */
  this.set_iXY = function( iX, iY )
  {
    this.obj_iX = iX;
    this.obj_iY = iY;  
  }
  
  /* 繪製背景線條圖區 ----------------------------------------------------------
   | @param iX x軸間距
   | @param iY y軸間距
  */
  this.draw_interval = function()
  {
    var b_canvas = oThis.obj_canvas;
    var b_context = oThis.obj_context;
    var iX = this.obj_iX;
    var iY = this.obj_iY;
    var iEY = b_canvas.height - iY; 
    // 垂直
    for (var x = oThis.obj_fSX; x < b_canvas.width; x += iX)
    {
      b_context.moveTo(x, oThis.obj_fSY);
      b_context.lineTo(x, iEY);
    }
    // 水平
    for (var y = oThis.obj_fSY + iY; y < b_canvas.height; y += iY)
    {
      b_context.moveTo(oThis.obj_fSX, y);
      b_context.lineTo(b_canvas.width, y);
    }
    // 設定顏色
    b_context.strokeStyle = "#eee";
    b_context.stroke();
  
  };
  
  /* 顯示 X 軸 提示字元 --------------------------------------------------------
   | @param arrData x軸文字資料陣列
   | @param 字的位置. 'line' 對齊垂直線 'center' 對齊中間
   */
  this.draw_x_text = function( arrData, sAlign )
  {
    var b_context = oThis.obj_context;
    var i = 0;
    var startx = oThis.obj_fSX;
    var starty = oThis.obj_canvas.height;
    // 判斷x起始位置
    if( sAlign == 'line' )
    {
      startx = oThis.obj_iX;
    }
    // 因為目前只有2種,所以其他歸中間
    else
    {
      startx = oThis.obj_fSX + oThis.obj_iX / 2;
    }
    
    b_context.textAlign = "center";
    b_context.textBaseline = "bottom";
    for( i = 0; i < arrData.length; i++ )
    {
      b_context.fillText(arrData[i], ( startx + ((i) * oThis.obj_iX) ), starty);
    }
    
  };
  
  /* 顯示 Y 軸 提示字元 --------------------------------------------------------
   | @param oData y軸文字資料陣列
   | {
   |  "fStart" : Y軸起始值, ex 10,
   |  "fMax"   : Y軸最大值, ex100,
   |  "fScore" : 間距 ex.10
   | }
   */
  this.draw_y_text = function( oData )
  {
    var b_context = oThis.obj_context;
    var i = 0;
    var startx = oThis.obj_fSX - 5; //oThis.obj_fSX ; 
    var starty = oThis.obj_canvas.height - oThis.obj_iY;
    b_context.textAlign = "right";
    b_context.textBaseline = "middle";
    
    // 繪製原則: 1. 不超過 最大值
    var maxItem = Math.floor( ( oData.fMax - oData.fStart ) / oData.fScore ) + 1;
    
    for( i = 1; i < maxItem; i++ )
    {
      b_context.fillText(i * oData.fScore , startx, ( starty - (i * oThis.obj_iY) ));
    }    
  };
  
  
  /* 繪製長條圖區 --------------------------------------------------------------
   | @param arrData 資料陣列
  */
  this.draw_bar = function( oData )
  {
    var b_canvas = oThis.obj_canvas;
    var b_context = oThis.obj_context;
    b_context.fillStyle = "#eee";
    b_context.strokeStyle = "#000";
    
    var iSY = 0;                  // Y的起始位置,需計算
    var iEY = oThis.obj_canvas.height  - oThis.obj_fSY - oThis.obj_iY ; // y的結束位置
    var iSX = oThis.obj_fSX + ( oThis.obj_iX * 0.2 ); // x 開始位置
    var fHeight = 0;              // Y的高度
    var fWeight = oThis.obj_iX * 0.6;      
    for( var i = 0; i < oData.arrData.length; i++)
    {
      // 資料超出可視範圍 先不秀,之後可以討論顯示方式 b_canvas.width
      fHeight = ( oData.arrData[i] / oData.yArr.fScore * oThis.obj_iY );
      iSY = oThis.obj_canvas.height - fHeight - oThis.obj_iY; // Y的高度 : 資料 / 集距 * 每格Y的PX值
      iSX = oThis.obj_fSX + ( oThis.obj_iX * 0.2 ) + i * oThis.obj_iX; // x 開始位置
      b_context.fillStyle = "#eee";
      b_context.fillRect(iSX, iSY, fWeight, fHeight);
      // b_context.strokeRect(iSX, iSY, fWeight, fHeight - 1);
      
      //顯示文字
      b_context.textAlign = "center";
      b_context.textBaseline = "bottom";
      iSX = oThis.obj_fSX + ( oThis.obj_iX * 0.2 ) + i * oThis.obj_iX + fWeight * 0.5; // x 開始位置
      oThis.obj_context.font = "this 15px sans-serif";
      b_context.fillStyle = "#000";
      b_context.fillText( oData.arrDataLabel[i] , iSX, iSY);
    
    }
    
  
  
  };
  
  
  
  /* 顯示提示字元 ----------------------------------------------------------- */
  this.draw_b = function( c_id )
            {
              if( ( c_id == null || c_id == "" ) && this.obj_b != "" ) { c_id = this.obj_b; }
              var b_canvas = document.getElementById(c_id);
              var b_context = b_canvas.getContext("2d");
              b_context.fillRect(50, 25, 150, 100);
            };
  
  
  
  /* 顯示提示字元 ----------------------------------------------------------- */
  this.clear_b = function ( c_id )
            {
              if( ( c_id == null || c_id == "" ) && this.obj_b != "" ) { c_id = this.obj_b; }
              var b_canvas = document.getElementById(c_id);
              b_canvas.width = b_canvas.width;
            };
            

  return this;
};



