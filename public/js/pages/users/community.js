$(function () {
    $('body').on('change','input[name="outside-user"]',function (){
        let ajaxurl = $(this).attr('ajaxurl');
        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: {is_outside : $(this).is(":checked")},
            beforeSend: function () {
                $('.cover-spin').show();
            },
            success: function (response) {
                $('.cover-spin').hide();
                showToastMessage(response.message,response.success);
            },
            error: function (response, status) {
                $('.cover-spin').hide();
                showToastMessage('Error in Outside user',false);
            }
        });

    });

    $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        order: [[7, "desc"]],
        ajax: {
            url: allUserCommunityTable,
            dataType: "json",
            type: "POST",
            data: {},
            dataSrc: function (json) {
                setTimeout(function () {
                    $(".gallery .gallery-item").each(function () {
                        var me = $(this);

                        me.attr("href", me.data("image"));
                        me.attr("title", me.data("title"));
                        if (me.parent().hasClass("gallery-fw")) {
                            me.css({
                                height: me.parent().data("item-height"),
                            });
                            me.find("div").css({
                                lineHeight: me.parent().data("item-height") + "px",
                            });
                        }
                        me.css({
                            backgroundImage: 'url("' + me.data("image") + '")',
                        });
                    });
                    if (jQuery().Chocolat) {
                        $(".gallery").Chocolat({
                            className: "gallery",
                            imageSelector: ".gallery-item",
                        });
                    }
                }, 500);

                return json.data;
            },
        },
        columns: [
            {data: "title", orderable: true},
            {data: "description", orderable: false},
            {data: "like_count", orderable: false},
            {data: "comment_count", orderable: false},
            {data: "view_count", orderable: false},
            {data: "images", orderable: false},
            {data: "type", orderable: false},
            {data: "date", orderable: true},
            {data: "actions", orderable: false},
        ],
    });

});

function deleteCommunity(url) {
    $.ajax({
        url: url,
        type: "POST",
        contentType: false,
        processData: false,
        data: {},
        beforeSend: function () {
            $('.cover-spin').show();
        },
        success: function (response) {
            $('.cover-spin').hide();
            $('#all-table').DataTable().ajax.reload();
            if (response.success == true) {
                iziToast.success({
                    title: '',
                    message: response.message,
                    position: 'topRight',
                    progressBar: false,
                    timeout: 1000,
                });
            } else {
                iziToast.error({
                    title: '',
                    message: response.message,
                    position: 'topRight',
                    progressBar: false,
                    timeout: 1500,
                });
            }
        },
        error: function (response, status) {
            $('.cover-spin').hide();
            iziToast.error({
                title: '',
                message: 'Error in community delete',
                position: 'topRight',
                progressBar: false,
                timeout: 1500,
            });
        }
    });
}
