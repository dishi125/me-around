function viewImage(id) {
    $.get(baseUrl + '/admin/reward-instagram/view/shop/image/' + id, function (data, status) {
        profileModal.html('');
        profileModal.html(data);
        profileModal.modal('show');
    });
}

$(function() {
  var allHospital = $("#reward-instagram-table").DataTable({
      searching: false,
      responsive: true,
      processing: true,
      serverSide: true,
      deferRender: true,
      order: [[ 9, "desc" ]],
      ajax: {
          url: rewardInstagramIndex,
          dataType: "json",
          type: "POST",
          data: { _token: csrfToken}
      },
      columns: [
          { data: "checkbox", orderable: false },
          { data: "shop_name", orderable: true },
          { data: "shop_profile", orderable: false },
          { data: "instagram", orderable: false },
          { data: "penalty", orderable: true },
          { data: "reject", orderable: true },
          { data: "reward", orderable: true },    
          { data: "request_count", orderable: true },    
          { data: "phone", orderable: false },        
          { data: "date", orderable: true },
          { data: "actions", orderable: false },
      ]
  });

});
$('#checkbox-all').click(function (event) {
  if (this.checked) {
      $('.check-reward-instagram:not(:disabled)').each(function () {
          this.checked = true;
      });
  } else {
      $('.check-reward-instagram:not(:disabled)').each(function () {
          this.checked = false;
      });
  }
});
/* Reward Degree */
$(document).on('click', '#reward_degree', function () {
    var id = [];
    $('.checkbox_id:checked').each(function () {
        id.push($(this).attr('data-id'));
    });
    if (id.length > 0) {
        $("#rewardModel").modal('show');
    } else {
        iziToast.error({
            title: '',
            message: 'Please select at least one checkbox',
            position: 'topRight',
            progressBar: true,
            timeout: 5000
        });
    }
  });

$(document).on('click', '#reward-btn', function () {
var id = [];
$('.checkbox_id:checked').each(function () {
    id.push($(this).attr('data-id'));
});
if (id.length > 0) {
    var strIds = id.join(",");
    $.ajax({
        url: rewardMultiple,
        method: 'POST',
        data: {
            _token: csrfToken,
            'ids': strIds,
        },
        success: function (data) {
            //  $("#request_hospital_data").ajax.reload();
            $("#rewardModel").modal('hide');
            $('#reward-instagram-table').dataTable().api().ajax.reload();
            $('#checkbox_id').attr('checked', false);
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
    
}
});
/* Reward Degree */

/* Reject Mention */

$(document).on('click', '#reject_notice_edit', function () {    
    $("#rejectMentionModel").modal('show');
});

$(document).on('click', '#reject-mention-btn', function () {
var content = $("#reject_comment_basic").val();
  $.ajax({
      url: rejectMentionRequest,
      method: 'POST',
      data: {
          _token: csrfToken,
          'content': content,
          'type' : 'reject'
      },
      success: function (data) {
          //  $("#request_hospital_data").ajax.reload();
          $("#rejectMentionModel").modal('hide');
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

/* Reject Mention */

/* Penalty Mention */

$(document).on('click', '#penalty_mention', function () {    
    $("#penaltyMentionModel").modal('show');
});

$(document).on('click', '#penalty-mention-btn', function () {
var content = $("#penalty_comment_basic").val();
  $.ajax({
      url: rejectMentionRequest,
      method: 'POST',
      data: {
          _token: csrfToken,
          'content': content,
          'type' : 'reject'
      },
      success: function (data) {
          //  $("#request_hospital_data").ajax.reload();
          $("#penaltyMentionModel").modal('hide');
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

/* Penalty Mention */
