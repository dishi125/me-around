@extends('layouts.app')
@section('styles')
<link rel="stylesheet" href="{!! asset('css/custom.css') !!}">
<link rel="stylesheet" href="{!! asset('plugins/bootstrap-daterangepicker/daterangepicker.css') !!}">
@endsection
@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')

<?php
$fromDate = \Carbon\Carbon::now()->format('Y-m-d');
$toDate = \Carbon\Carbon::now()->addDays(1)->format('Y-m-d');
$emptyObject = [
    'id' => '',
    'from_date' => $fromDate,
    'to_date' => $toDate,
    'before_price' => '',
    'final_price' => '',
    'currency_id' => '',
    'discount_percentage' => '',
    'category_id' => '',
    'title' => '',
    'sub_title' => '',
    'is_discount' => 0,
    'thumbnail_url' => (object)['image' => ''],
    'main_images' => []
];
$isUpdate = (!empty($post)) ? true : false;
$post = (!empty($post)) ? $post : (object)$emptyObject;

$bladeVar = !empty($bladeVar) ? $bladeVar : '';
$imagesArray = !empty($imagesArray) ? $imagesArray : [];
?>

<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-12">
            <div class="card profile-widget">
                <div class="profile-widget-description">
                    <div class="">
                        @if ($isUpdate)
                            {!! Form::open(['route' => ['admin.business-client.posts.update', $post->id], 'id' =>"postUpdateForm", 'method' => 'put', 'enctype' => 'multipart/form-data']) !!}
                        @else
                            {!! Form::open(['route' => 'admin.business-client.posts.store', 'id' =>"postForm", 'enctype' => 'multipart/form-data']) !!}
                        @endif
                            @csrf
                            @if (!$isUpdate)
                                {{ Form::hidden('hospital_id', $id, array('id' => 'hospital_id')) }}
                            @endif
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('period', __(Lang::get('forms.posts.period'))); !!}
                                            {!! Form::text('period', $post->from_date.' to '.$post->to_date, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.posts.period')) ]); !!}
                                            @error('period')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('period') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                    
                                        <div class="form-group">
                                            
                                            {!! Form::label('before_price', __(Lang::get('forms.posts.before_price'))); !!}
                                            {!! Form::number('before_price', $post->before_price, ['min'=>1, 'class' => 'form-control', 'placeholder' => __(Lang::get('forms.posts.before_price')) ]); !!}
                                            @error('before_price')
                                                {!! $errors->first('before_price', '<label class="error">:message</label>') !!}
                                            @enderror                                            
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            {!! Form::label('final_price', __(Lang::get('forms.posts.final_price'))); !!}
                                            {!! Form::number('final_price', $post->final_price, ['min'=>1, 'class' => 'form-control', 'placeholder' => __(Lang::get('forms.posts.final_price')) ]); !!}
                                            @error('final_price')
                                                {!! $errors->first('final_price', '<label class="error">:message</label>') !!}
                                            @enderror    
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('currency_id', __(Lang::get('forms.posts.currency_id'))); !!}
                                            {{ Form::select('currency_id', $allCurrency, $post->currency_id, ['id' => 'currency_id', 'class' => 'form-control', 'placeholder' => __(Lang::get('forms.posts.currency_id'))]) }}
                                            @error('currency_id')
                                                {!! $errors->first('currency_id', '<label class="error">:message</label>') !!}
                                            @enderror 
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('discount_percentage', __(Lang::get('forms.posts.discount_percentage'))); !!}
                                            {!! Form::number('discount_percentage', $post->discount_percentage, ['min'=>1, 'class' => 'form-control', 'placeholder' => __(Lang::get('forms.posts.discount_percentage')) ]); !!}
                                            @error('discount_percentage')
                                                {!! $errors->first('discount_percentage', '<label class="error">:message</label>') !!}
                                            @enderror 
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('category_id', __(Lang::get('forms.posts.category_id'))); !!}
                                            {{ Form::select('category_id', $categorySelect, $post->category_id, ['id' => 'category_id', 'class' => 'form-control', 'placeholder' => __(Lang::get('forms.posts.category_id'))]) }}
                                            @error('category_id')
                                                {!! $errors->first('category_id', '<label class="error">:message</label>') !!}
                                            @enderror 
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('title', __(Lang::get('forms.posts.title'))); !!}
                                            {!! Form::text('title', $post->title, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.posts.title')) ]); !!}
                                            @error('title')
                                                {!! $errors->first('title', '<label class="error">:message</label>') !!}
                                            @enderror 
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('sub_title', __(Lang::get('forms.posts.sub_title'))); !!}
                                            {!! Form::text('sub_title', $post->sub_title, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.posts.sub_title')) ]); !!}
                                            @error('sub_title')
                                                {!! $errors->first('sub_title', '<label class="error">:message</label>') !!}
                                            @enderror 
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('is_discount', __(Lang::get('forms.posts.is_discount'))); !!}
                                            {!! Form::label('is_discount_yes', 'Yes' ); !!}
                                            {{ Form::radio('is_discount', '1' , $post->is_discount == true, ['class' => 'form-radio', 'id' => 'is_discount_yes' ]) }} 
                                            {!! Form::label('is_discount_no', 'No' ); !!}
                                            {{ Form::radio('is_discount', '0' , $post->is_discount == false, ['class' => 'form-radio', 'id' => 'is_discount_no' ]) }} 
                                            @error('is_discount')
                                                {!! $errors->first('is_discount', '<label class="error">:message</label>') !!}
                                            @enderror 
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            {!! Form::label('thumbnail_image', __(Lang::get('forms.posts.thumbnail_image'))); !!}
                                            <div class="d-flex thumb-block">
                                                @if($isUpdate && $post->thumbnail_url && isset($post->thumbnail_url->image) && !empty($post->thumbnail_url->image))
                                                {{ Form::hidden('has_thumb', $post->thumbnail_url->image) }}
                                                <div class="post-thumb-img" style="background-image: url({{ $post->thumbnail_url->image }});">
                                                    <img src="{!! asset('img/noImage.png') !!}" alt="{{$post->title}}" />
                                                </div>
                                                @endif
                                                <div>
                                                    {{ Form::file('thumbnail_image',['accept'=>"image/jpg,image/png,image/jpeg", 'class' => 'form-control', 'id' => 'thumbnail_image' ]) }} 
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group post_languages">
                                            {!! Form::label('post_languages', __(Lang::get('forms.posts.post_languages'))); !!}
                                            {{ Form::select('post_languages[]', $postLanguages, $selectedLanguage, ['multiple'=>'multiple', 'id' => 'post_languages', 'class' => 'mr-2 select2 form-control']) }}
                                            @error('post_languages')
                                                {!! $errors->first('post_languages', '<label class="error">:message</label>') !!}
                                            @enderror 
                                        </div>
                                    </div>
                                    <div class="col-md-12 language-images">
                                        @if(Request::old('post_languages'))
                                            @foreach(Request::old('post_languages') as $languageID)
                                                @include('business.posts.language-image', ['id' => $languageID, 'text' => $postLanguages[$languageID] ])
                                            @endforeach
                                        @endif
                                        @if(count($post->main_images))
                                            @foreach($post->main_images as $images)
                                                @include('business.posts.language-image', ['id' => $images['language_id'], 'text' => $images['language_name'], 'photos' => $images['photos']])
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                                <div class="card-footer text-right">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __(Lang::get('general.save')) }}
                                    </button>
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
@section('scripts')

