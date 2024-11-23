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
    $data = (object) [];
    
    if (!empty($tests)) {
        $data = $tests;
    }
    
    $id = !empty($data->id) ? $data->id : '';
    $name = !empty($data->name) ? $data->name : '';
    
    ?>
    <div class="section-body">
        <div class="row mt-sm-4">
            <div class="col-12 col-md-12 col-lg-12">
                <div class="card profile-widget">
                    <div class="profile-widget-description">
                        <div class="">
                            @if (isset($tests))
                                {!! Form::open([
                                    'route' => ['admin.tests.update', $id],
                                    'id' => 'testsUpdateForm',
                                    'method' => 'put',
                                    'enctype' => 'multipart/form-data',
                                ]) !!}
                            @else
                                {!! Form::open(['route' => 'admin.tests.store', 'id' => 'testsForm', 'enctype' => 'multipart/form-data']) !!}
                            @endif
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('name', __(Lang::get('forms.brands.name'))) !!}
                                            {!! Form::text('name', $name, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.brands.name'))]) !!}
                                            @error('name')
                                                <div class="invalid-feedback">
                                                    {{ $errors->get('name') }}
                                                </div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit"
                                    class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                                <a href="{{ route('admin.certification-exam.index') }}"
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
        var mainImagesFiles = [];
        var base_url = "{{ url('/admin') }}";
        $('#testsForm').validate({
            rules: {
                'name': {
                    required: true
                }
            },
            messages: {
                'name': 'This field is required',
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

        $('#testsUpdateForm').validate({
            rules: {
                'name': {
                    required: true
                }
            },
            messages: {
                'name': 'This field is required',
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

        });
    </script>
@endsection
