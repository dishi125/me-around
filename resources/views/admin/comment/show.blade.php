@extends('layouts.app')

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
<link rel="stylesheet" href="{!! asset('css/chocolat.css') !!}">
@endsection

@section('content')
<div class="section-body">
    <div class="row mt-sm-4">        
        <div class="col-12 col-md-12 col-lg-12 mt-sm-3 pt-3">
            <div class="card">
                <div class="card-header">
                    <h4>Comment Details</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-md-6 col-12">
                            <label>User Name</label>
                            <input type="text" class="form-control" value="{{$comment->username}}" readonly>
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label>Comment Description</label>
                            <textarea class="form-control" readonly >{{$comment->comment}}</textarea>
                        </div>
                    </div>                    
                </div>
                
            </div>
        </div>
    </div>
</div>
</div>
</div>
</div>
@endsection

@section('scripts')
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/chocolat.js') !!}"></script>

@endsection