
$( window ).on( "load",function() {
    mainImagesFiles = JSON.parse($('#imagesFile').val());

    setTimeout(function(){
        $('select').select2({
            width: '450' ,
        });

        $("#manager").select2({
            maximumSelectionLength: 10,
            width: '450' 
        });
    },500);
});

$(document).on('submit',"#associationForm",function(event){
    event.preventDefault();
    $('label.error').remove();
    var formData = new FormData(this);

    if(mainImagesFiles){
        $.map(mainImagesFiles, function(file, index) {
            formData.append('main_language_image[]', file);
        });
    }

    $.ajax({
        url: baseUrl + "/admin/association/save",
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
                    window.location.href = listURL;
                },1000);

            }else {
                iziToast.error({
                    title: '',
                    message: 'Association has not been created successfully.',
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
                    //console.log(val);
                    var errorHtml = '<label class="error">'+val+'</label>';
                    if(key == 'main_language_image'){                       
                        $('.main_image_file').each(function(index, upload) {
                            if(!mainImagesFiles.length){
                                $(upload).parent().parent().after(errorHtml);
                            }
                        });
                    }else{
                        $('#'+key).parent().append(errorHtml);
                    }
                }); 
            }
        }
    });
});

$(document.body).on('click','.removeImage > span',function(){
    var imageid = $(this).attr('data-imageid');

    if(imageid){
        $.ajax({
            url: baseUrl + "/admin/association/image/remove",
            method: 'POST',
            data: {
                _token: csrfToken,
                imageid : imageid,
            },
            beforeSend: function() {
            },
            success: function(data) {
                mainImagesFiles.splice( $.inArray(imageid, mainImagesFiles), 1 );
            }
        });
    }
    $(this).parent().remove();
});

