@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
@endsection

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                @include('admin.important-setting.common-setting-menu', ['active' => 'app_version'])
            </div>
        </div>
    </div>
    <div class="col-md-12">
        {!! Form::open(['route' => 'admin.important-setting.app-version.update', 'id' =>"versionForm", 'enctype' => 'multipart/form-data']) !!}
        @csrf
        <div class="card">
            <div class="card-body">
                @if($settings)
                    @foreach($settings as $option)
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    {!! Form::label($option->key, $option->label ); !!}
                                    {!! Form::text($option->key, $option->value, [ 'class' => 'form-control decimal-input', 'placeholder' => $option->label ]); !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
            </div>
        </div>
        {!! Form::close() !!}
    </div>
</div>
<div class="cover-spin"></div>
<!-- Modal -->

@endsection

@section('scripts')

@endsection

@section('page-script')

@endsection
