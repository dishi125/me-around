$(function () {

    $(document).on("submit","#menuSettingForm",function(e){
        e.preventDefault();
        var ajaxurl = $(this).attr('action');

        $.ajax({
            method: 'POST',
            cache: false,
            data: $(this).serialize(),
            url: ajaxurl,
            success: function(results) {
                $(".cover-spin").hide();
                $('#menu-setting-table').dataTable().api().ajax.reload();
                if(results.success == true) {
                    iziToast.success({
                        title: '',
                        message: results.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });
                }else {
                    iziToast.error({
                        title: '',
                        message: results.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 2000,
                    });
                }
                $("#editMenuModal").modal('hide');

            },
            beforeSend: function(){ $(".cover-spin").show(); },
            error: function(response) {
                $(".cover-spin").hide();
            }
        });
    });

    loadTableData();
    $(document).on("change",'select[name="country"]',function (){
        var filter = $(this).val();
        $('#menu-setting-table').DataTable().destroy();
        loadTableData(filter);
    });

    $(document).on('change','.show-toggle-btn',function(e){
        var dataID = $(this).attr('data-id');
        $.ajax({
            type: "POST",
            dataType: "json",
            url: updateOnOff,
            data: {
                data_id: dataID,
                checked: e.target.checked,
                _token: csrfToken,
            },
            success: function (response) {
                $("#menu-setting-table").dataTable().api().ajax.reload();
            },
        });
    });

    $(document).on('change','.category-toggle-btn',function(e){
        var dataID = $(this).attr('data-id');
        $.ajax({
            type: "POST",
            dataType: "json",
            url: updateCategoryOnOff,
            data: {
                data_id: dataID,
                checked: e.target.checked,
                _token: csrfToken,
            },
            success: function (response) {
                $("#menu-setting-table").dataTable().api().ajax.reload();
            },
        });
    });

    $("#menu-setting-table > tbody").sortable({
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
    $("tr.row1").each(function (index, element) {
        order.push({
            id: $(this).attr("data-id"),
            position: index + 1,
        });
    });

    var country = $('select[name="country"]').val();
    $.ajax({
        type: "POST",
        dataType: "json",
        url: updateOrder,
        data: {
            order: order,
            _token: csrfToken,
            country: country,
        },
        success: function (response) {
            $("#menu-setting-table").dataTable().api().ajax.reload();

        },
    });
}

function EditMenuSetting(id) {
    $.get(baseUrl + '/admin/edit/menu-setting/' + id, function (data, status) {
        $('#editMenuModal').html('');
        $('#editMenuModal').html(data);
        $('#editMenuModal').modal('show');
    });
}

function loadTableData(filter){
    var filter = filter || '';
    $("#menu-setting-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        order: [[ 2, "asc" ]],
        ajax: {
            url: SettingTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken,filter : filter  },
            dataSrc: function ( json ) {
                setTimeout(function() {
                    $('.toggle-btn').bootstrapToggle();
                }, 300);
                return json.data;
            }
        },
        createdRow: function(row, data, dataIndex) {
            $(row).attr('data-id', data.id).addClass('row1');
            $('.toggle-btn').bootstrapToggle();
        },
        columns: [
            { data: "menu_name", orderable: false },
            { data: "is_show", orderable: false },
            { data: "menu_order", orderable: true },
            { data: "action", orderable: false },
        ],
    });
}
