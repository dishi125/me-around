@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
@endsection

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection
<?php $user = Auth::user(); ?>

@section('content')
<div class="row">
    <?php /*
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">                
                <div class="buttons">
                    <a href="{!! route('admin.manager.index') !!}" class="btn btn-primary">Manager List</a>
                    <!-- <a href="{!! route('admin.manager.activity-log.index') !!}" class="btn btn-primary">Activity Log</a> -->
                </div>
            </div>
        </div>
    </div>
    */?>
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-pills mb-4" id="myTab3" role="tablist">
                    <li class="nav-item mr-3">
                        <a class="nav-link active btn btn-primary" id="submanager-data" data-toggle="tab" href="#submanagerData" role="tab" aria-controls="sub-managers" aria-selected="false">Supporter</a>
                    </li>                    
                    <li class="nav-item mr-3">
                        <a class="nav-link  btn btn-primary" id="manager-data" data-toggle="tab" href="#managerData" role="tab" aria-controls="managers" aria-selected="true">Company</a>
                    </li>
                                      
                    <a class="btn btn-primary" href="{{ route('admin.manager.create') }}">Add Company/Supporter</a>
                </ul>

                <div class="tab-content" id="myTabContent2">
                    <div class="tab-pane fade " id="managerData" role="tabpanel" aria-labelledby="manager-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="manager-table">
                                <thead>
                                    <tr>                                        
                                        <th>Name</th>
                                        <th>Phone Number</th>
                                        <th>Email</th>
                                        <th>Client Count</th>
                                        <th>Active / Inactive Count</th>
                                        <th>Date</th>
                                        <th>Hospital & Shop count</th>
                                        <th>Supporter Code</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade show active" id="submanagerData" role="tabpanel" aria-labelledby="submanager-data">                            
                        <div class="table-responsive">
                            <table class="table table-striped" id="sub-manager-table">
                                <thead>
                                    <tr>                                        
                                        <th>Name</th>
                                        <th>Phone Number</th>
                                        <th>Email</th>
                                        <th>Client Count</th>
                                        <th>Active / Inactive Count</th>
                                        <th>Date</th>
                                        <th>Hospital & Shop count</th>
                                        <th>Supporter Code</th>
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
<div class="modal fade" id="managerDeleteModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
@endsection

@section('scripts')
<script>
    var pageModel = $("#managerDeleteModal");
    var managerTable = "{{ route('admin.manager.all.table') }}";
    var subManagerTable = "{{ route('admin.manager.sub-manager.table') }}";
    var csrfToken = "{{csrf_token()}}";    
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/manager/manager.js') !!}"></script>
@endsection
