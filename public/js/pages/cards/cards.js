$(function() {

    loadTableData('');
    $('body').on('click','.filterButton',function(){
        var filter = $(this).attr('data-tabid');
        $('#all-table').DataTable().destroy();
        loadTableData(filter);
    })

});


function loadTableData(filter){

    var filter = filter || '';

    var allHospital = $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 1, "asc" ]],
        ajax: {
            url: cardsTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken, filter : filter },
            dataSrc: function ( json ) {
                setTimeout(function(){
                    $('[data-toggle="tooltip"]').tooltip();
                }, 2000);
                return json.data;
            }
        },
        columns: [
            { data: "name", orderable: true },
            { data: "range", orderable: true },
            { data: "order", orderable: true },
            { data: "actions", orderable: false }
        ]
    });
}

function deleteCard(id) {
    $.get(baseUrl + '/admin/cards/delete/' + id, function (data, status) {
        $("#deleteModal").html('');
        $("#deleteModal").html(data);
        $("#deleteModal").modal('show');
    });
}



$(document).on('click', '#cardDelete', function(e) {
    var id = $(this).attr('card-id');
    $.ajax({
        url: baseUrl + "/admin/cards/destroy/"+id,
        method: 'DELETE',
        data: {
            '_token': csrfToken,
        },
       success: function (data) {
            $("#deleteModal").modal('hide');
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


