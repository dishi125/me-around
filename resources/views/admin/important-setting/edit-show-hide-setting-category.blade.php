@extends('layouts.app')
@section('styles')
    <style>
        .on_off_radio {
            height: 20px;
            width: 25px;
        }

        .on_off_button {
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
    $id = !empty($data->id) ? $data->id : '';
    $key = !empty($data->key) ? Str::ucfirst(str_replace('_', ' ', $data->key)) : '';
    $value = !empty($data->value) ? $data->value : 0;
    $selectedCountry = '';
    ?>
    <div class="section-body">
        <div class="row mt-sm-4">
            <div class="col-12 col-md-12 col-lg-5">
                {!! Form::open(['route' => ['admin.save.category.country'], 'id' =>"settingForm", 'method' => 'put', 'enctype' => 'multipart/form-data']) !!}

                {!! Form::hidden('id',$id) !!}
                <div class="row">
                    <div class="col-6">
                        <div class="form-group country_select mb-2">
                            <select class="form-control country_code_select2" name="country_code" id="country_code">
                                <option value="" >Select Country</option>
                                @foreach($countries as $countryData)
                                    <option class="{{$countryData->is_saved ? 'saved' : ''}}" value="{{$countryData->code}}" {{$country == $countryData->code ? 'selected' : ''}}> {{$countryData->name}} </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card profile-widget">
                    <div class="profile-widget-description">
                        <div class="">

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
                                        <div class="form-group">
                                            {{ Form::radio('value', 1,($value == 1 ? true : false),array('id'=>'on', 'class' => 'on_off_radio')) }}
                                            <label class="on_off_button" for="on"> On </label>
                                            {{ Form::radio('value', 0,($value == 0 ? true : false),array('id'=>'off', 'class' => 'ml-2 on_off_radio')) }}
                                            <label class="on_off_button" for="off"> Off </label>
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
                                <button type="submit"
                                        class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                                <a href="{{ route('admin.important-setting.show-hide.index')}}"
                                   class="btn btn-default">{{ __(Lang::get('general.cancel')) }}</a>
                            </div>
                        </div>
                    </div>
                </div>

                {!! Form::close() !!}
            </div>
        </div>
    </div>

@endsection
@section('scripts')
    <script>
        const categoryCountryURL = "{{route('admin.get.category.country.value')}}"
        $(document).on('change','select[name="country_code"]',function (){
            const country_code = $(this).val();
            $.ajax({
                type: "POST",
                dataType: "json",
                url: categoryCountryURL,
                data: {
                    id: {{$id}},
                    country_code
                },
                success: function (response) {
                    let checkedValue = response.value || 0;
                  //  $('input[type="radio"][value='+checkedValue+']').attr('checked', true);
                    $('input[type="radio"][value='+checkedValue+']').prop('checked', true);
                },
            });
        });
    </script>
@endsection
