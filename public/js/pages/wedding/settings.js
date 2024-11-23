$(function() {
    loadTableData('video_file');

    $('body').on('click','.filterButton',function(){
        var filter = $(this).attr('data-filter');
        $('#setting-table').DataTable().destroy();
        loadTableData(filter);
    })
});

function deleteWeddingConfirmation(wedding_id){
    $.get(baseUrl + '/admin/wedding/settings/get/delete/' + wedding_id, function (data, status) {
        $("#deleteWeddingModal").html('');
        $("#deleteWeddingModal").html(data);
        $("#deleteWeddingModal").modal('show');
    });
}

function deleteWedding(wedding_id){
    if(wedding_id){
        $.ajax({
            url: baseUrl + "/admin/wedding/settings/delete",
            method: 'POST',
            data: {
                _token: csrfToken,
                wedding_id : wedding_id,
            },
            beforeSend: function(){ $(".cover-spin").show(); },
            success: function(response) {
                $(".cover-spin").hide();
                $("#deleteWeddingModal").modal('hide');
                if(response.success == true){
                    iziToast.success({
                        title: '',
                        message: response.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });
                    $('#setting-table').DataTable().ajax.reload();
    
    
                }else {
                    iziToast.error({
                        title: '',
                        message: response.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1500,
                    });
                }
            }
        });
    }
}

function loadTableData(filter){

    var filter = filter || 'video_file';

    $("#setting-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 0, "asc" ]],
        ajax: {
            url: getJson,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken, filter: filter }
        },
        columns: [
            { data: "type", orderable: true },
            { data: "file", orderable: false },
            { data: "date", orderable: true },
            { data: "actions", orderable: false },
        ]
    });
}