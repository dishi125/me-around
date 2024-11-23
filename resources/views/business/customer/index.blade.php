@extends('business-layouts.app')

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
<div class="section-header-button">
    <a href="{{ route('business.posts.create') }}" class="btn btn-primary">Add New</a>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-pills mb-4" id="myTab3" role="tablist">
                    <li class="nav-item mr-3">
                        <a class="nav-link btn btn-primary filterButton" id="active-data" data-filter="booked_user" data-toggle="tab" href="#" role="tab" aria-controls="user" aria-selected="false">Booked User</a>
                    </li>
                    <li class="nav-item mr-3">
                        <a class="nav-link btn btn-primary filterButton" id="inactive-data" data-filter="visited_user" data-toggle="tab" href="#" role="tab" aria-controls="user" aria-selected="false">Visited User</a>
                    </li>
                    <li class="nav-item mr-3">
                        <a class="nav-link btn btn-primary filterButton" id="inactive-data" data-filter="completed_user" data-toggle="tab" href="#" role="tab" aria-controls="user" aria-selected="false">Completed User</a>
                    </li>
                </ul>

                <div class="tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="all-table">
                                <thead>
                                    <tr>
                                        <th>User Image</th>
                                        <th>User Name</th>
                                        <th>Category Name</th>
                                        <th>Category Image</th>
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
    var allUserTable = "{{ route('business.customers.table') }}";
    var csrfToken = "{{csrf_token()}}";    
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/business/customer/customers.js') !!}"></script>
@endsection
