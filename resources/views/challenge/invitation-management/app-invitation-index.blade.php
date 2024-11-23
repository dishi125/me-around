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
        <a href="{{ route('challenge.invitation-management.index') }}" class="btn btn-primary mr-2">Follower invitation</a>
        <a href="{{ route('challenge.invitation-management.app-invitation.index') }}" class="btn btn-primary mr-2">App invitation</a>
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
                                <table class="table table-striped" id="App-invitation-table">
                                    <thead>
                                        <tr>
                                            <th>Sender User</th>
                                            <th>Time</th>
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

<div class="modal fade" id="userListModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>

@section('scripts')
    <script>
        var allDataTable = "{{ route('challenge.invitation-management.app-invitation.table') }}";
        var csrfToken = "{{ csrf_token() }}";
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script>
        $(document).ready(function (){
            loadTableData();
        })

        function loadTableData() {
            var allData = $("#App-invitation-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                // order: [[ 3, "desc" ]],
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
                        data: "sender",
                        orderable: false
                    },
                    {
                        data: "time",
                        orderable: false
                    },
                ]
            });
        }

        function showInvitedUserList(user_id){
            $.get(baseUrl + '/challenge/invitation-management/invited-users/' + user_id, function (data, status) {
                $('#userListModal').html('');
                $('#userListModal').html(data);
                $('#userListModal').modal('show');
            });
        }
    </script>
@endsection
