$(function() {
    var allHospital = $("#limit-custom-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: limitCustomTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken}
        },
        columns: [
            { data: "field", orderable: true },
            { data: "figure", orderable: true },
            { data: "actions", orderable: false }
        ]
    });  

});