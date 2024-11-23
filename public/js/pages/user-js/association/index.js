$(function() {
    loadTableData();
});


function loadTableData(country = 0){

    $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 0, "desc" ]],
        ajax: {
            url: associationTableData,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken, header:header }
        },
        columns: [
            { data: "association_name", orderable: true },         
            { data: "actions", orderable: false }
        ]
    });
}
