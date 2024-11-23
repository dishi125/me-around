function deleteCategory(id) {
    $.get(baseUrl + '/admin/category/delete/' + id, function(data, status) {
        pageModel.html('');
        pageModel.html(data);
        pageModel.modal('show');
    });
}
$(function () {
    var dataTable = $('#category_hospital_data').DataTable({
        "responsive": true,
        "processing": true,
        "serverSide": true,
        "deferRender": true,
        "ajax": {
            "url": shopCategory,
            "dataType": "json",
            "type": "POST",
            "data": { _token: csrfToken }
        },
        "columns": [
            { "data": "name", orderable: true },
            { "data": "koreanname", orderable: true },
            { "data": "image", orderable: true },
            { "data": "category_type", orderable: false },
            { "data": "order", orderable: true },
            { "data": "status", orderable: true },
            { "data": "actions", orderable: false }
        ]
    });
});