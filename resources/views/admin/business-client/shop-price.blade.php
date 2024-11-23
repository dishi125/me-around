<?php

$id= !empty($priceData) ? $priceData->id : '';
$name = !empty($priceData) ? $priceData->name : '';
$price = !empty($priceData) ? $priceData->price : '';
$discount = !empty($priceData) ? $priceData->discount : '';
$main_price_display = !empty($priceData) ? $priceData->main_price_display : '';

?>
<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Shop Price</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-footer">
           <form id="viewShopPrice" data-shopId='<?= $id ?>' data-catId='<?= $cat_id ?>' style="width: 100%;" method="POST" action="javascript:void(0)" enctype="multipart/form-data">
                <input type="hidden" name="cat_id" id="cat_id" value="{!! $cat_id !!}">
                <input type="hidden" name="price_id" id="price_id" value="{!! $id !!}">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group mt-3">
                            <input type="text" class="form-control" value="{!! $name !!}" id="name"
                            name="name"
                            placeholder="Name"  >
                        </div>
                        <div class="form-group mt-3">
                            <input type="text" class="form-control" value="{!! $price !!}" id="price"
                            name="price"
                            placeholder="Price"  >
                        </div>
                        <div class="form-group mt-3">
                            <input type="text" class="form-control" value="{!! $discount !!}" id="discount_price"
                            name="discount_price"
                            placeholder="Discount Price"  >
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="d-flex align-items-center">
                            <div id="image_preview_price" class="d-flex flex-wrap">
                                @if($priceData && $priceData->images()->count())

                                    @foreach ($priceData->images()->get() as $key => $imageData)
                                    <div class="removeImage">
                                        <span class="pointer" data-index={{$key}} data-imageid="{{$imageData->id}}"><i class="fa fa-times-circle fa-2x"></i></span>
                                        @if(\App\Http\Controllers\Controller::get_image_mime_type($imageData->image_url) == true)
                                            <div style="background-image: url({{$imageData->thumb_image}});" class="bgcoverimage">
                                                <img src="{!! asset('img/noImage.png') !!}">
                                            </div>
                                        @else
                                            <div style="background-image: url({{$imageData->image_url}});" class="bgcoverimage">
                                                <img src="{!! asset('img/noImage.png') !!}">
                                            </div>
                                        @endif
                                    </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                        <div class="add-image-icon  mt-2" style="display:flex;" >
                            {{ Form::file('price_images',[ 'accept'=>"image/*,video/*", 'onchange'=>"imagesPreview(this, '#image_preview_price', 'price_images');", 'class' => 'main_image_file form-control', "multiple" => true, 'id' => "price_images", 'hidden' => 'hidden' ]) }}
                            <label class="pointer price_images" for="price_images"><i class="fa fa-plus fa-4x"></i></label>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Close</button>
                <button type="submit" id="saveShopPrice" class="btn btn-primary">Save</button>
               <div class="custom-checkbox" style="float: right">
                   <input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-main-price-display" @if($main_price_display==1) checked @endif value="1" name="main_price_display">
                   <label for="checkbox-main-price-display" class="custom-control-label">Main price display</label>
               </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).on('click','.removeImage > span',function(){
        var timestemp = $(this).attr('data-timestemp');
        var fieldname = $(this).attr('fieldname');
        var imageid = $(this).attr('data-imageid');
        var index = $(this).attr('data-index');

        if(imageid){
            $.ajax({
                url: baseUrl + "/admin/price/image/remove",
                method: 'POST',
                data: {
                    _token: csrfToken,
                    imageid : imageid,
                    index : index,
                },
                beforeSend: function() {
                },
                success: function(data) {
                    mainImagesFiles.splice( $.inArray(imageid, mainImagesFiles), 1 );
                }
            });
        }
        //if(timestemp){
             if(fieldname == 'wedding_gallery'){
                 mainImagesFiles = $.grep(mainImagesFiles, function(e){
                     return e.timestemp != timestemp;
                 });
             }
             $("#"+fieldname).val('');
       // }
        console.log($(this).closest( ".add-image-icon" ))
        $(this).parent().parent().parent().next( ".add-image-icon" ).show();

        $(this).parent().remove();
        $('.maxerror').remove();
    });

    if($("#imagesFile").length){
        var mainImagesFiles = JSON.parse($('#imagesFile').val());
    }else{
        var mainImagesFiles = [];
    }
    function imagesPreview(input, placeToInsertImagePreview){
        var noImage = baseUrl + "/public/img/noImage.png";
        mainImagesFiles = mainImagesFiles || [];

        if (input.files) {
            Array.from(input.files).forEach(async (file,index) => {

                const validImageTypes = ['image/gif', 'image/jpeg', 'image/jpg', 'image/png'];
                console.log(file.type)
                var currentTimestemp = new Date().getTime()+''+index;
                file.timestemp = currentTimestemp;
                mainImagesFiles.push(file);
                if (validImageTypes.includes(file.type)) {
                    var reader = await new FileReader(file);
                    reader.onload = function(event) {
                        var bgImage = $($.parseHTML('<div>')).attr('style', 'background-image: url('+event.target.result+')').addClass("bgcoverimage").wrapInner("<img src='"+noImage+"' />");
                        var container = jQuery("<div></div>",{class: "removeImage", html:'<span fieldname="price_images" data-timestemp="'+currentTimestemp+'" class="pointer"><i class="fa fa-times-circle fa-2x"></i></span>'});
                        container.append(bgImage);
                        container.appendTo(placeToInsertImagePreview);
                    }
                }else{
                    var videoURL = URL.createObjectURL(file);

                    var reader = await new FileReader(file);
                    reader.onload = function(event) {
                        var bgImage = $($.parseHTML('<div>')).addClass("bgcoverimage bgcovervideo").wrapInner("<video width='130' height='128' ><source src='"+videoURL+"' id='video_here'></video>");
                        var container = jQuery("<div></div>",{class: "removeImage", html:'<span fieldname="price_images" data-timestemp="'+currentTimestemp+'" class="pointer"><i class="fa fa-times-circle fa-2x"></i></span>'});
                        container.append(bgImage);
                        container.appendTo(placeToInsertImagePreview);
                    }
                }
                reader.readAsDataURL(file);
            });
        }
    }

    $("form#viewShopPrice").submit(function(e) {
        e.preventDefault();

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        var formData = new FormData($('form#viewShopPrice')[0]);
        if(mainImagesFiles){
            $.map(mainImagesFiles, function(file, index) {
                formData.append('main_images[]', file);
            });
        }
        var cat_id = $('#cat_id').val();
        var price_id = $('#price_id').val();
        var html = '';

        $.ajax({
            method: 'POST',
            processData: false,
            contentType: false,
            cache: false,
            enctype: 'multipart/form-data',
            data: formData,
            url: baseUrl + '/admin/business-client/save/shop/price',
            success: function(results) {
                $(".cover-spin").hide();

                if(results.response == true) {
                    iziToast.success({
                        title: '',
                        message: results.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });
                    $("#profileModal").modal('hide');
                    if(results.is_edit == true){

                        $("ul#shop_item_"+ cat_id +" > #shop_price_"+ price_id + ' > span.name').html($('#name').val());
                        $("ul#shop_item_"+ cat_id +" > #shop_price_"+ price_id + ' > span.price').html($('#price').val());

                        if(results.display_file){
                            var html = `<span class="mr-4 price-image-outer bgcoverimage" style="background-image: url(${results.display_file});" shop-price-id="${price_id}">
                                <img width="30" height="30" src="${results.display_file}" />
                            </span>`;
                        }else{
                            var html = '';
                        }

                        $("ul#shop_item_"+ cat_id +" >  #shop_price_"+ price_id ).find(".display_price_image").html(html);
                    }else{

                        html += "<li class='list-group-item'>";
                        html += "<span class='name'>"+$('#name').val()+"<span><br/>";
                        html += "<span class='price'>"+$('#price').val()+"<span>";
                        html += `<div class="float-right"><span class="display_price_image"><span class="mr-4 price-image-outer bgcoverimage" style="background-image: url(${results.display_file});" shop-price-id="${results.shop_item_id}">
                            <img width="30" height="30" src="${results.display_file}" />
                        </span></span></div>`;
                        html += "</li>";


                        if(results.display_file){
                            var testHtml = `<span class="mr-4 price-image-outer bgcoverimage" style="background-image: url(${results.display_file});" shop-price-id="${results.shop_item_id}">
                                <img width="30" height="30" src="${results.display_file}" />
                            </span>`;
                        }else{
                            var testHtml = '';
                        }

                        let div = document.createElement('div');
                        div.innerHTML = results.html;
                        let selected = div.getElementsByClassName('display_price_image');

                        if(selected)
                        selected[0].innerHTML = testHtml;


                        $("ul#shop_item_"+ cat_id).append(div.innerHTML);
                    }

                }else {
                    iziToast.error({
                        title: '',
                        message: results.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 2000,
                    });
                    //$("#deletePostModal").modal('hide');
                }

            },
            beforeSend: function(){ $(".cover-spin").show(); },
            error: function(data) {

            }
        });
    });

    $('form#viewShopPrice').validate({
        rules: {
            'shop_name': {
                required: true
            },
            'category': {
                required: true
            },
        },
        highlight: function (input) {
            $(input).parents('.form-line').addClass('error');
        },
        unhighlight: function (input) {
            $(input).parents('.form-line').removeClass('error');
        },
        errorPlacement: function (error, element) {
            $(element).parents('.form-group').append(error);
        },
    });
</script>
