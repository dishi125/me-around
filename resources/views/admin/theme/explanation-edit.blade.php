@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
@endsection

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')
<div class="row">
    @if(count($options))
    <div class="col-md-12">
        {!! Form::open(['route' => ['admin.explanation.update',$id], 'id' =>"settingForm", 'method' => 'put', 'enctype' => 'multipart/form-data']) !!}
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="row">
                    @foreach($options as $formOption)
                    <div class="col-md-6 mb-4">
                        @if($formOption->type == \App\Models\MetalkOptions::FILE)
                        <div class="d-flex">
                            {!! Form::label($formOption->key,$formOption->label." ( ".$formOption->language_name." )" ) !!}
                            @if($formOption->file_url && $formOption->value)
                            <div class="ml-auto"><strong>Uploaded File : </strong><a href="{{$formOption->file_url}}" target="_blank">{!! basename($formOption->file_url) !!}</a></div>
                            @endif
                        </div>
                        {!! Form::file($formOption->key.'['.$formOption->language.']', ['class' => 'form-control', 'placeholder' => __($formOption->label) ]); !!}

                        @elseif($formOption->type == \App\Models\MetalkOptions::TEXT)

                        {!! Form::label($formOption->key,$formOption->label." ( ".$formOption->language_name." )" ) !!}
                        {!! Form::text($formOption->key.'['.$formOption->language.']', $formOption->value, ['class' => 'form-control', 'placeholder' => __($formOption->label) ]); !!}

                        @endif

                    </div>
                    @endforeach
                </div>
            </div>

            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
            </div>
        </div>
        {!! Form::close() !!}
    </div>
    @endif
</div>
<div class="cover-spin"></div>
@endsection


@section('scripts')

@endsection