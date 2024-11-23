function deleteShopPost(id) {
    var URL = baseUrl + "/admin/business-client/shop/posts/delete/"+id;
    $.get( URL, function (data, id) {
        $("#shopPostModal").html('');
        $("#shopPostModal").html(data);
        $("#shopPostModal").modal('show');
    });
}
function loadTableData(filters,latitude,longitude,distance,filter_date,hashtag_id){
    var shopPost = $("#all-shop-post-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        "order": [[ 4, "desc" ]],
        ajax: {
            url: shopPostTable,
            dataType: "json",
            type: "POST",
            data: { _token: csrfToken,filters: filters,latitude: latitude,longitude: longitude,distance: distance,filter_date: filter_date,hashtag_id: hashtag_id },
        },
        columns: [
        { data: "business_name", orderable: true },
        { data: "business_phone", orderable: false },
        { data: "address", orderable: false },
        { data: "description", orderable: false },
        { data: "update_date", orderable: true },
        {data: "service",orderable: false},
        { data: "images", orderable: false },
        { data: "actions", orderable: false },
        { data: "checkbox", orderable: false },
        ],
    });

}

$(function () {
    loadTableData(['all'],"","","", "", hashtag_id);
});

function showImage(imageSrc,main_name,shop_name,business_link,social_name){
    // Get the modal
    $('#modelImageShow').html('');
    var validExtensions = ["jpg","jpeg","gif","png",'webp'];
    var extension = imageSrc.split('.').pop().toLowerCase();
    if(imageSrc){
        if($.inArray(extension, validExtensions) == -1){
            $('#modelImageEle').remove();
            $('#modelImageShow').html('<video width="100%" height="300" controls poster="" id="modelVideoEle"><source src="'+imageSrc+'" type="video/mp4">Your browser does not support the video tag.</video>');
        }else{
            $('#modelVideoEle').remove();
            $('#modelImageShow').html('<img src="'+imageSrc+'" class="w-100 " id="modelImageEle" />');
        }

        var shop_names_data = "";
        if (main_name!=""){
            shop_names_data += `Activate name: <span onclick="copyTextLink('${main_name}','${main_name}')" style="cursor:pointer;">${main_name}</span> `;
        }
        if (shop_name!=""){
            shop_names_data += `Shop name: <span onclick="copyTextLink('${shop_name}','${shop_name}')" style="cursor:pointer;">${shop_name}</span>`;
        }
        $("#PostPhotoModal").find("#shop_names_data").html(shop_names_data);
        // $("#PostPhotoModal").find("#download_post_btn").attr('file-url',imageSrc);

        var html_footer = "";
        if (business_link!=""){
            html_footer += `<a href="javascript:void(0);" onClick="copyTextLink('${business_link}','Business link')" class="mr-2 btn btn-primary btn-sm">Open link</a>`;
        }
        if (social_name!="" && social_name!=null){
            html_footer += `<a href="https://www.instagram.com/${social_name}" class="mr-2 btn btn-primary btn-sm" target="_blank">${social_name}</a>`;
        }
        html_footer += `<button type="button" class="btn btn-primary" id="download_post_btn" file-url="${imageSrc}">Download</button>`;
        $("#PostPhotoModal").find(".modal-footer").html(html_footer);
        $("#PostPhotoModal").modal('show');
    }
}

$(document).on('click', '#download_post_btn', function (){
    var url = $(this).attr('file-url');
    forceDownload2(url, url.substring(url.lastIndexOf('/')+1,url.length));
})

$(document).on('click','#remove_text_button',function(e){
    $("#removeTextPostModal").modal('show');
});

var timer = null;
$(document).on('keydown','#remove_text',function(e){
    clearTimeout(timer);
    timer = setTimeout(loadPostHtml, 500)
});

function loadPostHtml() {
    console.log("come");
    let remove_text = $("input[name='remove_text']").val();
    $.ajax({
        url: baseUrl + "/admin/get/remove-text/shop/posts/list",
        method: 'POST',
        data: {
            '_token': csrfToken,
            'remove_text': remove_text,
        },
        success: function (data) {
            $("#searchposttabledata").html(data.html);
        },
        error : function (){
        }
    });
}

