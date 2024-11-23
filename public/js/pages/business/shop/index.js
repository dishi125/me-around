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

    $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 6, "desc" ]],
        ajax: {
            url: allUserTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken, filter : filter }
        },
        columns: [
            { data: "main_name", orderable: true },
            { data: "shop_name", orderable: true },
            { data: "followers", orderable: true },
            { data: "work_complete", orderable: true },
            { data: "portfolio", orderable: true },
            { data: "reviews", orderable: true },
            { data: "date", orderable: true },
            { data: "actions", orderable: false }
        ]
    });
}

$(document).on('click', '#deletePostDetail', function(e) {
    var postid = $(this).attr('post-id');
    if(postid){
        $.ajax({
            url: deletePostUrl,
            method: 'POST',
            beforeSend: function(){ $(".cover-spin").show(); },
            data: {
                _token: csrfToken,
                'postid': postid,
            },
            success: function (data) {
                $("#deletePostModal").modal('hide'); 
                $(".cover-spin").hide();
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
    }
});