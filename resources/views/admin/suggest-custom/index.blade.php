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
                <div class="mb-3 row">
                    <div class="col-md-8">
                        <div class="font-weight-bold mb-1"> Client Total - {{$totalClients}} | User Total - {{$totalUsers}}</div>
                        <div class="font-weight-bold mb-1"> Hospital - {{$totalHospitals}} | Shop - {{$totalShops}}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="font-weight-bold mb-1"> Last Month Income - {{$lastMonthIncome}}</div>
                        <div class="font-weight-bold mb-1"> This Month Income - {{$currentMonthIncome}}</div>
                    </div>
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
                        <a class="nav-link btn btn-success" id="active-data" data-toggle="tab" href="#activeData" role="tab" aria-controls="shop" aria-selected="false">Activate</a>
                    </li>
                    <li class="nav-item mr-3">
                        <a class="nav-link btn btn-secondary" id="inactive-data" data-toggle="tab" href="#inactiveData" role="tab" aria-controls="shop" aria-selected="false">Not Activate</a>
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
                                        <th>Name</th>
                                        <th>Business Name</th>
                                        <th>Address</th>
                                        <th>Phone Number</th>
                                        <th>Purchased Credit</th>                                        
                                        <th>Join By</th>
                                        <th>Date</th>
                                        <th>Activate</th>
                                        <th>Credit Purchase Log</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="activeData" role="tabpanel" aria-labelledby="active-data">                            
                        <div class="table-responsive">
                            <table class="table table-striped" id="active-table">
                                <thead>
                                        <tr>
                                            <th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
                                                <div class="custom-checkbox custom-control">
                                                    <input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
                                                    <label for="checkbox-active" class="custom-control-label">&nbsp;</label>
                                                </div>
                                            </th>
                                            <th>Name</th>
                                            <th>Business Name</th>
                                            <th>Address</th>
                                            <th>Phone Number</th>
                                            <th>Purchased Credit</th>                                        
                                            <th>Join By</th>
                                            <th>Date</th>
                                            <th>Activate</th>
                                            <th>Credit Purchase Log</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="inactiveData" role="tabpanel" aria-labelledby="inactive-data">                            
                        <div class="table-responsive">
                            <table class="table table-striped" id="inactive-table">
                                <thead>
                                        <tr>
                                            <th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
                                                <div class="custom-checkbox custom-control">
                                                    <input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-inactive">
                                                    <label for="checkbox-inactive" class="custom-control-label">&nbsp;</label>
                                                </div>
                                            </th>
                                            <th>Name</th>
                                            <th>Business Name</th>
                                            <th>Address</th>
                                            <th>Phone Number</th>
                                            <th>Purchased Credit</th>                                        
                                            <th>Join By</th>
                                            <th>Date</th>
                                            <th>Activate</th>
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
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
<div class="modal fade" id="profileModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
@endsection

@section('scripts')
<script>
    var editModal = $("#editModal");
    var profileModal = $("#profileModal");
    var allTable = "{!! route('admin.suggest-custom.all.table') !!}";
    var activeTable = "{!! route('admin.suggest-custom.active.table') !!}";
    var inactiveTable = "{!! route('admin.suggest-custom.inactive.table') !!}";
    var addCredits = "{{ route('admin.business-client.add.credit') }}"; 
    var deleteBusinessProfile = "{{ route('admin.business-client.delete.profile') }}";
    var deleteUser = "{{ route('admin.business-client.delete.user') }}";
    var csrfToken = "{{csrf_token()}}";
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/suggest-custom/index.js') !!}"></script>
<script src="{!! asset('js/pages/business-client/common.js') !!}"></script>
@endsection

@section('page-script')
@endsection