$(document).on('click','#remove_button',function(e){
    let remove_text = $("input[name='remove_text']").val();
    var ids = $('input[name="remove_id[]"]:checked').map(function(_, el) {
        return $(el).val();
    }).get();

    if (ids.length == 0) {
        iziToast.error({
            title: '',
            message: 'Please select at least one post',
            position: 'topRight',
            progressBar: true,
            timeout: 5000
        });
    }else if(remove_text.trim().length < 1){
        iziToast.error({
            title: '',
            message: 'Please Enter remove text.',
            position: 'topRight',
            progressBar: true,
            timeout: 5000
        });
    }else{

        $.ajax({
            url: baseUrl + "/admin/remove-text/shop/posts",
            method: 'POST',
            data: {
                '_token': csrfToken,
                'remove_text': remove_text,
                'ids': ids,
            },
            beforeSend: function () { $(".cover-spin").show(); },
            success: function (data) {
                $("#removeTextPostModal").modal('hide');
                $("input[name='remove_text']").val('');
                $("#searchposttabledata").html('');
                $(".cover-spin").hide();
                $('#all-shop-post-table').dataTable().api().ajax.reload();
                if(data.success) {
                    iziToast.success({
                        title: '',
                        message: data.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });
                }else {
                    iziToast.error({
                        title: '',
                        message: data.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });
                }
            },
            error : function (){
                $("#removeTextPostModal").modal('hide');
                $("input[name='remove_text']").val('');
                $("#searchposttabledata").html('');
                $(".cover-spin").hide();
            }
        });
    }
});

$(document).on('click','.destroyForm',function(e){
    e.preventDefault();

    $.ajax({
        url: baseUrl + '/admin/business-client/shop/posts/destroy/'+$('.bussiness_client_destroy_form').data('id'),
        type: 'DELETE',
        data: {
           _token: csrfToken
       },
       success: function(data) {
        $(".cover-spin").hide();
        $("#shopPostModal").modal('hide');
        $('#all-shop-post-table').DataTable().destroy();
       var latitude = $("#final-lat").val();
       var longitude = $("#final-long").val();
       var distance = $("#final-distance").val();
       var filter_date = $("#filter-date").val();
        loadTableData(filters,latitude,longitude,distance,filter_date,hashtag_id);

        if(data.status_code == 200) {
            iziToast.success({
                title: '',
                message: data.message,
                position: 'topRight',
                progressBar: false,
                timeout: 1000,
            });

        }else {
            iziToast.error({
                title: '',
                message: data.message,
                position: 'topRight',
                progressBar: false,
                timeout: 1000,
            });
        }
    },
    beforeSend: function(){ $(".cover-spin").show(); },
    error: function(data) {
                //window.location.reload();
            }
        });
});

$(document).on('click','#save_filter_btn',function (){
    filters = [];
    $(".filter_shop_post_checkbox").each(function (){
        var thi_val = $(this).val();
        if($(this).is(':checked')){
            filters.push(thi_val);
        }
    })
    var latitude = $("#final-lat").val();
    var longitude = $("#final-long").val();
    var distance = $("#final-distance").val();
    var filter_date = $("#filter-date").val();

    $('#all-shop-post-table').DataTable().destroy();
    loadTableData(filters,latitude,longitude,distance,filter_date,hashtag_id);
})

function downloadPosts(URLs){
    // console.log(URLs);
    /*for (var i = 0; i < URLs.length; i++) {
        forceDownload2(URLs[i], URLs[i].substring(URLs[i].lastIndexOf('/')+1,URLs[i].length))
    }*/
    $(URLs).each(function (index,value){
        console.log("row_no:"+value.row_no);
        console.log("value:"+value.value);
        var url = value.value;
        forceDownload2(url, value.row_no+".png");
    });

    /*var xhr = new XMLHttpRequest();
    xhr.open('POST', downloadShopPost, true);
    xhr.responseType = 'blob';
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken); // Add this line
    xhr.onload = function () {
        console.log("this.status "+this.status);
        console.log("this.response "+this.response);
        if (this.status === 200) {
            var blob = new Blob([this.response], {type: 'application/zip'});
            var link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = 'files.zip';
            link.click();
        }
    };
    var data = JSON.stringify({urls: URLs}); // Replace with your actual data
    xhr.send(data);*/
}

