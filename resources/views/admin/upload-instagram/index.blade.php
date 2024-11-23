@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
@endsection

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
<div class="section-header-button">
    <a href="{{ url('admin/instagram/redirect') }}" class="btn btn-primary">Add Account</a>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="all-table">
                                <thead>
                                    <tr>
                                        <th>Activate name</th>
                                        <th>Shop name</th>
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

@section('scripts')
<script>
    var allTable = "{!! route('admin.upload-instagram.all.table') !!}";
    var csrfToken = "{{ csrf_token() }}";
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script type="text/javascript">
    $(function () {
        loadTableData();
    });

    function loadTableData() {
        $("#all-table").DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            deferRender: true,
            order: [[0, "desc"]],
            ajax: {
                url: allTable,
                dataType: "json",
                type: "POST",
                data: { _token: csrfToken },
            },
            columns: [
                {data: "active_name", orderable: true},
                {data: "shop_name", orderable: true},
                {data: "instagram", orderable: true},
                {data: "signup_date", orderable: true},
                {data: "email", orderable: true},
                {data: "view_shop", orderable: false},
                {data: "action", orderable: false},
            ],
        });
    }
</script>
@endsection
