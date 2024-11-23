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
        <button class="btn btn-primary mr-2" id="add_new">Add new</button>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="tab-content" id="myTabContent2">
                        <button class="btn btn-primary mr-2" id="update_image" onclick="editImage()">Image</button>
                        <button class="btn btn-primary" id="update_bio" onclick="editBio()">Bio</button>

                        <div class="tab-pane fade show active" id="allData" role="tabpanel"
                            aria-labelledby="all-data">
                            <div class="table-responsive">
                                <table class="table table-striped" id="Notice-table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Notice</th>
                                            <th>Time</th>
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
<div class="modal fade" id="newNoticeModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="noticeForm" method="post">
                {{ csrf_field() }}
                <div class="modal-header justify-content-center">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body justify-content-center">
                    <div class="align-items-xl-center mb-3">
                        <div class="row mb-2">
                            <div class="col-md-2">
                                <label>Title</label>
                            </div>
                            <div class="col-md-10">
                                <input type="text" name="title" id="title" class="form-control"/>
                                @error('title')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-2">
                                <label>Notice</label>
                            </div>
                            <div class="col-md-10">
                                <textarea name="notice" id="notice" class="form-control" style="height: 300px;"></textarea>
                                @error('notice')
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

<div class="modal fade" id="deleteNoticeModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

@section('scripts')
    <script>
        var allDataTable = "{{ route('challenge.admin-notice.all.table') }}";
        var csrfToken = "{{ csrf_token() }}";
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script>
        $(document).ready(function (){
            loadTableData();
        })

        function loadTableData() {
            var allData = $("#Notice-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                order: [[ 2, "desc" ]],
                ajax: {
                    url: allDataTable,
                    dataType: "json",
                    type: "POST",
                    data: {
                        _token: csrfToken,
                    }
                },
                columns: [
                    {
                        data: "title",
                        orderable: true
                    },
                    {
                        data: "notice",
                        orderable: true
                    },
                    {
                        data: "time",
                        orderable: true
                    },
                    {
                        data: "action",
                        orderable: false
                    },
                ]
            });
        }

        $(document).on('click', '#add_new', function (){
            $("#newNoticeModal").modal('show');
        })

        $('#newNoticeModal').on('hidden.bs.modal', function () {
            $("#noticeForm")[0].reset();
            $(".text-danger").remove();
        })

        $(document).on('submit', '#noticeForm', function (e){
            e.preventDefault();
            $(".text-danger").remove();
            var formData = $(this).serialize();

            $.ajax({
                url: "{{ url('challenge/admin-notice/add') }}",
                // processData: false,
                // contentType: false,
                type: 'POST',
                data: formData,
                success:function(response){
                    $(".cover-spin").hide();
                    if(response.success == true){
                        $("#newNoticeModal").modal('hide');
                        showToastMessage('Notice added successfully.',true);
                        $('#Notice-table').DataTable().destroy();
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

        function editImage() {
            $.get(baseUrl + '/challenge/admin-notice/edit/image', function (data, status) {
                $("#editModal").html('');
                $("#editModal").html(data);
                $("#editModal").modal('show');
            });
        }

        $(document).on('submit', '#editImageForm', function (e){
            e.preventDefault();
            $(".text-danger").remove();
            // var formData = $(this).serialize();
            var formData = new FormData(this);

            $.ajax({
                url: "{{ url('challenge/admin-notice/update-image') }}",
                processData: false,
                contentType: false,
                type: 'POST',
                data: formData,
                success:function(response){
                    $(".cover-spin").hide();
                    if(response.success == true){
                        $("#editModal").modal('hide');
                        showToastMessage('Image updated successfully.',true);
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

        $(document).on('change','#admin_image',function (){
            let reader = new FileReader();
            reader.onload = (e) => {
                $('#image_preview').attr('src', e.target.result);
            }
            reader.readAsDataURL(this.files[0]);
        })

        function editBio() {
            $.get(baseUrl + '/challenge/admin-notice/edit/bio', function (data, status) {
                $("#editModal").html('');
                $("#editModal").html(data);
                $("#editModal").modal('show');
            });
        }

        $(document).on('submit', '#editBioForm', function (e){
            e.preventDefault();
            $(".text-danger").remove();
            // var formData = $(this).serialize();
            var formData = new FormData(this);

            $.ajax({
                url: "{{ url('challenge/admin-notice/update-bio') }}",
                processData: false,
                contentType: false,
                type: 'POST',
                data: formData,
                success:function(response){
                    $(".cover-spin").hide();
                    if(response.success == true){
                        $("#editModal").modal('hide');
                        showToastMessage('Bio updated successfully.',true);
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

        function editNotice(id) {
            $.get(baseUrl + '/challenge/admin-notice/edit/' + id, function (data, status) {
                $("#editModal").html('');
                $("#editModal").html(data);
                $("#editModal").modal('show');
            });
        }

        $(document).on('submit', '#editnoticeForm', function (e){
            e.preventDefault();
            $(".text-danger").remove();
            // var formData = $(this).serialize();
            var formData = new FormData(this);

            $.ajax({
                url: "{{ url('challenge/admin-notice/update') }}",
                processData: false,
                contentType: false,
                type: 'POST',
                data: formData,
                success:function(response){
                    $(".cover-spin").hide();
                    if(response.success == true){
                        $("#editModal").modal('hide');
                        showToastMessage('Admin notice updated successfully.',true);
                        $('#Notice-table').DataTable().destroy();
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

        function deleteNotice(id) {
            $.get(baseUrl + '/challenge/admin-notice/get-delete/' + id, function (data, status) {
                $("#deleteNoticeModal").html('');
                $("#deleteNoticeModal").html(data);
                $("#deleteNoticeModal").modal('show');
            });
        }

        $(document).on('click', '#deleteNotice', function(e) {
            var noticeId = $(this).attr('notice-id');
            $.ajax({
                url: baseUrl + "/challenge/admin-notice/delete",
                method: 'POST',
                data: {
                    '_token': $('meta[name="csrf-token"]').attr('content'),
                    'noticeId': noticeId,
                },
                success: function (data) {
                    $("#deleteNoticeModal").modal('hide');
                    $('#Notice-table').dataTable().api().ajax.reload();

                    if(data.status_code == 200) {
                        iziToast.success({
                            title: '',
                            message: data.message,
                            position: 'topRight',
                            progressBar: false,
                            timeout: 1000,
                        });
                    }else {
                        iziToast.error({
                            title: '',
                            message: data.message,
                            position: 'topRight',
                            progressBar: false,
                            timeout: 1000,
                        });
                    }
                }
            });
        });
    </script>
@endsection
