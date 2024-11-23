@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
<style>
.table-responsive button#show-profile {width: 130px;margin:5px;white-space:normal;}
.table-responsive .shops-date button#show-profile{width:180px;}
.table-responsive .shops-rate button#show-profile{width:80px;}
.table-responsive th{text-align:center;}
.table-responsive td span{margin:5px;}
</style>
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
                    <a href="{!! route('admin.reload-coin.index') !!}" class="btn btn-primary">All</a>
                    <select
                        class="form-control select2 col-md-3"
                        name="country_id" id="country_id">
                        <option value="0">{{__(Lang::get('forms.top-post.select-country'))}}</option>
                        @foreach($countries as $countryData)
                        <option value="{{$countryData->code}}"> {{$countryData->name}} </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-pills mb-4" id="myTab3" role="tablist">
                    <li class="nav-item mr-3">
                        <a class="nav-link active btn btn-primary" id="all-data" data-toggle="tab" href="#allData" role="tab" aria-controls="shop" aria-selected="true">All</a>
                    </li>
                    <li class="nav-item mr-3">
                        <a class="nav-link btn btn-primary" id="shop-data" data-toggle="tab" href="#shopData" role="tab" aria-controls="shop" aria-selected="false">Shop</a>
                    </li>
                    <li class="nav-item mr-3">
                        <a class="nav-link btn btn-primary" id="hospital-data" data-toggle="tab" href="#hospitalData" role="tab" aria-controls="shop" aria-selected="false">Hospital</a>
                    </li>
                </ul>

                <div class="tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
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
                                        <th>Activate Name</th>
                                        <th>Category</th>
                                        <th>Coin Amount</th>
                                        <th>Total Amount</th>                                        
                                        <th>Order Number</th>
                                        <th>Sender Name</th>
                                        <th>Phone Number</th>
                                        <th>Manager</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="shopData" role="tabpanel" aria-labelledby="shop-data">                            
                        <div class="table-responsive">
                            <table class="table table-striped" id="shop-table">
                                <thead>
                                        <tr>
                                            <th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
                                                <div class="custom-checkbox custom-control">
                                                    <input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
                                                    <label for="checkbox-active" class="custom-control-label">&nbsp;</label>
                                                </div>
                                            </th>
                                            <th>Activate Name</th>
                                            <th>Category</th>
                                            <th>Coin Amount</th>
                                            <th>Total Amount</th>                                        
                                            <th>Order Number</th>
                                            <th>Sender Name</th>
                                            <th>Phone Number</th>
                                            <th>Manager</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="hospitalData" role="tabpanel" aria-labelledby="hospital-data">                            
                        <div class="table-responsive">
                            <table class="table table-striped" id="hospital-table">
                                <thead>
                                        <tr>
                                            <th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
                                                <div class="custom-checkbox custom-control">
                                                    <input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-inactive">
                                                    <label for="checkbox-inactive" class="custom-control-label">&nbsp;</label>
                                                </div>
                                            </th>
                                            <th>Activate Name</th>
                                            <th>Category</th>
                                            <th>Coin Amount</th>
                                            <th>Total Amount</th>                                        
                                            <th>Order Number</th>
                                            <th>Sender Name</th>
                                            <th>Phone Number</th>
                                            <th>Manager</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>               
            </div>
        </div>
    </div>
</div>
<div class="cover-spin"></div>
<!-- Modal -->
<div class="modal fade" id="categoryDeleteModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

@endsection

@section('scripts')
<script>
    var allTable = "{!! route('admin.reload-coin.all.table',[$countryId]) !!}";
    var hospitalTable = "{!! route('admin.reload-coin.all.hospital.table',[$countryId]) !!}";
    var shopTable = "{!! route('admin.reload-coin.all.shop.table',[$countryId]) !!}";    
    var csrfToken = "{{csrf_token()}}";  
    var pageModel = $("#categoryDeleteModal");  
    $("#country_id").change(function () {
       var countryId = this.value;
       sessionStorage.setItem("selectedCountry", countryId);
       var url = window.location.href.substring(0, window.location.href.indexOf('?'));
        location.href = url +"?countryId="+countryId;
        window.location.href;
   });
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/reload-coin/common.js') !!}"></script>
@endsection
