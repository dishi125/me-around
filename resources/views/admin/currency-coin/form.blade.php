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
if (!empty($currencyCoin)) {
    $data = $currencyCoin;
}
$id = !empty($data->id) ? $data->id : '' ;
$name= !empty($data->name) ? $data->name : '' ;
$coins= !empty($data->coins) ? $data->coins : '' ;
$currency_id = !empty($data->currency_id) ? $data->currency_id : '' ;
$checkId = !empty($data->id) ? $data->id : 0 ;
?>
<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-5">
            <div class="card profile-widget">
                <div class="profile-widget-header">
                    
                </div>
                <div class="profile-widget-description">
                    <div class="">
                        @if ( isset($currencyCoin ))
                            {!! Form::open(['route' => ['admin.currency-coin.update', $id], 'id' =>"currencyForm", 'method' => 'put', 'enctype' => 'multipart/form-data']) !!}
                        @else
                            {!! Form::open(['route' => 'admin.currency-coin.store', 'id' =>"currencyForm", 'enctype' => 'multipart/form-data']) !!}
                        @endif
                            @csrf
                            <input type="hidden" value="{{$checkId}}" id="check_id"/>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            {!! Form::label('currency_id', __(Lang::get('forms.currency-coin.currency'))); !!}
                                            {!! Form::select('currency_id', $currency_list, $currency_id,  ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.currency-coin.select-currency'))]); !!}
                                            @error('currency_id')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('currency_id') }}
                                            </div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            {!! Form::label('coins', __(Lang::get('forms.currency-coin.coins'))); !!}
                                            {!! Form::text('coins', $coins, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.currency-coin.coins')) ]); !!}
                                            @error('coins')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('coins') }}
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
    var base_url = '{{ url('/admin') }}';
   
    $('#currencyForm').validate({
        rules: {
            'currency_id': {
                required: true,
                'remote': {
                    url: base_url + '/currency-coin/check-currency/' + $('#check_id').val(),
                    type: "get",
                    async:false
                },
            },
            'coins': {
                required: true
            },
        },
        messages: {
            'currency_id': {
                required: 'This field is required',
                'remote': 'The currency data already exists.',
            },
            'coins':'This field is required',
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