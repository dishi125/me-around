var mySound;
var tableInit;

$(function() {
    tableInit = $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 2, "asc" ]],
        ajax: {
            url: getJson,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken }
        },
        createdRow: function(row, data, dataIndex) {
            $(row).attr('data-id', data.id).addClass('row1');
        },
        columns: [
            { data: "name", orderable: true },
            { data: "date", orderable: true },
            { data: "order", orderable: true },
            { data: "actions", orderable: false },
        ]
    });

    $("#all-table > tbody").sortable({
        items: "tr",
        cursor: "move",
        opacity: 0.6,
        update: function () {
            sendOrderToServer();
        },
    });

  });

function sendOrderToServer() {
    var order = [];
    var info = tableInit.page.info();
    const startIndex = (info.page * 10) + 1;
    $("tr.row1").each(function (index, element) {
        order.push({
            id: $(this).attr("data-id"),
            position: index + startIndex,
        });
    });

    console.log(order);

    $.ajax({
        type: "POST",
        dataType: "json",
        url: updateOrder,
        data: {
            order: order,
            _token: csrfToken,
        },
        success: function (response) {
            $("#all-table").dataTable().api().ajax.reload(null, false);

        },
    });
}

  
function deleteMusicConfirmation(music_id){
    $.get(baseUrl + '/admin/music/get/delete/' + music_id, function (data, status) {
        $("#deleteMusicModal").html('');
        $("#deleteMusicModal").html(data);
        $("#deleteMusicModal").modal('show');
    });
}

function deleteMusic(music_id){
    if(music_id){
        $.ajax({
            url: baseUrl + "/admin/music/delete",
            method: 'POST',
            data: {
                _token: csrfToken,
                music_id : music_id,
            },
            beforeSend: function(){ $(".cover-spin").show(); },
            success: function(response) {
                $(".cover-spin").hide();
                $("#deleteMusicModal").modal('hide');
                if(response.success == true){
                    showToastMessage(response.message,true);
                    $('#all-table').DataTable().ajax.reload();
    
    
                }else {
                    showToastMessage(response.message,false);
                }
            }
        });
    }
}

function playMusic(file, el){
    if(mySound){
        mySound.pause();
        $('.playicon').addClass('fa-play').removeClass('fa-pause');
        $('.playiconparent').not(el).addClass('play');
        $('.playiconparent').not(el).removeClass('pause');
        
    }       
    if($(el).hasClass('play')){
        $(el).addClass('pause').removeClass('play');
        $(el).find('.playicon').addClass('fa-pause').removeClass('fa-play');
        mySound = new Audio(file);
        mySound.play()
    }else{
        $(el).addClass('play').removeClass('pause');
    }
}