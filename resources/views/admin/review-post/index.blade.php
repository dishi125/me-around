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
                        <a class="nav-link active btn btn-primary filterButton" id="all-data" data-filter="all" data-toggle="tab" href="#" role="tab" aria-controls="bad" aria-selected="true">All</a>
                    </li>
                    <li class="nav-item mr-3">
                        <a class="nav-link btn btn-primary filterButton" id="shop-data" data-filter="shop" data-toggle="tab" href="#" role="tab" aria-controls="bad" aria-selected="true">Shop</a>
                    </li>
                    <li class="nav-item mr-3">
                        <a class="nav-link btn btn-primary filterButton" id="hospital-data" data-filter="hospital" data-toggle="tab" href="#" role="tab" aria-controls="bad" aria-selected="true">Hospital</a>
                    </li>
                </ul>
                <div class="tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="all-table">
                                <thead>
                                    <tr>
                                        <th>User Name</th>
                                        <th>User Phone Number</th>
                                        <th>Business Name</th>
                                        <th>Business Phone Number</th>
                                        <th>Updated Date</th>
                                        <th>Images</th>
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

<div class="modal fade" id="PostPhotoModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header justify-content-center" style="border-bottom:none; padding: 8px;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center" style="padding: 0px;">
                <img src="{!! asset('img/logo-main.png') !!}" class="w-100 " id="modelImageEle" />
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
    var reviewTableData = "{{ route('admin.review-post.table') }}";
    var csrfToken = "{{csrf_token()}}";    
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/review-post/index.js') !!}"></script>
@endsection
