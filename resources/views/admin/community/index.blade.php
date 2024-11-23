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
                        <a class="nav-link active btn btn-primary filterButton" id="all-data" data-filter="all" data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="true">All</a>
                    </li>
                    @if($communityCategory)
                        @foreach($communityCategory as $category)
                            <li class="nav-item mr-3">
                                <a class="nav-link  btn btn-primary filterButton" id="all-{{$category->id}}" data-filter="{{$category->id}}" data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="true">{{$category->name}}</a>
                            </li>
                        @endforeach
                    @endif
                </ul>

                <div class="tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="all-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone Number</th>
                                        <th>Updated date</th>
                                        <th>Post title</th>
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
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

@section('scripts')
<script>
    var communityTable = "{{ route('admin.community.table') }}";
    var csrfToken = "{{csrf_token()}}";   
    var deleteUser = "{{ route('admin.community.delete.user') }}";
    var deleteModal = $("#deleteModal"); 
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/community/community.js') !!}"></script>
@endsection
