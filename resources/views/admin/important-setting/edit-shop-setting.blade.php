@extends('layouts.app')
@section('styles')
<link rel="stylesheet" href="{!! asset('css/custom.css') !!}">
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
$id = !empty($data['id']) ? $data['id'] : '' ;
$package= !empty($data['package_plan_name']) ? $data['package_plan_name'] : '' ;
$deduct_rate= !empty($data['deduct_rate']) ? $data['deduct_rate'] : 0 ;
$amount= !empty($data['amount']) ? $data['amount'] : 0 ;
$no_of_posts= !empty($data['no_of_posts']) ? $data['no_of_posts'] : 0 ;
$km= !empty($data['km']) ? $data['km'] : 0 ;

?>
<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-5">
            <div class="card profile-widget">                
                <div class="profile-widget-description">
                    <div class="">
                        {!! Form::open(['route' => ['admin.important-setting.shop.update',$id], 'id' =>"settingForm", 'method' => 'put', 'enctype' => 'multipart/form-data']) !!}
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            {!! Form::label('package', __(Lang::get('forms.important-setting.package'))); !!}
                                            {!! Form::text('package', $package, ['class' => 'form-control'. ( $errors->has('package') ? ' is-invalid' : '' ),'readonly' => true, 'placeholder' => __(Lang::get('forms.important-setting.package')) ]); !!}
                                            @error('package')
                                            <div class="invalid-feedback">
                                                {{ $errors->first('package') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            {!! Form::label('deduct_rate', __(Lang::get('forms.important-setting.deduct_rate'))); !!}
                                            {!! Form::text('deduct_rate', $deduct_rate, ['class' => 'form-control'. ( $errors->has('deduct_rate') ? ' is-invalid' : '' ), 'placeholder' => __(Lang::get('forms.important-setting.deduct_rate')) ]); !!}
                                            @error('deduct_rate')
                                            <div class="invalid-feedback">
                                                {{ $errors->first('deduct_rate') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            {!! Form::label('amount', __(Lang::get('forms.important-setting.amount'))); !!}
                                            {!! Form::text('amount', $amount, ['class' => 'form-control'. ( $errors->has('amount') ? ' is-invalid' : '' ), 'placeholder' => __(Lang::get('forms.important-setting.amount')) ]); !!}
                                            @error('amount')
                                            <div class="invalid-feedback">
                                                {{ $errors->first('amount') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                        {!! Form::label('km', __(Lang::get('forms.important-setting.km'))); !!}
                                            {!! Form::text('km', $km, ['class' => 'form-control'. ( $errors->has('km') ? ' is-invalid' : '' ), 'placeholder' => __(Lang::get('forms.important-setting.km')) ]); !!}
                                            @error('km')
                                            <div class="invalid-feedback">
                                                {{ $errors->first('km') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                
                                
                            </div>
                            <div class="card-footer text-right">
                                <button type="submit"
                                    class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                                <a href="{{ route('admin.important-setting.index')}}"
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
    $('#settingForm').validate({
        rules: {
            'package': {
                required: true
            },
            'deduct_rate' :{
                required: true
            },
            'amount' :{
                required: true
            },
            'km' :{
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