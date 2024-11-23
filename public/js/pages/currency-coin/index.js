function deleteCategory(id) {
    $.get(baseUrl + "/admin/currency-coin/currency/delete/" + id, function(
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
            url: currencyList,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken }
        },
        columns: [
            { data: "priority", orderable: true },
            { data: "name", orderable: true },
            { data: "bank_name", orderable: true },
            { data: "bank_account_number", orderable: true },
            { data: "created_at", orderable: true },
            { data: "actions", orderable: false }
        ]
    });
});
