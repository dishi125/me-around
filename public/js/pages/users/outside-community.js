$(function () {

    $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        order: [[6, "desc"]],
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
            /*{data: "like_count", orderable: false},*/
            {data: "comment_count", orderable: false},
            {data: "view_count", orderable: false},
            {data: "images", orderable: false},
            {data: "type", orderable: false},
            {data: "date", orderable: true},
            /*{data: "actions", orderable: false},*/
        ],
    });

});
