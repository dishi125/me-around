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
                            {!! Form::open(['route' => 'admin.gif-filter.store', 'id' =>"gifFilterForm", 'enctype' => 'multipart/form-data']) !!}
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('title', __(Lang::get('forms.gif_filter.title'))); !!}
                                            {!! Form::text('title', '', ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.gif_filter.title')) ]); !!}
                                            @error('title')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('title') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        {!! Form::label('file', __(Lang::get('forms.gif_filter.gif_file'))); !!}
                                        {!! Form::file('file',  ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.gif_filter.gif_file')) ]); !!}
                                        @error('file')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>

                            </div>
                            <div class="card-footer text-right">
                                <button type="submit" class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                                <a href="{{ route('admin.gif-filter.index')}}" class="btn btn-default">{{ __(Lang::get('general.cancel')) }}</a>
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
        $('#gifFilterForm').validate({
            rules: {
                'title': {
                    required: true
                },
                'file': {
                    required: true,
                    accept: "image/gif"
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
