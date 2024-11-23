
function viewProfile(id) {
    $.get(baseUrl + '/admin/suggest-custom/view/profile/' + id, function (data, status) {
        profileModal.html('');
        profileModal.html(data);
        profileModal.modal('show');
    });
}
function editCredits(id) {
    $.get(baseUrl + '/admin/suggest-custom/edit/credit/' + id, function (data, status) {
        editModal.html('');
        editModal.html(data);
        editModal.modal('show');
    });
}

function viewLogs(id) {
    $.get(baseUrl + '/admin/business-client/view/logs/' + id, function (data, status) {
        profileModal.html('');
        profileModal.html(data);
        profileModal.modal('show');
    });
}

    $(function() {
    var allData = $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: allTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken }
        },
        columns: [
            { data: "checkbox", orderable: false },
            { data: "name", orderable: true },
            { data: "business_name", orderable: true },
            { data: "address", orderable: true },
            { data: "mobile", orderable: true },
            { data: "credits", orderable: true },            
            { data: "join_by", orderable: true },
            { data: "date", orderable: true },
            { data: "status", orderable: true },
            { data: "credit_purchase_log", orderable: true },
            { data: "actions", orderable: false }
        ]
    });
    var activeData = $("#active-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: activeTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken }
        },
        columns: [
            { data: "checkbox", orderable: false },
            { data: "name", orderable: true },
            { data: "business_name", orderable: true },
            { data: "address", orderable: true },
            { data: "mobile", orderable: true },
            { data: "credits", orderable: true },            
            { data: "join_by", orderable: true },
            { data: "date", orderable: true },
            { data: "status", orderable: true },
            { data: "credit_purchase_log", orderable: true },
            { data: "actions", orderable: false }
        ]
    });
    var inactiveData = $("#inactive-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: inactiveTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken }
        },
        columns: [
            { data: "checkbox", orderable: false },
            { data: "name", orderable: true },
            { data: "business_name", orderable: true },
            { data: "address", orderable: true },
            { data: "mobile", orderable: true },
            { data: "credits", orderable: true },            
            { data: "join_by", orderable: true },
            { data: "date", orderable: true },
            { data: "status", orderable: true },
            { data: "credit_purchase_log", orderable: true },
            { data: "actions", orderable: false }
        ]
    });

$('#checkbox-all').click(function (event) {
    if (this.checked) {
        $('.check-all-custom').each(function () {
            this.checked = true;
        });
    } else {
        $('.check-all-custom').each(function () {
            this.checked = false;
        });
    }
});
$('#checkbox-active').click(function (event) {
    if (this.checked) {
        $('.check-active-custom').each(function () {
            this.checked = true;
        });
    } else {
        $('.check-active-custom').each(function () {
            this.checked = false;
        });
    }
});
$('#checkbox-inactive').click(function (event) {
    if (this.checked) {
        $('.check-inactive-custom').each(function () {
            this.checked = true;
        });
    } else {
        $('.check-inactive-custom').each(function () {
            this.checked = false;
        });
    }
});
$(document).on('click', '#save-shop-credits', function(e) {
    var shopId = $('#shop-id').val();
    var shopCredits = $('#shop-credits').val();
    editModal.modal('hide');    
    
});

$(document).on('click', '#delete-business-profile', function(e) {
    var shopId = $('#shop-id').val();
    var shopMemberId = $('#shop-member-id').val();
    editModal.modal('hide');
    
});
$(document).on('click', '#delete-business-user', function(e) {
    var shopId = $('#shop-id').val();
    var shopMemberId = $('#shop-member-id').val();
    editModal.modal('hide');
});

});