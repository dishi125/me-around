$('body').on("click",".add_button",function(){
     var catId = $(this).data('cat-id');
     var entityId = $(this).data('entity-id');
     var section = $(this).data('section');
     var fieldHTML = '<li><div class="custom-file addBanner" data-cat-id="' + catId + '" data-entity-id="' + entityId + '" data-section="' + section + '"><i class="fa fa-plus"></i></div><div class="form-group"><span class="badge badge-primary">Info</span></div></li>'; 
     var wrapper = $(this).parent('ul');      
     $(fieldHTML).insertBefore( this);  

});

var getUrlParameter = function getUrlParameter(sParam) {
   var sPageURL = window.location.search.substring(1),
       sURLVariables = sPageURL.split('&'),
       sParameterName,
       i;

   for (i = 0; i < sURLVariables.length; i++) {
       sParameterName = sURLVariables[i].split('=');

       if (sParameterName[0] === sParam) {
           return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
       }
   }
};

$(function() {
   var countrySelected = getUrlParameter('countryId');
   if(countrySelected) {
      $("#country_id").val(countrySelected).change();
   }

});



$('body').on("click",".addBanner",function(){
    var catId = $(this).attr("data-cat-id");
    var entityId = $(this).attr("data-entity-id");
    var section = $(this).attr("data-section");
    var countryId = $('#country_id').val();
     $.ajax({
             url: addTopPost,
             method: 'POST',
             data: {
                'cat-id' : catId,
                'entity-id' : entityId,
                'section' : section,
                'country-id' : countryId
             },            
            success: function (data) {
                pageModel.html('');
                pageModel.html(data);
                pageModel.modal('show');
                 
             }
       });
 });
 
 
 $(document).on('click', '#add-post-btn', function(e) {
   var countryId = $('#country_id').val();
    var file_data = $('#post-image').prop('files')[0];   
    var bannerId = $('#banner-id').val();
    var link = $('#post-link').val();
    var slide_duration = $('#post-slide-duration').val();
    var order = $('#post-display-order').val();
    var error_message = $('.error-msg');
    if (file_data == undefined) {
         $("#post-image").focus();
         error_message.html('This field is required');
         return false;
    }
    var form_data = new FormData();                  
    form_data.append('file', file_data);
    form_data.append('banner_id', bannerId);
    form_data.append('link', link);
    form_data.append('slide_duration', slide_duration);
    form_data.append('order', order);
    console.log(form_data);
    event.preventDefault();
    $.ajax({
       url:storeTopPost,
       method:"POST",
       data:form_data,
       dataType:'JSON',
       contentType: false,
       cache: false,
       processData: false,
       success:function(data)
       {
          pageModel.modal('hide'); 
          if(data.status_code == 200) {
                iziToast.success({
                   title: '',
                   message: data.message,
                   position: 'topRight',
                   progressBar: false,
                   timeout: 1000,
                });
                var url = window.location.href.substring(0, window.location.href.indexOf('?'));
                location.href = url +"?countryId="+countryId;
                console.log(location.href);
                window.location.href;
 
          }else {
                iziToast.error({
                   title: '',
                   message: data.message,
                   position: 'topRight',
                   progressBar: false,
                   timeout: 1000,
                });
                var url = window.location.href.substring(0, window.location.href.indexOf('?'));
                location.href = url +"?countryId="+countryId;
                console.log(location.href);
                window.location.href;
 
          }   
       }   
    });
 });
 
 function editPosts(id) {
     $.get(baseUrl + '/admin/top-post/edit/post/' + id, function (data, status) {
         pageModel.html('');
         pageModel.html(data);
         pageModel.modal('show');
     });
 }
 
 $(document).on('click', '#edit-post-btn', function(e) {
   var countryId = $('#country_id').val();
    var file_data = $('#post-image').prop('files')[0];   
    var bannerImageId = $('#banner-image-id').val();
    var link = $('#post-link').val();
    var slide_duration = $('#post-slide-duration').val();
    var order = $('#post-display-order').val();
    var form_data = new FormData();                  
    form_data.append('file', file_data);
    form_data.append('banner_image_id', bannerImageId);
    form_data.append('link', link);
    form_data.append('slide_duration', slide_duration);
    form_data.append('order', order);
    event.preventDefault();
    $.ajax({
       url:updateTopPost,
       method:"POST",
       data:form_data,
       dataType:'JSON',
       contentType: false,
       cache: false,
       processData: false,
       success:function(data)
       {
          pageModel.modal('hide'); 
          if(data.status_code == 200) {
                iziToast.success({
                   title: '',
                   message: data.message,
                   position: 'topRight',
                   progressBar: false,
                   timeout: 1000,
                });
                var url = window.location.href.substring(0, window.location.href.indexOf('?'));
                location.href = url +"?countryId="+countryId;
                console.log(location.href);
                window.location.href;
 
          }else {
                iziToast.error({
                   title: '',
                   message: data.message,
                   position: 'topRight',
                   progressBar: false,
                   timeout: 1000,
                });
                var url = window.location.href.substring(0, window.location.href.indexOf('?'));
                location.href = url +"?countryId="+countryId;
                console.log(location.href);
                window.location.href;
 
          }   
       }   
    });
 });
 
 function deletePost(id) {
     $.get(baseUrl + '/admin/top-post/delete/' + id, function(data, status) {
         pageModel.html('');
         pageModel.html(data);
         pageModel.modal('show');
     });
 }

 $("body").on('change','.is-random-check',(function(event) {
    var countryId = $('#country_id').val();
    var catId = $(this).attr("data-cat-id");
    var entityId = $(this).attr("data-entity-id");
    var section = $(this).attr("data-section");
    var countryCode = $(this).attr("data-country-code");
    var is_random = this.checked;
    var form_data = new FormData();                  
    form_data.append('cat_id', catId);
    form_data.append('entity_id', entityId);
    form_data.append('section', section);
    form_data.append('is_random', is_random);
    form_data.append('country_code', countryCode);
    event.preventDefault();
    $.ajax({
       url:updateRandomCheckbox,
       method:"POST",
       data:form_data,
       dataType:'JSON',
       contentType: false,
       cache: false,
       processData: false,
       success:function(data)
       {
          pageModel.modal('hide'); 
          if(data.status_code == 200) {
                iziToast.success({
                   title: '',
                   message: data.message,
                   position: 'topRight',
                   progressBar: false,
                   timeout: 1000,
                });
                var url = window.location.href.substring(0, window.location.href.indexOf('?'));
                location.href = url +"?countryId="+countryId;
                console.log(location.href);
                window.location.href;
 
          }else {
                iziToast.error({
                   title: '',
                   message: data.message,
                   position: 'topRight',
                   progressBar: false,
                   timeout: 1000,
                });
                var url = window.location.href.substring(0, window.location.href.indexOf('?'));
                location.href = url +"?countryId="+countryId;
                console.log(location.href);
                window.location.href;
 
          }   
       }   
    });
    
}));


