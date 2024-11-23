$(function() {

    loadTableData('booked_user');
    $('body').on('click','.filterButton',function(){
        var filter = $(this).attr('data-filter');
        $('#all-table').DataTable().destroy();
        loadTableData(filter);
    })
    
});


function loadTableData(filter){

    var filter = filter || 'booked_user';

    var allHospital = $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        ajax: {
            url: allUserTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken, filter : filter }
        },
        columns: [
        { data: "user_image", orderable: false },
        { data: "user_name", orderable: false },
        { data: "category_name", orderable: false },
        { data: "category_image", orderable: false },
        { data: "booking_date", orderable: false },
        { data: "actions", orderable: false }
        ]
    });
}
