var mainImagesFiles = [];
$(document).ready(function() { 
    if($("#imagesFile").length){
        mainImagesFiles = JSON.parse($('#imagesFile').val());
    }


    $(document).on('click','#remove_more',function(){
        $(this).parent().parent().remove();
    });

    $(document).on('click','#add_more',function(){
        $(this).attr('disabled','disabled');
        let field = $(this).attr('field');
        let index = $(".index_"+field).length;
        let thisElement = $(this);
        if(field){
            $.ajax({
                url: baseUrl + "/admin/wedding/add/more",
                method: 'POST',
                data: {
                    _token: csrfToken,
                    field : field,
                    index : index,
                },
                beforeSend: function() {
                },
                success: function(data) {
                    $(thisElement).removeAttr("disabled");
                    $(thisElement).closest('.repeater-content').append(data);
                }
            });
        }
    });

   $(document).on('click','.removeImage > span',function(){
       var timestemp = $(this).attr('data-timestemp');
       var fieldname = $(this).attr('fieldname');
       var imageid = $(this).attr('data-imageid');
       var index = $(this).attr('data-index');

       if(imageid){
           $.ajax({
               url: baseUrl + "/admin/wedding/image/remove",
               method: 'POST',
               data: {
                   _token: csrfToken,
                   imageid : imageid,
                   index : index,
               },
               beforeSend: function() {
               },
               success: function(data) {
                   mainImagesFiles.splice( $.inArray(imageid, mainImagesFiles), 1 );
               }
           });
       }
       //if(timestemp){
            if(fieldname == 'wedding_gallery'){
                mainImagesFiles = $.grep(mainImagesFiles, function(e){ 
                    return e.timestemp != timestemp; 
                });
            }
            $("#"+fieldname).val('');
      // }
       console.log($(this).closest( ".add-image-icon" ))
       $(this).parent().parent().parent().next( ".add-image-icon" ).show();
       
       $(this).parent().remove();
       $('.maxerror').remove();
   });

    $(document).on('submit',"#weddingForm",function(event){
        event.preventDefault();
        $('label.error').remove();
        var formData = new FormData(this);
        if(mainImagesFiles){
            $.map(mainImagesFiles, function(file, index) {
                formData.append('main_images[]', file);
            });
        }
        $.ajax({
            url: $(this).attr('action'),
            type:"POST",
            contentType: false, 
            processData: false,
            data: formData,
            beforeSend: function() {
                $('.cover-spin').show();
            },
            success:function(response) {
                $('.cover-spin').hide();
                if(response.success == true){
                    iziToast.success({
                        title: '',
                        message: response.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });

                    setTimeout(function(){
                        window.location.href = response.redirect;
                    },1000);

                }else {
                    iziToast.error({
                        title: '',
                        message: 'Wedding has not been created successfully.',
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1500,
                    });
                }
            },
            error:function (response, status) {
                $('.cover-spin').hide();
                if( response.responseJSON.success === false ) {
                    var errors = response.responseJSON.errors;
                    
                    $.each(errors, function (key, val) {
                        console.log(val)
                        var errorHtml = '<label class="error">'+    val.join("<br />")+'</label>';
                        $('#'+key.replaceAll(".", "_")).parent().append(errorHtml);
                    }); 
                }
            }
        });
    });
});

function imagesPreview(input, placeToInsertImagePreview, fieldname, is_multi) {
    console.log(is_multi);
    var noImage = baseUrl + "/public/img/noImage.png";
    mainImagesFiles = mainImagesFiles || [];

    if (input.files) {
        $('.maxerror').remove();
        Array.from(input.files).forEach(async (file,index) => { 
            console.log(index)
            var currentTimestemp = new Date().getTime()+''+index;
            file.timestemp = currentTimestemp;
            if(fieldname == 'wedding_gallery'){
                mainImagesFiles.push(file);
            }
            var reader = await new FileReader(file);
            reader.onload = function(event) {
                var bgImage = $($.parseHTML('<div>')).attr('style', 'background-image: url('+event.target.result+')').addClass("bgcoverimage").wrapInner("<img src='"+noImage+"' />"); 
                var container = jQuery("<div></div>",{class: "removeImage", html:'<span fieldname="'+ fieldname +'" data-timestemp="'+currentTimestemp+'" class="pointer"><i class="fa fa-times-circle fa-2x"></i></span>'});
                container.append(bgImage);
                if(!is_multi){
                    $(placeToInsertImagePreview).html(container);
                }else{
                    container.appendTo(placeToInsertImagePreview);
                }
            }
            reader.readAsDataURL(file);
            if(!is_multi){
                $(input).parent().hide();
            }
        });
    }
};
