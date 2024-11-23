
$(function() {
  var allHospital = $("#all-table").DataTable({
      responsive: true,
      processing: true,
      serverSide: true,
      deferRender: true,
      "order": [[ 8, "desc" ]],
      ajax: {
          url: suggestIndex,
          dataType: "json",
          type: "POST",
          data: { _token: csrfToken }
      },
      columns: [
            { data: "checkbox", orderable: false },
            { data: "business_name", orderable: true },
            { data: "type_of_business", orderable: true },
            { data: "address", orderable: true },
            { data: "city", orderable: true },
            { data: "phone_number", orderable: false },
            { data: "email", orderable: true },            
            { data: "photos", orderable: false },
            { data: "date", orderable: true },
      ]
  });
});
$('#checkbox-all').click(function (event) {
  if (this.checked) {
      $('.check-suggest').each(function () {
          this.checked = true;
      });
  } else {
      $('.check-suggest').each(function () {
          this.checked = false;
      });
  }
});
