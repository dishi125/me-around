@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
<style>
.table-responsive button#show-profile {width: 130px;margin:5px;white-space:normal;}
.table-responsive .shops-date button#show-profile{width:180px;}
.table-responsive .shops-rate button#show-profile{width:80px;}
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
                <ul class="nav nav-pills mb-4" id="myTab3" role="tablist">
                    <li class="nav-item mr-3">
                        <a class="nav-link active btn btn-primary filterButton" id="all-data" data-filter="oneday" data-toggle="tab" href="#" role="tab" aria-controls="bad" aria-selected="true">One Day</a>
                    </li>
                    <li class="nav-item mr-3">
                    <a class="nav-link btn btn-primary filterButton" id="all-data" data-filter="twoweek" data-toggle="tab" href="#" role="tab" aria-controls="bad" aria-selected="true">2 Weeks</a>
                    </li>
                </ul>
                <div class="tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="all-table">
                                <thead>
                                    <tr>
                                        <th>Business User Name</th>
                                        <th>Phone Number</th>
                                        <th>Customer Name</th>
                                        <th>Customer Phone Number</th>
                                        <th>Complete Times</th>
                                        <th>Booking Date</th>
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

<!-- Modal -->
<div class="modal fade" id="deletePostModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

@section('scripts')
<script>
    var allTableData = "{{ route('admin.bad-complete.all.table') }}";
    var twoWeekTableData = "{{ route('admin.bad-complete.two-week.table') }}";
    var csrfToken = "{{csrf_token()}}";    
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/bad-complete/index.js') !!}"></script>
@endsection
