@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
@endsection

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
<div class="section-header-button">
    <?php $user = Auth::user();?>
    <a href="{{ route('admin.insta-category.create') }}" class="btn btn-primary">Add New</a>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="insta_category_data">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Sub Title</th>
                                <th>Order</th>
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
<div class="modal fade" id="InstaCategoryDeleteModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
@endsection

@section('scripts')
<script src="{!! asset('plugins/jquery-ui/jquery-ui.js') !!}"></script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script type="text/javascript">
var updateOrder = "{!! route('admin.insta-category.update.order') !!}";
var csrfToken = "{{csrf_token()}}";

$(function () {
    var dataTable = $('#insta_category_data').DataTable({
        "responsive": true,
        "processing": true,
        "serverSide": true,
        "deferRender": true,
        order: [[ 2, "asc" ]],
        "ajax": {
            "url": "{{ route('admin.insta-category.table') }}",
            "dataType": "json",
            "type": "POST",
            "data": { _token: "{{csrf_token()}}" }
        },
        createdRow: function(row, data, dataIndex) {
            $(row).attr('data-id', data.id).addClass('row1');
        },
        "columns": [
            { "data": "title", orderable: true },
            { "data": "sub_title", orderable: true },
            { "data": "order", orderable: true },
            { "data": "actions", orderable: false }
        ]
    });

    $("#insta_category_data > tbody").sortable({
        items: "tr",
        cursor: "move",
        opacity: 0.6,
        update: function () {
            sendOrderToServer();
        },
    });
});

function deleteInstaCategory(id) {
    var pageModel = $("#InstaCategoryDeleteModal");

    $.get("{{ url('admin/insta-category/delete') }}" + "/" + id, function(data, status) {
        pageModel.html('');
        pageModel.html(data);
        pageModel.modal('show');
    });
}

function sendOrderToServer() {
    var order = [];
    $("tr.row1").each(function (index, element) {
        order.push({
            id: $(this).attr("data-id"),
            position: index + 1,
        });
    });

    $.ajax({
        type: "POST",
        dataType: "json",
        url: updateOrder,
        data: {
            order: order,
            _token: csrfToken,
        },
        success: function (response) {
            $("#insta_category_data").dataTable().api().ajax.reload();
        },
    });
}
</script>
@endsection
