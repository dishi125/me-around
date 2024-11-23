@extends('layouts.app')
@section('styles')
<link rel="stylesheet" href="{!! asset('css/custom.css') !!}">
@endsection
@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')
<?php
$id = !empty($shoppost['id']) ? $shoppost['id'] : '' ;
$from = $from ?? '';
$description = !empty($shoppost['description']) ? $shoppost['description'] : '' ;
$multiple_shop_posts = !empty($shoppost['multiple_shop_posts']) ? json_decode(json_encode($shoppost['multiple_shop_posts']), FALSE) : '' ;
$imagesfile = json_encode($multiple_shop_posts);
$social_name = !empty($instaData) ? $instaData->social_name : null;
?>
<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12">
            <div class="card profile-widget">

                <div class="profile-widget-description">
                    <div class="">
                        {!! Form::open(['route' => ['admin.business-client.shoppost.update',$id], 'id' =>"settingForm", 'method' => 'put', 'enctype' => 'multipart/form-data']) !!}
                            @csrf

                            {{ Form::hidden('from',$from, array('id' => 'from')) }}
                            {{ Form::hidden('imagesFile',$imagesfile, array('id' => 'imagesFile')) }}
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-10">
                                        @if($id)
                                            <div class="form-group d-flex">
                                                Created At : {{$shoppost['display_created_at']}}
                                                @if(!Carbon::parse($shoppost['updated_at'])->isSameDay(Carbon::parse($shoppost['display_created_at'])))
                                                    <div class='edited-dots ml-3'>Updated At : {{$shoppost['updated_at']}}</div>
                                                @endif
                                            </div>
                                        @endif
                                        <div class="form-group">
                                            {!! Form::label('description', __(Lang::get('forms.association.description'))); !!}
                                            {!! Form::textarea('description', $description, ['style' => 'height:200px !important;', 'class' => 'form-control'. ( $errors->has('description ') ? ' is-invalid' : '' ), 'placeholder' => __(Lang::get('forms.association.description')) ]); !!}
                                            @error('description')
                                            <div class="invalid-feedback">
                                                {{ $errors->first('description') }}
                                            </div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <div class="d-flex align-items-center imagesupload">
                                                <div class="form-group">
                                                    <label for="name">{!! __(Lang::get('forms.posts.image')); !!}</label>
                                                    <div class="">
                                                        <div id="image_preview" class="d-flex align-items-center flex-wrap">
                                                        @if(isset($multiple_shop_posts))
                                                        @foreach($multiple_shop_posts as $photo)
                                                        <div class="removeImage mb-3">
                                                            <?php $post_in = "shop_posts"; ?>
                                                            @if(!isset($photo->post_type))
                                                                <?php $post_in = "multiple_shop_posts"; ?>
                                                                <span class="pointer" data-imageid="{{$photo->id}}"><i class="fa fa-times-circle fa-2x"></i></span>
                                                            @endif

                                                            @if($photo->type == 'image')
                                                            <div onclick="showImage(`{{$photo->post_item}}`,'image',`{{$shop->main_name}}`,`{{$shop->shop_name}}`,`{{$shop->business_link}}`,`{{$social_name}}`,`{{$post_in}}`,`{{$photo->id}}`,`{{$photo->display_video}}`)" style="background-image: url({{$photo->post_item}});" class="pointer bgcoverimage">
                                                                <img src="{!! asset('img/noImage.png') !!}">
                                                            </div>
                                                            @elseif($photo->type == 'video')
                                                            <i class="fas fa-play-circle" style="font-size: 30px; top: 50%; left: 50%; position: absolute; transform: translate(-50%, -50%); margin-left: -4px;"></i>
                                                            <div onclick="showImage(`{{$photo->post_item}}`,'video',`{{$shop->main_name}}`,`{{$shop->shop_name}}`,`{{$shop->business_link}}`,`{{$social_name}}`,`{{$post_in}}`,`{{$photo->id}}`,`{{$photo->display_video}}`)" style="background-image: url({{$photo->video_thumbnail}});" class="pointer bgcoverimage">
                                                                <img src="{!! asset('img/noImage.png') !!}">
                                                            </div>
                                                            @endif
                                                        </div>
                                                        @endforeach
                                                        @endif
                                                        </div>
                                                        <div class="add-image-icon">
                                                            {{ Form::file("main_image",[ 'accept'=>"image/jpg,image/png,image/jpeg", 'onchange'=>"imagesPreview(this, '#image_preview', 'no');", 'class' => 'main_image_file form-control', 'multiple' => 'multiple', 'id' => "main_image", 'hidden' => 'hidden' ]) }}
                                                            <label class="pointer" for="main_image"><i class="fa fa-plus fa-4x"></i></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    <div class="card-footer">

                                        <a href="{{ route('admin.shoppost.remove',[$shoppost['id']])}}"
                                            class="btn btn-danger mr-2">Remove</a>

                                        <button type="submit"
                                            class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                                        <a href="{{ route('admin.business-client.shop.show',[$shoppost['shop_id']])}}"
                                            class="btn btn-default">{{ __(Lang::get('general.cancel')) }}</a>
                                    </div>
                                    </div>
                                </div>
                            </div>

                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="cover-spin"></div>
