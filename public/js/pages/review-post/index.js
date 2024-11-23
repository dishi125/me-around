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
        "order": [[ 4, "desc" ]],
        ajax: {
            url: reviewTableData,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken, filter : filter }
        },
        columns: [
            { data: "user_name", orderable: true },
            { data: "phone", orderable: false },
            { data: "business_name", orderable: true },
            { data: "business_phone", orderable: false },            
            { data: "updated_at", orderable: true },
            { data: "images", orderable: false },
            { data: "actions", orderable: false }
        ]
    });
}

function deletePost(URL) {
    $.get( URL, function (data, status) {
        $("#deletePostModal").html('');
        $("#deletePostModal").html(data);
        $("#deletePostModal").modal('show');
    });
}

$(document).on('click', '#deletePostDetail', function(e) {
    var reviewId = $(this).attr('review-id');
    $.ajax({
        url: baseUrl + "/admin/check-review/delete/detail",
        method: 'POST',
        beforeSend: function(){ $(".cover-spin").show(); },
        data: {
            _token: csrfToken,
            'reviewId': reviewId,
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
});


function showImage(imageSrc){
    // Get the modal
    if(imageSrc){
        var modalImg = document.getElementById("modelImageEle");
        modalImg.src = imageSrc;
        $("#PostPhotoModal").modal('show');

    }
}