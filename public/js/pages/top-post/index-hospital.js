var $gallery = $(".gallery");
var recycle_icon = "<a href='link/to/recycle/script/when/we/have/js/off' title='Delete this item' class='ui-icon ui-icon-trash'>Delete item</a>";

$(document).ready(function(){
   $(".owl-carousel").owlCarousel();
   $('.block').hide();
       $('#post-list-all').show();
       $('#hospital-post-list').change(function () {
           $('.block').hide();
           $('#post-list-'+$(this).val()).fadeIn();
       });
});

function deleteItem($item) {     
   $item.fadeOut(function() {
     $item.remove();
   });
}   

function cloneObject($item) {
   var obj = $item.clone();
   obj.click(function(event) {
     var $item = $(this),
       $target = $(event.target);
     if ($target.is("a.ui-icon-trash")) {
       deleteItem($item);
     } 
     return false;
   });

   return obj;
}

$(function() {
   var $trash = $("#all-list-home-drop");

   $("li", $gallery).draggable({
     cancel: "a.ui-icon", 
     revert: "invalid", 
     containment: "document",
     helper: "clone",
     cursor: "move"
   });

   $trash.droppable({
     accept: ".gallery > li",
     activeClass: "ui-state-highlight",
     drop: function(event, ui) {
       getItem(ui.draggable);
     }
   }).sortable({
      items: "li",
      sort: function() {
        $( this ).removeClass( "ui-state-default" );
      }
   }); 

   function getItem($item) {

     var ulId = 'ul-'+$($trash).attr('id');

     var obj = cloneObject($item);
     var $list = $("ul", $trash).length ?
         $("ul", $trash) :
         $("<ul class='ui-helper-reset' id='"+ulId+"'/>").appendTo($trash);

         obj.append(recycle_icon).appendTo($list).fadeIn();
   }   
});
$(function() {
   var $trash = $("#all-list-top-drop");
   $trash.droppable({
     accept: ".gallery > li",
     activeClass: "ui-state-highlight",
     drop: function(event, ui) {
       getItem(ui.draggable);
     }
   });   

   function getItem($item) {
      var ulId = 'ul-'+$($trash).attr('id');

      var obj = cloneObject($item);
      var $list = $("ul", $trash).length ?
          $("ul", $trash) :
          $("<ul class='ui-helper-reset' id='"+ulId+"'/>").appendTo($trash);
 
         obj.append(recycle_icon).appendTo($list).fadeIn();
     }
});

$.each(hospitalCategoryArray, function (key, value) {
   $(function() {
      var $trash = $("#list-home-"+value.id+"-drop");
      
      $trash.droppable({
         accept: ".gallery > li",
         activeClass: "ui-state-highlight",
         drop: function(event, ui) {
            getItem(ui.draggable);
         }
      });   

      function getItem($item) {
         var ulId = 'ul-'+$($trash).attr('id');

         var obj = cloneObject($item);
         var $list = $("ul", $trash).length ?
            $("ul", $trash) :
            $("<ul class='ui-helper-reset' id='"+ulId+"'/>").appendTo($trash);

            obj.append(recycle_icon).appendTo($list).fadeIn();
      }
   });
   $(function() {
      var $trash = $("#list-top-"+value.id+"-drop");
      
      $trash.droppable({
         accept: ".gallery > li",
         activeClass: "ui-state-highlight",
         drop: function(event, ui) {
            getItem(ui.draggable);
         }
      });   

      function getItem($item) {
         var ulId = 'ul-'+$($trash).attr('id');
         var obj = cloneObject($item);
         var $list = $("ul", $trash).length ?
               $("ul", $trash) :
               $("<ul class='ui-helper-reset' id='"+ulId+"'/>").appendTo($trash);

            obj.append(recycle_icon).appendTo($list).fadeIn();
      }
   });
});


$(document).on('click', '.delete-hospital-post', function(e) {
   $(this).parent().remove();
});

$(document).on('click', '.add-hospital-post', function(e) {
   var section = $(this).data('section');
   var category = $(this).data('category');
   var listId = $(this).siblings().find('ul').attr('id');
   var postArray = []; 
   $("#" + listId + " li").each(function(i, n) {
      postArray.push($(this).attr('id'));
   }); 
   console.log(postArray);
   var form_data = new FormData();                  
   form_data.append('section', section);
   form_data.append('category', category);
   form_data.append('postArray', postArray);
   event.preventDefault();
   $.ajax({
      url:updateHopitalPost,
      method:"POST",
      data:form_data,
      dataType:'JSON',
      contentType: false,
      cache: false,
      processData: false,
      success:function(data)
      {
         if(data.status_code == 200) {
               iziToast.success({
                  title: '',
                  message: data.message,
                  position: 'topRight',
                  progressBar: false,
                  timeout: 1000,
               });
               location.reload(true);

         }else {
               iziToast.error({
                  title: '',
                  message: data.message,
                  position: 'topRight',
                  progressBar: false,
                  timeout: 1000,
               });
               location.reload(true);

         }   
      }   
   });
});

$(document).ready(function(){  
   function fetch_event_data(search = '',hospital_id = 0)
   {
    $.ajax({
     url:getEvents,
     method:'GET',
     data:{search:search,hospital_id:hospital_id},
     dataType:'json',
     success:function(data)
     {
      $('#events-list').html(data.events_data);
      $("li", $gallery).draggable({
         cancel: "a.ui-icon", 
         revert: "invalid", 
         containment: "document",
         helper: "clone",
         cursor: "move"
      });
     }
    })
   }
  
   $(document).on('keyup', '#event-search-box', function(){ 
   var search = $(this).val();
   var hospital_id = 0;
   if($(".hospital-selected").length > 0){
      hospital_id = $(".hospital-selected").data('hospital-id');
    }
    fetch_event_data(search,hospital_id);
    
   });
   $(document).on('click', '.hospital-item', function(e) {
      var search = $('#event-search-box').val();
      if (!$(this).hasClass("hospital-selected")) {
         $('.hospital-list .hospital-item').removeClass("hospital-selected");
         $(this).toggleClass("hospital-selected");
         var hospital_id = $(this).data('hospital-id');
         fetch_event_data(search,hospital_id);
     }else {        
      $(this).toggleClass("hospital-selected");
      fetch_event_data(search);
     }
   });
});


