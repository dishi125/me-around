function deleteReport(id) {
    $.get(baseUrl + '/admin/reported-client/delete/' + id, function(data, status) {
        pageModel.html('');
        pageModel.html(data);
        pageModel.modal('show');
    });
}

$(document).on('click', '#warning_mention', function () {    
    $("#warningMentionModel ").modal('show');
});

$(document).on('click', '#warning-mention-btn', function () {
    // alert(1);
    var shop_warning_comment = $("#shop_warning_comment").val();
    var hospital_warning_comment = $("#hospital_warning_comment").val();
    var community_warning_comment = $("#community_warning_comment").val();
    var review_warning_comment = $("#review_warning_comment").val();
    var shop_user_warning_comment = $("#shop_user_warning_comment").val();
  $.ajax({
      url: warningMentionRequest,
      method: 'POST',
      data: {
          _token: csrfToken,
          'shop_warning_comment': shop_warning_comment,
          'hospital_warning_comment': hospital_warning_comment,
          'community_warning_comment': community_warning_comment,
          'review_warning_comment': review_warning_comment,
          'shop_user_warning_comment': shop_user_warning_comment,
      },
      success: function (data) {
          $("#warningMentionModel").modal('hide');
          if(data['status_code'] == 200) {
              iziToast.success({
                  title: '',
                  message: data['message'],
                  position: 'topRight',
                  progressBar: false,
                  timeout: 5000,
              });
          }else{
              iziToast.error({
                  title: '',
                  message: data['message'],
                  position: 'topRight',
                  progressBar: false,
                  timeout: 5000,
              });
          }
      }
  });  
});

function deletePost(id) {
    $.get(baseUrl + '/admin/reported-client/get/post/' + id, function (data, status) {
        $("#deletePostModal").html('');
        $("#deletePostModal").html(data);
        $("#deletePostModal").modal('show');
    });
}
function deleteAllPost(id) {
    $.get(baseUrl + '/admin/reported-client/get/all/post/' + id, function (data, status) {
        $("#deletePostModal").html('');
        $("#deletePostModal").html(data);
        $("#deletePostModal").modal('show');
    });
}
function deleteUser(id) {
    $.get(baseUrl + '/admin/reported-client/get/account/' + id, function (data, status) {
        $("#deletePostModal").html('');
        $("#deletePostModal").html(data);
        $("#deletePostModal").modal('show');
    });
}
