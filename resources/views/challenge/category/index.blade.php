@extends('challenge-layouts.app')

@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/bootstrap-toggle/bootstrap4-toggle.min.css') !!}">
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
    <style>
        .table-responsive button#show-profile {
            width: auto;
            margin: 5px;
            white-space: normal;
        }

        .table-responsive .shops-date button#show-profile {
            width: 180px;
        }

        .table-responsive .shops-rate button#show-profile {
            width: 80px;
        }

        .table-responsive td span {
            margin: 5px;
        }

        .button-container {
            display: flex;
            flex-direction: row;
            /*justify-content: space-between;*/
            align-items: center;
        }

        .vertical-buttons {
            display: flex;
            flex-direction: column;
        }

        /*.button {
            margin-bottom: 10px;
        }*/
    </style>
@endsection

@section('header-content')
    <h1>
        @if (@$title)
            {{ @$title }}
        @endif
    </h1>
    <div class="section-header-button">
        <a class="btn btn-primary" id="add_category">{{ __('general.add_new') }}</a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <a class="btn btn-primary" onclick="filterData('all')">{{ __('general.all') }}</a>
                    <a class="btn btn-primary" onclick="filterData('period_challenge')">{{ __('general.period_challenge') }}</a>
                    <a class="btn btn-primary" onclick="filterData('challenge')">{{ __('general.challenge') }}</a>

                    <div class="tab-content" id="myTabContent2">
                        <div class="tab-pane fade show active" id="allData" role="tabpanel"
                            aria-labelledby="all-data">
                            <div class="table-responsive">
                                <table class="table table-striped" id="Category-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('datatable.category.title') }}</th>
                                            <th>{{ __('datatable.category.type') }}</th>
                                            <th>{{ __('datatable.category.order') }}</th>
                                            <th></th>
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
<div class="modal fade" id="newCategoryModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="categoryForm" method="post">
                {{ csrf_field() }}
                <div class="modal-header justify-content-center">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body justify-content-center">
                    <div class="align-items-xl-center mb-3">
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>Title</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="title" id="title" class="form-control"/>
                                @error('title')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>Type</label>
                            </div>
                            <div class="col-md-8">
                                <select name="challenge_type" class="form-control">
                                    <option value="1" selected>Challenge</option>
                                    <option value="2">Period challenge</option>
                                </select>
                                @error('challenge_type')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>Order</label>
                            </div>
                            <div class="col-md-8">
                                <input type="number" name="order" id="order" class="form-control"/>
                                @error('order')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{!! __(Lang::get('general.close')) !!}</button>
                    <button type="submit" class="btn btn-primary" id="save_btn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>

@section('scripts')
    <script>
        var allTable = "{!! route('challenge.category.table') !!}";
        var csrfToken = "{{ csrf_token() }}";
        var updateOrder = "{!! route('challenge.category.update.order') !!}";
    </script>
    <script src="{!! asset('plugins/bootstrap-toggle/bootstrap4-toggle.min.js') !!}"></script>
    <script src="{!! asset('plugins/jquery-ui/jquery-ui.js') !!}"></script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script>
        $(document).ready(function (){
            loadTableData("all");

            $("#Category-table > tbody").sortable({
                items: "tr",
                cursor: "move",
                opacity: 0.6,
                update: function () {
                    sendOrderToServer();
                },
            });
        })

        function loadTableData(filter) {
            var allData = $("#Category-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                "order": [[ 2, "ASC" ]],
                ajax: {
                    url: allTable,
                    dataType: "json",
                    type: "POST",
                    data: { _token: csrfToken, filter: filter },
                    dataSrc: function ( json ) {
                        setTimeout(function() {
                            $('.toggle-btn').bootstrapToggle();
                        }, 300);
                        return json.data;
                    }
                },
                createdRow: function(row, data, dataIndex) {
                    $(row).attr('data-id', data.id).addClass('row1');
                    $('.toggle-btn').bootstrapToggle();
                },
                columns: [
                    { data: "name", orderable: true },
                    { data: "type", orderable: false },
                    { data: "order", orderable: true },
                    { data: "status", orderable: false },
                    { data: "action", orderable: false },
                ]
            });
        }

        $(document).on('click', '#add_category', function (){
            $("#newCategoryModal").modal('show');
        })

        $('#newCategoryModal').on('hidden.bs.modal', function () {
            $("#categoryForm")[0].reset();
            $(".text-danger").remove();
        })

        $(document).on('submit', '#categoryForm', function (e){
            e.preventDefault();
            $(".text-danger").remove();
            var formData = $(this).serialize();

            $.ajax({
                url: "{{ url('challenge/category/add') }}",
                // processData: false,
                // contentType: false,
                type: 'POST',
                data: formData,
                success:function(response){
                    $(".cover-spin").hide();
                    if(response.success == true){
                        $("#newCategoryModal").modal('hide');
                        showToastMessage('Category added successfully.',true);
                        $('#Category-table').DataTable().ajax.reload();
                    }
                    else {
                        if(response.errors) {
                            var errors = response.errors;
                            $.each(errors, function (key, value) {
                                $('#' + key).next('.text-danger').remove();
                                $('#' + key).after('<div class="text-danger">' + value[0] + '</div>');
                            });
                        }
                        else {
                            showToastMessage(response.message,false);
                        }
                    }
                },
                beforeSend: function (){
                    $(".cover-spin").show();
                },
                error: function (xhr) {
                    $(".cover-spin").hide();
                    showToastMessage('Something went wrong!!',false);
                },
            });
        })

        $(document).on('change','.showhide-toggle-btn',function(e){
            var dataID = $(this).attr('data-id');
            $.ajax({
                type: "POST",
                dataType: "json",
                url: "{!! route('challenge.category.update-show-hide') !!}",
                data: {
                    data_id: dataID,
                    checked: e.target.checked,
                    _token: csrfToken,
                },
                success: function (response) {
                    $('#Category-table').DataTable().ajax.reload();
                },
            });
        });

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
                    $("#Category-table").dataTable().api().ajax.reload();
                },
            });
        }

        function editCategory(id) {
            $.get(baseUrl + '/challenge/category/edit/' + id, function (data, status) {
                $("#editModal").html('');
                $("#editModal").html(data);
                $("#editModal").modal('show');
            });
        }

        $(document).on('submit', '#editForm', function (e){
            e.preventDefault();
            $(".text-danger").remove();
            var formData = $(this).serialize();

            $.ajax({
                url: "{{ url('challenge/category/update') }}",
                // processData: false,
                // contentType: false,
                type: 'POST',
                data: formData,
                success:function(response){
                    $(".cover-spin").hide();
                    if(response.success == true){
                        $("#editModal").modal('hide');
                        showToastMessage('Category updated successfully.',true);
                        $('#Category-table').DataTable().ajax.reload();
                    }
                    else {
                        if(response.errors) {
                            var errors = response.errors;
                            $.each(errors, function (key, value) {
                                $('#' + key).next('.text-danger').remove();
                                $('#' + key).after('<div class="text-danger">' + value[0] + '</div>');
                            });
                        }
                        else {
                            showToastMessage(response.message,false);
                        }
                    }
                },
                beforeSend: function (){
                    $(".cover-spin").show();
                },
                error: function (xhr) {
                    $(".cover-spin").hide();
                    showToastMessage('Something went wrong!!',false);
                },
            });
        })

        function filterData(filter){
            $('#Category-table').DataTable().destroy();
            loadTableData(filter);
        }
    </script>
@endsection
