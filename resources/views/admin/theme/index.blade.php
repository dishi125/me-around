@extends('layouts.app')
@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')
<div class="row">
    @if(count($options))
    <div class="col-md-12">
        {!! Form::open(['route' => 'admin.save.options', 'id' =>"optionForm", 'enctype' => 'multipart/form-data']) !!}
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="row">
                    @foreach($options as $formOption)
                    <div class="col-md-6 mb-4">
                        @if($formOption->type == \App\Models\MetalkOptions::FILE)
                            <div class="d-flex">
                                {!! Form::label($formOption->key,$formOption->label) !!}
                                @if($formOption->file_url && $formOption->value)
                                    <div class="ml-auto"><strong>Uploaded File : </strong><a href="{{$formOption->file_url}}" target="_blank">{!! basename($formOption->file_url) !!}</a></div>
                                @endif
                            </div>
                            {!! Form::file($formOption->key, ['class' => 'form-control', 'placeholder' => __($formOption->label) ]); !!}
                        @elseif($formOption->type == \App\Models\MetalkOptions::TEXT)
                            {!! Form::label($formOption->key,$formOption->label) !!}
                            {!! Form::text($formOption->key, $formOption->value, ['class' => 'form-control', 'placeholder' => __($formOption->label) ]); !!}
                        @elseif($formOption->type == \App\Models\MetalkOptions::DROPDOWN)
                            {!! Form::label($formOption->key,$formOption->label) !!}
                            {!!Form::select($formOption->key, $formOption->optionsData()->pluck('label','key'), $formOption->value , ['class' => 'form-control','placeholder' => __($formOption->label)])!!}
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
@endsection
@section('page-script')
@endsection