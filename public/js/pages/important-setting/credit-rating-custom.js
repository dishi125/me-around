$(function() {
    var allHospital = $("#hospital-setting-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: hospitalSettingTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken}
        },
        columns: [
            { data: "package", orderable: true },
            { data: "type", orderable: true },
            { data: "deducting_rate", orderable: true },
            { data: "regular_payment", orderable: true },
            { data: "post", orderable: true },            
            { data: "km", orderable: true },
            { data: "actions", orderable: false }
        ]
    });
   /*  var activeHospital = $("#shop-setting-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: shopSettingTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken}
        },
        columns: [
            { data: "package", orderable: true },
            { data: "deducting_rate", orderable: true },
            { data: "regular_payment", orderable: true },
            { data: "km", orderable: true },
            { data: "actions", orderable: false }
        ]
    }); */

    $(document).on('click', '#important_setting_notification_shop', function () {
        $.ajax({
            url: sendNotification,
            method: 'POST',
            beforeSend: function(){ $(".cover-spin").show(); },
            complete:function(data){ $(".cover-spin").hide(); },
            data: {
                _token: csrfToken,
                'type' : 'shop'
            },
            success: function (data) {
                if(data['status_code'] == 200) {
                    iziToast.success({
                        title: '',
                        message: data['message'],
                        position: 'topRight',
                        progressBar: false,
                        timeout: 5000,
                    });
                }else{
                    iziToast.error({
                        title: '',
                        message: data['message'],
                        position: 'topRight',
                        progressBar: false,
                        timeout: 5000,
                    });
                }
            }
        });  
    });
   
    $(document).on('click', '#important_setting_notification_hospital', function () {
        $.ajax({
            url: sendNotification,
            method: 'POST',
            beforeSend: function(){ $(".cover-spin").show(); },
            complete:function(data){ $(".cover-spin").hide(); },
            data: {
                _token: csrfToken,
                'type' : 'hospital'
            },
            success: function (data) {
                if(data['status_code'] == 200) {
                    iziToast.success({
                        title: '',
                        message: data['message'],
                        position: 'topRight',
                        progressBar: false,
                        timeout: 5000,
                    });
                }else{
                    iziToast.error({
                        title: '',
                        message: data['message'],
                        position: 'topRight',
                        progressBar: false,
                        timeout: 5000,
                    });
                }
            }
        });  
    });
});