<script src="{!! asset('plugins/bootstrap-daterangepicker/daterangepicker.js') !!}"></script>

<script>
    var mainImagesFiles = <?php echo json_encode($imagesArray); ?>;
    

    <?php if(!empty($post->id)){ ?>
    $(document.body).on('submit','#postUpdateForm',function(event){
        event.preventDefault();
        $('label.error').remove();

        var form = $('#postUpdateForm')[0]; 
        var formData = new FormData(this);

        $.map(mainImagesFiles, function(value, index) {
            value.forEach(file => {
                formData.append('main_language_image['+index+'][]', file);
            })
            return [value];
        });
                
        console.log(mainImagesFiles);
        $.ajax({
            url:  $(this).attr('action'),
            type: 'POST',
            contentType: false, 
            processData: false,
            data: formData,
            beforeSend: function() {
                $('.cover-spin').show();
            },
            success: function(data) {
                $('.cover-spin').hide();
                if(data.success == true) {
                    iziToast.success({
                        title: '',
                        message: data.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1500,
                    });
                }else {
                    iziToast.error({
                        title: '',
                        message: data.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1500,
                    });
                }

                if(data.redirect){
                    setTimeout(function(){ 
                        window.location.href = data.redirect;
                    }, 1500);
                }
                

            },
            error: function(errors){
                $('.cover-spin').hide();
                var errorsMsg = errors.responseJSON.errors;
                $.each(errorsMsg, function(key,valueObj){
                    var errorHtml = '<label class="error">'+valueObj+'</label>';
                    $('#'+key).parent().append(errorHtml);
                    if(key == 'main_language_image'){                       
                        $('.main_image_file').each(function(index, upload) {
                            console.log(upload)
                            var languageID = $(upload).attr('data-languageid');
                            if(!mainImagesFiles[languageID].length){
                                $(upload).parent().parent().after(errorHtml);
                            }
                        });
                    }
                });
            }
        });
    });

    <?php }else{ ?>
    $(document.body).on('submit','#postForm',function(event){
        event.preventDefault();
        $('label.error').remove();

        var form = $('#postForm')[0]; 
        var formData = new FormData(this);
        mainImagesFiles.forEach((val,index) => {
            val.forEach(file => {
                formData.append('main_language_image['+index+'][]', file);
            })            
        })
        
        console.log(mainImagesFiles);
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            contentType: false, 
            processData: false,
            data: formData,
            beforeSend: function() {
                $('.cover-spin').show();
            },
            success: function(data) {
                $('.cover-spin').hide();
                if(data.success == true) {
                    iziToast.success({
                        title: '',
                        message: data.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1500,
                    });
                }else {
                    iziToast.error({
                        title: '',
                        message: data.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1500,
                    });
                }

                if(data.redirect){
                    setTimeout(function(){ 
                        window.location.href = data.redirect;
                    }, 1500);
                }
                

            },
            error: function(errors){
                $('.cover-spin').hide();
                var errorsMsg = errors.responseJSON.errors;
                $.each(errorsMsg, function(key,valueObj){
                    var errorHtml = '<label class="error">'+valueObj+'</label>';
                    $('#'+key).parent().append(errorHtml);
                    if(key == 'main_language_image'){                       
                        $('.main_image_file').each(function(index, upload) {
                            console.log(upload)
                            if(!upload.files.length){
                                $(upload).parent().parent().after(errorHtml);
                            }
                        });
                    }
                });
            }
        });
    });
    <?php } ?>
    var loadImageHtml = function(languageid, selected) {
        selected = selected || true;
        $.ajax({
            url: "{{ route('business.post.add-image') }}",
            method: 'POST',
            data: {
                '_token': "{{csrf_token()}}",
                selected : selected,
                id : languageid,
            },
            beforeSend: function() {
                // setting a timeout
                $('.cover-spin').show();
            },
            success: function(data) {
                $('.cover-spin').hide();
                $('.language-images').append(data);

            }
        });
    };
    /* var isSelectedLang = $("#post_languages").val();
    if(isSelectedLang.length){
        $.each(isSelectedLang, function( index, value ) {
            loadImageHtml(value,true);
        });
    } */
    
    $('input[name="period"]').daterangepicker({        
        locale: {
            format: 'YYYY-MM-DD',
            separator : ' to ',
        }
    });

    $('#post_languages').on('select2:unselect', function (e) {
        var data = e.params.data;

        var languageID = data.id;
        if(data.selected == false && languageID){
            $( "div[data-language='"+languageID+"']" ).remove();
        }
    });
    $('#post_languages').on('select2:select', function (e) {
        var ajaxData = e.params.data;
        loadImageHtml(ajaxData.id,ajaxData.selected);
    });

    $(document.body).on('click','.removeImage > span',function(){
        var postid = $(this).attr('data-postid');
        var imageid = $(this).attr('data-imageid');
        var timestemp = $(this).attr('data-timestemp');
        var languageID = $(this).attr('data-languageID');
        if(languageID && timestemp){
            mainImagesFiles[languageID] = $.grep(mainImagesFiles[languageID], function(e){ 
                return e.timestemp != timestemp; 
            });
            if(!mainImagesFiles[languageID].length){
                $("#main_image_"+languageID).val('');
            }
            console.log(mainImagesFiles)
        }

        if(postid && imageid){
            $.ajax({
                url: "{{ route('business.post-image.remove') }}",
                method: 'POST',
                data: {
                    '_token': "{{csrf_token()}}",
                    postid : postid,
                    imageid : imageid,
                },
                beforeSend: function() {
                },
                success: function(data) {
                }
            });
            
            mainImagesFiles[languageID] = $.grep(mainImagesFiles[languageID], function(e){ 
                return JSON.parse(e).id != imageid; 
            });
            if(!mainImagesFiles[languageID].length){
                $("#main_image_"+languageID).val('');
            }
            console.log(mainImagesFiles)
        }
        $(this).parent().remove();
    });


    // Html function
    

    var imagesPreview = async function(input, placeToInsertImagePreview, languageID) {

        mainImagesFiles[languageID] = mainImagesFiles[languageID] || [];
        console.log(input)
        if ((parseInt(input.files.length) + parseInt(mainImagesFiles[languageID].length)) > 5){
            var errorMsg = '<label class="error">You can only upload a maximum of 5 files.</label>';
            $(input).parent().parent().parent().find('label.error').remove();
            $(input).parent().parent().after(errorMsg);
            $("#main_image_"+languageID).val('');
        }else{

        

            if (input.files) {

                Array.from(input.files).forEach(async (file,index) => { 
                    console.log(index)
                    var currentTimestemp = new Date().getTime()+''+index;
                    file.timestemp = currentTimestemp;
                    mainImagesFiles[languageID].push(file);
                    var reader = await new FileReader(file);
                    reader.onload = function(event) {
                        var bgImage = $($.parseHTML('<div>')).attr('style', 'background-image: url('+event.target.result+')').addClass("bgcoverimage").wrapInner("<img src='{!! asset('img/noImage.png') !!}' />"); 
                        var container = jQuery("<div></div>",{class: "removeImage", html:'<span data-languageID="'+languageID+'" data-timestemp="'+currentTimestemp+'" class="pointer"><i class="fa fa-times-circle fa-2x"></i></span>'});
                        container.append(bgImage);
                        container.appendTo(placeToInsertImagePreview);
                    }
                    reader.readAsDataURL(file);
                });
            }
        }
    };

</script>
@endsection