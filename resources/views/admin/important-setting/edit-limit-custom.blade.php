@extends('layouts.app')
@section('styles')
    <link rel="stylesheet" href="{!! asset('css/custom.css') !!}">
@endsection
@section('header-content')
    <h1>
        @if (@$title)
            {{ @$title }}
        @endif
    </h1>
@endsection

@section('content')
    <?php
    $data = [];
    if (!empty($settings)) {
        $data = $settings;
    }
    
    $id = !empty($data['id']) ? $data['id'] : '';
    $key = !empty($data['key']) ? $data['key'] : '';
    $value = !empty($data['value']) ? $data['value'] : '';
    
    $valueArray = explode('|',$value);
    $newValue = $valueArray[0];
    $selectedMethod = $valueArray[1] ?? '';

    ?>
    <div class="section-body">
        <div class="row mt-sm-4">
            <div class="col-12 col-md-12 col-lg-5">
                <div class="card profile-widget">

                    <div class="profile-widget-description">
                        <div class="">
                            @if (!empty($newsettings) && $newsettings->key == 'product_payment_method')
                                {!! Form::open([
                                    'route' => ['admin.important-setting.limit-custom.update-product', $id],
                                    'id' => 'settingForm',
                                    'method' => 'put',
                                    'enctype' => 'multipart/form-data',
                                ]) !!}
                            @else
                                {!! Form::open([
                                    'route' => ['admin.important-setting.limit-custom.update', $id],
                                    'id' => 'settingForm',
                                    'method' => 'put',
                                    'enctype' => 'multipart/form-data',
                                ]) !!}
                            @endif
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            {!! Form::label('key', __(Lang::get('forms.important-setting.key'))) !!}
                                            {!! Form::text('key', $key, [
                                                'class' => 'form-control' . ($errors->has('key') ? ' is-invalid' : ''),
                                                'readonly' => true,
                                                'placeholder' => __(Lang::get('forms.important-setting.key')),
                                            ]) !!}
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
                                        <div class="form-group">
                                            {!! Form::label('value', __(Lang::get('forms.important-setting.value'))) !!}
                                            @if (!empty($newsettings) && $newsettings->key == 'product_payment_method')
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <label>결제방식</label>
                                                    </div>
                                                    <div class="col-md-6">
                                                        {!! Form::select('payment_type', ['01' => '정기 결제', '02' => '앱카드 결제'], $newValue, [
                                                            'class' => 'form-control',
                                                        ]) !!}
                                                    </div>
                                                </div>
                                                <div class="row mt-3">
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-6">
                                                        {!! Form::select('payment_method', ['PAY' => '카드등록 및 결제', 'AUTH' => '카드등록'], $selectedMethod, [
                                                            'class' => 'form-control',
                                                        ]) !!}
                                                    </div>
                                                </div>
                                            @else
                                                {!! Form::text('value', $value, [
                                                    'class' => 'form-control' . ($errors->has('value') ? ' is-invalid' : ''),
                                                    'placeholder' => __(Lang::get('forms.important-setting.value')),
                                                ]) !!}
                                            @endif

                                            @error('value')
                                                <div class="invalid-feedback">
                                                    {{ $errors->get('value') }}
                                                </div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button type="submit" class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                                @if (!empty($newsettings) && in_array($newsettings->key, App\Models\Config::PAYPLE_FIELDS ))
                                    <a href="{{ route('admin.important-setting.payple-setting.index') }}" class="btn btn-default">{{ __(Lang::get('general.cancel')) }}</a>
                                @else
                                    <a href="{{ route('admin.important-setting.limit-custom.index') }}" class="btn btn-default">{{ __(Lang::get('general.cancel')) }}</a>
                                @endif
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
        jQuery(window).on('load', function(){
            if(jQuery('select[name="payment_type"]').length){
                jQuery('select[name="payment_type"]').trigger('change');
            }
        });
        
        jQuery('body').on('change', 'select[name="payment_type"]', function() {
            if (jQuery(this).val() == '01') {
                jQuery('select[name="payment_method"]').show();
            } else {
                jQuery('select[name="payment_method"]').hide();
            }
        });
        $('#settingForm').validate({
            rules: {
                'field': {
                    required: true
                },
                'figure': {
                    required: true
                },
            },
            highlight: function(input) {
                $(input).parents('.form-line').addClass('error');
            },
            unhighlight: function(input) {
                $(input).parents('.form-line').removeClass('error');
            },
            errorPlacement: function(error, element) {
                $(element).parents('.form-group').append(error);
            },
        });
    </script>
@endsection
