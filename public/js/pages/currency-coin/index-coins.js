function deleteCategory(id) {
    $.get(baseUrl + "/admin/currency-coin/delete/" + id, function(
        data,
        status
    ) {
        pageModel.html("");
        pageModel.html(data);
        pageModel.modal("show");
    });
}
$(function() {
    var dataTable = $("#currency_data").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: currencyCategory,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken }
        },
        columns: [
            { data: "currency_name", orderable: true },
            { data: "coins", orderable: true },
            { data: "created_at", orderable: true },
            { data: "actions", orderable: false }
        ]
    });
});
