function editUserPassword(id) {
    $.get(baseUrl + '/admin/user/profile/change/password/'+id, function (data, status) {
        changePasswordModal.html('');
        changePasswordModal.html(data);
        changePasswordModal.modal('show');
    });
}

function editUserEmail(id){
    $.ajax({
        url: changeEmailUrl,
        method: 'POST',
        data: {
            '_token': csrfToken,
            'user_id': id
        },
        beforeSend: function(){ $(".cover-spin").show(); },
        complete:function(data){ $(".cover-spin").hide(); },
        success: function (data) {            
            if(data['status_code'] == 200) {
                iziToast.success({
                    title: '',
                    message: data['message'],
                    position: 'topRight',
                    progressBar: false,
                    timeout: 1000,
                });
            }else{
                iziToast.error({
                    title: '',
                    message: data['message'],
                    position: 'topRight',
                    progressBar: false,
                    timeout: 1000,
                });
            }
        }
    });
}
$('#userProfileForm').validate({
    rules: {
        'name': {
            required: true
        },
        'mobile': {
            required: true
        },
    },
    highlight: function (input) {
        $(input).parents('.form-line').addClass('error');
    },
    unhighlight: function (input) {
        $(input).parents('.form-line').removeClass('error');
    },
    errorPlacement: function (error, element) {
        $(element).parents('.form-group').append(error);
    },
});