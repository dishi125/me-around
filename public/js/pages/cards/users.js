$(function(){
    $("#requested-card-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 0, "desc" ]],
        ajax: {
            url: userCardsTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken }
        },
        columns: [
            { data: "card_name", orderable: true },      
            { data: "user_name", orderable: true },      
            { data: "email_address", orderable: false },      
            { data: "phone", orderable: false },      
            { data: "actions", orderable: false }
        ]
    });
});
