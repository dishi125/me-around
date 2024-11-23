$(function () {

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
            data: {}
        },
        columns: [
            {data: "name", orderable: true},
            {data: "gender", orderable: false},
            {data: "location", orderable: false},
            {data: "love_count", orderable: false},
            {data: "first_access", orderable: true},
            {data: "last_access", orderable: false},
            {data: "actions", orderable: false},
        ],
    });

});

function viewLocations(user_id){
    $.get(baseUrl + '/admin/show/locations/user/' + user_id, function (data, status) {
        $('#show-locations').html('');
        $('#show-locations').html(data);
        $('#show-locations').modal('show');
    });
}
