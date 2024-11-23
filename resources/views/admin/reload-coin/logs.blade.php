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
                    <div class="col-md-4">
                        <div class="font-weight-bold mb-1"> Client Total - {{$totalClients}} | User Total - {{$totalUsers}}</div>
                        <div class="font-weight-bold mb-1"> Hospital - {{$totalHospitals}} | Shop - {{$totalShops}}</div>
                    </div>
                    <div class="col-md-8 text-right">
                        <div class="font-weight-bold mb-1"> Your client total purchase coins - {{$totalIncome}}</div>
                        <div class="font-weight-bold mb-1"> Your client purchase coins last month - {{$lastMonthIncome}}</div>
                        <div class="font-weight-bold mb-1"> Your client purchase coins this month - {{$currentMonthIncome}}</div>
                    </div>
                </div>
                <div class="buttons">
                    <a href="{!! route('admin.my-business-client.index') !!}?manager_id={{$manager_id}}" class="btn btn-primary">All</a>
                    <a href="{!! route('admin.my-business-client.hospital.index') !!}?manager_id={{$manager_id}}" class="btn btn-primary">Hospital</a>
                    <a href="{!! route('admin.my-business-client.shop.index') !!}?manager_id={{$manager_id}}" class="btn btn-primary">Shop</a>
                    <a href="{!! route('admin.reload.coin-logs.show') !!}?manager_id={{$manager_id}}" class="btn btn-primary ml-5">Reload Coin Logs</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="all-table">
                                <thead>
                                    <tr>
                                        <th>User Name</th>
                                        <th>Active Name</th>
                                        <th>Address</th>
                                        <th>Phone Number</th>
                                        <th>Reloaded Amount</th>                                        
                                        <th>Current Coin</th>
                                        <th>Date</th>
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
@endsection

@section('scripts')
<script>
    var allTable = "{!! route('admin.reload.coin-logs.data') !!}";
    var csrfToken = csrfToken;    
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/reload-coin/logs.js') !!}"></script>
@endsection
