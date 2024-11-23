@extends('layouts.app')
@section('styles')
<link rel="stylesheet" href="{!! asset('css/custom.css') !!}">
<style>
    .on_off_radio{
        height: 20px;
        width: 25px;
    }
    .on_off_button{
        font-size: 25px !important;
    }
</style>
@endsection
@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')

<?php
$data = array();
if (!empty($settings)) {
    $data = $settings;
}
$id = !empty($data->id) ? $data->id : '' ;
$key= !empty($data->key) ? Str::ucfirst(str_replace('_', ' ', $data->key)) : '' ;
$value= !empty($data->value) ? $data->value : 0 ;
$original_key= $data->key;
$valueArray = explode('|',$value);
$newValue = $valueArray[0];
$selectedCountry = $valueArray[1] ?? '';
$countryNumber = $valueArray[2] ?? '';


?>
<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-5">
            <div class="card profile-widget">
                
                <div class="profile-widget-description">
                    <div class="">
                        @if($original_key == 'show_fixed_country')
                        {!! Form::open(['route' => ['admin.important-setting.country.update',$id], 'id' =>"settingForm", 'method' => 'put', 'enctype' => 'multipart/form-data']) !!}
                        @else
                        {!! Form::open(['route' => ['admin.important-setting.show-hide.update',$id], 'id' =>"settingForm", 'method' => 'put', 'enctype' => 'multipart/form-data']) !!}
                        @endif
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                        {!! Form::label('key', __(Lang::get('forms.important-setting.key'))); !!}
                                        {!! Form::text('key', $key, ['class' => 'form-control'. ( $errors->has('key') ? ' is-invalid' : '' ),'readonly' => true, 'placeholder' => __(Lang::get('forms.important-setting.key')) ]); !!}
                                            @error('key')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('key') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        @if($original_key == 'show_fixed_country')
                                            <div class="form-group">
                                                {!! Form::label('value', __(Lang::get('forms.important-setting.value'))); !!} <br/>
                                                {{ Form::radio('value', 'Auto',($newValue == 'Auto' ? true : false), array('id'=>'auto', 'class' => 'country_radio')) }} <label for="auto"> Auto </label>
                                                {{ Form::radio('value', 'Fixed',($newValue == 'Fixed' ? true : false), array('id'=>'fixed', 'class' => 'country_radio')) }} <label for="fixed"> Fixed </label>

                                            </div>
                                                <div class="form-group country_select" style="display: {{$newValue == 'Fixed' ? 'block' : 'none' }};">
                                                    <select class="form-control select2" name="country_id" id="country_id">
                                                        @foreach($countries as $countryData)
                                                        <option value="{{$countryData->code}}" {{$selectedCountry == $countryData->code ? 'selected' : ''}}> {{$countryData->name}} </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-group country_number" style="display: {{$newValue == 'Fixed' ? 'block' : 'none' }};">
                                                    <input type="text"  class="form-control numeric" name="country_number" id="country_number" placeholder="Country Number" value="{{$countryNumber}}" />
                                                </div>
                                            @else
                                            <div class="form-group">
                                                {{ Form::radio('value', 1,($value == 1 ? true : false),array('id'=>'on', 'class' => 'on_off_radio')) }} <label class="on_off_button" for="on"> On </label>
                                                {{ Form::radio('value', 0,($value == 0 ? true : false),array('id'=>'off', 'class' => 'ml-2 on_off_radio')) }} <label class="on_off_button" for="off"> Off </label>
                                                @error('value')
                                                <div class="invalid-feedback">
                                                    {{ $errors->get('value') }}
                                                </div>
                                                @enderror
                                            </div>
                                            @endif

                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button type="submit"
                                    class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                                <a href="{{ route('admin.important-setting.show-hide.index')}}"
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
    $('input[type=radio][name=value]').change(function() {
        if($(this).hasClass('country_radio')){
            if (this.value == 'Fixed') {
                $('.country_select').show();
                $('.country_number').show();
            } else {
                $('.country_select').hide();
                $('.country_number').hide();
            }
        }
    });

    $('#settingForm').validate({
        rules: {
            'field': {
                required: true
            },
            'figure' :{
                required: true
            },
            'country_number' :{
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
@endsection