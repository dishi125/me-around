
$(function() {
    var allHospital = $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 7, "desc" ]],
        ajax: {
            url: hospitalIndex,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken}
        },
        columns: [
            { data: "checkbox", orderable: false },
            { data: "go_actions", orderable: false },
            { data: "delete_actions", orderable: false },
            { data: "report_item_name", orderable: false },
            { data: "mobile", orderable: false },
            { data: "report_item_category", orderable: false },
            { data: "category_name", orderable: false },
            { data: "status", orderable: true },
            { data: "date", orderable: true },
            { data: "actions", orderable: false },
        ]
    });

    $('#select_report_category').on('change', function(){
      var filter_value = $(this).val();
      var new_url = hospitalIndex + '/'+ filter_value;
      allHospital.ajax.url(new_url).load();
    });
});

$('#checkbox-all').click(function (event) {
  if (this.checked) {
      $('.check-hospital').each(function () {
          this.checked = true;
      });
  } else {
      $('.check-hospital').each(function () {
          this.checked = false;
      });
  }
});




