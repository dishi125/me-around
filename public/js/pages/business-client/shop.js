
function viewShopProfile(id) {
    $.get(
        baseUrl + "/admin/business-client/view/shop/profile/" + id,
        function (data, status) {
            profileModal.html("");
            profileModal.html(data);
            profileModal.modal("show");
        }
        );
}
function viewShopPriceCategory(shop_id,cat_id) {
    $.get(
        baseUrl + "/admin/business-client/view/price/category/" + shop_id+'/'+cat_id,
        function (data, status) {
            profileModal.html("");
            profileModal.html(data);
            profileModal.modal("show");
        }
        );
}

function viewShopPrice(shop_id,cat_id,price_id) {
    $.get(
        baseUrl + "/admin/business-client/view/price/" + shop_id+'/'+cat_id+'/'+price_id,
        function (data, status) {
            profileModal.html("");
            profileModal.html(data);
            profileModal.modal("show");
        }
        );
}

function deleteShopPrice(id,type) {
    var URL = baseUrl + "/admin/business-client/shop/price/delete/"+id+'/'+type;
    $.get( URL, function (data, id) {
        profileModal.html('');
        profileModal.html(data);
        profileModal.modal('show');
    });
}

function viewLogs(id) {
    $.get(
        baseUrl + "/admin/business-client/view/logs/" + id,
        function (data, status) {
            profileModal.html("");
            profileModal.html(data);
            profileModal.modal("show");
        }
        );
}

$(function () {
    var allShop = $("#all-shop-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: allShopTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken },
        },
        columns: [
        { data: "checkbox", orderable: false },
        {
            data: "name",
            orderable: true,
            render: function (data, type, row) {
                return data.split("|").join("<br/>");
            },
        },
        { data: "address", orderable: true },
        { data: "mobile", orderable: true },
        {
            data: "credits",
            orderable: true,
            render: function (data, type, row) {
                return data.split("|").join("<br/>");
            },
        },
        { data: "join_by", orderable: true },
        { data: "date", orderable: true },
        { data: "avg_rating", orderable: false },
        { data: "business_license_number", orderable: true },
        { data: "status", orderable: true },
        { data: "referral", orderable: false },
        { data: "shop_profile", orderable: false },
        { data: "credit_purchase_log", orderable: false },
        { data: "actions", orderable: false },
        ],
    });
    var activeShop = $("#active-shop-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: activeShopTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken },
        },
        columns: [
        { data: "checkbox", orderable: false },
        {
            data: "name",
            orderable: true,
            render: function (data, type, row) {
                return data.split("|").join("<br/>");
            },
        },
        { data: "address", orderable: true },
        { data: "mobile", orderable: true },
        {
            data: "credits",
            orderable: true,
            render: function (data, type, row) {
                return data.split("|").join("<br/>");
            },
        },
        { data: "join_by", orderable: true },
        { data: "date", orderable: true },
        { data: "avg_rating", orderable: false },
        { data: "business_license_number", orderable: true },
        { data: "status", orderable: true },
        { data: "referral", orderable: false },
        { data: "shop_profile", orderable: false },
        { data: "credit_purchase_log", orderable: false },
        { data: "actions", orderable: false },
        ],
    });
    var inactiveShop = $("#inactive-shop-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: inactiveShopTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken },
        },
        columns: [
        { data: "checkbox", orderable: false },
        {
            data: "name",
            orderable: true,
            render: function (data, type, row) {
                return data.split("|").join("<br/>");
            },
        },
        { data: "address", orderable: true },
        { data: "mobile", orderable: true },
        {
            data: "credits",
            orderable: true,
            render: function (data, type, row) {
                return data.split("|").join("<br/>");
            },
        },
        { data: "join_by", orderable: true },
        { data: "date", orderable: true },
        { data: "avg_rating", orderable: false },
        { data: "business_license_number", orderable: true },
        { data: "status", orderable: true },
        { data: "referral", orderable: false },
        { data: "shop_profile", orderable: false },
        { data: "credit_purchase_log", orderable: false },
        { data: "actions", orderable: false },
        ],
    });

    $("#pending-shop-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: pendingShopTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken },
        },
        columns: [
        { data: "checkbox", orderable: false },
        {
            data: "name",
            orderable: true,
            render: function (data, type, row) {
                return data.split("|").join("<br/>");
            },
        },
        { data: "address", orderable: true },
        { data: "mobile", orderable: true },
        {
            data: "credits",
            orderable: true,
            render: function (data, type, row) {
                return data.split("|").join("<br/>");
            },
        },
        { data: "join_by", orderable: true },
        { data: "date", orderable: true },
        { data: "avg_rating", orderable: false },
        { data: "business_license_number", orderable: true },
        { data: "status", orderable: true },
        { data: "referral", orderable: false },
        { data: "shop_profile", orderable: false },
        { data: "credit_purchase_log", orderable: false },
        { data: "actions", orderable: false },
        ],
    });
});

