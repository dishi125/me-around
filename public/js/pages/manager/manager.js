function deleteManager(id) {
    $.get(baseUrl + '/admin/manager/delete/' + id, function(data, status) {
        pageModel.html('');
        pageModel.html(data);
        pageModel.modal('show');
    });
}

$(function() {
    var manager = $("#manager-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        order: [[ 4, "desc" ]],
        ajax: {
            url: managerTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken }
        },
        columns: [
            { data: "name", orderable: true, width: "8%" },
            { data: "mobile", orderable: true, width: "8%" },
            { data: "email", orderable: true, width: "20%" },
            { data: "client_count", orderable: true, width: "4%" },
            { data: "active_count", orderable: true, width: "15%" },
            { data: "date", orderable: true, width: "15%" },
            { data: "shoptotal", orderable: true, width: "5%" },
            { data: "supporter_code", orderable: false, width: "5%" },
            { data: "actions", orderable: false, width: "20%" }
        ]
    });
    var submanager = $("#sub-manager-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        order: [[ 4, "desc" ]],
        ajax: {
            url: subManagerTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken }
        },
        columns: [
            { data: "name", orderable: true, width: "8%" },
            { data: "mobile", orderable: true, width: "8%" },
            { data: "email", orderable: true, width: "20%" },
            { data: "client_count", orderable: true, width: "4%" },
            { data: "active_count", orderable: true, width: "15%" },
            { data: "date", orderable: true, width: "15%" },
            { data: "shoptotal", orderable: true, width: "5%" },
            { data: "supporter_code", orderable: false, width: "5%" },
            { data: "actions", orderable: false, width: "25%" }
        ]
    });
});