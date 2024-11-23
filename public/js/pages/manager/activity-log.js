
$(function() {
    var allActivity = $("#all-activity-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: allActivityTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken }
        },
        columns: [
            { data: "manager_name", orderable: true },
            { data: "activity", orderable: true },
            { data: "ip", orderable: true },
            { data: "date", orderable: true },
        ]
    });
    var deductingRateActivity = $("#deducting-rate-activity-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: deductingRateTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken }
        },
        columns: [
            { data: "manager_name", orderable: true },
            { data: "activity", orderable: true },
            { data: "ip", orderable: true },
            { data: "date", orderable: true },
        ]
    });
    var clientCreditActivity = $("#client-credit-activity-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: clientCreditTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken }
        },
        columns: [
            { data: "manager_name", orderable: true },
            { data: "activity", orderable: true },
            { data: "ip", orderable: true },
            { data: "date", orderable: true },
        ]
    });
    var deleteAccountActivity = $("#delete-account-activity-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: deleteAccountTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken }
        },
        columns: [
            { data: "manager_name", orderable: true },
            { data: "activity", orderable: true },
            { data: "ip", orderable: true },
            { data: "date", orderable: true },
        ]
    });
   
});