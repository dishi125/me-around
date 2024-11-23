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
if (!empty($category)) {
    $data = $category;
}
$id = !empty($data->id) ? $data->id : '' ;
$name= !empty($data->name) ? $data->name : '' ;
$status_id = !empty($data->status_id) ? $data->status_id : '' ;
$order = !empty($data->order) ? $data->order : 0 ;
?>
<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-12">
            <div class="card profile-widget">
                <div class="profile-widget-header">
                    @if (!empty($category))
                        <img alt="image"
                             src="@if($category->logo) {{ asset($category->logo_url) }} @else {!! asset('img/avatar/avatar-1.png') !!} @endif"
                             class="rounded-circle profile-widget-picture">
                    @endif
                </div>
                <div class="profile-widget-description">
                    <div class="">
                        @if (isset($category))
                            {!! Form::open(['route' => ['admin.big-category.update', $id], 'id' =>"BigCategoryUpdateForm", 'method' => 'post', 'enctype' => 'multipart/form-data']) !!}
                        @else
                            {!! Form::open(['route' => 'admin.big-category.store', 'id' =>"BigCategoryForm", 'enctype' => 'multipart/form-data']) !!}
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
                                        {!! Form::select('status_id', $statusData, $status_id,  ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.category.select-status'))]); !!}
                                        @error('status_id')
                                        <div class="invalid-feedback">
                                            {{ $errors->get('status_id') }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            </div>
                            <div class="card-footer text-right">
                                <button type="submit"
                                    class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                                <a href="{{ route('admin.big-category.index')}}"
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
    $('#BigCategoryForm').validate({
        rules: {
            'name': {
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

    $('#BigCategoryUpdateForm').validate({
        rules: {
            'name': {
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
@endsection
