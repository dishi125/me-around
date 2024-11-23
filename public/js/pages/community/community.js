$(function() {

    loadTableData('all');
    $('body').on('click','.filterButton',function(){
        var filter = $(this).attr('data-filter');
        $('#all-table').DataTable().destroy();
        loadTableData(filter);
    })
    
});


function loadTableData(filter){

    var filter = filter || 'all';

    var allHospital = $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 3, "desc" ]],
        ajax: {
            url: communityTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken, filter : filter }
        },
        columns: [
            { data: "name", orderable: true },
            { data: "email", orderable: true },
            { data: "mobile", orderable: true },
            { data: "updated_date", orderable: true },
            { data: "post_title", orderable: true },            
            { data: "actions", orderable: false }
        ]
    });
}

function deleteCommunity(id) {
    $.get(baseUrl + '/admin/community/delete/' + id, function (data, status) {
        $("#deleteModal").html('');
        $("#deleteModal").html(data);
        $("#deleteModal").modal('show');
    });
}

function deleteUser(id) {
    $.get(baseUrl + '/admin/community/delete/user/' + id, function (data, status) {
        $("#deleteModal").html('');
        $("#deleteModal").html(data);
        $("#deleteModal").modal('show');
    });
}

$(document).on('click', '#communityDelete', function(e) {
    var id = $(this).attr('comm-id');
    $.ajax({
        url: baseUrl + "/admin/community/delete",
        method: 'POST',
        data: {
            '_token': csrfToken,
            'id': id,
        },
       success: function (data) {
            deleteModal.modal('hide'); 
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

$(document).on('click', '#userDelete', function(e) {
    var userId = $(this).attr('user-id');
    $.ajax({
        url: baseUrl + "/admin/community/delete/user",
        method: 'POST',
        data: {
            '_token': csrfToken,
            'userId': userId,
        },
       success: function (data) {
            deleteModal.modal('hide'); 
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
