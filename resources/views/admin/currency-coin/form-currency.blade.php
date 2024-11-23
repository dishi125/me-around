@extends('layouts.app')
@section('styles')
<link rel="stylesheet" href="{!! asset('css/custom.css') !!}">
@endsection
@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')

<?php
$data = (object)[];
if (!empty($currency)) {
    $data = (object)$currency;
}
$id = !empty($data->id) ? $data->id : '' ;
$name= !empty($data->name) ? $data->name : '' ;
$priority= !empty($data->priority) ? $data->priority : '' ;
$bank_name= !empty($data->bank_name) ? $data->bank_name : '' ;
$bank_account_number= !empty($data->bank_account_number) ? $data->bank_account_number : '' ;
$country_id = $data->country_id ?? '';
?>
<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-5">
            <div class="card profile-widget">
                <div class="profile-widget-header">
                    
                </div>
                <div class="profile-widget-description">
                    <div class="">
                        @if ( isset($currency ))
                            {!! Form::open(['route' => ['admin.currency.coin.update.currency', $id], 'id' =>"currencyForm", 'method' => 'put', 'enctype' => 'multipart/form-data']) !!}
                        @else
                            {!! Form::open(['route' => 'admin.currency.coin.store.currency', 'id' =>"currencyForm", 'enctype' => 'multipart/form-data']) !!}
                        @endif
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            {!! Form::label('country_id', __(Lang::get('forms.association.country'))); !!}
                                            {!!Form::select('country_id', $countries, $country_id , ['class' => 'form-control','placeholder' => __(Lang::get('forms.association.country'))])!!}
                                        </div>
                                        <div class="form-group">
                                            {!! Form::label('name', __(Lang::get('forms.category.name'))); !!}
                                            {!! Form::text('name', $name, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.category.name')) ]); !!}
                                            @error('name')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('name') }}
                                            </div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            {!! Form::label('priority', __(Lang::get('forms.category.priority'))); !!}
                                            {!! Form::number('priority', $priority, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.category.priority')) ]); !!}
                                            @error('priority')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('priority') }}
                                            </div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            {!! Form::label('bank_name', __(Lang::get('forms.category.bank_name'))); !!}
                                            {!! Form::text('bank_name', $bank_name, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.category.bank_name')) ]); !!}
                                            @error('bank_name')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('bank_name') }}
                                            </div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            {!! Form::label('bank_account_number', __(Lang::get('forms.category.bank_account_number'))); !!}
                                            {!! Form::text('bank_account_number', $bank_account_number, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.category.bank_account_number')) ]); !!}
                                            @error('bank_account_number')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('bank_account_number') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>                                
                            </div>
                            <div class="card-footer text-right">
                                <button type="submit"
                                    class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                                <a href="{{ route('admin.currency-coin.index')}}"
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
    setTimeout(function(){ $('select').select2(); }, 500);     
   

    $('#currencyForm').validate({
        rules: {
            'name': {
                required: true
            },
            'priority': {
                required: true
            },
            'bank_name': {
                required: true
            },
            'bank_account_number': {
                required: true
            },
        },
        messages: {
            'name':'This field is required',
            'priority':'This field is required',
            'bank_name':'This field is required',
            'bank_account_number':'This field is required',
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