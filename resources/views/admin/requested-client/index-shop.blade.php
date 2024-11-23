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
                    <a href="{!! route('admin.requested-client.index') !!}" class="btn btn-primary">All</a>
                    <a href="{!! route('admin.requested-client.hospital.index') !!}" class="btn btn-primary">Hospital</a>
                    <a href="{!! route('admin.requested-client.shop.index') !!}" class="btn btn-primary">Shop</a>
                    <a href="{!! route('admin.requested-client.suggest.index') !!}" class="btn btn-primary">Suggest</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                
                <div class="confirm-btn">
                    <input type="submit" class="btn btn-primary" id="confirm_hospital" name="confirm_hospital" value="Confirm">
                    <input type="submit" class="btn btn-primary ml-2" id="reject_hospital" name="reject_hospital" value="Reject">
                    <input type="submit" class="btn btn-primary ml-2 mr-5" id="reject_mention" name="reject_mention" value="Reject Mention">

                    <a href="{!! route('admin.requested-client.confirmed.index',['type' => 'shop']) !!}" class="btn btn-primary ml-5">Confirmed</a>
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
                                <th>Business Name</th>
                                <th>Type of Business</th>
                                <th>Address</th>
                                <th>City</th>
                                <th>Phone Number</th>
                                <th>Email</th>      
                                <th>Business Licence Number</th>                                   
                                <th>Photo</th>
                                <th>Date</th>                                
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
@include('admin.requested-client.all-popup')
@endsection

@section('scripts')
<script>
var shopIndex = "{!! route('admin.requested-client.all.shop.table') !!}";
var csrfToken = "{{csrf_token()}}";
var approveRequest = "{!! route('admin.requested-client.approve.multiple') !!}";
var rejectRequest = "{!! route('admin.requested-client.reject.multiple') !!}";
var rejectMentionRequest = "{!! route('admin.requested-client.reject-mention') !!}";
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/requested-client/shop.js') !!}"></script>
<script src="{!! asset('js/pages/requested-client/common.js') !!}"></script>
@endsection
