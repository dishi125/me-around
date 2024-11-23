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

if (!empty($brandCategory)) {
    $data = $brandCategory;
}

$id = !empty($data->id) ? $data->id : '' ;
$name= !empty($data->name) ? $data->name : '' ;
$country_code= !empty($data->country_code) ? $data->country_code : '' ;


?>
<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-12">
            <div class="card profile-widget">
                <div class="profile-widget-description">
                    <div class="">
                        @if ( isset($brandCategory ))
                            {!! Form::open(['route' => ['admin.brand-category.update', $id], 'id' =>"categoryUpdateForm", 'method' => 'put', 'enctype' => 'multipart/form-data']) !!}
                        @else
                            {!! Form::open(['route' => 'admin.brand-category.store', 'id' =>"categoryForm", 'enctype' => 'multipart/form-data']) !!}
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

                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('country', __(Lang::get('forms.association.country'))); !!}
                                        {!!Form::select('country_code', $countries, $country_code , ['class' => 'form-control select2','placeholder' => __(Lang::get('forms.association.country'))])!!}
                                    </div>
                                </div>
                            </div>
                                
                            </div>
                            <div class="card-footer text-right">
                                <button type="submit"
                                    class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                                <a href="{{ route('admin.brand-category.index')}}"
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
    $('#categoryForm').validate({
        rules: {
            'name': {
                required: true
            }
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

    $('#categoryUpdateForm').validate({
        rules: {
            'name': {
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
</script>
@endsection