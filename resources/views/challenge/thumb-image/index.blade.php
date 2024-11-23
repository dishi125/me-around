@extends('challenge-layouts.app')

@section('styles')
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
        <button class="btn btn-primary mr-2" id="add_thumb">{{ __('general.add_thumb') }}</button>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="tab-content" id="myTabContent2">
                        <div class="tab-pane fade show active" id="allData" role="tabpanel"
                            aria-labelledby="all-data">
                            <div class="table-responsive">
                                <table class="table table-striped" id="thumb-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('datatable.thumb_list.thumb') }}</th>
                                            <th>{{ __('datatable.thumb_list.type') }}</th>
                                            <th>{{ __('datatable.thumb_list.category') }}</th>
                                            <th>{{ __('datatable.thumb_list.order') }}</th>
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
<div class="modal fade" id="addThumbModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="thumbForm" method="post">
                {{ csrf_field() }}
                <div class="modal-header justify-content-center">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body justify-content-center">
                    <div class="align-items-xl-center mb-3">
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>{{ __('forms.thumb.image') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="file" name="image" id="image" class="form-control"/>
                                @error('image')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>{{ __('forms.thumb.order') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="order" id="order" class="form-control"/>
                                @error('order')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>{{ __('forms.thumb.type') }}</label>
                            </div>
                            <div class="col-md-8">
                                <select name="challenge_type" class="form-control challenge_type" id="challenge_type">
                                    <option selected disabled>Select...</option>
                                    <option value="1">{{ __('general.challenge') }}</option>
                                    <option value="2">{{ __('general.period_challenge') }}</option>
                                </select>
                                @error('challenge_type')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2" style="display: none" id="category_div">
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
        var allThumbTable = "{{ route('challenge.thumb-image.all.table') }}";
        var csrfToken = "{{ csrf_token() }}";
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script>
        $(document).ready(function (){
            loadTableData();
        })

        function loadTableData() {
            var allData = $("#thumb-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                order: [[ 3, "ASC" ]],
                ajax: {
                    url: allThumbTable,
                    dataType: "json",
                    type: "POST",
                    data: {
                        _token: csrfToken,
                    }
                },
                columns: [
                    {
                        data: "image",
                        orderable: false
                    },
                    {
                        data: "type",
                        orderable: false
                    },
                    {
                        data: "category",
                        orderable: false
                    },
                    {
                        data: "order",
                        orderable: true
                    },
                    {
                        data: "action",
                        orderable: false
                    },
                ]
            });
        }

        $(document).on('click', '#add_thumb', function (){
            $("#addThumbModal").modal('show');
        })

        $('#addThumbModal').on('hidden.bs.modal', function () {
            $("#thumbForm")[0].reset();
            $(".text-danger").remove();
            $("#category_div").html("");
            $("#category_div").hide();
        })

        $(document).on('submit', '#thumbForm', function (e){
            e.preventDefault();
            $(".text-danger").remove();
            // var formData = $(this).serialize();
            var formData = new FormData(this);

            $.ajax({
                url: "{{ url('challenge/thumb-image/store') }}",
                processData: false,
                contentType: false,
                type: 'POST',
                data: formData,
                success:function(response){
                    $(".cover-spin").hide();
                    if(response.success == true){
                        $("#addThumbModal").modal('hide');
                        showToastMessage('Thumb added successfully.',true);
                        $('#thumb-table').DataTable().destroy();
                        loadTableData();
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

        $(document).on('change','.challenge_type',function (){
            var challenge_type = $(this).val();
            $(this).parents('.align-items-xl-center:first').find("#category_div").html("");
            $(this).parents('.align-items-xl-center:first').find("#category_div").hide();
            var thi = $(this);
            $.ajax({
                url: "{{ url('challenge/get/category-dropdown') }}",
                type: 'POST',
                data: {challenge_type: challenge_type},
                success:function(response){
                    $(thi).parents('.align-items-xl-center:first').find("#category_div").html(response.html);
                    $(thi).parents('.align-items-xl-center:first').find("#category_div").show();
                },
                error: function (xhr) {
                },
            });
        })

        function editThumb(id) {
            $.get(baseUrl + '/challenge/thumb-image/edit/' + id, function (data, status) {
                $("#editModal").html('');
                $("#editModal").html(data);
                $("#editModal").modal('show');
            });
        }

        $(document).on('submit', '#editThumbForm', function (e){
            e.preventDefault();
            $(".text-danger").remove();
            // var formData = $(this).serialize();
            var formData = new FormData(this);

            $.ajax({
                url: "{{ url('challenge/thumb-image/update') }}",
                processData: false,
                contentType: false,
                type: 'POST',
                data: formData,
                success:function(response){
                    $(".cover-spin").hide();
                    if(response.success == true){
                        $("#editModal").modal('hide');
                        showToastMessage('Thumb updated successfully.',true);
                        $('#thumb-table').DataTable().destroy();
                        loadTableData();
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
                            showToastMessage("Something went wrong!!",false);
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
    </script>
@endsection
