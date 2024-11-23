$(function() {

    $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 0, "desc" ]],
        ajax: {
            url: allUserTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken }
        },
        columns: [
            { data: "title", orderable: true },
            { data: "value", orderable: false },
            { data: "actions", orderable: false }
        ]
    });
    
});

function showImage(imageSrc){
    // Get the modal
    if(imageSrc){
        var modalImg = document.getElementById("modelImageEle");
        modalImg.src = imageSrc;
        $("#PostPhotoModal").modal('show');

    }
}