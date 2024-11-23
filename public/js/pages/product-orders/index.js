let tableInit;

$(function () {

    loadTableData();
    $(document).on("change",'select[name="product_id"]',function (){
        var filter = $(this).val();
        $('#category_data').DataTable().destroy();
        loadTableData(filter);
    });
});

function loadTableData(filter){
    var filter = filter || '';
    tableInit = $('#category_data').DataTable({
        "responsive": true,
        "processing": true,
        "serverSide": true,
        "deferRender": true,
        "order": [[ 4, "asc" ]],
        "dom": '<"row"<"col-sm-12 col-md-3"l><"col-sm-12 col-md-3"B><"col-sm-12 col-md-6"f>><t><"row"<"col-sm-12 col-md-5"<i>><"col-sm-12 col-md-7"<p>>>',
        "buttons": [{
            extend: 'excel',
            text: 'Export',
            className: 'exportExcel btn-primary',
            filename: 'product-orders',
            exportOptions: {
                modifier: {
                    page: 'all'
                },
                columns: [ 0, 1, 2, 3, 4 ]
            },
            action: newexportaction
        }], 
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
            { "data": "user_name", orderable: true },
            { "data": "product_name", orderable: true },
            { "data": "price", orderable: true },
            { "data": "phone", orderable: true },
            { "data": "date", orderable: true }
        ]
    });
}


function newexportaction(e, dt, button, config) {
    var self = this;
    var oldStart = dt.settings()[0]._iDisplayStart;
    dt.one('preXhr', function (e, s, data) {
        // Just this once, load all data from the server...
        data.start = 0;
        data.length = 2147483647;
        dt.one('preDraw', function (e, settings) {
            // Call the original action function
            if (button[0].className.indexOf('buttons-excel') >= 0) {
                $.fn.dataTable.ext.buttons.excelHtml5.available(dt, config) ?
                    $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt, button, config) :
                    $.fn.dataTable.ext.buttons.excelFlash.action.call(self, e, dt, button, config);
            } 
            dt.one('preXhr', function (e, s, data) {
                settings._iDisplayStart = oldStart;
                data.start = oldStart;
            });
            setTimeout(dt.ajax.reload, 0);
            return false;
        });
    });
    dt.ajax.reload();
};