function forceDownload(url, fileName){
    /*var corsAnywhereUrl = 'https://cors-anywhere.herokuapp.com/';
    url = corsAnywhereUrl + url;*/
    var xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.responseType = "blob";
    xhr.onload = function(){
        var urlCreator = window.URL || window.webkitURL;
        var imageUrl = urlCreator.createObjectURL(this.response);
        var tag = document.createElement('a');
        tag.href = imageUrl;
        tag.download = fileName;
        document.body.appendChild(tag);
        tag.click();
        document.body.removeChild(tag);
    }
    xhr.send();
}

function forceDownload2(url, filename){
    // console.log(url);
    fetch(baseUrl+'/admin/proxy-image?url=' + encodeURIComponent(url))
        .then(response => {
            // console.log(response);
            if (response.ok) {
                return response.blob();
            }
            else {
                showToastMessage("Shop post not found.", false);
                // throw new Error('Error occurred while fetching the image.');
            }
        })
        .then(blob => {
            // console.log("blob: ");
            // console.log(blob);
            var tag = document.createElement('a');
            tag.href = URL.createObjectURL(blob);
            tag.download = filename;
            document.body.appendChild(tag);
            tag.click();
            document.body.removeChild(tag);
        })
        .catch(error => {
            // Handle any errors that occur during the request
            console.error(error);
        });
}

$(document).on('click', '#download_button', function (){
    var id = $('input[name="checkbox_id[]"]:checked').map(function(_, el) {
        return $(el).val();
    }).get();

    if (id.length == 0) {
        iziToast.error({
            title: '',
            message: 'Please select at least one checkbox',
            position: 'topRight',
            progressBar: true,
            timeout: 5000
        });
    } else {
        $.ajax({
            url: getCheckedShopPosts,
            method: 'POST',
            data: {
                _token: csrfToken,
                ids: id,
            },
            beforeSend: function() {
                $('.cover-spin').show();
            },
            success: function(response) {
                $('.cover-spin').hide();
                if (response.success == true) {
                    console.log(response.urls);
                    downloadPosts(response.urls);
                } else {
                    iziToast.error({
                        title: '',
                        message: 'Something went wrong!!',
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1500,
                    });
                }
            }
        });
    }
})

function addressPopup(){
    $.get(baseUrl + '/admin/address-detail', function (data, status) {
        $('#addressModal').html('');
        $('#addressModal').html(data);
        initialize();
        $('#addressModal').modal('show');
    });
}

$(document).on('click', '#apply_address_btn', function (){
    var address = $("#address").val();
    var expose_distance = $("input[name=expose_distance]").val();

    if (address==""){
        showToastMessage("Please enter location.", false);
    }
    if (expose_distance==""){
        showToastMessage("Please enter radius.", false);
    }

    if (address!="" && expose_distance!=""){
        $("#address_popup").val(address);
        $("#radius").val(expose_distance);
        $("#final-lat").val($("#circle-lat").val());
        $("#final-long").val($("#circle-long").val());
        $("#final-distance").val($("#circle-distance").val());
        $("#addressModal").modal('hide');
    }
})

$(document).on('click', '#download_blogging_button', function (e){
    e.preventDefault();
    var shop_ids = $('input[name="checkbox_id[]"]:checked').map(function(_, el) {
        return $(el).attr('shop-id');
    }).get();

    if (shop_ids.length == 0) {
        iziToast.error({
            title: '',
            message: 'Please select at least one checkbox',
            position: 'topRight',
            progressBar: true,
            timeout: 5000
        });
    }
    else {
        $.ajax({
            url: baseUrl+'/admin/shops/generate-text-file',
            method: 'POST',
            data: {
                '_token': csrfToken,
                'shop_ids': shop_ids,
            },
            beforeSend: function() {
                $('.cover-spin').show();
            },
            success: function(response) {
                $('.cover-spin').hide();
                if (response.success == true) {
                    forceDownload(response.url, response.url.substring(response.url.lastIndexOf('/')+1,response.url.length));
                } else {
                    iziToast.error({
                        title: '',
                        message: 'Something went wrong!!',
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1500,
                    });
                }
            }
        });
    }
});

