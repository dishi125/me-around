@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">

@endsection

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@role('Admin') 
    <div class="section-header-button">
        <a href="javascript:void(0);" onclick="editAllBusinessCredits('outside',`{{route('admin.give.all.user.credit')}}`);" class="btn btn-primary">Give coin to all Business User</a>
    </div> 
@endrole
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                <div class="tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="all-shop-table">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
                                            <div class="custom-checkbox custom-control">
                                                <input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-all">
                                                <label for="checkbox-all" class="custom-control-label">&nbsp;</label>
                                            </div>
                                        </th>
                                        <th>Name</th>
                                        <th>Address</th>
                                        <th>Phone Number</th>
                                        <th>Purchased Credit</th>                                        
                                        <th>Join By</th>
                                        <th>Date</th>
                                        <th>Rate</th>
                                        <th>Business Number</th>
                                        <th>Activate</th>
                                        <th>Credit Purchase Log</th>
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
<div class="modal fade" id="allBusinessModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
<div class="modal fade" id="profileModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
@endsection

@section('scripts')
<script>
    var editModal = $("#editModal");
    var profileModal = $("#profileModal");
    var allShopTable = "{!! route('admin.outside-user.all.shop.table') !!}";
    var activeShopTable = inactiveShopTable = "{!! route('admin.outside-user.all.shop.table') !!}";
    var addCredits = "{{ route('admin.business-client.add.credit') }}"; 
    var giveAllCredits = "{{ route('admin.business-client.give.all.credit') }}"; 
    var deleteBusinessProfile = "{{ route('admin.business-client.delete.profile') }}";
    var deleteUser = "{{ route('admin.business-client.delete.user') }}";
    var csrfToken = csrfToken;    
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/business-client/shop.js') !!}"></script>
<script src="{!! asset('js/pages/business-client/common.js') !!}"></script>
@endsection
