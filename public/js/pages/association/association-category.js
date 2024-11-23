$(function() {
    loadTableData(); 
});


function loadTableData(){

    $("#category-data").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 1, "asc" ]],
        ajax: {
            url: baseUrl + "/admin/association/category/data",
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken,'association_id':$('#association_id').val() }
        },
        columns: [
            { data: "name", orderable: true },
            { data: "order", orderable: true },          
            { data: "actions", orderable: false }
        ]
    });
}

function associateCategoryForm(association_id,id) {

    var form1 = baseUrl + "/admin/association/category/form/"+association_id;
    if(typeof id != 'undefined'){
        form1 = baseUrl + "/admin/association/category/form/"+association_id+"/"+id;
    }
    
    $.get( form1, function (data) {        
        $("#myCategoryModel").html('');
        $("#myCategoryModel").html(data);
        $("#myCategoryModel").modal('show');
    });
}

function deleteAssociationCategory(id) {
    var URL = baseUrl + "/admin/association/category/get/delete/"+id;
    $.get( URL, function (data, id) {
        $("#myCategoryModel").html('');
        $("#myCategoryModel").html(data);
        $("#myCategoryModel").modal('show');
    });
}

$(document).on('submit',"#associationCategoryForm",function(event){
    event.preventDefault();
    $('label.error').remove();
    var formData = new FormData(this);
    $.ajax({
        url: baseUrl + "/admin/association/category/save",
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
                $("#myCategoryModel").modal('hide');
                $('#category-data').DataTable().destroy();
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
                    message: 'Category has not been created successfully.',
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
                    $('#'+key).parent().append(errorHtml);
                }); 
            }
        }
    });
});

$(document).on('click','.destroyForm',function(e){
    e.preventDefault();
   
    $.ajax({
        url: baseUrl + '/admin/association/category/destroy/'+$('.destroy_form').data('id'),
        type: 'DELETE',
        data: {
             _token: csrfToken
        },
        success: function(data) {
            $(".cover-spin").hide();
            $("#myCategoryModel").modal('hide');
            $('#category-data').DataTable().destroy();
            loadTableData();

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
        },
        beforeSend: function(){ $(".cover-spin").show(); },  
        error: function(data) {
                //window.location.reload();
            }
        });
});

