function editCredits(id) {
    $.get(baseUrl + "/admin/my-business-client/edit/credit/" + id, function(
        data,
        status
    ) {
        editModal.html("");
        editModal.html(data);
        editModal.modal("show");
    });
}

$(document).on("click", "#save-credits", function(e) {
    var userId = $("#user-id").val();
    var credits = $("#user-credits").val();

    var error_message = $(".error-msg");
    if (credits == "") {
        $("#user-credits").focus();
        error_message.html("This field is required");
        return false;
    }
    $.ajax({
        url: addCredits,
        method: "POST",
        data: {
            _token: csrfToken,
            userId: userId,
            credits: credits
        },
        success: function(data) {
            editModal.modal("hide");
            $("#all-table")
                .dataTable()
                .api()
                .ajax.reload();
            $("#active-table")
                .dataTable()
                .api()
                .ajax.reload();
            $("#inactive-table")
                .dataTable()
                .api()
                .ajax.reload();
            $("#all-shop-table")
                .dataTable()
                .api()
                .ajax.reload();
            $("#active-shop-table")
                .dataTable()
                .api()
                .ajax.reload();
            $("#inactive-shop-table")
                .dataTable()
                .api()
                .ajax.reload();
            if (data.status_code == 200) {
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

$(document).on("click", "#delete-business-profile", function(e) {
    var userId = $("#user-id").val();

    $.ajax({
        url: deleteBusinessProfile,
        method: "POST",
        data: {
            _token: csrfToken,
            userId: userId
        },
        success: function(data) {
            editModal.modal("hide");
            $("#all-shop-table")
                .dataTable()
                .api()
                .ajax.reload();
            $("#active-shop-table")
                .dataTable()
                .api()
                .ajax.reload();
            $("#inactive-shop-table")
                .dataTable()
                .api()
                .ajax.reload();
            if (data.status_code == 200) {
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
$(document).on("click", "#delete-business-user", function(e) {
    var userId = $("#user-id").val();

    $.ajax({
        url: deleteUser,
        method: "POST",
        data: {
            _token: csrfToken,
            userId: userId
        },
        success: function(data) {
            editModal.modal("hide");
            $("#all-shop-table")
                .dataTable()
                .api()
                .ajax.reload();
            $("#active-shop-table")
                .dataTable()
                .api()
                .ajax.reload();
            $("#inactive-shop-table")
                .dataTable()
                .api()
                .ajax.reload();
            if (data.status_code == 200) {
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
