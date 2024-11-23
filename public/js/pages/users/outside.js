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
            {data: "email", orderable: false},
            {data: "phone", orderable: false},
            {data: "signup", orderable: false},
            {data: "actions", orderable: false},
        ],
    });

});
