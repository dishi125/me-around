@extends('layouts.app')

@section('styles')
    <style>
        .section-body textarea.form-control {
            height: auto !important;
        }
    </style>
@endsection


@section('header-content')
    <h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection
<?php
$multiple_shop_posts = $community_data->images ?? [];
$title = $community_data->title ?? '';
$description = $community_data->description ?? '';
$category_id = $community_data->category_id ?? '';
$matchId = $community_data->associations_id ?? '';
if ($type == 'category') {
    $catData = DB::table('category')->whereId($category_id)->first();
    $matchId = $catData->parent_id ?? '';
}
$imagesfile = json_encode($multiple_shop_posts);

$disabled = (!empty($category_id)) ? 'disabled' : '';
?>

@section('content')
    <div class="section-body">
        <div class="row mt-sm-4">
            <div class="col-12 col-md-12 col-lg-9">
                <div class="card profile-widget">
                    <div class="profile-widget-description">
                        <div class="">
                            {!! Form::open(['route' => array('admin.user.community.create',$id), 'id' => 'userCommunityForm', 'enctype' => 'multipart/form-data']) !!}
                            @csrf

                            {!! Form::hidden('user_id',$id) !!}
                            {!! Form::hidden('community_id',$community_id) !!}
                            {{ Form::hidden('imagesFile',$imagesfile, array('id' => 'imagesFile')) }}
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            {!! Form::label('title', __(Lang::get('forms.community.title'))) !!}
                                            {!! Form::text('title', $title, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.community.title'))]) !!}
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            {!! Form::label('description', __(Lang::get('forms.community.description'))) !!}
                                            {!! Form::textarea('description',$description,['class'=>'form-control','rows' => 6, 'placeholder' => __(Lang::get('forms.community.description'))]) !!}
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            {!! Form::label('category', __(Lang::get('forms.community.category'))) !!}
                                            <select {{$disabled}} editcategoryid="{{$category_id}}" id="category"
                                                    ajaxurl="{{route('admin.user.community.load.category')}}"
                                                    name="category" class="form-control">
                                                <option value="">Select</option>
                                                @foreach($community_tabs as $tabs)
                                                    <option
                                                        value="{{$tabs['type'].'_'.$tabs['id']}}" {{$tabs['type'].'_'.$tabs['id'] == $type.'_'.$matchId ? 'selected' : ''}}>{{$tabs['name']}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            {!! Form::label('subcategory', __(Lang::get('forms.community.subcategory'))) !!}
                                            <select name="subcategory" id="subcategory" class="form-control">

                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="d-flex align-items-center imagesupload">
                                        <div class="form-group">
                                            <label for="name">{!! __(Lang::get('forms.posts.image')); !!}</label>
                                            <div class="">
                                                <div id="image_preview" class="d-flex align-items-center flex-wrap">
                                                    @if(isset($multiple_shop_posts))
                                                        @foreach($multiple_shop_posts as $photo)
                                                            <div class="removeCommunityImage mb-3">
                                                                <span class="pointer" deletetype="{{$type}}" data-imageid="{{$photo->id}}"><i
                                                                        class="fa fa-times-circle fa-2x"></i></span>
                                                                <div
                                                                    onclick="showImage(`{{$photo->image}}`,'image')"
                                                                    style="background-image: url({{$photo->image}});"
                                                                    class="pointer bgcoverimage">
                                                                    <img src="{!! asset('img/noImage.png') !!}">
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                                <div class="add-image-icon">
                                                    {{ Form::file("main_image",[ 'accept'=>"image/jpg,image/png,image/jpeg", 'onchange'=>"imagesPreview(this, '#image_preview', 'no');", 'class' => 'main_image_file form-control', 'multiple' => 'multiple', 'id' => "main_image", 'hidden' => 'hidden' ]) }}
                                                    <label class="pointer" for="main_image"><i
                                                            class="fa fa-plus fa-4x"></i></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="card-footer">
                                <button type="submit"
                                        class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                                <a href="{{ route('admin.user.show-community',[$id]) }}"
                                   class="btn btn-default">{{ __(Lang::get('general.cancel')) }}</a>
                            </div>
                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
<div class="cover-spin"></div>
@section('scripts')
    <script src="{!! asset('js/file-upload.js') !!}"></script>
    <script>
        var csrfToken = "{{csrf_token()}}";


        $( window ).on( "load",function() {
            mainImagesFiles = JSON.parse($('#imagesFile').val());
        });

        $(document.body).on('click','.removeCommunityImage > span',function(){
            var imageid = $(this).attr('data-imageid');
            var deletetype = $(this).attr('deletetype');

            if(imageid && deletetype){
                $.ajax({
                    url: baseUrl + "/admin/users/community/remove/image",
                    method: 'POST',
                    data: {
                        _token: csrfToken,
                        imageid : imageid,
                        deletetype : deletetype,
                    },
                    beforeSend: function() {
                    },
                    success: function(data) {
                        mainImagesFiles.splice( $.inArray(imageid, mainImagesFiles), 1 );
                    }
                });
            }
            $(this).parent().remove();
        });

        $(document).on('submit', "#userCommunityForm", function (event) {
            event.preventDefault();

            $('label.error').remove();
            var formData = new FormData(this);

            if (mainImagesFiles) {
                $.map(mainImagesFiles, function (file, index) {
                    formData.append('main_language_image[]', file);
                });
            }

            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                contentType: false,
                processData: false,
                data: formData,
                beforeSend: function () {
                    $('.cover-spin').show();
                },
                success: function (response) {
                    $('.cover-spin').hide();
                    if (response.success == true) {
                        iziToast.success({
                            title: '',
                            message: response.message,
                            position: 'topRight',
                            progressBar: false,
                            timeout: 1000,
                        });

                        setTimeout(function () {
                            window.location.href = response.redirect;
                        }, 1000);

                    } else {
                        iziToast.error({
                            title: '',
                            message: 'Community has not been created successfully.',
                            position: 'topRight',
                            progressBar: false,
                            timeout: 1500,
                        });
                    }
                },
                error: function (response, status) {
                    $('.cover-spin').hide();
                    if (response.responseJSON.success === false) {
                        var errors = response.responseJSON.errors;

                        $.each(errors, function (key, val) {
                            //console.log(val);
                            var errorHtml = '<label class="error">' + val + '</label>';
                            if (key == 'main_language_image') {
                                $('.main_image_file').each(function (index, upload) {
                                    if (!mainImagesFiles.length) {
                                        $(upload).parent().parent().after(errorHtml);
                                    }
                                });
                            } else {
                                $('#' + key).parent().append(errorHtml);
                            }
                        });
                    }
                }
            });
        });

        <?php if (!empty($category_id)) {
            echo 'setTimeout(function(){  $("#category").trigger("change"); }, 500);';
        } ?>

        $(document).on('change', 'select[name="category"]', function () {
            var selectedType = $(this).val();
            var editcategoryid = $(this).attr('editcategoryid');
            $.ajax({
                url: $(this).attr('ajaxurl'),
                type: "POST",
                data: {category: selectedType, editcategoryid: editcategoryid},
                beforeSend: function () {
                    $('.cover-spin').show();
                },
                success: function (response) {
                    $('.cover-spin').hide();
                    if (response.success == true) {
                        $("#subcategory").html(response.html);
                    }
                },
            });
        });

    </script>
@endsection
