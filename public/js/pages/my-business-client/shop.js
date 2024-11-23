function viewShopProfile(id) {
    $.get(
        baseUrl + "/admin/my-business-client/view/shop/profile/" + id,
        function (data, status) {
            profileModal.html("");
            profileModal.html(data);
            profileModal.modal("show");
        }
    );
}

function viewLogs(id) {
    $.get(
        baseUrl + "/admin/my-business-client/view/logs/" + id,
        function (data, status) {
            profileModal.html("");
            profileModal.html(data);
            profileModal.modal("show");
        }
    );
}

$(function () {
    var allShop = $("#all-shop-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: allShopTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken },
        },
        columns: [
            { data: "checkbox", orderable: false },
            {
                data: "name",
                orderable: true,
                render: function (data, type, row) {
                    return data.split("|").join("<br/>");
                },
            },
            { data: "address", orderable: true },
            { data: "mobile", orderable: true },
            {
                data: "credits",
                orderable: true,
                render: function (data, type, row) {
                    return data.split("|").join("<br/>");
                },
            },
            { data: "join_by", orderable: true },
            { data: "date", orderable: true },
            { data: "avg_rating", orderable: false },
            { data: "business_license_number", orderable: true },
            { data: "status", orderable: true },
            { data: "credit_purchase_log", orderable: true },
            { data: "actions", orderable: false },
        ],
    });
    var activeShop = $("#active-shop-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: activeShopTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken },
        },
        columns: [
            { data: "checkbox", orderable: false },
            {
                data: "name",
                orderable: true,
                render: function (data, type, row) {
                    return data.split("|").join("<br/>");
                },
            },
            { data: "address", orderable: true },
            { data: "mobile", orderable: true },
            {
                data: "credits",
                orderable: true,
                render: function (data, type, row) {
                    return data.split("|").join("<br/>");
                },
            },
            { data: "join_by", orderable: true },
            { data: "date", orderable: true },
            { data: "avg_rating", orderable: false },
            { data: "business_license_number", orderable: true },
            { data: "status", orderable: true },
            { data: "credit_purchase_log", orderable: true },
            { data: "actions", orderable: false },
        ],
    });
    var inactiveShop = $("#inactive-shop-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: inactiveShopTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken },
        },
        columns: [
            { data: "checkbox", orderable: false },
            {
                data: "name",
                orderable: true,
                render: function (data, type, row) {
                    return data.split("|").join("<br/>");
                },
            },
            { data: "address", orderable: true },
            { data: "mobile", orderable: true },
            {
                data: "credits",
                orderable: true,
                render: function (data, type, row) {
                    return data.split("|").join("<br/>");
                },
            },
            { data: "join_by", orderable: true },
            { data: "date", orderable: true },
            { data: "avg_rating", orderable: false },
            { data: "business_license_number", orderable: true },
            { data: "status", orderable: true },
            { data: "credit_purchase_log", orderable: true },
            { data: "actions", orderable: false },
        ],
    });
});

$("#checkbox-all").click(function (event) {
    if (this.checked) {
        $(".check-all-shop").each(function () {
            this.checked = true;
        });
    } else {
        $(".check-all-shop").each(function () {
            this.checked = false;
        });
    }
});
$("#checkbox-active").click(function (event) {
    if (this.checked) {
        $(".check-active-shop").each(function () {
            this.checked = true;
        });
    } else {
        $(".check-active-shop").each(function () {
            this.checked = false;
        });
    }
});
$("#checkbox-inactive").click(function (event) {
    if (this.checked) {
        $(".check-inactive-shop").each(function () {
            this.checked = true;
        });
    } else {
        $(".check-inactive-shop").each(function () {
            this.checked = false;
        });
    }
});
