
$(function() {
    var filter = $('input[name="type"]').val() || 'all';
    $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 3, "desc" ]],
        ajax: {
            url: allUserTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken, filter : filter }
        },
        columns: [
            { data: "name", orderable: true },
            { data: "email", orderable: true },
            { data: "phone", orderable: false },
            { data: "signup", orderable: true },
            { data: "business_type", orderable: false },            
            { data: "status", orderable: false },            
            { data: "last_access", orderable: true },
            { data: "actions", orderable: false }
        ]
    });


    $(document).on('change','select[name="category_select"]',function(){
        var categoryID  = $(this).val();
        var shop_id  = $(this).attr('shop_id');
        $.ajax({
            url: baseUrl + "/admin/update/user/shop/category",
            method: 'POST',
            data: {
                _token: csrfToken,
                category : categoryID,
                shop_id : shop_id,
            },
            beforeSend: function() {
                $('.cover-spin').show();
            },
            success: function(response) {
                $('.cover-spin').hide(); 
                if(response.success == true){    
                    iziToast.success({
                        title: '',
                        message: "Category Updated successfully.",
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });
    
                }else {
                    iziToast.error({
                        title: '',
                        message: 'Category has not been updated successfully.',
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1500,
                    });
                }
            }
        });

    });

});


function viewShopProfile(id) {
    $.get(baseUrl + '/admin/business-client/view/shop/profile/' + id, function (data, status) {
        profileModal.html('');
        profileModal.html(data);
        profileModal.modal('show');
        $('.selectform').select2({
            width: '150' ,
        });
    });
}

function editPassword(id) {
    $.get(baseUrl + '/admin/users/get/edit/account/' + id, function (data, status) {
        $("#deletePostModal").html('');
        $("#deletePostModal").html(data);
        $("#deletePostModal").modal('show');
    });
}

function deleteUser(id) {
    $.get(baseUrl + '/admin/users/get/account/' + id, function (data, status) {
        $("#deletePostModal").html('');
        $("#deletePostModal").html(data);
        $("#deletePostModal").modal('show');
    });
}

function editCredits(id) {
    $.get(baseUrl + '/admin/business-client/edit/credit/' + id, function (data, status) {
        editModal.html('');
        editModal.html(data);
        editModal.modal('show');
    });
}