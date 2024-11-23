$(function() {
   if(typeof allHospitalTable != 'undefined')
   loadDataTable();
});

$("body").on('click','.categories',function(){
   var categoryFilter = $(this).attr('data-categoryID');
   
   $('input[name="categoryFilter"]').val(categoryFilter);
   $('input[name="statusFilter"]').val('all');
   $('#all-table').DataTable().destroy();
   loadDataTable();
});

$(".statusFilterData").on('click','a',function(){
   var statusFilter = $(this).attr('data-value');
   
   $('input[name="statusFilter"]').val(statusFilter);
   $('#all-table').DataTable().destroy();
   loadDataTable();
});

function loadDataTable(){

   var dateFilter = $('input[name="dateFilter"]').val();
   var statusFilter = $('input[name="statusFilter"]').val();
   var countryFilter = $('input[name="countryFilter"]').val();
   var categoryFilter = $('input[name="categoryFilter"]').val();
   var pageType = $('input[name="pageType"]').val();

   var allHospital = $("#all-table").DataTable({
      responsive: true,
      processing: true,
      serverSide: true,
      deferRender: true,
      "order": [[ 5, "desc" ]],
      ajax: {
          url: allHospitalTable,
          dataType: "json",
          type: "POST",
          data: { _token: csrfToken, status: statusFilter, dateFilter: dateFilter, countryFilter:countryFilter, categoryFilter: categoryFilter, pageType:pageType },
          dataSrc: function ( json ) {
            $.each( json.statusCount, function( i, val ) {
               if(i != 'all'){
                  $('.nav-link[data-value="'+i+'"]').find('span').text(' ('+ val +')');
               }else if(i == 'all'){
                  $('.nav-link[data-value="all"]').find('span').html('&nbsp;');
               }
            });

            return json.data;
         }    
      },
      columns: [
          { data: "request_booking_status_name", orderable: true },
          { data: "user_name", orderable: true },
          { data: "business_user_name", orderable: true },
          { data: "business_name", orderable: true },            
          { data: "business_address", orderable: true },
          { data: "created_at", orderable: true }
      ]
  });

}
$("#country_id").change(function () {
   var countryId = this.value;
   $('input[name="countryFilter"]').val(countryId);
   $('#all-table').DataTable().destroy();
   loadDataTable();

 });

$("#date-filter").change(function () {
   var dateSelected = this.value;

   $('input[name="dateFilter"]').val(dateSelected);
   $('#all-table').DataTable().destroy();
   loadDataTable();
});

var getUrlParameter = function getUrlParameter(sParam) {
   var sPageURL = window.location.search.substring(1),
      sURLVariables = sPageURL.split('&'),
      sParameterName,
      i;

   for (i = 0; i < sURLVariables.length; i++) {
      sParameterName = sURLVariables[i].split('=');

      if (sParameterName[0] === sParam) {
         return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
      }
   }
};