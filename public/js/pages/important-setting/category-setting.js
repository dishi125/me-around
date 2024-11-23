let tableInit;
$(function () {

    loadTableData($('select[name="country"]').val());
    load_big_category($('select[name="country"]').val());

    /* $(document).on("change", 'select[name="country"]', function () {
        var filter = $(this).val();
        $('#category-setting-table').DataTable().destroy();
        loadTableData(filter);
        load_big_category(filter);
    }); */

    $(document).on('change', '.toggle-btn', function (e) {
        var dataID = $(this).attr('data-id');
        const country = $('select[name="country"]').val();
        $.ajax({
            type: "POST",
            dataType: "json",
            url: updateOnOff,
            data: {
                data_id: dataID,
                checked: e.target.checked,
                country:country,
                _token: csrfToken,
            },
            success: function (response) {
                $("#category-setting-table").dataTable().api().ajax.reload(null, false);
            },
        });
    });

    $(document).on('change', '.toggle-hidden-button', function (e) {
        var dataID = $(this).attr('data-id');
        const country = $('select[name="country"]').val();
        $.ajax({
            type: "POST",
            dataType: "json",
            url: updateOnOffHidden,
            data: {
                data_id: dataID,
                checked: e.target.checked,
                country:country,
                _token: csrfToken,
            },
            success: function (response) {
                $("#category-setting-table").dataTable().api().ajax.reload(null, false);
            },
        });
    });

    $("#category-setting-table > tbody").sortable({
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
            $("#category-setting-table").dataTable().api().ajax.reload(null, false);

        },
    });
}


function loadTableData(filter) {
    var filter = filter || '';
    tableInit = $("#category-setting-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        order: [[3, "asc"]],
        ajax: {
            url: SettingTable,
            dataType: "json",
            type: "POST",
            data: {_token: csrfToken, filter: filter},
            dataSrc: function (json) {
                setTimeout(function () {
                    $('.toggle-btn-display').bootstrapToggle();
                }, 300);
                return json.data;
            }
        },
        createdRow: function (row, data, dataIndex) {
            $(row).attr('data-id', data.id).addClass('row1');
            $('.toggle-btn-display').bootstrapToggle();
        },
        columns: [
            {data: "category_name", orderable: true, width:"7%"},
            {data: "is_show", orderable: false, width:"5%"},
            {data: "image", orderable: false, width:"5%"},
            {data: "menu_order", orderable: true, width:"3%"},
            {data: "is_hidden", orderable: false, width:"5%"},
            {data: "actions", orderable: false , width:"75%"},
        ],
    });
}

function load_big_category(filter){
    $.ajax({
        data: {"filter": filter},
        type: 'POST',
        url: getBigCategory,
        dataType: "json",
        beforeSend: function() {
        },
        success:function(response) {
            if (response.success == true){
                $("#sortable_big_cats").html(response.data);
            }
        },
        error:function (response, status) {
        }
    });
}

$("#sortable_big_cats").sortable({
    // revert: true,
    cursor: true,
    axis: 'x',
    update: function (event, ui) {
        var data = $(this).sortable('serialize');
        var country = $('select[name="country"]').val();
        // POST to server using $.post or $.ajax
        $.ajax({
            data: {"country": country, "orders": data},
            type: 'POST',
            url: updateOrderBigCategory,
            beforeSend: function() {
                $('.cover-spin').show();
            },
            success:function(response) {
                $('.cover-spin').hide();
            },
            error:function (response, status) {
                $('.cover-spin').hide();
            }
        });
    }
});
