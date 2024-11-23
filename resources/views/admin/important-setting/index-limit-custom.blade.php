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
                @include('admin.important-setting.common-setting-menu', ['active' => 'limit'])
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="limit-custom-table">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Figure</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="cover-spin"></div>
<!-- Modal -->

@endsection

@section('scripts')
<script>
var limitCustomTable = "{!! route('admin.important-setting.limit-custom.table') !!}";
var csrfToken = "{{csrf_token()}}";
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/important-setting/limit-custom.js') !!}"></script>
@endsection

@section('page-script')

@endsection
