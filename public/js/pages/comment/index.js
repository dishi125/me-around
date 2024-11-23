$(function() {
    var allShop = $("#all-comment-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: allCommentTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken }
        },
        columns: [
            { data: "name", orderable: true },
            { data: "email", orderable: true },
            { data: "mobile", orderable: false },
            { data: "update_date", orderable: true },            
            { data: "post_type", orderable: true },
            { data: "post_title", orderable: true },
            { data: "actions", orderable: false }
        ]
    });
});


function deleteUser(id) {
    $.get(baseUrl + '/admin/comment/get/account/' + id, function (data, status) {
        $("#deletePostModal").html('');
        $("#deletePostModal").html(data);
        $("#deletePostModal").modal('show');
    });
}


function deleteComment(id, table) {
    
    $.get(baseUrl + '/admin/comment/get/comment/' + id + '/' + table, function (data, status) {
        $("#deletePostModal").html('');
        $("#deletePostModal").html(data);
        $("#deletePostModal").modal('show');
    });
}