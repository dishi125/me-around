$(function () {
    $("#card-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 0, "desc" ]],
        ajax: {
            url: cardsTable,
            dataType: "json",
            type: "POST",
            data: {},
        },
        columns: [
            {data: "title", orderable: false},
            {data: "range", orderable: false},
            {data: "background_riv", orderable: false},
            {data: "character_riv", orderable: false},
            {data: "assigned_date", orderable: false},
            {data: "card_level", orderable: false},
            {data: "love_count", orderable: false},
            {data: "actions", orderable: false},
        ],
    });

    $(document).on("click", "#deleteUserCardDetail", function(e) {
        var card_id = $(this).attr("card-id");
        if(card_id){
            $.ajax({
                url: removeCard,
                method: "POST",
                data: {
                    _token: csrfToken,
                    card_id: card_id
                },
                beforeSend: function(){ $(".cover-spin").show(); },
                success: function(data) {
                    $(".cover-spin").hide();
                    $("#deletePostModal").modal("hide");
                    $("#card-table").dataTable().api().ajax.reload();
                    if (data.success) {
                        iziToast.success({
                            title: "",
                            message: data.message,
                            position: "topRight",
                            progressBar: false,
                            timeout: 1000
                        });
                    } else {
                        iziToast.error({
                            title: "",
                            message: data.message,
                            position: "topRight",
                            progressBar: false,
                            timeout: 1000
                        });
                    }
                }
            });
        }
    });

    $(document).on("click", "#saveDetail", function(e) {
        var give_card = $("select[name='give_card']").val();
        if(give_card){
            $.ajax({
                url: giveCard,
                method: "POST",
                data: {
                    _token: csrfToken,
                    give_card: give_card
                },
                beforeSend: function(){ $(".cover-spin").show(); },
                success: function(data) {
                    $(".cover-spin").hide();
                    $("#giveCardModal").modal("hide");
                    $("#card-table").dataTable().api().ajax.reload();
                    if (data.success) {
                        iziToast.success({
                            title: "",
                            message: data.message,
                            position: "topRight",
                            progressBar: false,
                            timeout: 1000
                        });
                    } else {
                        iziToast.error({
                            title: "",
                            message: data.message,
                            position: "topRight",
                            progressBar: false,
                            timeout: 1000
                        });
                    }
                }
            });
        }
    });

    $(document).on("click", "#save-user-exp", function(e) {
        var credits = $("#user-exp").val();

        var error_message = $(".error-msg");
        if (credits == "") {
            $("#user-exp").focus();
            error_message.html("This field is required");
            return false;
        }
        $.ajax({
            url: giveEXPCoin,
            method: "POST",
            data: {
                _token: csrfToken,
                exp: credits
            },
            beforeSend: function(){ $(".cover-spin").show(); },
            success: function(data) {
                $(error_message).html('');
                $("#user-exp").val('');
                $(".cover-spin").hide();
                $("#giveExpModal").modal("hide");
                if (data.success) {
                    iziToast.success({
                        title: "",
                        message: data.message,
                        position: "topRight",
                        progressBar: false,
                        timeout: 1000
                    });
                } else {
                    iziToast.error({
                        title: "",
                        message: data.message,
                        position: "topRight",
                        progressBar: false,
                        timeout: 1000
                    });
                }
            }
        });
    });

});

function giveEXP(){
    $("#giveExpModal").modal("show");
}

function deleteUserCards(id){
    $.get(baseUrl + '/admin/delete/user/card/model/' + id, function (data, status) {
        $("#deletePostModal").html('');
        $("#deletePostModal").html(data);
        $("#deletePostModal").modal('show');
    });
}

function giveCards(id){
    $.get(baseUrl + '/admin/give/user/card/model/' + id, function (data, status) {
        $("#giveCardModal").html('');
        $("#giveCardModal").html(data);
        $("#giveCardModal").modal('show');
    });
}
