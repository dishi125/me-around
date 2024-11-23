
$(document).on('click', '#confirm_hospital', function () {
   
    var id = [];
    $('.checkbox_id:checked').each(function () {
        id.push($(this).attr('data-id'));
    });
    if (id.length > 0) {
        $("#confirmRequestModel").modal('show');
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
  $(document).on('click', '#confirm-request', function () {
    var id = [];
    $('.checkbox_id:checked').each(function () {
        id.push($(this).attr('data-id'));
    });
    if (id.length > 0) {
      var strIds = id.join(",");
        confirmRequest(strIds);        
    }
  });
  
  function confirmRequest(strIds){
    $.ajax({
        url: approveRequest,
        method: 'POST',
        data: {
            _token: csrfToken,
            'ids': strIds,
        },
        beforeSend: function () {
            $(".cover-spin").show();
        },
        success: function (data) {
            $(".cover-spin").hide();
            //  $("#request_hospital_data").ajax.reload();
            $("#confirmRequestModel").modal('hide');
            $('#all-table').dataTable().api().ajax.reload();
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
  $(document).on('click', '#reject_hospital', function () {
    var id = [];
    $('.checkbox_id:checked').each(function () {
        id.push($(this).attr('data-id'));
    });
    if (id.length > 0) {
        $("#rejectRequestModel").modal('show');
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
  
  $(document).on('click', '#reject-request', function () {
    var id = [];
    $('.checkbox_id:checked').each(function () {
        id.push($(this).attr('data-id'));
    });
    if (id.length > 0) {
        var reject_comment = $('#reject_comment').val();
        var strIds = id.join(",");
        $.ajax({
          url: rejectRequest,
          method: 'POST',
          data: {
              _token: csrfToken,
              'ids': strIds,
              'reject_comment': reject_comment,
          },
          success: function (data) {
              $("#rejectRequestModel").modal('hide');
              $('#all-table').dataTable().api().ajax.reload();
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
  
  
  $(document).on('click', '#reject_mention', function () {    
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