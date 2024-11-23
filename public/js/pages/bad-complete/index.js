$(function() {

    loadTableData('oneday');
    $('body').on('click','.filterButton',function(){
        var filter = $(this).attr('data-filter');
        $('#all-table').DataTable().destroy();
        loadTableData(filter);
    })
    
});


function loadTableData(filter){

    var filter = filter || 'oneday';

    if(filter == 'twoweek'){
        var ajaxURL = twoWeekTableData;
    }else{
        var ajaxURL = allTableData;
    }

    var allHospital = $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 5, "desc" ]],
        ajax: {
            url: ajaxURL,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken, filter : filter }
        },
        columns: [
            { data: "business_name", orderable: true },
            { data: "phone", orderable: false },
            { data: "customer_name", orderable: true },
            { data: "customer_phone", orderable: false },            
            { data: "complete_times", orderable: true },
            { data: "booking_date", orderable: true },
            { data: "actions", orderable: false }
        ]
    });
}

function deleteUser(id) {
    $.get(baseUrl + '/admin/users/get/account/' + id, function (data, status) {
        $("#deletePostModal").html('');
        $("#deletePostModal").html(data);
        $("#deletePostModal").modal('show');
    });
}

function editPassword(id) {
    $.get(baseUrl + '/admin/users/get/edit/account/' + id, function (data, status) {
        $("#deletePostModal").html('');
        $("#deletePostModal").html(data);
        $("#deletePostModal").modal('show');
    });
}