@endsection

<div class="modal fade" id="PostPhotoModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header justify-content-center" style="border-bottom:none; padding: 8px;">
                <h5 id="shop_names_data"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center" style="padding: 0px;     margin: auto;" id="modelShow">
                <img src="{!! asset('img/logo-main.png') !!}" class="w-100 " id="modelImageEle" />
            </div>
            <div class="modal-footer pr-0 mt-3 mr-2">
            </div>
        </div>
    </div>
</div>


@section('scripts')
<script>
    var csrfToken = "{{csrf_token()}}";
    var userRole = "{{ $role }}";

    function showImage(imageSrc, type, main_name, shop_name, business_link, social_name, post_in, id, display_video){
        // Get the modal
        $('#modelShow').html('');
        var validExtensions = ["jpg","jpeg","gif","png"];
        var extension = imageSrc.split('.').pop().toLowerCase();
        if(imageSrc){
            if($.inArray(extension, validExtensions) == -1){
                $('#modelImageEle').remove();
                $('#modelShow').html('<video width="450" height="300" controls poster="" id="modelVideoEle"><source src="'+imageSrc+'" type="video/mp4">Your browser does not support the video tag.</video>');
            }else{
                $('#modelVideoEle').remove();
                $('#modelShow').html('<img src="'+imageSrc+'" class="w-100 " id="modelImageEle" />');
            }
            var shop_names_data = "";
            if (main_name!=""){
                shop_names_data += `Activate name: <span onclick="copyTextLink('${main_name}','${main_name}')" style="cursor:pointer;">${main_name} </span>`;
            }
            if (shop_name!=""){
                shop_names_data += `Shop name: <span onclick="copyTextLink('${shop_name}','${shop_name}')" style="cursor:pointer;">${shop_name}</span>`;
            }
            $("#PostPhotoModal").find("#shop_names_data").html(shop_names_data);

            var html_footer = "";
            if(type=="video") {
                var checked_display_video = "";
                if(display_video==1){
                    checked_display_video = "checked";
                }
                html_footer += `<div class="custom-checkbox custom-control">
                                <input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-display-video" post-in="${post_in}" post-id="${id}" ${checked_display_video}>
                                <label for="checkbox-display-video" class="custom-control-label">Display video</label>
                            </div>`;
            }
            if(userRole!="Sub Admin") {
                if (business_link != "") {
                    html_footer += `<a href="javascript:void(0);" onClick="copyTextLink('${business_link}','Business link')" class="mr-2 btn btn-primary btn-sm">Open link</a>`;
                }
                if (social_name != "" && social_name != null) {
                    html_footer += `<a href="https://www.instagram.com/${social_name}" class="mr-2 btn btn-primary btn-sm" target="_blank">${social_name}</a>`;
                }
                html_footer += `<button type="button" class="btn btn-primary" id="download_post_btn" file-url="${imageSrc}">Download</button>`;
            }
            $("#PostPhotoModal").find(".modal-footer").html(html_footer);
            $("#PostPhotoModal").modal('show');
        }
    }

    $( window ).on( "load",function() {
        mainImagesFiles = JSON.parse($('#imagesFile').val());
    });

    $(document).on('submit',"#settingForm",function(event){
        event.preventDefault();

        $('label.error').remove();
        var formData = new FormData(this);

        if(mainImagesFiles){
            $.map(mainImagesFiles, function(file, index) {
                formData.append('main_language_image[]', file);
            });
        }

        formData.append('image_count', mainImagesFiles.length);

        $.ajax({
            url: $(this).attr('action'),
            type:"POST",
            contentType: false,
            processData: false,
            data: formData,
            beforeSend: function() {
                $('.cover-spin').show();
            },
            success:function(response) {
                $('.cover-spin').hide();
                if(response.success == true){
                    iziToast.success({
                        title: '',
                        message: response.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });

                    setTimeout(function(){
                        window.location.href = response.redirect;
                    },1000);

                }else {
                    iziToast.error({
                        title: '',
                        message: 'Portfolio has not been created successfully.',
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1500,
                    });
                }
            },
            error:function (response, status) {
                $('.cover-spin').hide();
                if( response.responseJSON.success === false ) {
                    var errors = response.responseJSON.errors;

                    $.each(errors, function (key, val) {
                        //console.log(val);
                        var errorHtml = '<label class="error">'+val+'</label>';
                        if(key == 'main_language_image'){
                            $('.main_image_file').each(function(index, upload) {
                                if(!mainImagesFiles.length){
                                    $(upload).parent().parent().after(errorHtml);
                                }
                            });
                        }else{
                            $('#'+key).parent().append(errorHtml);
                        }
                    });
                }
            }
        });
    });

    $(document.body).on('click','.removeImage > span',function(){
        var imageid = $(this).attr('data-imageid');

        if(imageid){
            $.ajax({
                url: baseUrl + "/admin/shoppost/image/remove",
                method: 'POST',
                data: {
                    _token: csrfToken,
                    imageid : imageid,
                },
                beforeSend: function() {
                },
                success: function(data) {
                    mainImagesFiles.splice( $.inArray(imageid, mainImagesFiles), 1 );
                    $('#settingForm').submit();
                }
            });
        }
        $(this).parent().remove();
    });

    $(document).on('click', '#download_post_btn', function (){
        var url = $(this).attr('file-url');
        forceDownload2(url, url.substring(url.lastIndexOf('/')+1,url.length));
    })

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

    $(document).on('click','#checkbox-display-video',function (){
        var display_video;
        if (this.checked) {
            this.checked = true;
            display_video = 1;
        } else {
            this.checked = false;
            display_video = 0;
        }
        $(this).prop('disabled',true);
        var thi = $(this);
        var post_in = $(this).attr('post-in');
        var post_id = $(this).attr('post-id');

        $.ajax({
            url: "{{ route('admin.shoppost.edit.display-video') }}",
            method: 'POST',
            data: {
                '_token': "{{ csrf_token() }}",
                post_in : post_in,
                post_id : post_id,
                display_video : display_video,
            },
            beforeSend: function() {
            },
            success: function(res) {
                $(thi).prop('disabled',false);
                showToastMessage(res.message, res.success);
                location.reload();
            },
            error: function(response) {
                $(thi).prop('disabled',false);
                showToastMessage("Failed to update display video.",false);
            }
        });
    });
</script>

<script src="{!! asset('js/file-upload.js') !!}"></script>
@endsection
