function editCredits(id) {
    $.get(baseUrl + '/admin/business-client/edit/credit/' + id, function (data, status) {
        editModal.html('');
        editModal.html(data);
        editModal.modal('show');
    });
}
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

$(document).on('click', '#save-credits', function(e) {
    var userId = $('#user-id').val();
    var credits = $('#user-credits').val();

    var error_message = $('#credit-error');
    if (credits == '') {
        $("#user-credits").focus();
        error_message.html('This field is required');
        return false;
    }
      $.ajax({
            url: addCredits,
            method: 'POST',
            data: {
                '_token': csrfToken,
                'userId': userId,
                'credits': credits
            },
           success: function (data) {
                editModal.modal('hide');
                $('#all-table').dataTable().api().ajax.reload();
                $('#active-table').dataTable().api().ajax.reload();
                $('#inactive-table').dataTable().api().ajax.reload();
                $('#all-shop-table').dataTable().api().ajax.reload();
                $('#active-shop-table').dataTable().api().ajax.reload();
                $('#inactive-shop-table').dataTable().api().ajax.reload();
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

$(document).on('click', '#save-love-amount', function(e) {
    var userId = $(this).attr('user-id');
    var daily_love_count = $('#user-love-amount').val();

    var error_message = $('#love-amount-error');
    if (daily_love_count == '') {
        $("#user-love-amount").focus();
        error_message.html('This field is required');
        return false;
    }
    $.ajax({
        url: editLoveCountDaily,
        method: 'POST',
        data: {
            '_token': csrfToken,
            'userId': userId,
            'daily_love_count': daily_love_count
        },
        success: function (data) {
            error_message.html('');
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

$(document).on('click', '#delete-business-profile', function(e) {
    var userId = $('#user-id').val();

    $.ajax({
        url: deleteBusinessProfile,
        method: 'POST',
        data: {
            '_token': csrfToken,
            'userId': userId,        },
       success: function (data) {
            editModal.modal('hide');
            $('#all-shop-table').dataTable().api().ajax.reload();
            $('#active-shop-table').dataTable().api().ajax.reload();
            $('#inactive-shop-table').dataTable().api().ajax.reload();
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
$(document).on('click', '#delete-business-user', function(e) {
    var userId = $('#user-id').val();

    $.ajax({
        url: deleteUser,
        method: 'POST',
        data: {
            '_token': csrfToken,
            'userId': userId,        },
       success: function (data) {
            editModal.modal('hide');
            $('#all-shop-table').dataTable().api().ajax.reload();
            $('#active-shop-table').dataTable().api().ajax.reload();
            $('#inactive-shop-table').dataTable().api().ajax.reload();
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

function openCopyLinkPopup(id) {
    $.get(
        baseUrl + "/admin/business-client/view/shop/profile/link/" + id,
        function (data, status) {
            profileLinkModal.html("");
            profileLinkModal.html(data);
            profileLinkModal.modal("show");
        }
    );
}

function showReferralDetail(id){
    $.get(baseUrl + '/admin/show/referral/users/' + id, function (data, status) {
        $('#show-referral').html('');
        $('#show-referral').html(data);
        $('#show-referral').modal('show');
    });
}

function updateCoffeecnt(user_id){
    $.get(baseUrl + '/admin/users/send_coffee/' + user_id, function (data, status) {
        if(data.success == true) {
            $("#processed_coffee").html(`Processed Coffee: ${data.processed_coffee}`);
            $("#coffee_count").html(`0 Coffee`);
            $("#coffee_count").css('pointer-events', 'none');
        }
        showToastMessage(data.message, data.success);
    });
}

function getGifticonModal(id = 0,gifti_id = 0){
    $.get(baseUrl + `/admin/show/gifticon/modal/${id}/${gifti_id}`, function (data, status) {
        $('#show-gifticon').html('');
        $('#show-gifticon').html(data);
        $('#show-gifticon').modal('show');
    });
}


function imagesPreview(input, placeToInsertImagePreview, fieldname, is_multi) {
    var noImage = baseUrl + "/public/img/noImage.png";

    if (input.files) {
        Array.from(input.files).forEach(async (file,index) => {
            console.log(index)
            var currentTimestemp = new Date().getTime()+''+index;
            file.timestemp = currentTimestemp;
            var reader = await new FileReader(file);
            reader.onload = function(event) {
                var bgImage = $($.parseHTML('<div>')).attr('style', 'background-image: url('+event.target.result+')').addClass("bgcoverimage").wrapInner("<img src='"+noImage+"' />");
                var container = jQuery("<div></div>",{class: "removeImage", html:'<span fieldname="'+ fieldname +'" data-timestemp="'+currentTimestemp+'" class="pointer"><i class="fa fa-times-circle fa-2x"></i></span>'});
                container.append(bgImage);
                $(placeToInsertImagePreview).html(container);
            }
            reader.readAsDataURL(file);
            $(input).parent().hide();

        });
    }
};

$(document).on('click','.removeImage > span',function(){
    var timestemp = $(this).attr('data-timestemp');
    var fieldname = $(this).attr('fieldname');
    var imageid = $(this).attr('data-imageid');
    var index = $(this).attr('data-index');

    if(imageid){
        $.ajax({
            url: baseUrl + "/admin/gifticon/image/remove",
            method: 'POST',
            data: {
                _token: csrfToken,
                imageid : imageid,
                // index : index,
            },
            beforeSend: function() {
            },
            success: function(data) {
                // mainImagesFiles.splice( $.inArray(imageid, mainImagesFiles), 1 );
            }
        });
    }
    $("#"+fieldname).val('');
    $(this).parent().parent().parent().next( ".add-image-icon" ).show();
    $(this).parent().remove();
});

$(document).on('submit','#storegifticon',function(e){
    e.preventDefault();
    var ajaxurl = $(this).attr('action');
    var formData = new FormData(this);
    $.ajax({
        method: 'POST',
        contentType: false,
        processData: false,
        data: formData,
        url: ajaxurl,
        success: function(results) {
            $("#gifticon_cnt").html(`Total Gifticon: ${results.gifticons_cnt}`);
            $(".cover-spin").hide();
            showToastMessage(results.message,results.success);
            $("#show-gifticon").modal('hide');
            setTimeout(function(){
                $("#show-referral").modal('hide');
            }, 500);
        },
        beforeSend: function(){ $(".cover-spin").show(); },
        error: function(response) {
            $(".cover-spin").hide();
            showToastMessage("Gifticon is not created successfully.",false);
        }
    });
});

$(document).on('submit','#editgifticon',function(e){
    e.preventDefault();
    var ajaxurl = $(this).attr('action');
    var formData = new FormData(this);
    $.ajax({
        method: 'POST',
        contentType: false,
        processData: false,
        data: formData,
        url: ajaxurl,
        success: function(results) {
            $(".cover-spin").hide();
            showToastMessage(results.message,results.success);
            $("#show-gifticon").modal('hide');
            setTimeout(function(){
                $("#show-referral").modal('hide');
            }, 500);
        },
        beforeSend: function(){ $(".cover-spin").show(); },
        error: function(response) {
            $(".cover-spin").hide();
            showToastMessage("Gifticon is not updated successfully.",false);
        }
    });
});

$('#show-gifticon').on('hidden.bs.modal', function () {
    if($('.modal.show').length){
        $('body').addClass('modal-open');
    }
});

$(document).on('click', '#checkbox-admin-access', function(event) {
    var access;
    if (this.checked) {
        this.checked = true;
        access = 1;
    } else {
        this.checked = false;
        access = 0;
    }

    // console.log(access);
    $.ajax({
        url: editAccess,
        method: 'POST',
        data: {
            '_token': csrfToken,
            user_id : $("#user-id").val(),
            is_admin_access : access,
        },
        beforeSend: function() {
        },
        success: function(res) {
            showToastMessage(res.message, res.success);
        },
        error: function(response) {
            showToastMessage("Failed to update admin access.",false);
        }
    });
});

$(document).on('click', '#checkbox-supporter', function(event) {
    var access;
    if (this.checked) {
        this.checked = true;
        access = 1;
    } else {
        this.checked = false;
        access = 0;
        $('.supporter_option').prop('checked',false);
    }

    // console.log(access);
    $.ajax({
        url: editSupporter,
        method: 'POST',
        data: {
            '_token': csrfToken,
            user_id : $("#user-id").val(),
            is_support_user : access,
        },
        beforeSend: function() {
        },
        success: function(res) {
            showToastMessage(res.message, res.success);
        },
        error: function(response) {
            showToastMessage("Failed to update support access.",false);
        }
    });
});

$(document).on('change', 'input[type=radio][name=supporter_option]', function (event) {
    var supporter_option = $(this).val();

    if (!$("#checkbox-supporter").is(":checked")) {
        $('.supporter_option').prop('checked', false);
    }
    else {
        $.ajax({
            url: editSupporterOption,
            method: 'POST',
            data: {
                '_token': csrfToken,
                'user_id': $("#user-id").val(),
                'supporter_option': supporter_option,
            },
            beforeSend: function () {
            },
            success: function (res) {
                showToastMessage(res.message, res.success);
            },
            error: function (response) {
                showToastMessage("Failed to update supporter!!", false);
            }
        });
    }
});

$(document).on('click', '#checkbox-love-amount', function(event) {
    var access;
    if (this.checked) {
        this.checked = true;
        access = 1;
    } else {
        this.checked = false;
        access = 0;
    }

    // console.log(access);
    $.ajax({
        url: editLoveCountCheckbox,
        method: 'POST',
        data: {
            '_token': csrfToken,
            user_id : $("#save-love-amount").attr('user-id'),
            is_increase_love_count_daily : access,
        },
        beforeSend: function() {
        },
        success: function(res) {
            showToastMessage(res.message, res.success);
            if ($("#Feed-log-table").length > 0){
                $('#Feed-log-table').DataTable().ajax.reload();
            }
        },
        error: function(response) {
            showToastMessage("Failed to update Get Love Amount Daily.",false);
        }
    });
});
