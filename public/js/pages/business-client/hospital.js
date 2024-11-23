function viewLogs(id) {
    $.get(
        baseUrl + "/admin/business-client/view/logs/" + id,
        function (data, status) {
            profileModal.html("");
            profileModal.html(data);
            profileModal.modal("show");
        }
    );
}

function viewProfile(id) {
    $.get(
        baseUrl + "/admin/business-client/view/profile/" + id,
        function (data, status) {
            profileModal.html("");
            profileModal.html(data);
            profileModal.modal("show");
        }
    );
}

$(function () {
    var allHospital = $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: allHospitalTable,
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
            { data: "avg_rating", orderable: true },
            { data: "business_license_number", orderable: true },
            { data: "status", orderable: true },
            { data: "referral", orderable: false },
            { data: "shop_profile", orderable: false },
            { data: "credit_purchase_log", orderable: false },
            { data: "actions", orderable: false },
        ],
    });
    var activeHospital = $("#active-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: activeHospitalTable,
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
            { data: "avg_rating", orderable: true },
            { data: "business_license_number", orderable: true },
            { data: "status", orderable: true },
            { data: "referral", orderable: false },
            { data: "shop_profile", orderable: false },
            { data: "credit_purchase_log", orderable: false },
            { data: "actions", orderable: false },
        ],
    });
    var inactiveHospital = $("#inactive-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: inactiveHospitalTable,
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
            { data: "avg_rating", orderable: true },
            { data: "business_license_number", orderable: true },
            { data: "status", orderable: true },
            { data: "referral", orderable: false },
            { data: "shop_profile", orderable: false },
            { data: "credit_purchase_log", orderable: false },
            { data: "actions", orderable: false },
        ],
    });

    $("#pending-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: pendingHospitalTable,
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
            { data: "avg_rating", orderable: true },
            { data: "business_license_number", orderable: true },
            { data: "status", orderable: true },
            { data: "referral", orderable: false },
            { data: "shop_profile", orderable: false },
            { data: "credit_purchase_log", orderable: false },
            { data: "actions", orderable: false },
        ],
    });

    $("#checkbox-all").click(function (event) {
        if (this.checked) {
            $(".check-all-hospital").each(function () {
                this.checked = true;
            });
        } else {
            $(".check-all-hospital").each(function () {
                this.checked = false;
            });
        }
    });
    $("#checkbox-active").click(function (event) {
        if (this.checked) {
            $(".check-active-hospital").each(function () {
                this.checked = true;
            });
        } else {
            $(".check-active-hospital").each(function () {
                this.checked = false;
            });
        }
    });
    $("#checkbox-inactive").click(function (event) {
        if (this.checked) {
            $(".check-inactive-hospital").each(function () {
                this.checked = true;
            });
        } else {
            $(".check-inactive-hospital").each(function () {
                this.checked = false;
            });
        }
    });
});
