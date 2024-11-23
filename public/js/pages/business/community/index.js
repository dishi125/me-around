var catId = $('.filterButton.active').attr('data-filter');

$(function() {
    mainImagesFiles = [];
    $( "#createPostModel > .modal-dialog" ).css( "maxWidth", ( $( window ).width() * 0.5 | 0 ) + "px" );
    
    
    loadTableData(catId);
    $('body').on('click','.filterButton',function(){
        var filter = $(this).attr('data-filter');
        $('#all-table').DataTable().destroy();
        loadTableData(filter);
    })


    $(document.body).on('click','.removeImage > span',function(){
        var timestemp = $(this).attr('data-timestemp');
        if(timestemp){
            mainImagesFiles = $.grep(mainImagesFiles, function(e){ 
                return e.timestemp != timestemp; 
            });
            if(!mainImagesFiles.length){
                $("#main_image").val('');
            }
            console.log(mainImagesFiles)
        }
        $(this).parent().remove();
        $('.maxerror').remove();
    });

    
});


function loadTableData(filter){

    var filter = filter || 'all';

    $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 4, "desc" ]],
        ajax: {
            url: allUserTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken, filter : filter }
        },
        columns: [
            { data: "title", orderable: true },
            { data: "category", orderable: true },
            { data: "user", orderable: true },
            { data: "views_count", orderable: true },
            { data: "comment_count", orderable: true },
            { data: "time_ago", orderable: true },
            { data: "actions", orderable: false }
        ]
    });
}

$(document).on('submit', '#communitypostform', function(e) {
    e.preventDefault();
    $('label.error').remove();

    var formData = new FormData(this);

    $.map(mainImagesFiles, function(file, index) {
        formData.append('main_language_image[]', file);;
    });
    var ajaxUrl = $(this).attr('action');
    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        contentType: false, 
        processData: false,
        data: formData,
        beforeSend: function() {
            $('.cover-spin').show();
        },
        success: function(data) {
            $('.cover-spin').hide();
            if(data.success == true) {
                iziToast.success({
                    title: '',
                    message: data.message,
                    position: 'topRight',
                    progressBar: false,
                    timeout: 1500,
                });
            }else {
                iziToast.error({
                    title: '',
                    message: data.message,
                    position: 'topRight',
                    progressBar: false,
                    timeout: 1500,
                });
            }
            $('#all-table').DataTable().destroy();
            loadTableData(catId);
            $('#createPostModel').modal('hide');
            

        },
        error: function(errors){
            $('.cover-spin').hide();
            var errorsMsg = errors.responseJSON.errors;
            $.each(errorsMsg, function(key,valueObj){
                var errorHtml = '<label class="error">'+valueObj+'</label>';
                $('#'+key).parent().append(errorHtml);
                if(key == 'main_language_image'){                       
                    $('.main_image_file').each(function(index, upload) {
                        if(!mainImagesFiles.length){
                            $(upload).parent().parent().after(errorHtml);
                        }
                    });
                }
            });
        }
    });

});

function imagesPreview(input, placeToInsertImagePreview) {

    mainImagesFiles = mainImagesFiles || [];
    console.log(input)
    if ((parseInt(input.files.length) + parseInt(mainImagesFiles.length)) > 4){
        var errorMsg = '<label class="error maxerror">You can only upload a maximum of 4 files.</label>';
        $(input).parent().parent().parent().find('label.error').remove();
        $(input).parent().parent().after(errorMsg);
        $("#main_image").val('');
    }else{
        if (input.files) {
            $('.maxerror').remove();
            Array.from(input.files).forEach(async (file,index) => { 
                console.log(index)
                var currentTimestemp = new Date().getTime()+''+index;
                file.timestemp = currentTimestemp;
                mainImagesFiles.push(file);
                var reader = await new FileReader(file);
                reader.onload = function(event) {
                    var bgImage = $($.parseHTML('<div>')).attr('style', 'background-image: url('+event.target.result+')').addClass("bgcoverimage").wrapInner("<img src='"+noImage+"' />"); 
                    var container = jQuery("<div></div>",{class: "removeImage", html:'<span data-timestemp="'+currentTimestemp+'" class="pointer"><i class="fa fa-times-circle fa-2x"></i></span>'});
                    container.append(bgImage);
                    container.appendTo(placeToInsertImagePreview);
                }
                reader.readAsDataURL(file);
            });
        }
    }
};