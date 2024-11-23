var filter;
var is_referral = false;
$(function () {
    loadTableData('all','all');
    $('body').on('click', '.filterButton', function () {
        filter = $(this).attr('data-filter');

        if(filter == 'referred-user'){
            is_referral = true;
        }
        if(filter != 'referred-user' && is_referral == true){
            $('.unread_referral_count').hide();
        }

        $('#all-table').DataTable().destroy();

        let category = $('select[name="category-filter"]').val();
        loadTableData(filter,category);
    })

    $(document).on('change', 'select[name="category-filter"]', function () {
        var categoryID = $(this).val();
        $('#all-table').DataTable().destroy();
        loadTableData(filter,categoryID);
    });

    $(document).on('change', 'select[name="category_select"]', function () {
        var categoryID = $(this).val();
        var shop_id = $(this).attr('shop_id');
        $.ajax({
            url: baseUrl + "/admin/update/user/shop/category",
            method: 'POST',
            data: {
                _token: csrfToken,
                category: categoryID,
                shop_id: shop_id,
            },
            beforeSend: function () {
                $('.cover-spin').show();
            },
            success: function (response) {
                $('.cover-spin').hide();
                if (response.success == true) {
                    iziToast.success({
                        title: '',
                        message: "Category Updated successfully.",
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });

                } else {
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

    $(document).on('click', '.save_supporter_details', function(e) {
            $shop_id = $(this).attr('shop_id');
            e.preventDefault();
            var actionurl = baseUrl + '/admin/instagram-service/update/shop/' + $shop_id;
            $.ajax({
                url: actionurl,
                method: 'POST',
                data: $('#instagram_service').serialize(),
                beforeSend: function() {
                    $('.cover-spin').show();
                },
                success: function(data) {
                    $('.cover-spin').hide();
                    if (data.status_code == 200) {
                        shopPostModel.modal('hide');

                        iziToast.success({
                            title: '',
                            message: data.message,
                            position: 'topRight',
                            progressBar: false,
                            timeout: 1000,
                        });
                        //$('#all-table').dataTable().api().ajax.reload();

                        $('#all-table').DataTable().destroy();
                        let category = $('select[name="category-filter"]').val();
                        loadTableData(filter,category);

                    }
                }
            });

        });

    $(document).on('click','#btn_save_signup_code', function (){
        var user_id = $(this).attr('user-id');
        var signup_code = $(this).siblings('#signup_code').val();
        if (signup_code==""){
            showToastMessage("Please enter code.", false);
        }
        else {
            $.ajax({
                url: saveSignupCode,
                method: 'POST',
                data: {
                    'user_id': user_id,
                    'signup_code': signup_code,
                },
                beforeSend: function () {
                    $('.cover-spin').show();
                },
                success: function (data) {
                    $('.cover-spin').hide();
                    showToastMessage(data.message, data.success);
                    if (data.success == true) {
                        $('#all-table').DataTable().destroy();
                        let category = $('select[name="category-filter"]').val();
                        loadTableData(filter, category);
                    }
                }
            });
        }
    })
});

/*$(document).on('click', '.filterButton', function() {
    var filter = $(this).attr('data-filter');
    $('#like-order-table').DataTable().destroy();
    loadTableData(filter);
});*/

function loadTableData(filter,category) {
    var filter = filter || 'all';
    var category = category || 'all';
    var hide_other;
    if ($("#checkbox-hide-other").is(":checked")) {
        hide_other = 1;
    }
    else {
        hide_other = 0;
    }

    var allHospital = $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "lengthMenu": [25, 50, 100, 200, 500],
        "pageLength": 100,
        "order": [
            [6, "desc"]
        ],
        ajax: {
            url: allUserTable,
            dataType: "json",
            type: "POST",
            data: {
                _token: csrfToken,
                filter: filter,
                category: category,
                hide_other: hide_other,
            }
        },
        columns: [{
                data: "name",
                orderable: true
            },
            {
                data: "business_profile",
                orderable: false
            },
            {
                data: "email",
                orderable: true
            },
            {
                data: "phone",
                orderable: false
            },
            {
                data: "location",
                orderable: false
            },
            {
                data: "service",
                orderable: false
            },
            {
                data: "signup",
                orderable: true
            },
            {
                data: "business_type",
                orderable: false
            },
            {
                data: "portfolio_count",
                orderable: false
            },
            {
                data: "status",
                orderable: false
            },
            {
                data: "last_access",
                orderable: true
            },
            {
                data: "love_count",
                orderable: true
            },
            {
                data: "level",
                orderable: true
            },
            {
                data: "referral",
                orderable: false
            },
            {
                data: "actions",
                orderable: false
            }
        ]
    });
}

function deleteUser(id) {
    $.get(baseUrl + '/admin/users/get/account/' + id, function (data, status) {
        $("#deletePostModal").html('');
        $("#deletePostModal").html(data);
        $("#deletePostModal").modal('show');
    });
}

function editPassword(id) {
    $.get(baseUrl + '/admin/users/get/edit/account/' + id, function (data, status) {
        $("#deletePostModal").html('');
        $("#deletePostModal").html(data);
        $("#deletePostModal").modal('show');
    });
}

function viewUserToShop(id) {
    $.get(baseUrl + '/admin/view/user/to/shop/' + id, function (data, status) {
        $("#deletePostModal").html('');
        $("#deletePostModal").html(data);
        $("#deletePostModal").modal('show');
    });
}

function viewLocations(user_id){
    $.get(baseUrl + '/admin/show/locations/user/' + user_id, function (data, status) {
        $('#show-locations').html('');
        $('#show-locations').html(data);
        $('#show-locations').modal('show');
    });
}

$(document).on('click', '.copy_code', function (){
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val($(this).text()).select();
    $temp.focus();
    document.execCommand("copy");
    $temp.remove();
    // alert("Phone number is copied.");
    showToastMessage("Referral code is copied.",true);
})

function editUsername(url){
    $.get(url, function (data, status) {
        $("#editUsernameModal").html('');
        $("#editUsernameModal").html(data);
        $("#editUsernameModal").modal('show');
    });
}

$(document).on("submit","#edituserForm",function(e){
    e.preventDefault();
    var ajaxurl = $(this).attr('action');

    $.ajax({
        method: 'POST',
        cache: false,
        data: $(this).serialize(),
        url: ajaxurl,
        success: function(results) {
            $(".cover-spin").hide();
            if(results.success == true) {
                iziToast.success({
                    title: '',
                    message: results.message,
                    position: 'topRight',
                    progressBar: false,
                    timeout: 1000,
                });
                $('#all-table').DataTable().destroy();
                loadTableData('all');
            }else {
                iziToast.error({
                    title: '',
                    message: results.message,
                    position: 'topRight',
                    progressBar: false,
                    timeout: 2000,
                });
            }
            $("#editUsernameModal").modal('hide');

        },
        beforeSend: function(){ $(".cover-spin").show(); },
        error: function(response) {
            $(".cover-spin").hide();
            if( response.responseJSON.success == false ) {
                var error = response.responseJSON.message;
                showToastMessage(error,false);
            }
        }
    });
});

function editPhone(url){
    $.get(url, function (data, status) {
        $("#editPhoneModal").html('');
        $("#editPhoneModal").html(data);
        $("#editPhoneModal").modal('show');
    });
}

function addShopProfile(user_id){
    $.ajax({
        url: baseUrl + "/admin/shop/save",
        type:"POST",
        // contentType: false,
        // processData: false,
        data: {'user_id': user_id},
        beforeSend: function() {
            $('.cover-spin').show();
        },
        success:function(response) {
            $('.cover-spin').hide();
            if(response.success == true){
                $("#deletePostModal").modal('hide');
                $('#all-table').DataTable().destroy();
                let category = $('select[name="category-filter"]').val();
                loadTableData(filter,category);

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
                    message: 'Shop profile has not been created successfully.',
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
                iziToast.error({
                    title: '',
                    message: 'Shop profile has not been created successfully.',
                    position: 'topRight',
                    progressBar: false,
                    timeout: 1500,
                });
            }
        }
    });
}

$(document).on('click', '#checkbox-hide-other', function(event) {
    if (this.checked) {
        this.checked = true;
    } else {
        this.checked = false;
    }

    $('#all-table').DataTable().destroy();
    let category = $('select[name="category-filter"]').val();
    loadTableData(filter,category);
});
