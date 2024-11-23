let tableInit;

$(function () {

    loadTableData();
    $(document).on("change",'select[name="category_id"]',function (){
        var filter = $(this).val();
        $('#category_data').DataTable().destroy();
        loadTableData(filter);
    });

    $("#category_data > tbody").sortable({
        items: "tr",
        cursor: "move",
        opacity: 0.6,
        update: function () {
            sendOrderToServer();
        },
    });

});

function sendOrderToServer() {
    var order = [];
    var info = tableInit.page.info();
    const startIndex = (info.page * 10) + 1;
    $("tr.row1").each(function (index, element) {
        order.push({
            id: $(this).attr("data-id"),
            position: index + startIndex,
        });
    });

    $.ajax({
        type: "POST",
        dataType: "json",
        url: updateOrder,
        data: {
            order: order,
            _token: csrfToken
        },
        success: function (response) {
            $("#category_data").dataTable().api().ajax.reload(null, false);

        },
    });
}

function loadTableData(filter){
    var filter = filter || '';
    tableInit = $('#category_data').DataTable({
        "responsive": true,
        "processing": true,
        "serverSide": true,
        "deferRender": true,
        "order": [[ 2, "asc" ]],
        "ajax": {
            "url": category,
            "dataType": "json",
            "type": "POST",
            "data": { _token: csrfToken, filter }
        },
        createdRow: function(row, data, dataIndex) {
            $(row).attr('data-id', data.id).addClass('row1');
        },
        "columns": [
            { "data": "name", orderable: true },
            { "data": "categoryname", orderable: true },
            { "data": "order", orderable: true },
            { "data": "actions", orderable: false }
        ]
    });
}

function deleteCategory(id) {
    $.get(baseUrl + '/admin/brands/delete/' + id, function(data, status) {
        pageModel.html('');
        pageModel.html(data);
        pageModel.modal('show');
    });
}