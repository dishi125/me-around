@extends('layouts.app')
@section('styles')
<link rel="stylesheet" href="{!! asset('css/custom.css') !!}">
@endsection
@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')

<?php

$shop_id = !empty($shoppost['id']) ? $shoppost['id'] : '' ;
$images = []; //!empty($images) ? $images : [];
$imagesfile = json_encode($images);

?>
<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12">
            <div class="card profile-widget">
                
                <div class="profile-widget-description">
                    <div class="">
                        {!! Form::open(['route' => ['admin.business-client.shoppost.store',$id], 'id' =>"settingForm", 'method' => 'put', 'enctype' => 'multipart/form-data']) !!}
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-group">
                                            {!! Form::label('type', __(Lang::get('forms.association.type'))); !!}
                                            {!! Form::select('type', array('image' => 'Image', 'video' => 'Video'), ['class' => 'form-control'. ( $errors->has('type ') ? ' is-invalid' : '' ), 'placeholder' => __(Lang::get('forms.association.type')) ]); !!}
                                        </div>
                                        
                                        <div class="thumb_image" style="display:none;">
                                            <div class="form-group">
                                                {!! Form::label('post_item', __(Lang::get('forms.posts.video'))); !!}
                                                {!! Form::file('post_item', [ 'accept' => "video/mp4"  , 'class' => 'form-control'. ( $errors->has('post_item ') ? ' is-invalid' : '' ), 'placeholder' => __(Lang::get('forms.posts.video')) ]); !!}
                                                @error('post_item')
                                                <div class="error">
                                                    {{ $errors->first('post_item') }}
                                                </div>
                                                @enderror
                                            </div>
                                            <div class="form-group ">
                                                {!! Form::label('video_thumbnail', 'Video Thumbnail'); !!}
                                                {!! Form::file('video_thumbnail', [  'accept' => "image/jpg,image/png,image/jpeg", 'class' => 'form-control'. ( $errors->has('video_thumbnail ') ? ' is-invalid' : '' ), 'placeholder' => __('Video Thumbnail') ]); !!}
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center imagesupload">
                                            <div class="form-group">
                                                <label for="name">{!! __(Lang::get('forms.posts.image')); !!}</label>
                                                <div class="">
                                                    <div id="image_preview" class="d-flex align-items-center flex-wrap">
                                                    @if(isset($images))
                                                    @foreach($images as $photo)
                                                    <div class="removeImage mb-3">
                                                        @if(!isset($photo->post_type))
                                                            <span class="pointer" data-imageid="{{$photo->id}}"><i class="fa fa-times-circle fa-2x"></i></span>
                                                        @endif
                                                        <div style="background-image: url({{$photo->post_item}});" class="bgcoverimage">
                                                            <img src="{!! asset('img/noImage.png') !!}">
                                                        </div>
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
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            {!! Form::label('description', __(Lang::get('forms.association.description'))); !!}
                                            {!! Form::textarea('description', '', ['style' => 'height:200px !important;', 'class' => 'form-control'. ( $errors->has('description ') ? ' is-invalid' : '' ), 'placeholder' => __(Lang::get('forms.association.description')) ]); !!}
                                            @error('description')
                                            <div class="invalid-feedback">
                                                {{ $errors->first('description') }}
                                            </div>
                                            @enderror
                                        </div>
                                       
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                            <button type="submit"
                                                class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                                            <a href="{{ route('admin.business-client.shop.show',[$id])}}"
                                                class="btn btn-default">{{ __(Lang::get('general.cancel')) }}</a>
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
@section('scripts')
<script>
    $("select[name='type']").change(function (){
        var type = $(this).val();

        if(type == 'video'){
            $('.thumb_image').show();
            $('.imagesupload').hide();
            $('.imagesupload > .form-group').hide();
        }else{
            $('.thumb_image').hide();
            $('.imagesupload').show();
            $('.imagesupload > .form-group').show();
        }
    })

    $(document).on('submit',"#settingForm",function(event){
        event.preventDefault();
        
        $('label.error').remove();
        var formData = new FormData(this);

        if(mainImagesFiles){
            $.map(mainImagesFiles, function(file, index) {
                formData.append('main_language_image[]', file);
            });
        }

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
</script>

<script src="{!! asset('js/file-upload.js') !!}"></script>
@endsection