
$(function() {
    var allHospital = $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 7, "desc" ]],
        ajax: {
            url: communityIndex,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken}
        },
        columns: [
            { data: "checkbox", orderable: false },
            { data: "go_actions", orderable: false },
            { data: "delete_actions", orderable: false },
            { data: "report_item_name", orderable: false },
            { data: "mobile", orderable: false },
            { data: "report_item_category", orderable: false },
            { data: "category_name", orderable: false },
            { data: "status", orderable: true },
            { data: "date", orderable: true },
            { data: "actions", orderable: false },
        ]
    });

    $('#select_report_category').on('change', function(){
        var filter_value = $(this).val();
        var new_url = communityIndex + '/'+ filter_value;
        allHospital.ajax.url(new_url).load();
      });
  
  });
  $('#checkbox-all').click(function (event) {
    if (this.checked) {
        $('.check-community').each(function () {
            this.checked = true;
        });
    } else {
        $('.check-community').each(function () {
            this.checked = false;
        });
    }
  });
  

function deletePost(id) {
    $("#deletePostModel").modal('show');
}
function deleteAllPost(id) {
    $("#deleteAllPostModel").modal('show');
}
function deleteUser(id) {
    $("#deleteUserModel").modal('show');
}


$(document).on('click', '#delete-post', function () {
    $("#deletePostModel").modal('hide');  
    iziToast.success({
        title: '',
        message: 'Post Deleted',
        position: 'topRight',
        progressBar: false,
        timeout: 5000,
    });
  
});
  
$(document).on('click', '#delete-all-post', function () {    
    $("#deleteAllPostModel").modal('hide');
    iziToast.success({
        title: '',
        message: 'All Post Deleted',
        position: 'topRight',
        progressBar: false,
        timeout: 5000,
    });
});
$(document).on('click', '#delete-user', function () {    
    $("#deleteUserModel").modal('hide');
    iziToast.success({
        title: '',
        message: 'User Deleted',
        position: 'topRight',
        progressBar: false,
        timeout: 5000,
    });
});
  