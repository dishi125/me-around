
$(function() {
    $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        searching: false,
        "order": [[ 6, "desc" ]],
        ajax: {
            url: allTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken }
        },
        columns: [
            { data: "user_name", orderable: true },
            { data: "activate_name", orderable: true },
            { data: "address", orderable: true },
            { data: "phone_number", orderable: true },
            { data: "reload_amount", orderable: true },
            { data: "current_coin", orderable: true },
            { data: "date", orderable: true },
        ]
    });
});
