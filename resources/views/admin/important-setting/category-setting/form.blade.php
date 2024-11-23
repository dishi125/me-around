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
$data = (object)array();
if (!empty($englishCat)) {
    $data = $englishCat;
}
$id = !empty($data->id) ? $data->id : '' ;
$name= !empty($data->name) ? $data->name : '' ;
$category_type_id = !empty($data->category_type_id) ? $data->category_type_id : '' ;
$parent_id = !empty($data->parent_id) ? $data->parent_id : '' ;
$status_id = !empty($data->status_id) ? $data->status_id : \App\Models\Status::INACTIVE ;
$order = !empty($data->order) ? $data->order : 0 ;
$is_hidden = ($action=="add") ? 1 : $data->is_hidden;
$is_show = ($action=="add") ? 0 : $data->is_show;
if(isset($category_settings) && $category_settings!="" && !empty($category_settings)){
    $order = !empty($category_settings->order) ? $category_settings->order : 0 ;
    $status_id = !empty($category_settings->status_id) ? $category_settings->status_id : \App\Models\Status::INACTIVE ;
    $is_hidden = ($action=="add") ? 1 : $category_settings->is_hidden;
    $is_show = ($action=="add") ? 0 : $category_settings->is_show;
}
?>
<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-12">
            <div class="card profile-widget">
                <div class="profile-widget-header">
                    @if (!empty($englishCat) && ($category_type_id == $shopCategory))
                    <img alt="image"
                        src="@if($englishCat->logo) {{ asset($englishCat->logo) }} @else {!! asset('img/avatar/avatar-1.png') !!} @endif"
                        class="rounded-circle profile-widget-picture">
                    @endif
                </div>
                <div class="profile-widget-description">
                    <div class="">
                        @if (isset($englishCat))
                            {!! Form::open(['route' => ['admin.important-setting.category-setting.update', $id], 'id' =>"categoryUpdateForm", 'method' => 'post', 'enctype' => 'multipart/form-data']) !!}
                            <input type="hidden" name="country_code" value="{{ $country_code }}">
                        @else
                            {!! Form::open(['route' => 'admin.important-setting.category-setting.store', 'id' =>"categoryForm", 'enctype' => 'multipart/form-data']) !!}
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
                                    <div class="form-group col-md-6">
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
                                            {!! Form::select('is_hidden', array(1 => 'Hidden', 0 => 'Visible'), $is_hidden,  ['class' => 'form-control']); !!}
                                            @error('is_hidden')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('is_hidden') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('is_show', __(Lang::get('forms.category.show_category'))); !!}
                                            {!! Form::select('is_show', array(1 => 'Show', 0 => 'Hide'), $is_show,  ['class' => 'form-control']); !!}
                                            @error('is_show')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('is_show') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="card-footer text-right">
                                <button type="button" id="delete_category" class="btn btn-danger" category-id="{{ $id }}">Remove</button>
                                <button type="submit" class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                                <a href="{{ route('admin.important-setting.category-setting.index')}}" class="btn btn-default">{{ __(Lang::get('general.cancel')) }}</a>
                            </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
<!-- Modal -->
<div class="modal fade" id="CategoryDeleteModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

@section('scripts')

<script>
    var base_url = "{{ url('/admin') }}";
    var parentBlankSelect = "{{ __(Lang::get('forms.category.select-parent')) }}";
    var shopCategory = "{{ $shopCategory }}";
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
            'logo': {
                required: true,
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
    });

    $(document).on('click', '#delete_category', function (){
        var category_id = $(this).attr('category-id');
        var pageModel = $("#CategoryDeleteModal");

        $.get("{{ url('admin/category-settings/delete') }}" + "/" + category_id, function(data, status) {
            pageModel.html('');
            pageModel.html(data);
            pageModel.modal('show');
        });
    })
</script>
@endsection
