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
    if (!empty($currencyCoin)) {
        $data = $currencyCoin;
    }
    $id = !empty($data->id) ? $data->id : '';
    $name = !empty($data->name) ? $data->name : '';
    $coins = !empty($data->coins) ? $data->coins : '';
    $currency_id = !empty($data->currency_id) ? $data->currency_id : '';
    $checkId = !empty($data->id) ? $data->id : 0;
    ?>
    <div class="section-body">
        <div class="row mt-sm-4">
            <div class="col-12 col-md-12 col-lg-5">
                <div class="card profile-widget">
                    <div class="profile-widget-header">

                    </div>
                    <div class="profile-widget-description">
                        <div class="">
                            {!! Form::open(['route' => 'admin.user.store', 'id' => 'userForm', 'enctype' => 'multipart/form-data']) !!}
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('username', __(Lang::get('forms.user.username'))) !!}
                                            {!! Form::text('username', '', [
                                                'class' => 'form-control',
                                                'placeholder' => __(Lang::get('forms.user.username')),
                                            ]) !!}

                                            @if ($errors->has('username'))
                                                <span class="error" role="alert">
                                                    <strong>{{ $errors->first('username') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('email', __(Lang::get('forms.user.email'))) !!}
                                            {!! Form::text('email', '', ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.user.email'))]) !!}

                                            @if ($errors->has('email'))
                                                <div class="error">
                                                    <strong>{{ $errors->first('email') }}</strong>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            {!! Form::label('phone_number', __(Lang::get('forms.user.phone_number'))) !!}
                                            {!! Form::text('phone_number', '', [
                                                'class' => 'form-control',
                                                'placeholder' => __(Lang::get('forms.user.phone_number')),
                                            ]) !!}

                                            @if ($errors->has('phone_number'))
                                                <div class="error">
                                                    <strong>{{ $errors->first('phone_number') }}</strong>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('password', __(Lang::get('forms.user.password'))) !!}
                                            {!! Form::text('password', '', [
                                                'class' => 'form-control',
                                                'placeholder' => __(Lang::get('forms.user.password')),
                                            ]) !!}

                                            @if ($errors->has('password'))
                                                <div class="error">
                                                    <strong>{{ $errors->first('password') }}</strong>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('password_confirmation', __(Lang::get('forms.user.password_confirmation'))) !!}
                                            {!! Form::text('password_confirmation', '', [
                                                'class' => 'form-control',
                                                'placeholder' => __(Lang::get('forms.user.password_confirmation')),
                                            ]) !!}

                                            @if ($errors->has('password_confirmation'))
                                                <div class="error">
                                                    <strong>{{ $errors->first('password_confirmation') }}</strong>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('gender', __(Lang::get('forms.user.gender'))) !!}
                                            {!! Form::select(
                                                'gender',
                                                ['Male' => 'Male', 'Female' => 'Female'],
                                                [
                                                    'class' => 'form-control' . ($errors->has('gender ') ? ' is-invalid' : ''),
                                                    'placeholder' => __(Lang::get('forms.user.gender')),
                                                ],
                                            ) !!}

                                            @if ($errors->has('gender'))
                                                <div class="error">
                                                    <strong>{{ $errors->first('gender') }}</strong>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button type="submit" class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                                <a href="{{ route('admin.user.index') }}"
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
        $('#userForm').validate({
            rules: {
                'username': {
                    required: true,
                },
                'email': {
                    required: true
                },
                'phone_number': {
                    required: true
                },
                'password': {
                    required: true
                },
                'password_confirmation': {
                    required: true
                },
            },
            messages: {
                'username': 'This field is required',
                'email': 'This field is required',
                'phone_number': 'This field is required',
                'password': 'This field is required',
                'password_confirmation': 'This field is required',
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
