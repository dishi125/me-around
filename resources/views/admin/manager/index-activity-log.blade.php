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
                    <a href="{!! route('admin.manager.index') !!}" class="btn btn-primary">Manager List</a>
                    <a href="{!! route('admin.manager.activity-log.index') !!}" class="btn btn-primary">Activity Log</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-pills mb-4" id="myTab3" role="tablist">
                    <li class="nav-item mr-3">
                        <a class="nav-link active btn btn-primary" id="activity-log-data" data-toggle="tab" href="#allActivity" role="tab" aria-controls="activity-log" aria-selected="true">All</a>
                    </li>
                    <li class="nav-item mr-3">
                        <a class="nav-link btn btn-primary" id="deducting-rate-activity" data-toggle="tab" href="#deductingRateData" role="tab" aria-controls="activity-log" aria-selected="false">Deducting Rate</a>
                    </li>             
                    <li class="nav-item mr-3">
                        <a class="nav-link btn btn-primary" id="client-credit-activity" data-toggle="tab" href="#clientCreditData" role="tab" aria-controls="activity-log" aria-selected="false">Edit Client Credit</a>
                    </li>             
                    <li class="nav-item mr-3">
                        <a class="nav-link btn btn-primary" id="delete-account-activity" data-toggle="tab" href="#deleteAccountData" role="tab" aria-controls="activity-log" aria-selected="false">Delete Account</a>
                    </li>             
                </ul>

                <div class="tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="allActivity" role="tabpanel" aria-labelledby="activity-log-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="all-activity-table">
                                <thead>
                                    <tr>                                        
                                        <th>Manager Name</th>
                                        <th>Activity</th>
                                        <th>IP</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="deductingRateData" role="tabpanel" aria-labelledby="deducting-rate-activity">                            
                        <div class="table-responsive">
                            <table class="table table-striped" id="deducting-rate-activity-table">
                                <thead>
                                    <tr>                                        
                                        <th>Manager Name</th>
                                        <th>Activity</th>
                                        <th>IP</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>                    
                    <div class="tab-pane fade" id="clientCreditData" role="tabpanel" aria-labelledby="client-credit-activity">                            
                        <div class="table-responsive">
                            <table class="table table-striped" id="client-credit-activity-table">
                                <thead>
                                    <tr>                                        
                                        <th>Manager Name</th>
                                        <th>Activity</th>
                                        <th>IP</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>                    
                    <div class="tab-pane fade" id="deleteAccountData" role="tabpanel" aria-labelledby="delete-account-activity">                            
                        <div class="table-responsive">
                            <table class="table table-striped" id="delete-account-activity-table">
                                <thead>
                                    <tr>                                        
                                        <th>Manager Name</th>
                                        <th>Activity</th>
                                        <th>IP</th>
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
    var allActivityTable = "{{ route('admin.manager.activity-log.all.table') }}";
    var deductingRateTable = "{{ route('admin.manager.activity-log.deducting-rate.table') }}";
    var clientCreditTable = "{{ route('admin.manager.activity-log.client-credit.table') }}";
    var deleteAccountTable = "{{ route('admin.manager.activity-log.delete-account.table') }}";
    var csrfToken = "{{csrf_token()}}";    
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/manager/activity-log.js') !!}"></script>
@endsection
