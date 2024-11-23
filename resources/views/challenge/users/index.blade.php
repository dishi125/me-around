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
        <button class="btn btn-primary mr-2" id="add_user">{{ __('general.add_user') }}</button>
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
                                <table class="table table-striped" id="all-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('datatable.user.name') }}</th>
                                            <th>{{ __('datatable.user.email') }}</th>
                                            @if(Auth::user()->hasRole("Sub Admin"))
                                            <th></th>
                                            @else
                                            <th>{{ __('datatable.user.phone') }}</th>
                                            @endif
                                            <th>{{ __('datatable.user.signup_date') }}</th>
                                            <th>{{ __('datatable.user.last_access') }}</th>
                                            <th>Achievement Rate</th>
                                            <th></th>
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
<div class="modal fade" id="newUserModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="userForm" method="post">
                {{ csrf_field() }}
                <div class="modal-header justify-content-center">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body justify-content-center">
                    <div class="align-items-xl-center mb-3">
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>User Name</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="user_name" id="user_name" class="form-control"/>
                                @error('user_name')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>E-mail</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="email" id="email" class="form-control"/>
                                @error('email')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>Phone Number</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="phone" id="phone" class="form-control"/>
                                @error('phone')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>Gender</label>
                            </div>
                            <div class="col-md-8">
                                <select id="gender" name="gender" class="form-control">
                                    <option value="Female" selected>Female</option>
                                    <option value="Male">Male</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>Password</label>
                            </div>
                            <div class="col-md-8">
                                <input type="password" name="password" id="password" class="form-control"/>
                                @error('password')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>Confirm Password</label>
                            </div>
                            <div class="col-md-8">
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control"/>
                                @error('password_confirmation')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>Referral code</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="referral_code" id="referral_code" class="form-control"/>
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

<div class="modal fade" id="challengeListModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>

<div class="modal fade" id="seeChallengeModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>

<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>

@section('scripts')
    <script>
        var allUserTable = "{{ route('challenge.user.all.table') }}";
        var csrfToken = "{{ csrf_token() }}";
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script>
        $(document).ready(function (){
            loadTableData('all','all');
        })

        function loadTableData(filter,category) {
            var filter = filter || 'all';
            var category = category || 'all';

            var allHospital = $("#all-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                order: [[ 3, "desc" ]],
                ajax: {
                    url: allUserTable,
                    dataType: "json",
                    type: "POST",
                    data: {
                        _token: csrfToken,
                        filter: filter,
                        category: category,
                    }
                },
                columns: [
                    {
                        data: "name",
                        orderable: true
                    },
                    {
                        data: "email",
                        orderable: true
                    },
                    {
                        data: "phone",
                        orderable: false
                    },
                    {
                        data: "signup",
                        orderable: true
                    },
                    {
                        data: "last_access",
                        orderable: true
                    },
                    {
                        data: "achievement",
                        orderable: false
                    },
                    {
                        data: "action",
                        orderable: false
                    },
                ]
            });
        }

        $(document).on('click', '#add_user', function (){
            $("#newUserModal").modal('show');
        })

        $('#newUserModal').on('hidden.bs.modal', function () {
            $("#userForm")[0].reset();
            $(".text-danger").remove();
        })

        $(document).on('submit', '#userForm', function (e){
            e.preventDefault();
            $(".text-danger").remove();
            var formData = $(this).serialize();

            $.ajax({
                url: "{{ url('challenge/users/add-user') }}",
                // processData: false,
                // contentType: false,
                type: 'POST',
                data: formData,
                success:function(response){
                    $(".cover-spin").hide();
                    if(response.success == true){
                        $("#newUserModal").modal('hide');
                        showToastMessage('User added successfully.',true);
                        $('#all-table').DataTable().destroy();
                        loadTableData('all','all');
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

        function showChallengeList(user_id){
            $.get(baseUrl + '/challenge/users/challenges/' + user_id, function (data, status) {
                $('#challengeListModal').html('');
                $('#challengeListModal').html(data);
                $('#challengeListModal').modal('show');
            });
        }

        function seeChallenge(challenge_id){
            $.get(baseUrl + '/challenge/challenge-page/view/' + challenge_id, function (data, status) {
                $('#seeChallengeModal').html('');
                $('#seeChallengeModal').html(data);
                $('#seeChallengeModal').modal('show');
            });
        }

        function editUser(id) {
            $.get(baseUrl + '/challenge/users/edit/' + id, function (data, status) {
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
                url: "{{ url('challenge/users/edit-user') }}",
                // processData: false,
                // contentType: false,
                type: 'POST',
                data: formData,
                success:function(response){
                    $(".cover-spin").hide();
                    if(response.success == true){
                        $("#editModal").modal('hide');
                        showToastMessage('User updated successfully.',true);
                        $('#all-table').DataTable().destroy();
                        loadTableData('all','all');
                    }
                    else {
                        if(response.errors) {
                            var errors = response.errors;
                            $.each(errors, function (key, value) {
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
    </script>
@endsection
