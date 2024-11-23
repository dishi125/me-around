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
        "order": [[ 6, "desc" ]],
        ajax: {
            url: allUserTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken, filter : filter }
        },
        columns: [
            { data: "manager_name", orderable: true },
            { data: "email", orderable: true },
            { data: "activity_type", orderable: true },
            { data: "phone", orderable: false },
            { data: "name", orderable: true },
            { data: "activity", orderable: true },
            { data: "date", orderable: true }
        ]
    });
}

function deleteUser(id) {
    $.get(baseUrl + '/admin/users/get/account/' + id, function (data, status) {
        $("#deletePostModal").html('');
        $("#deletePostModal").html(data);
        $("#deletePostModal").modal('show');
    });
}

function editPassword(id) {
    $.get(baseUrl + '/admin/users/get/edit/account/' + id, function (data, status) {
        $("#deletePostModal").html('');
        $("#deletePostModal").html(data);
        $("#deletePostModal").modal('show');
    });
}