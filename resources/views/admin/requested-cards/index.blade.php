@extends('layouts.app')
@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="requested-card-data" role="tabpanel" aria-labelledby="all-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="requested-card-table">
                                <thead>
                                    <tr>
                                        <th>User Name</th>
                                        <th>Recipient Name</th>
                                        <th>Bank Name</th>
                                        <th>Bank Account Number</th>
                                        <th>Price</th>
                                        <th>Name</th>
                                        <th>Range</th>
                                        <th>Card Level</th>
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

<div class="modal fade" id="processedModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>

@endsection
@section('page-script')
<script>
    var requestedCardsTable = "{{ route('admin.requested-cards.table') }}";
    var processedModal = $("#processedModal");
    var rejectModal = $("#rejectModal");
    var csrfToken = "{{csrf_token()}}";
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/cards/requested.js') !!}"></script>
@endsection
