$(function () {
    loadTableData('all');

    $('body').on('click', '.filterButton', function (e) {
        e.preventDefault();
        var filter = $(this).attr('data-filter');
        var id = $(this).attr('id');
        if (id=="inactive-data"){
            $("#send_mail_all_yellow").show();
        }
        else {
            $("#send_mail_all_yellow").hide();
        }
        $('#all-table').DataTable().destroy();
        loadTableData(filter);
    })

});

function loadTableData(filter) {
    var filter = filter || 'all';

    $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        order: [[0, "desc"]],
        ajax: {
            url: allTable,
            dataType: "json",
            type: "POST",
            data: {filter: filter}
        },
        columns: [
            {data: "active_name", orderable: true},
            {data: "shop_name", orderable: true},
            {data: "status", orderable: false},
            {data: "instagram", orderable: true},
            {data: "signup_date", orderable: true},
            {data: "email", orderable: true},
            {data: "view_shop", orderable: false},
            {data: "actions", orderable: false},
        ],
    });
}

$(document).on('click', '.sendmail', function (){
    var insta_id = $(this).attr('data-id');
    var thi = $(this);

    $.get(baseUrl + '/admin/instagram-account/status/send-mail/' + insta_id, function (data, status) {
        if(data.success == true){
            $(thi).html(`Send Mail (${data.mail_count})`);
            $(thi).siblings('p').html(`Last sent at: ${data.last_send_mail_at}`);
            showToastMessage("Mail sent successfully.",true);
        }
    });
})

$(document).on('click', '#send_mail_all_yellow', function (){
    var thi = $(this);

    $.get(baseUrl + '/admin/instagram-account/status/send-mail-all', function (data, status) {
        if(data.success == true){
            showToastMessage("Mail sent to all successfully.",true);
            $('#all-table').DataTable().destroy();
            loadTableData('inactive');
        }
    });
})

function disconnectInsta(id,insta_id){
    console.log(id,insta_id);
    var url = disconnectInstaUrl + "/" + id;
    $("#DisconnectInstaModal").find("#disconnectInstaForm").attr('action',url);
    $("#DisconnectInstaModal").find("input[name='insta_id']").val(insta_id);
    $("#DisconnectInstaModal").modal('show');
}
