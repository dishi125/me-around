@extends('layouts.app')
@section('styles')
<link rel="stylesheet" href="{!! asset('css/custom.css') !!}">
@endsection
@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')

<?php
$data = (object)array();

if (!empty($brand)) {
    $data = $brand;
}

$id = !empty($data->id) ? $data->id : '' ;
$name= !empty($data->name) ? $data->name : '' ;
$category_id= !empty($data->category_id) ? $data->category_id : '' ;
$imageURL = '';

?>
<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-12">
            <div class="card profile-widget">
                <div class="profile-widget-description">
                    <div class="">
                        @if ( isset($brand ))
                            {!! Form::open(['route' => ['admin.brands.update', $id], 'id' =>"categoryUpdateForm", 'method' => 'put', 'enctype' => 'multipart/form-data']) !!}
                        @else
                            {!! Form::open(['route' => 'admin.brands.store', 'id' =>"categoryForm", 'enctype' => 'multipart/form-data']) !!}
                        @endif
                            @csrf
                            {!! Form::hidden('is_image_remove','0') !!}
                            <div class="card-body">
                            <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('name', __(Lang::get('forms.brands.name'))); !!}
                                            {!! Form::text('name', $name, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.brands.name')) ]); !!}
                                            @error('name')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('name') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('category_id', __(Lang::get('forms.brands.category'))); !!}
                                        {!!Form::select('category_id', $category, $category_id , ['class' => 'form-control select2','placeholder' => __(Lang::get('forms.brands.category'))])!!}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('brand_logo', __('Brand Logo')); !!}
                                        <div class="d-flex align-items-center">
                                            <div id="image_preview" class="d-flex">
                                                @if($id)
                                                    <?php
                                                        $imageURL = $brand->brand_logo ? Storage::disk('s3')->url($brand->brand_logo) : '';
                                                        
                                                        if(!empty($imageURL)){
                                                    ?>

                                                        <div class="removeImage" >
                                                            <span class="pointer" data-id="yes"><i class="fa fa-times-circle fa-2x"></i></span>
                                                            <div style="background-image: url({{$imageURL}});" class="bgcoverimage">
                                                                <img src="{!! asset('img/noImage.png') !!}">
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="add-image-icon" style="display: {{($imageURL) ? 'none' : 'flex'}};" >
                                            {{ Form::file('brand_logo',[ 'accept'=>"image/jpg,image/png,image/jpeg", 'onchange'=>"imagesPreview(this, '#image_preview');", 'class' => 'main_image_file form-control', 'id' => "brand_logo", 'hidden' => 'hidden' ]) }}
                                            <label class="pointer" for="brand_logo"><i class="fa fa-plus fa-4x"></i></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                                
                            </div>
                            <div class="card-footer text-right">
                                <button type="submit"
                                    class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                                <a href="{{ route('admin.brands.index')}}"
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
@section('scripts')

<script>
    var mainImagesFiles = [];
    var base_url = "{{ url('/admin') }}";
    $('#categoryForm').validate({
        rules: {
            'name': {
                required: true
            },
            'category_id': {
                required: true
            }
        },
        messages: {
            'name':'This field is required',
            'category_id':'This field is required',
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

    $('#categoryUpdateForm').validate({
        rules: {
            'name': {
                required: true
            },
            'category_id': {
                required: true
            },
        },
        messages: {
            'name':'This field is required',
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

    $(document).ready(function(){
        
    });

    $(document.body).on('click','.removeImage > span',function(){
        var timestemp = $(this).attr('data-timestemp');
        var isID = $(this).attr('data-id');
        if(isID){
            $('input[name="is_image_remove"]').val(1);
        }
        if(timestemp){
            mainImagesFiles = $.grep(mainImagesFiles, function(e){ 
                return e.timestemp != timestemp; 
            });
            if(!mainImagesFiles.length){
                $("#brand_logo").val('');
            }
            console.log(mainImagesFiles)
        }
        $(this).parent().remove();
        $( ".add-image-icon" ).show();
        $('.maxerror').remove();
    });


function imagesPreview(input, placeToInsertImagePreview, is_multi) {
    console.log(is_multi);
    var noImage = baseUrl + "/public/img/noImage.png";
    mainImagesFiles = mainImagesFiles || [];

    if (input.files) {
        $('.maxerror').remove();
        Array.from(input.files).forEach(async (file,index) => { 
            console.log(index)
            var currentTimestemp = new Date().getTime()+''+index;
            file.timestemp = currentTimestemp;
            
            var reader = await new FileReader(file);
            reader.onload = function(event) {
                var bgImage = $($.parseHTML('<div>')).attr('style', 'background-image: url('+event.target.result+')').addClass("bgcoverimage").wrapInner("<img src='"+noImage+"' />"); 
                var container = jQuery("<div></div>",{class: "removeImage", html:'<span fieldname="brand_logo" data-timestemp="'+currentTimestemp+'" class="pointer"><i class="fa fa-times-circle fa-2x"></i></span>'});
                container.append(bgImage);
                $(placeToInsertImagePreview).html(container);
                
            }
            reader.readAsDataURL(file);
            if(!is_multi){
                $(input).parent().hide();
            }
        });
    }
};
</script>
@endsection