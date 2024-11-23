let tableInit;

$(function () {

    loadTableData();
    
});

function loadTableData(filter){
    var filter = filter || '';
    tableInit = $('#table_data').DataTable({
        "responsive": true,
        "processing": true,
        "serverSide": true,
        "deferRender": true,
        "order": [[ 0, "asc" ]],
        "ajax": {
            "url": category,
            "dataType": "json",
            "type": "POST",
            "data": { _token: csrfToken }
        },
        "columns": [
            { "data": "name", orderable: true },
            { "data": "actions", orderable: false }
        ]
    });
}

function deleteTests(id) {
    $.get(baseUrl + '/admin/certification-exam/tests/delete/' + id, function(data, status) {
        pageModel.html('');
        pageModel.html(data);
        pageModel.modal('show');
    });
}