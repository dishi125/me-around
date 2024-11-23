@extends('layouts.app')
@section('styles')
<link rel="stylesheet" href="{!! asset('css/custom.css') !!}">
@endsection
@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')

<?php
$shopCategory = \App\Models\CategoryTypes::SHOP;
$customCategory = \App\Models\CategoryTypes::CUSTOM;
$customCategory2 = \App\Models\CategoryTypes::CUSTOM2;
$shopCategory2 = \App\Models\CategoryTypes::SHOP2;
$data = (object)array();
if (!empty($category)) {
    $data = $category;
}
$id = !empty($data->id) ? $data->id : '' ;
$name= !empty($data->name) ? $data->name : '' ;
$category_type_id = !empty($data->category_type_id) ? $data->category_type_id : '' ;
$parent_id = !empty($data->parent_id) ? $data->parent_id : '' ;
$status_id = !empty($data->status_id) ? $data->status_id : \App\Models\Status::INACTIVE ;
$is_hidden = isset($data->is_hidden) ? $data->is_hidden : 1;
$is_show = isset($data->is_show) ? $data->is_show : 0;
$order = !empty($data->order) ? $data->order : 0 ;

$routeParams = [];
if(in_array($category_type_id,[$customCategory, $customCategory2, $shopCategory2])){
    $routeParams = ['custom'=>$category_type_id];
}

