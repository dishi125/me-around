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
                                        <th>Card Name</th>
                                        <th>User Name</th>
                                        <th>Email Address</th>
                                        <th>Phone Number</th>
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
@section('page-script')
<script>
    var userCardsTable = "{{ route('admin.cards.table.users',['card' => $card]) }}";
    var csrfToken = "{{csrf_token()}}";   
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/cards/users.js') !!}"></script>
@endsection