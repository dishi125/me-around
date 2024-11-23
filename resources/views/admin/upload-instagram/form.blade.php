@extends('layouts.app')
@section('styles')
    <link rel="stylesheet" href="{!! asset('css/custom.css') !!}">
@endsection
@section('header-content')
    <h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')
<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-12">
            <div class="card profile-widget">
                <div class="profile-widget-description">
                    <div class="">
                        {!! Form::open(['route' => 'admin.upload-instagram.store', 'id' =>"uploadForm", 'enctype' => 'multipart/form-data']) !!}
                        @csrf
                        <input type="hidden" name="access_token" value="{{ $access_token }}">
                        <div class="card-body">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    {!! Form::label('media_file', 'Select Image/Video'); !!}
                                    {!! Form::file('media_file',  ['class' => 'form-control' ]); !!}
                                    @error('logo')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button type="submit" class="btn btn-primary">Upload</button>
                            <a href="{{ route('admin.upload-instagram.index')}}" class="btn btn-default">{{ __(Lang::get('general.cancel')) }}</a>
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
    $('#uploadForm').validate({
            rules: {
                'media_file': {
                    required: true,
                    accept: "image/*,video/*"
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
