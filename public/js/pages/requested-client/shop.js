$(function() {
  var allHospital = $("#all-table").DataTable({
      responsive: true,
      processing: true,
      serverSide: true,
      deferRender: true,
      "order": [[ 9, "desc" ]],
      ajax: {
          url: shopIndex,
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
          { data: "business_license_number", orderable: true },            
          { data: "photos", orderable: false },
          { data: "date", orderable: true },
      ]
  });
});
$('#checkbox-all').click(function (event) {
  if (this.checked) {
      $('.check-shop').each(function () {
          this.checked = true;
      });
  } else {
      $('.check-shop').each(function () {
          this.checked = false;
      });
  }
});
