
function giveCoin(id) {
    $.get(baseUrl + '/admin/reload-coin/confirm/popup/' + id, function(data, status) {
        pageModel.html('');
        pageModel.html(data);
        pageModel.modal('show');
    });
}

function rejectCoin(id) {
    $.get(baseUrl + '/admin/reload-coin/reject/popup/' + id, function(data, status) {
        pageModel.html('');
        pageModel.html(data);
        pageModel.modal('show');
    });
}

$(function() {
    var allHospital = $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 9, "desc" ]],
        ajax: {
            url: allTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken }
        },
        columns: [
            { data: "checkbox", orderable: false },
            { data: "activate_name", orderable: true },
            { data: "category_name", orderable: true },
            { data: "coin_amount", orderable: true },
            { data: "total_amount", orderable: true },            
            { data: "order_number", orderable: true },
            { data: "sender_name", orderable: true },
            { data: "phone_number", orderable: true },
            { data: "manager", orderable: true },
            { data: "date", orderable: true },
            { data: "actions", orderable: false }
        ]
    });
    var activeHospital = $("#shop-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 9, "desc" ]],
        ajax: {
            url: shopTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken }
        },
        columns: [
            { data: "checkbox", orderable: false },
            { data: "activate_name", orderable: true },
            { data: "category_name", orderable: true },
            { data: "coin_amount", orderable: true },
            { data: "total_amount", orderable: true },            
            { data: "order_number", orderable: true },
            { data: "sender_name", orderable: true },
            { data: "phone_number", orderable: true },
            { data: "manager", orderable: true },
            { data: "date", orderable: true },
            { data: "actions", orderable: false }
        ]
    });
    var inactiveHospital = $("#hospital-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 9, "desc" ]],
        ajax: {
            url: hospitalTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken }
        },
        columns: [
            { data: "checkbox", orderable: false },
            { data: "activate_name", orderable: true },
            { data: "category_name", orderable: true },
            { data: "coin_amount", orderable: true },
            { data: "total_amount", orderable: true },            
            { data: "order_number", orderable: true },
            { data: "sender_name", orderable: true },
            { data: "phone_number", orderable: true },
            { data: "manager", orderable: true },
            { data: "date", orderable: true },
            { data: "actions", orderable: false }
        ]
    });

    $('#checkbox-all').click(function (event) {
        if (this.checked) {
            $('.check-all-hospital').each(function () {
                this.checked = true;
            });
        } else {
            $('.check-all-hospital').each(function () {
                this.checked = false;
            });
        }
    });
    $('#checkbox-active').click(function (event) {
        if (this.checked) {
            $('.check-active-hospital').each(function () {
                this.checked = true;
            });
        } else {
            $('.check-active-hospital').each(function () {
                this.checked = false;
            });
        }
    });
    $('#checkbox-inactive').click(function (event) {
        if (this.checked) {
            $('.check-inactive-hospital').each(function () {
                this.checked = true;
            });
        } else {
            $('.check-inactive-hospital').each(function () {
                this.checked = false;
            });
        }
    });
});


var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;
 
    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');
 
        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
        }
    }
 };
 
 $(function() {
    var countrySelected = getUrlParameter('countryId');
    if(countrySelected ) {
       $("#country_id").val(countrySelected);
    }
 
 });