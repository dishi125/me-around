@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
@endsection

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
<div class="section-header-button">
    <?php $user = Auth::user();?>
    <a href="{{ route('admin.big-category.create') }}" class="btn btn-primary">Add New</a>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="big_category_data">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Korean Name</th>
                                <th>Image</th>
                                <th>Order</th>
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

<!-- Modal -->
<div class="modal fade" id="BigCategoryDeleteModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
@endsection

@section('scripts')
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script type="text/javascript">
$(function () {
    var dataTable = $('#big_category_data').DataTable({
        "responsive": true,
        "processing": true,
        "serverSide": true,
        "deferRender": true,
        "ajax": {
            "url": "{{ route('admin.big-category.table') }}",
            "dataType": "json",
            "type": "POST",
            "data": { _token: "{{csrf_token()}}" }
        },
        "columns": [
            { "data": "name", orderable: true },
            { "data": "koreanname", orderable: true },
            { "data": "image", orderable: false },
            { "data": "order", orderable: true },
            { "data": "status", orderable: true },
            { "data": "actions", orderable: false }
        ]
    });
});

function deleteBigCategory(id) {
    var pageModel = $("#BigCategoryDeleteModal");

    $.get("{{ url('admin/big-category/delete') }}" + "/" + id, function(data, status) {
        pageModel.html('');
        pageModel.html(data);
        pageModel.modal('show');
    });
}
</script>
@endsection