$route = $category_type_id != '' ? config('constant.category_url_'.$category_type_id) : config('constant.category_url_2');
?>
<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-12">
            <div class="card profile-widget">
                <div class="profile-widget-header">
                    @if (!empty($category) && ($category->category_type_id == $shopCategory || $category->category_type_id == $customCategory))
                    <img alt="image"
                        src="@if($category->logo) {{ asset($category->logo) }} @else {!! asset('img/avatar/avatar-1.png') !!} @endif"
                        class="rounded-circle profile-widget-picture">
                    @endif
                </div>
                <div class="profile-widget-description">
                    <div class="">
                        @if ( isset($category ))
                            {!! Form::open(['route' => ['admin.category.update', $id], 'id' =>"categoryUpdateForm", 'method' => 'put', 'enctype' => 'multipart/form-data']) !!}
                        @else
                            {!! Form::open(['route' => 'admin.category.store', 'id' =>"categoryForm", 'enctype' => 'multipart/form-data']) !!}
                        @endif
                            @csrf
                            <div class="card-body">
                            <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('name', __(Lang::get('forms.category.name')).'(English)'); !!}
                                            {!! Form::text('name', $name, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.category.name')).'(English)' ]); !!}
                                            @error('name')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('name') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                @foreach($postLanguages as $postLanguage)
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <?php
                                                $label = __(Lang::get('forms.category.name')).'('.$postLanguage->name.')';
                                                $cName = array_key_exists($postLanguage->id,$categoryLanguages) ? $categoryLanguages[$postLanguage->id] : '';
                                            ?>
                                            {!! Form::label('cname', $label); !!}
                                            {!! Form::text('cname['.$postLanguage->id.']', $cName, ['class' => 'form-control', 'placeholder' => $label ]); !!}
                                            @error('name')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('name') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('category_type_id', __(Lang::get('forms.category.category-type'))); !!}
                                            {!! Form::select('category_type_id', $categoryType, $category_type_id,  ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.category.select-category-type'))]); !!}
                                            @error('category_type_id')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('category_type_id') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                    @if(!in_array($name,$parentCat))
                                        <div class="col-md-6" id="parent_id_row">
                                            <div class="form-group">
                                                {!! Form::label('parent_id', __(Lang::get('forms.category.parent'))); !!}
                                                {!! Form::select('parent_id', $parentCat, $parent_id,  ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.category.select-parent'))]); !!}
                                                @error('parent_id')
                                                <div class="invalid-feedback">
                                                    {{ $errors->get('parent_id') }}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>
                                    @endif

                                    <div class="form-group col-md-6" id="logo_row">
                                        {!! Form::label('logo', __(Lang::get('forms.category.logo'))); !!}
                                        {!! Form::file('logo',  ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.category.logo')) ]); !!}
                                        @error('logo')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('order', __(Lang::get('forms.category.order'))); !!}
                                            {!! Form::number('order', $order, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.category.order')) ]); !!}
                                            @error('order')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('order') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('status_id', __(Lang::get('forms.category.status'))); !!}
                                            {!! Form::select('status_id', $statusData, $status_id,  ['class' => 'form-control']); !!}
                                            @error('status_id')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('status_id') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('is_hidden', __(Lang::get('forms.category.visibility'))); !!}
                                            {!! Form::select('is_hidden', array(0 => 'Visible', 1 => 'Hidden'), $is_hidden,  ['class' => 'form-control']); !!}
                                            @error('is_hidden')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('is_hidden') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('is_show', __(Lang::get('forms.category.show_cat'))); !!}
                                            {!! Form::select('is_show', array(1 => 'Show', 0 => 'Hide'), $is_show,  ['class' => 'form-control']); !!}
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="card-footer text-right">
                                <button type="submit"
                                    class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                                <a href="{{ route($route,$routeParams)}}"
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
    var base_url = "{{ url('/admin') }}";
    var parentBlankSelect = "{{ __(Lang::get('forms.category.select-parent')) }}";
    var shopCategory = "{{ $shopCategory }}";
    var customCategory = "{{ $customCategory }}";
    var customCategory2 = "{{ $customCategory2 }}";
    var shopCategory2 = "{{ $shopCategory2 }}";
    $('#categoryForm').validate({
        rules: {
            'name': {
                required: true
            },
            'category_type_id' :{
                required: true
            },
            'status_id' :{
                required: true
            },
            'parent_id' :{
                required: function(element){
                    return ($("#category_type_id").val() != shopCategory || $("#category_type_id").val() != customCategory);
                },
            },
            'logo': {
                required: function(element){
                    return ($("#category_type_id").val() == shopCategory || $("#category_type_id").val() == customCategory);
                },
                accept: "image/jpg,image/jpeg,image/png,image/gif"
            },

        },
        messages: {
            'name':'This field is required',
            'category_type_id':'This field is required',
            'parent_id':'This field is required',
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
            'category_type_id' :{
                required: true
            },
            'order' :{
                required: true
            },
            'status_id' :{
                required: true
            },
            'parent_id' :{
                required: function(element){
                    return ($("#category_type_id").val() != shopCategory || $("#category_type_id").val() != customCategory);
                },
            },
            'logo': {
                accept: "image/jpg,image/jpeg,image/png,image/gif"
            },

        },
        messages: {
            'name':'This field is required',
            'category_type_id':'This field is required',
            'parent_id':'This field is required',
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
        var catSelcted = $('#category_type_id').val();
        if([shopCategory, shopCategory2, customCategory, customCategory2].includes(catSelcted) ) {
            $('#parent_id_row').hide();
        }else {
            $('#logo_row').hide();
        }
        $("#category_type_id").change(function(){
            var id = $(this).val();
            if([shopCategory, shopCategory2, customCategory, customCategory2].includes(id) ){
                $('#parent_id_row').hide();
                $('#logo_row').show();
            }
            else {
                $('#parent_id_row').show();
                $('#logo_row').hide();
                $("#parent_id").empty();
                $.ajax({
                   type:'GET',
                   url:baseUrl + '/admin/category/parent/' + id,
                   dataType: 'json',
                   success:function(data) {
                        $('#parent_id').append('<option value=""> '+ parentBlankSelect +' </option>');
                       $.each( data.category_data, function(key, value) {
                            $('#parent_id').append('<option value="'+ key +'">'+ value +'</option>');
                        });
                    }
                });
            }
        });
    });
</script>
@endsection
