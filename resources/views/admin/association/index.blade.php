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
                <div class="col-md-5 mb-3 float-right d-inline-flex">
                    <a href="{!! route('admin.association.manage') !!}" class="btn btn-primary mr-3">Create an association</a>
                    {!!Form::select('country', $countryData, '' , ['class' => 'form-control','id'=>'country'])!!}
                </div>
                <div class="tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="all-table">
                                <thead>
                                    <tr>
                                        <th>Association Name</th>
                                        <th>President</th>
                                        <th>Manager</th>
                                        <th>Member</th>
                                        <th>Status</th>
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

<style type="text/css">
    li.select2-selection__choice {
        COLOR: black !important;
    }  
</style>

<!-- Modal -->
<div class="modal fade" id="deletePostModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>
<div class="modal fade" id="deleteAssociationModal" tabindex="-1" role="dialog" aria-labelledby="deleteAssociationModal"></div>

@section('scripts')
<script type="text/javascript">
    var associationTableData = "{{ route('admin.association.data') }}";
    var form = "{{ route('admin.association.form') }}";
    var csrfToken = "{{csrf_token()}}";    
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/file-upload.js') !!}"></script>
<script src="{!! asset('js/pages/association/association.js?time()') !!}"></script>
@endsection
