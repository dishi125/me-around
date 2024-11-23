$(function() {

    $.fn.modal.Constructor.prototype._enforceFocus = function() {};
    $('.modal').on('shown.bs.modal', function () {
        $('select').select2({
            width: '450' ,
        });

        $("#manager").select2({
            maximumSelectionLength: 10,
            width: '450' 
        });

    });

    loadTableData();
    $('body').on('click','.filterButton',function(){
        var filter = $(this).attr('data-filter');
        $('#all-table').DataTable().destroy();
        loadTableData();
    })
    
});

function deleteAssociationConfirmation(association_id){
    $.get(baseUrl + '/admin/association/get/delete/' + association_id, function (data, status) {
        $("#deleteAssociationModal").html('');
        $("#deleteAssociationModal").html(data);
        $("#deleteAssociationModal").modal('show');
    });
}

function deleteAssociation(association_id){
    if(association_id){
        $.ajax({
            url: baseUrl + "/admin/association/delete",
            method: 'POST',
            data: {
                _token: csrfToken,
                association_id : association_id,
            },
            beforeSend: function(){ $(".cover-spin").show(); },
            success: function(response) {
                $(".cover-spin").hide();
                $("#deleteAssociationModal").modal('hide');
                if(response.success == true){
                    iziToast.success({
                        title: '',
                        message: response.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });
                    $('#all-table').DataTable().destroy();
                    loadTableData();
    
    
                }else {
                    iziToast.error({
                        title: '',
                        message: response.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1500,
                    });
                }
            }
        });
    }
}

function loadTableData(country = 0){

    $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 0, "desc" ]],
        ajax: {
            url: associationTableData,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken,'country':country }
        },
        columns: [
            { data: "association_name", orderable: true },
            { data: "president", orderable: false },
            { data: "manager", orderable: false },
            { data: "member", orderable: false },            
            { data: "status", orderable: false },            
            { data: "actions", orderable: false }
        ]
    });
}

function deletePost(URL) {
    $.get( URL, function (data, status) {
        $("#deletePostModal").html('');
        $("#deletePostModal").html(data);
        $("#deletePostModal").modal('show');
    });
}

function associateForm(id) {

    var form1 = form;
    if(typeof id != 'undefined'){
        form1 = baseUrl + "/admin/association/form/"+id;
    }
    
    $.get( form1, function (data) {        
        $("#deletePostModal").html('');
        $("#deletePostModal").html(data);
        $( "#deletePostModal > .modal-dialog" ).css( "maxWidth", ( $( window ).width() * 0.6 | 0 ) + "px" );
        $("#deletePostModal").modal('show');
        mainImagesFiles = JSON.parse($('#imagesFile').val());
      
    });
}

$(document).on('change',"#country",function(event){
    event.preventDefault();
    $('#all-table').DataTable().destroy();
    loadTableData($(this).val());

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
                $("#deletePostModal").modal('hide');
                $('#all-table').DataTable().destroy();
                loadTableData();

                iziToast.success({
                    title: '',
                    message: response.message,
                    position: 'topRight',
                    progressBar: false,
                    timeout: 1000,
                });

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

$(document).on('click', '#deletePostDetail', function(e) {
    var reviewId = $(this).attr('review-id');
    $.ajax({
        url: baseUrl + "/admin/check-review/delete/detail",
        method: 'POST',
        beforeSend: function(){ $(".cover-spin").show(); },
        data: {
            _token: csrfToken,
            'reviewId': reviewId,
        },
        success: function (data) {
            $("#deletePostModal").modal('hide'); 
            $(".cover-spin").hide();
            $('#all-table').dataTable().api().ajax.reload();

            if(data.status_code == 200) {
                iziToast.success({
                    title: '',
                    message: data.message,
                    position: 'topRight',
                    progressBar: false,
                    timeout: 1000,
                });
            }else {
                iziToast.error({
                    title: '',
                    message: data.message,
                    position: 'topRight',
                    progressBar: false,
                    timeout: 1000,
                });
            }
        }
    });  
});

