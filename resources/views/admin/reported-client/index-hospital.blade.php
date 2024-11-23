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
                
                <div class="buttons">
                    <a href="{!! route('admin.reported-client.index') !!}" class="btn btn-primary">All</a>
                    <a href="{!! route('admin.reported-client.hospital.index') !!}" class="btn btn-primary">Hospital</a>
                    <a href="{!! route('admin.reported-client.shop.index') !!}" class="btn btn-primary">Beauty Shop</a>
                    <a href="{!! route('admin.reported-client.user.index') !!}" class="btn btn-primary">User</a>
                    <a href="{!! route('admin.reported-client.community.index') !!}" class="btn btn-primary">Community</a>
                    <a href="{!! route('admin.reported-client.review.index') !!}" class="btn btn-primary">Review</a>
                    <input type="submit" class="btn btn-primary ml-2" id="warning_mention" name="warning_mention" value="Warning Mention">
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">                
                <div class="confirm-btn col-md-4">
                    <select class="form-control" id="select_report_category" name="select_report_category">
                        <option value="0">{!! __(Lang::get('forms.report-client.select')) !!}</option>                    
                            @foreach($categoryList as $category)
                                @if(!empty($category))
                                    @if($category->parent_name == 'Hospital')
                                    <option                                
                                        value="{{ $category->id }}">{{$category->name}}
                                    </option>
                                    @endif
                                @endif
                            @endforeach
                    </select>  
                </div>
                <div class="table-responsive">
                    <table class="table table-striped" id="all-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
                                    <div class="custom-checkbox custom-control">
                                        <input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-all">
                                        <label for="checkbox-all" class="custom-control-label">&nbsp;</label>
                                    </div>
                                </th>
                                <th>Go</th>
                                <th>Delete</th>
                                <th>Business Name</th>
                                <th>Phone Number</th>
                                <th>Type of Business</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                    
            </div>
        </div>
    </div>
</div>
<div class="cover-spin"></div>
@include('admin.reported-client.all-popup')
<!-- Modal -->
<div class="modal fade" id="deletePostModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
@endsection

@section('scripts')
<script>
var hospitalIndex = "{!! route('admin.reported-client.all.hospital.table') !!}";
var warningMentionRequest = "{!! route('admin.reported-client.warning-mention') !!}";
var csrfToken = "{{csrf_token()}}";
var pageModel = $("#deletePostModal");

</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/reported-client/hospital.js') !!}"></script>
<script src="{!! asset('js/pages/reported-client/common.js') !!}"></script>
@endsection
