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
                <ul class="nav nav-pills mb-4" id="myTab3" role="tablist">
                    <li class="nav-item mr-3 mb-3">
                        <a class="nav-link active btn btn-primary filterButton" id="all-data" data-filter="all"
                             >All</a>
                    </li>
                    <li class="nav-item mr-3 mb-3">
                        <a class="nav-link btn btn-success filterButton" id="active-data" data-filter="active"
                             >Green</a>
                    </li>
                    <li class="nav-item mr-3 mb-3">
                        <a class="nav-link btn  filterButton" style="background-color: #fff700;"  id="inactive-data" data-filter="inactive"
                             >Yellow</a>
                    </li>
                    <li class="nav-item mr-3 mb-3">
                        <a class="nav-link btn btn-secondary filterButton" id="pending-data"
                            data-filter="disconnect"  >Red</a>
                    </li>
                </ul>
                <div class="tab-content" id="myTabContent2">
                    <button id="send_mail_all_yellow" class="btn btn-primary" style="display: none">
                        Send e-mail for instagram yellow status accounts
                    </button>

                    <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="all-table">
                                <thead>
                                    <tr>
                                        <th>Activate name</th>
                                        <th>Shop name</th>
                                        <th>Status</th>
                                        <th>Instagram account</th>
                                        <th>Signup Date</th>
                                        <th>E-mail</th>
                                        <th>View Shop</th>
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
<div class="modal fade" id="DisconnectInstaModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5>Disconnect Instagram</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center">
                <h6>Are you sure you want to disconnect instagram?</h6>
            </div>
            <div class="modal-footer">
                <form action="" method="post" id="disconnectInstaForm">
                    @csrf
                    <input type="hidden" value="" name="insta_id" />
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Confirm</button>
                </form>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
    var allTable = "{!! route('admin.instagram-account.all.table') !!}";
    var csrfToken = csrfToken;
    var disconnectInstaUrl = "{{ url('admin/disconnect/instagram') }}";
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/instagram-account/index.js') !!}"></script>
@endsection
