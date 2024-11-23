@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
    <style>
        .table-responsive button#show-profile {
            width: auto;
            margin: 5px 5px 5px 0;
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
    </style>
@endsection

@section('header-content')
    <h1>
        @if (@$title)
            {{ @$title }}
        @endif
    </h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="tab-content" id="myTabContent2">
                        <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="comment-data">
                            <div class="table-responsive">
                                <table class="table table-striped" id="Admin-chat-table">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Last Message</th>
                                            <th>Time</th>
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

<div class="modal fade" id="show-messages" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

@section('scripts')
    <script>
        var allTable = "{!! route('admin.admin-chat.table') !!}";
        var csrfToken = "{{ csrf_token() }}";

        $(function() {
            var all = $("#Admin-chat-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                // "order": [[ 7, "desc" ]],
                ajax: {
                    url: allTable,
                    dataType: "json",
                    type: "POST",
                    data: { _token: csrfToken }
                },
                columns: [
                    { data: "user_name", orderable: false },
                    { data: "last_message", orderable: false },
                    { data: "time", orderable: false },
                    { data: "see_more", orderable: false },
                ]
            });
        });

        function viewEntireChat(user_id){
            $.get(baseUrl + '/admin/admin-chat/show/user-messages/' + user_id, function (data, status) {
                $('#show-messages').html('');
                $('#show-messages').html(data);
                $('#show-messages').modal('show');
            });
        }
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
@endsection
