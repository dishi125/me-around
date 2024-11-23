let active_tab_id = '';
let active_tab_type = '';
let ordervalue = '';
window.onload = (event) => {
    let activeObject = $('.community-tab.active');
    active_tab_id = $(activeObject).attr('tab_id');
    active_tab_type = $(activeObject).attr('tab_type');

    loadCommunityData();
    $('body').on('change', '#country', function () {
        loadCommunityData();
    });

    $('body').on('click', '.community-tab', function () {
        active_tab_id = $(this).attr('tab_id');
        active_tab_type = $(this).attr('tab_type');
        loadCommunityData();
    });

    $('body').on('click', '.order-button > div.orderBtn', function () {
        ordervalue = $(this).attr('ordervalue');
        var category = $('select[name="category"]').val();
        loadCommunityData(ordervalue, category);
        $('.orderBtn').removeClass('active');
        $(this).addClass('active');
    });

    $('body').on('change', 'select[name="category"]', function () {
        let category = $(this).val();
        loadCommunityData(ordervalue, category);
    });

    loadSlickSlider();


    $('body').on("submit", "#comment-form", function (e) {
        e.preventDefault();

        let formObject = {
            '_token': csrfToken,
            active_tab_id,
            active_tab_type
        };
        let formData = $(this).serialize() + '&' + $.param(formObject);

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            beforeSend: function () {
                $('.cover-spin').show();
            },
            success: function (data) {
                if (data.success) {
                    data.refresh_url && loadCommunityDetail(data.refresh_url);
                } else {
                }
                $('.cover-spin').hide();
            }
        });

        console.log(formData)
    });
};

function loadSlickDetailSlider() {
    $('.detail-slider').slick({
        infinite: true,
        slidesToShow: 3,
        arrows: false,
        autoplay: false,
    });
}

function loadSlickSlider() {
    $('.community-slider').slick({
        infinite: true,
        slidesToShow: 1,
        arrows: false,
        autoplay: true
    });
}

function loadCommunityData(ordervalue, category) {
    var ordervalue = ordervalue || 'popular';
    var category = category || '';
    var country = $('select[name="country"]').val() || "KR";

    $.ajax({
        url: ajaxURL,
        method: 'POST',
        data: {
            '_token': csrfToken,
            active_tab_id,
            active_tab_type,
            ordervalue,
            category,
            country
        },
        beforeSend: function () {
            $('.cover-spin').show();
        },
        success: function (data) {
            $("#postDetailContent").empty();
            if (data.success) {
                $("#postContent").html(data.html);
                $('select[name="category"]').html(data.categoryHtml);
                loadSlickSlider();
            }
            $('.cover-spin').hide();
        }
    });

}

function loadCommunityDetail(detailURL) {
    var country = $('select[name="country"]').val() || "KR";
    $.ajax({
        url: detailURL,
        method: 'POST',
        data: {
            '_token': csrfToken,
            active_tab_id,
            active_tab_type,
            country
        },
        beforeSend: function () {
            $('.cover-spin').show();
        },
        success: function (data) {
            if (data.success) {
                $([document.documentElement, document.body]).animate({
                    scrollTop: $(".nav.nav-tabs").offset().top
                }, 500);
                $("#postDetailContent").html(data.html);
                loadSlickDetailSlider();

            }
            $('.cover-spin').hide();
        }
    });
}


function focusOnComment(parent_id,is_reply) {
    let parent = parent_id || 0;
    let isReply = is_reply || 'no';
    $('input[name="is_reply_id"]').val(isReply);
    $('input[name="parent_id"]').val(parent);
    $("input[name='comment']").focus();
}

function likeByAdmin(ajaxURL, entity_id, type, is_like, is_reply) {
    var is_reply = is_reply || "false";
    if (is_like && type == 'comment' && active_tab_type == 'category') {
        return false;
    }
    var user_id = $('input[name="user_id"]').val();
    $.ajax({
        url: ajaxURL,
        method: 'POST',
        data: {
            '_token': csrfToken,
            active_tab_id,
            active_tab_type,
            entity_id,
            type,
            id: is_like,
            user_id,
            is_reply
        },
        beforeSend: function () {
            $('.cover-spin').show();
        },
        success: function (data) {
            if (data.success) {
                data.refresh_url && loadCommunityDetail(data.refresh_url);
            } else {
                $('.cover-spin').hide();
            }
        }
    });
}

function postNewComments(){

}
