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
@role('Admin')
    <div class="section-header-button">
        <a href="javascript:void(0);" onclick="editAllBusinessCredits('shop',`{{route('admin.give.all.user.credit')}}`);" class="btn btn-primary">Give coin to all Business User</a>
    </div> 
@endrole
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="mb-3 row">
                    <div class="col-md-4">
                        <div class="font-weight-bold mb-1"> Client Total - {{$totalClients}} | User Total - {{$totalUsers}}</div>
                        <div class="font-weight-bold mb-1"> Hospital - {{$totalHospitals}} | Shop - {{$totalShops}}</div>
                    </div>
                    <div class="col-md-8 text-right">
                        <div class="font-weight-bold mb-1"> Total income - {{$totalIncome}}</div>
                        <div class="font-weight-bold mb-1"> Last month income - {{$lastMonthIncome}}</div>
                        <div class="font-weight-bold mb-1"> This month income - {{$currentMonthIncome}}</div>
                    </div>
                </div>

                <div class="buttons">
                    <a href="{!! route('admin.business-client.index') !!}?manager_id={{$manager_id}}" class="btn btn-primary">All</a>
                    <a href="{!! route('admin.business-client.hospital.index') !!}?manager_id={{$manager_id}}" class="btn btn-primary">Hospital</a>
                    <a href="{!! route('admin.business-client.shop.index') !!}?manager_id={{$manager_id}}" class="btn btn-primary">Shop</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
            <ul class="nav nav-pills mb-4" id="myTab3" role="tablist">
                    <li class="nav-item mr-3">
                        <a class="nav-link active btn btn-primary" id="all-data" data-toggle="tab" href="#allData" role="tab" aria-controls="hospital" aria-selected="true">All</a>
                    </li>
                    <li class="nav-item mr-3">
                        <a class="nav-link btn btn-success" id="active-data" data-toggle="tab" href="#activeData" role="tab" aria-controls="shop" aria-selected="false">Activate</a>
                    </li>
                    <li class="nav-item mr-3">
                        <a class="nav-link btn btn-secondary" id="inactive-data" data-toggle="tab" href="#inactiveData" role="tab" aria-controls="shop" aria-selected="false">Not Activate</a>
                    </li>
                    <li class="nav-item mr-3">
                        <a class="nav-link btn" style="background-color: #fff700;"  id="pending-data" data-toggle="tab" href="#pendingData" role="tab" aria-controls="shop" aria-selected="false">Pending</a>
                    </li>
                </ul>

                <div class="tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="all-shop-table">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
                                            <div class="custom-checkbox custom-control">
                                                <input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-all">
                                                <label for="checkbox-all" class="custom-control-label">&nbsp;</label>
                                            </div>
                                        </th>
                                        <th>Name</th>
                                        <th>Address</th>
                                        <th>Phone Number</th>
                                        <th>Purchased Credit</th>                                        
                                        <th>Join By</th>
                                        <th>Date</th>
                                        <th>Rate</th>
                                        <th>Business Number</th>
                                        <th>Activate</th>
                                        <th>Referral</th>
                                        <th>Shop Profile</th>
                                        <th>Credit Purchase Log</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="activeData" role="tabpanel" aria-labelledby="active-data">                            
                        <div class="table-responsive">
                            <table class="table table-striped" id="active-shop-table">
                                <thead>
                                        <tr>
                                            <th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
                                                <div class="custom-checkbox custom-control">
                                                    <input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
                                                    <label for="checkbox-active" class="custom-control-label">&nbsp;</label>
                                                </div>
                                            </th>
                                            <th>Name</th>
                                            <th>Address</th>
                                            <th>Phone Number</th>
                                            <th>Purchased Credit</th>                                        
                                            <th>Join By</th>
                                            <th>Date</th>
                                            <th>Rate</th>
                                            <th>Business Number</th>
                                            <th>Activate</th>
                                            <th>Referral</th>
                                            <th>Shop Profile</th>
                                            <th>Credit Purchase Log</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="inactiveData" role="tabpanel" aria-labelledby="inactive-data">                            
                        <div class="table-responsive">
                            <table class="table table-striped" id="inactive-shop-table">
                                <thead>
                                        <tr>
                                            <th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
                                                <div class="custom-checkbox custom-control">
                                                    <input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-inactive">
                                                    <label for="checkbox-inactive" class="custom-control-label">&nbsp;</label>
                                                </div>
                                            </th>
                                            <th>Name</th>
                                            <th>Address</th>
                                            <th>Phone Number</th>
                                            <th>Purchased Credit</th>                                        
                                            <th>Join By</th>
                                            <th>Date</th>
                                            <th>Rate</th>
                                            <th>Business Number</th>
                                            <th>Activate</th>
                                            <th>Referral</th>
                                            <th>Shop Profile</th>
                                            <th>Credit Purchase Log</th>
                                            <th>Action</th>
                                        </tr>
                                </thead>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pendingData" role="tabpanel" aria-labelledby="pending-data">                            
                        <div class="table-responsive">
                            <table class="table table-striped" id="pending-shop-table">
                                <thead>
                                        <tr>
                                            <th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
                                                <div class="custom-checkbox custom-control">
                                                    <input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-inactive">
                                                    <label for="checkbox-pending" class="custom-control-label">&nbsp;</label>
                                                </div>
                                            </th>
                                            <th>Name</th>
                                            <th>Address</th>
                                            <th>Phone Number</th>
                                            <th>Purchased Credit</th>                                        
                                            <th>Join By</th>
                                            <th>Date</th>
                                            <th>Rate</th>
                                            <th>Business Number</th>
                                            <th>Activate</th>
                                            <th>Referral</th>
                                            <th>Shop Profile</th>
                                            <th>Credit Purchase Log</th>
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
<div class="modal fade" id="allBusinessModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
<div class="modal fade" id="profileModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
<div class="modal fade" id="profileLinkModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
@endsection

@section('scripts')
<script>
    var editModal = $("#editModal");
    var profileModal = $("#profileModal");
    var profileLinkModal = $("#profileLinkModal");
    var allShopTable = "{!! route('admin.business-client.all.shop.table',[$manager_id]) !!}";
    var activeShopTable = "{!! route('admin.business-client.active.shop.table',[$manager_id]) !!}";
    var inactiveShopTable = "{!! route('admin.business-client.inactive.shop.table',[$manager_id]) !!}";
    var pendingShopTable = "{!! route('admin.business-client.pending.shop.table',[$manager_id]) !!}";
    var addCredits = "{{ route('admin.business-client.add.credit') }}"; 
    var deleteBusinessProfile = "{{ route('admin.business-client.delete.profile') }}";
    var deleteUser = "{{ route('admin.business-client.delete.user') }}";
    var csrfToken = csrfToken;    
    var giveAllCredits = "{{ route('admin.business-client.give.all.credit') }}"; 
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/business-client/shop.js') !!}"></script>
<script src="{!! asset('js/pages/business-client/common.js') !!}"></script>
@endsection