$("#checkbox-all").click(function (event) {
    if (this.checked) {
        $(".check-all-shop").each(function () {
            this.checked = true;
        });
    } else {
        $(".check-all-shop").each(function () {
            this.checked = false;
        });
    }
});
$("#checkbox-active").click(function (event) {
    if (this.checked) {
        $(".check-active-shop").each(function () {
            this.checked = true;
        });
    } else {
        $(".check-active-shop").each(function () {
            this.checked = false;
        });
    }
});
$("#checkbox-inactive").click(function (event) {
    if (this.checked) {
        $(".check-inactive-shop").each(function () {
            this.checked = true;
        });
    } else {
        $(".check-inactive-shop").each(function () {
            this.checked = false;
        });
    }
});

$(document).on('click', '#saveShopDetail', function(e) {
    $('label.custom-error').remove();
    var selectedOption = $("input[name='chat_option']:checked").val();
    if(selectedOption == "1"){
       var fieldValue = $("input[name='business_link']").val();
       if(!fieldValue || fieldValue == ''){
        $("input[name='chat_option'][value='0']").prop('checked', true).trigger('change');
        //$("input[name='business_link']").after('<label id="error" class="error custom-error" for="name">This field is required</label>')
        ////$("input[name='business_link']").focus();
        //return;
       }
    }
    var form = $('#saveShopDetailForm')[0];
    var formData = new FormData(form);

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="token"]').attr('value')
        }
    });
    $.ajax({
        url: updateShopDetail,
        processData: false,
        contentType: false,
        type: 'POST',
        data: formData,
        success: function (data) {
            if(data.status_code == 200) {
                $('#thumbnail_image_src').attr('src',data.url);
                iziToast.success({
                    title: '',
                    message: data.message,
                    position: 'topRight',
                    progressBar: false,
                    timeout: 1000,
                });
            }else {
                if(data.modal){
                    profileModal.html("");
                    profileModal.html(data.modal);
                    profileModal.modal("show");
                }

                if (data.message) {
                    iziToast.error({
                        title: '',
                        message: data.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });
                }
            }

            if(data.is_reload == true){
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        }
    });
});

$(document).on('change','input[type=radio][name=outside_bussiness]',function(e){
    if (this.value == 'yes') {
        $('div.business_link_block').show();
    }
    else if (this.value == 'no') {
        $('div.business_link_block').hide();
    }
});

$(document).on('change','input[type=radio][name=another]',function(e){
    if (this.value == 'yes') {
        $('div.business_another_mobile').show();
    }
    else if (this.value == 'no') {
        $('div.business_another_mobile').hide();
    }
});

$(document).on('change','.upload_shop_images',function(e){

    var placeType = $(this).attr('shop-type');
    var fileData = new FormData();

    var fileAttr = $('#uploadWorkPlaceImages');
    if(placeType == 'main_profile'){
        fileAttr = $('#uploadMainProfileImages');
    }

    let TotalFiles = fileAttr[0].files.length;
    for (let i = 0; i < TotalFiles; i++) {
        fileData.append('files[]', fileAttr.prop('files')[i]);
    }
    fileData.append('TotalFiles', TotalFiles);
    fileData.append('shop_id', $('#shop_id').val());
    fileData.append('main_name', $('#main_name').val());
    fileData.append('type', placeType);

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="token"]').attr('value')
        }
    });
    $.ajax({
        type: "POST",
        url: uploadImages,
        dataType: "json",
        contentType: "application/json; charset=utf-8",
        contentType: false,
        processData: false,
        data: fileData,
        success: function (result) {
            if(result.status_code == 200) {

                if(placeType == 'main_profile'){
                    $('#main_profile_gallery').append(result.uploadedFilesHtml);
                }else{
                    $('#work_place_gallery').append(result.uploadedFilesHtml);
                }
                iziToast.success({
                    title: '',
                    message: result.message,
                    position: 'topRight',
                    progressBar: false,
                    timeout: 1000,
                });
            }else {
                iziToast.error({
                    title: '',
                    message: result.message,
                    position: 'topRight',
                    progressBar: false,
                    timeout: 1000,
                });
            }
        }
    });
});

$(document).on('click','.deleteImages',function(e){

    var id = $(this).attr('id');
    var placeType = $(this).attr('type');

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="token"]').attr('value')
        }
    });
    $.ajax({
        method: "POST",
        url: deleteImages,
        data: {'id' : id,'type' : placeType},
        success: function (result) {
            if(result.status_code == 200) {
                $('div#image_'+id).remove();
                iziToast.success({
                    title: '',
                    message: result.message,
                    position: 'topRight',
                    progressBar: false,
                    timeout: 1000,
                });
            }else {
                iziToast.error({
                    title: '',
                    message: result.message,
                    position: 'topRight',
                    progressBar: false,
                    timeout: 1000,
                });
            }
        }
    });
});

$(document).on('click','.destroyForm',function(e){
    e.preventDefault();
    var id = $('.bussiness_client_destroy_form').data('id');
    var type = $('.bussiness_client_destroy_form').data('type');

    $.ajax({
        url: baseUrl + '/admin/business-client/shop/price/destroy/'+id+'/'+type,
        type: 'DELETE',
        data: {
           _token: csrfToken
       },
       success: function(data) {

        $(".cover-spin").hide();
        profileModal.modal('hide');

        if(data.response == true) {
            iziToast.success({
                title: '',
                message: data.message,
                position: 'topRight',
                progressBar: false,
                timeout: 1000,
            });

            if(type == 'shop_price'){
                $('li#shop_price_'+id).remove();
            }

            if(type == 'shop_category'){
                $('li#list_'+id).remove();
            }

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


