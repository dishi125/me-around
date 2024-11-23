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
                
                <div class="buttons">
                    <a href="{!! route('admin.requested-client.index') !!}" class="btn btn-primary">All</a>
                    <a href="{!! route('admin.requested-client.hospital.index') !!}" class="btn btn-primary">Hospital</a>
                    <a href="{!! route('admin.requested-client.shop.index') !!}" class="btn btn-primary">Shop</a>
                    <a href="{!! route('admin.requested-client.suggest.index') !!}" class="btn btn-primary">Suggest</a>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="type" value="{{$type}}" id="type" />
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                
                <div class="confirm-btn">
                    <input type="submit" disabled  class="btn btn-primary" id="confirm_hospital" name="confirm_hospital" value="Confirm">
                    <input type="submit" disabled  class="btn btn-primary ml-2" id="reject_hospital" name="reject_hospital" value="Reject">
                    <input type="submit" disabled  class="btn btn-primary ml-2 mr-5" id="reject_mention" name="reject_mention" value="Reject Mention">
                    
                    <a href="{!! route('admin.requested-client.confirmed.index') !!}" class="btn btn-primary ml-5">Confirmed</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped" id="all-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email Address</th>
                                <th>Phone Number</th>
                                <th>SignUp date</th>
                                <th>Business Type</th>                                        
                                <th>Status</th>                                        
                                <th>Last access MeAround</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                    
            </div>
        </div>
    </div>
</div>
<div class="cover-spin"></div>

<!-- Modal -->
<div class="modal fade" id="deletePostModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
<!-- Modal -->
@endsection

@section('scripts')
<script>
    var editModal = $("#editModal");
    var allUserTable = "{{ route('admin.requested-client.confirmed.all.table') }}";
    var profileModal = $("#deletePostModal");
    var csrfToken = "{{csrf_token()}}";    

</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/requested-client/confirmed.js') !!}"></script>
@endsection
