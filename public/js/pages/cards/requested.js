$(function(){
    $("#requested-card-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 8, "desc" ]],
        ajax: {
            url: requestedCardsTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken }
        },
        columns: [
            { data: "user_name", orderable: true },
            { data: "recipient_name", orderable: false },
            { data: "bank_name", orderable: false },
            { data: "bank_account_number", orderable: false },
            { data: "price", orderable: false },
            { data: "name", orderable: true },
            { data: "range", orderable: true },
            { data: "card_level", orderable: false },
            { data: "actions", orderable: true }
        ]
    });

    $(document).on("submit","#reject_form",function(){
        var card_id = $(this).attr('data-id');
        var reason = $("input[name='reason']").val();
        $.ajax({
            url: baseUrl + "/admin/requested-cards/action/reject/" + card_id,
            method: 'POST',
            data: {reason:reason},
            beforeSend: function(){ $(".cover-spin").show(); },
            success: function (data) {
                $(".cover-spin").hide();
                $("#rejectModal").modal('hide');
                $("#requested-card-table")
                .dataTable()
                .api()
                .ajax.reload();

                if(data.success) {
                    iziToast.success({
                        title: '',
                        message: data.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });
                }else {
                    iziToast.error({
                        title: '',
                        message: data.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });
                }
            }
        });
    });

    $(document).on("submit","#process_form",function(){
        var card_id = $(this).attr('data-id');
        $.ajax({
            url: baseUrl + "/admin/requested-cards/action/processed/" + card_id,
            method: 'POST',
            data: {},
            beforeSend: function(){ $(".cover-spin").show(); },
            success: function (data) {
                $(".cover-spin").hide();
                $("#processedModal").modal('hide');
                $("#requested-card-table")
                .dataTable()
                .api()
                .ajax.reload();

                if(data.success) {
                    iziToast.success({
                        title: '',
                        message: data.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });
                }else {
                    iziToast.error({
                        title: '',
                        message: data.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });
                }
            }
        });
    });
});

function processedCard(card_id){
    $.get(
        baseUrl + "/admin/requested-cards/processed/" + card_id,
        function (data, status) {
            processedModal.html("");
            processedModal.html(data);
            processedModal.modal("show");
        }
    );
}

function rejectCard(card_id){
    $.get(
        baseUrl + "/admin/requested-cards/reject/" + card_id,
        function (data, status) {
            rejectModal.html("");
            rejectModal.html(data);
            rejectModal.modal("show");
        }
    );
}
