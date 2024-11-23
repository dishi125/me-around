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
                @include('admin.important-setting.common-setting-menu', ['active' => 'instagram'])
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                {!! Form::open(['route' => 'admin.save.instagram_time', 'id' =>"optionForm", 'enctype' => 'multipart/form-data']) !!}
                @csrf

                <div class="row">
                    <div class="col-md-4">
                        {!! Form::label('instagram_sync_time','Instagram Post Sync Time') !!}
                        {!!Form::select('instagram_sync_time', $times, $selectedVal , ['class' => 'form-control'])!!}
                    </div>
                </div>
                <div class="card-footer p-0 pt-4">
                    <button type="submit" class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>
<div class="cover-spin"></div>
<!-- Modal -->

@endsection

@section('scripts')
<script>
var limitCustomTable = "{!! route('admin.important-setting.show-hide.table') !!}";
var csrfToken = "{{csrf_token()}}";
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
@endsection

@section('page-script')

@endsection
