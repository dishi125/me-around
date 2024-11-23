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
    // dd($manager);
    if (!empty($manager)) {
        $data = $manager;
    }
    $id = !empty($data['id']) ? $data['id'] : '';
    $name = !empty($data['name']) ? $data['name'] : '';
    $email = !empty($data['email']) ? $data['email'] : '';
    $mobile = !empty($data['mobile']) ? $data['mobile'] : '';
    $address = !empty($data['address']) ? $data['address'] : '';
    $country = !empty($data['country_id']) ? $data['country_id'] : '';
    $state = !empty($data['state_id']) ? $data['state_id'] : '';
    $city = !empty($data['city_id']) ? $data['city_id'] : '';
    $recommended_code = !empty($data['recommended_code']) ? $data['recommended_code'] : '';
    $role = '';
    
    ?>
    <div class="section-body">
        <div class="row mt-sm-4">
            <div class="col-12 col-md-12 col-lg-12">
                <div class="card profile-widget">
                    <div class="profile-widget-header">

                    </div>
                    <div class="profile-widget-description">
                        <div class="">
                            @if (isset($manager) && $manager)
                                {!! Form::open([
                                    'route' => ['admin.manager.update', $id],
                                    'id' => 'managerForm',
                                    'method' => 'put',
                                    'enctype' => 'multipart/form-data',
                                ]) !!}
                            @else
                                {!! Form::open(['route' => 'admin.manager.store', 'id' => 'managerForm', 'enctype' => 'multipart/form-data']) !!}
                            @endif
                            @csrf
                            <div class="card-body row">
                                @if (!isset($manager))
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('role', __(Lang::get('forms.manager.role'))) !!}
                                            {!! Form::select('role', $roles, $role, [
                                                'class' => 'form-control' . ($errors->has('role') ? ' is-invalid' : ''),
                                                'placeholder' => __(Lang::get('forms.manager.select-role')),
                                            ]) !!}
                                            @error('role')
                                                <div class="invalid-feedback">
                                                    {{ $errors->first('role') }}
                                                </div>
                                            @enderror
                                        </div>
                                    </div>
                                @endif
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('name', __(Lang::get('forms.manager.name'))) !!}
                                        {!! Form::text('name', $name, [
                                            'class' => 'form-control' . ($errors->has('name') ? ' is-invalid' : ''),
                                            'placeholder' => __(Lang::get('forms.manager.name')),
                                        ]) !!}
                                        @error('name')
                                            <div class="invalid-feedback">
                                                {{ $errors->first('name') }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('email', __(Lang::get('forms.manager.email'))) !!}
                                        {!! Form::text('email', $email, [
                                            'class' => 'form-control' . ($errors->has('email') ? ' is-invalid' : ''),
                                            'readonly' => isset($manager) ? true : false,
                                            'placeholder' => __(Lang::get('forms.manager.email')),
                                        ]) !!}
                                        @error('email')
                                            <div class="invalid-feedback">
                                                {{ $errors->first('email') }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('password', __(Lang::get('forms.manager.password'))) !!}
                                        {!! Form::password('password', [
                                            'class' => 'form-control' . ($errors->has('password') ? ' is-invalid' : ''),
                                            'placeholder' => __(Lang::get('forms.manager.password')),
                                        ]) !!}
                                        @error('password')
                                            <div class="invalid-feedback">
                                                {{ $errors->first('password') }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('mobile', __(Lang::get('forms.manager.mobile'))) !!}
                                        {!! Form::text('mobile', $mobile, [
                                            'class' => 'form-control' . ($errors->has('mobile') ? ' is-invalid' : ''),
                                            'placeholder' => __(Lang::get('forms.manager.mobile')),
                                        ]) !!}
                                        @error('mobile')
                                            <div class="invalid-feedback">
                                                {{ $errors->first('mobile') }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('address', __(Lang::get('forms.manager.address'))) !!}
                                        {!! Form::textarea('address', $address, [
                                            'class' => 'form-control' . ($errors->has('address') ? ' is-invalid' : ''),
                                            'rows' => 5,
                                            'placeholder' => __(Lang::get('forms.manager.address')),
                                        ]) !!}
                                        @error('address')
                                            <div class="invalid-feedback">
                                                {{ $errors->first('address') }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('country', __(Lang::get('forms.manager.country'))) !!}
                                        {!! Form::select('country', $countryList, $country, [
                                            'class' => 'form-control' . ($errors->has('country') ? ' is-invalid' : ''),
                                            'placeholder' => __(Lang::get('forms.manager.select-country')),
                                        ]) !!}
                                        @error('country')
                                            <div class="invalid-feedback">
                                                {{ $errors->first('country') }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('recommended_code', __(Lang::get('forms.manager.recommended_code'))) !!}
                                        {!! Form::text('recommended_code', $recommended_code, [
                                            'class' => 'form-control' . ($errors->has('recommended_code') ? ' is-invalid' : ''),
                                            'placeholder' => __(Lang::get('forms.manager.recommended_code')),
                                            'maxlength' => 7,
                                        ]) !!}
                                        @error('recommended_code')
                                            <div class="invalid-feedback">
                                                {{ $errors->first('recommended_code') }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                                <?php /* <div class="col-md-6">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <div class="form-group">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            {!! Form::label('state', __(Lang::get('forms.manager.state'))); !!}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            {!! Form::select('state', $stateList, $state,  ['class' => 'form-control'. ( $errors->has('state') ? ' is-invalid' : '' ), 'placeholder' => __(Lang::get('forms.manager.select-state'))]); !!}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            @error('state')
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <div class="invalid-feedback">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                {{ $errors->first('state') }}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            @enderror
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </div>                                    
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <div class="col-md-6">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <div class="form-group">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            {!! Form::label('city', __(Lang::get('forms.manager.city'))); !!}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            {!! Form::select('city', $cityList, $city,  ['class' => 'form-control'. ( $errors->has('city') ? ' is-invalid' : '' ), 'placeholder' => __(Lang::get('forms.manager.select-city'))]); !!}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            @error('city')
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <div class="invalid-feedback">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                {{ $errors->first('city') }}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            @enderror
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </div>   */
                                ?>
                            </div>
                            <div class="card-footer text-right">
                                <button type="submit" class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                                <a href="{{ route('admin.manager.index') }}"
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
        $('#managerForm').validate({
            rules: {
                'role': {
                    required: true
                },
                'name': {
                    required: true
                },
                'email': {
                    required: true
                },
                'mobile': {
                    required: true
                },
                'address': {
                    required: true
                },
                'country': {
                    required: true
                },
                'state': {
                    required: true
                },
                'city': {
                    required: true
                },
                'recommended_code': {
                    required: true,
                    remote: {
                        type: 'post',
                        url: "{{ route('admin.manager.recommended-code.check') }}",
                        async: false,
                        data: {
                            'manager_id': {{ $id }}
                        }
                    }
                },

            },
            messages: {
                'recommended_code': {
                    required: 'The Recommended code is required.',
                    remote: 'The Recommended code is already in use.',
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


        $(document).ready(function() {
            var stateBlankSelect = '{{ __(Lang::get('forms.manager.select-state')) }}';
            var cityBlankSelect = '{{ __(Lang::get('forms.manager.select-city')) }}';

            $("#country").change(function() {
                var id = $(this).val();
                $("#state").empty();
                $.ajax({
                    type: 'GET',
                    url: baseUrl + '/admin/get/state/' + id,
                    dataType: 'json',
                    success: function(data) {
                        $('#state').append('<option value=""> ' + stateBlankSelect +
                            ' </option>');
                        $.each(data.state_data, function(key, value) {
                            $('#state').append('<option value="' + key + '">' + value +
                                '</option>');
                        });
                    }
                });
            });
            $("#state").change(function() {
                var id = $(this).val();
                $("#city").empty();
                $.ajax({
                    type: 'GET',
                    url: baseUrl + '/admin/get/city/' + id,
                    dataType: 'json',
                    success: function(data) {
                        $('#city').append('<option value=""> ' + cityBlankSelect +
                            ' </option>');
                        $.each(data.city_data, function(key, value) {
                            $('#city').append('<option value="' + key + '">' + value +
                                '</option>');
                        });
                    }
                });
            });
        });
    </script>
@endsection
