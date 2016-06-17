var f_ = {
  obj_b : "",
  
  draw_b : function( c_id )
            {
              if( ( c_id == null || c_id == "" ) && this.obj_b != "" ) { c_id = this.obj_b; }
              var b_canvas = document.getElementById(c_id);
              var b_context = b_canvas.getContext("2d");
              b_context.fillRect(50, 25, 150, 100);
            },
  clear_b : function ( c_id )
            {
              if( ( c_id == null || c_id == "" ) && this.obj_b != "" ) { c_id = this.obj_b; }
              var b_canvas = document.getElementById(c_id);
              b_canvas.width = b_canvas.width;
            }
            


};



