let tableInit;
$(function () {
    loadTableData();

    $('#global-price-setting-table tbody').on('click', 'td.details-control', function () {
        var tr = $(this).closest('tr');
        var row = tableInit.row( tr );

        if ( row.child.isShown() ) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('shown');
        } else {
            // Open this row
            row.child( format(row.data()) ).show();
            tr.addClass('shown');
        }
    });
});

function format ( d ) {
    // `d` is the original data object for the row
    return d.table1;
}

function loadTableData() {
    tableInit = $("#global-price-setting-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        order: [[1, "asc"]],
        ajax: {
            url: SettingTable,
            dataType: "json",
            type: "POST",
            data: {_token: csrfToken},
        },
        columns: [
            {"className": 'details-control', "orderable": false, "data": null, "defaultContent": ''},
            {data: "name", orderable: true},
            {data: "korean_name", orderable: true},
            {data: "actions", orderable: false },
        ],
    });
}

function deleteGlobalPrice(id, type){
    var URL = baseUrl + "/admin/important-setting/global-price-settings/delete/"+id+'/'+type;
    $.get(URL, function (data, id) {
        profileModal.html('');
        profileModal.html(data);
        profileModal.modal('show');
    });
}

$(document).on('click','.destroyForm',function(e){
    e.preventDefault();
    var id = $('.global_price_destroy_form').data('id');
    var type = $('.global_price_destroy_form').data('type');

    $.ajax({
        url: baseUrl + '/admin/important-setting/global-price-settings/destroy/'+id+'/'+type,
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

                $('#global-price-setting-table').DataTable().ajax.reload();
            } else {
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
