@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
@endsection

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')
<div class="col-md-12">
    <div class="card">
        <div class="card-body mt-2">
            <div class="col-md-4">
                <div class="font-weight-bold mb-1">Total Non-Login Users - {{$totalUser}}</div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                <div class="tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="all-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Gender</th>
                                        <th>Location</th>
                                        <th>Love Count</th>
                                        <th>First Access</th>
                                        <th>Last Access</th>
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
@endsection

<div class="modal fade" id="show-locations" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

@section('scripts')
<script>
    var allTable = "{!! route('admin.non-login-user.all.table') !!}";
    var csrfToken = csrfToken;
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/users/non-login.js') !!}"></script>
@endsection
