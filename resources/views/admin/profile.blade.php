@extends($header.'layouts.app')
@section('styles')
<link rel="stylesheet" href="{!! asset('css/custom.css') !!}">
@endsection
@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

<?php
$data = array();
if (!empty($userDetail)) {
    $data = $userDetail;
}
$id = !empty($data['id']) ? $data['id'] : '' ;
$name= !empty($data['name']) ? $data['name'] : '' ;
$email= !empty($data['email']) ? $data['email'] : '' ;
$mobile= !empty($data['mobile']) ? $data['mobile'] : '' ;
$avatar= !empty($data['avatar']) ? $data['avatar'] : '' ;

?>
@section('content')
<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-5 pt-1">
            <div class="card profile-widget">
                <div class="profile-widget-header">
                    <img alt="image"
                        src="@if($avatar) {{ $avatar }} @else {!! asset('img/avatar/avatar-1.png') !!} @endif"
                        class="rounded-circle profile-widget-picture">
                </div>
                <div class="profile-widget-description">
                    <div class="">
                        {!! Form::open(['route' => ['admin.profile.update',$id], 'id' =>"profileForm", 'method' => 'put', 'enctype' => 'multipart/form-data']) !!}
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="form-group col-lg-12">
                                        {!! Form::label('name', __(Lang::get('forms.profile.name'))); !!}
                                        {!! Form::text('name', $name, ['class' => 'form-control'. ( $errors->has('name') ? ' is-invalid' : '' ), 'placeholder' => __(Lang::get('forms.profile.name')) ]); !!}
                                        @error('name')
                                        <div class="invalid-feedback">
                                            {{ $errors->first('name') }}
                                        </div>
                                        @enderror
                                    </div>
                                    <div class="form-group col-lg-12">
                                        {!! Form::label('email', __(Lang::get('forms.profile.email'))); !!}
                                        {!! Form::text('email', $email, ['class' => 'form-control'. ( $errors->has('email') ? ' is-invalid' : '' ),'readonly' => true, 'placeholder' => __(Lang::get('forms.profile.email')) ]); !!}
                                        @error('email')
                                        <div class="invalid-feedback">
                                            {{ $errors->first('email') }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-lg-12">
                                        {!! Form::label('mobile', __(Lang::get('forms.profile.mobile'))); !!}
                                        {!! Form::text('mobile', $mobile, ['class' => 'form-control'. ( $errors->has('mobile') ? ' is-invalid' : '' ), 'placeholder' => __(Lang::get('forms.profile.mobile')) ]); !!}
                                        @error('mobile')
                                        <div class="invalid-feedback">
                                            {{ $errors->first('mobile') }}
                                        </div>
                                        @enderror
                                    </div>
                                    
                                </div>
                                <div class="row">
                                    <div class="form-group col-lg-12">
                                        {!! Form::label('avatar', __(Lang::get('forms.profile.avatar'))); !!}
                                        {!! Form::file('avatar',  ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.profile.avatar')) ]); !!}
                                        @error('avatar')
                                        <div class="invalid-feedback">
                                        {{ $errors->first('avatar') }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <input type="submit" class="btn btn-primary" value="Save Changes">
                            </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-12 col-lg-5 mt-sm-3 pt-4">
            <div class="card">
                {!! Form::open(['route' => ['admin.profile.changepassword',$id], 'id' =>"changePasswordForm", 'method' => 'put', 'enctype' => 'multipart/form-data']) !!}
                    @csrf
                    <div class="card-header">
                        <h4>Change Password</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-12 col-12">
                                {!! Form::label('old_password', __(Lang::get('forms.profile.old_password'))); !!}
                                {!! Form::password('old_password', ['class' => 'form-control'. ( $errors->has('old_password') ? ' is-invalid' : '' ), 'placeholder' => __(Lang::get('forms.profile.old_password')) ]); !!}
                                @error('old_password')
                                <div class="invalid-feedback">
                                    {{ $errors->first('old_password') }}
                                </div>
                                @enderror
                            </div>
                            <div class="form-group col-md-12 col-12">
                                {!! Form::label('password', __(Lang::get('forms.profile.password'))); !!}
                                {!! Form::password('password', ['class' => 'form-control'. ( $errors->has('password') ? ' is-invalid' : '' ), 'placeholder' => __(Lang::get('forms.profile.password')) ]); !!}
                                @error('password')
                                <div class="invalid-feedback">
                                    {{ $errors->first('password') }}
                                </div>
                                @enderror
                            </div>
                            <div class="form-group col-md-12 col-12">
                                {!! Form::label('password_confirm', __(Lang::get('forms.profile.password_confirm'))); !!}
                                {!! Form::password('password_confirm', ['class' => 'form-control'. ( $errors->has('password_confirm') ? ' is-invalid' : '' ), 'placeholder' => __(Lang::get('forms.profile.password_confirm')) ]); !!}
                                @error('password_confirm')
                                <div class="invalid-feedback">
                                    {{ $errors->first('password_confirm') }}
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <input type="submit" class="btn btn-primary" value="Save Changes">
                        <!-- <button class="btn btn-primary">Save Changes</button> -->
                    </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$('#profileForm').validate({
        rules: {
            'name': {
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

$('#changePasswordForm').validate({
        rules: {
            'old_password': {
                required: true
            },
            'password': {
                minlength : 5,
                required: true
            },
            'password_confirm': {
                required: true,
                minlength : 5,
                equalTo : "#password"
            },
        },
        messages: {
            password_confirm: {
                equalTo: "Confirm password does not match with password",
            }
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