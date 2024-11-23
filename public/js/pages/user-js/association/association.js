$(function() {
    loadTableData('manager'); 
    $('body').on('click','.filterButton',function(){
        var filter = $(this).attr('data-filter');
        $('#member-data').DataTable().destroy();
        loadTableData(filter);
        console.log(filter)
    })
});

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

function loadTableData(filter){

    

    var filter = filter || 'manager';
    
    /* $("#member-data").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 0, "asc" ]],
        ajax: {
            url: associationTableData,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken,'association_id':$('#association_id').val(),  filter : filter }
        },
        columns: [
            { data: "name", orderable: true },
            { data: "activate_name", orderable: false },          
            { data: "address", orderable: false },
            { data: "phone", orderable: false },
            { data: "date", orderable: false }
        ]
    }); */

    $("#member-data").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: associationTableData,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken,'association_id':$('#association_id').val(),  filter : filter }
        },
        //lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        //dom: 'lfBrtip',
        "dom": '<"row"<"col-sm-12 col-md-3"l><"col-sm-12 col-md-3"B><"col-sm-12 col-md-6"f>><t><"row"<"col-sm-12 col-md-5"<i>><"col-sm-12 col-md-7"<p>>>',
        buttons: [
        {
            extend: 'excel',
            text: 'Export',
            className: 'exportExcel btn-primary',
            filename: association_name + ' ' + filter,
            exportOptions: {
                modifier: {
                    page: 'all'
                },
                columns: [ 0, 1, 2, 3, 4 ]
            },
            action: newexportaction
        }], 
        columns: [
            { data: "name", orderable: true },
            { data: "activate_name", orderable: false },          
            { data: "address", orderable: false },
            { data: "phone", orderable: false },
            { data: "date", orderable: false }
        ]
    });

}
