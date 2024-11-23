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
                        <div class="row">
                            <div class="col-2">
                                <button id="add_auto_chat" class="btn btn-primary">
                                    Add Auto Chat
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="myTabContent2">
                        <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="comment-data">
                            <div class="table-responsive">
                                <table class="table table-striped" id="Chat-bot-table">
                                    <thead>
                                    <tr>
                                        <th>User name</th>
                                        <th>Message</th>
                                        <th>Time</th>
                                        <th>Week Day</th>
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

<div class="modal fade" id="applyUserModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" style="max-width: 550px;">
        <div class="modal-content">
            <form id="AutoChatForm" method="post">
            {{ csrf_field() }}
                <input type="hidden" name="country" value="{{ $country }}">
            <div class="modal-header justify-content-center">
{{--                <h5>Edit Client Credits</h5>--}}
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center">
                <div class="align-items-xl-center mb-3">
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>Select User:</label>
                        </div>
                        <div class="col-md-8">
                            {!!Form::select('user', $users, '', ['class' => 'form-control select2','placeholder' => __(Lang::get('general.select-user')), 'required'])!!}
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>Message:</label>
                        </div>
                        <div class="col-md-8">
                            {!! Form::textarea('message', '', ['class' => 'form-control', 'placeholder' => __(Lang::get('general.message')), 'required']); !!}
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>Time:</label>
                        </div>
                        <div class="col-md-8">
                            <input type="time" value="00:00" name="time" step="60" required/>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>Week Day:</label>
                        </div>
                        <div class="col-md-8">
                            {!!Form::select('weekday', $week_days, '', ['class' => 'form-control select2','placeholder' => __(Lang::get('general.select-weekday')), 'required'])!!}
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{!! __(Lang::get('general.close')) !!}</button>
                <button type="submit" class="btn btn-primary" id="apply_user_btn">Apply</button>
            </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
    <script>
        var allTable = "{!! route('admin.message.chat-bot.table') !!}";
        var csrfToken = "{{ csrf_token() }}";

        $(function() {
            var all = $("#Chat-bot-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                // "order": [[ 4, "desc" ]],
                ajax: {
                    url: allTable,
                    dataType: "json",
                    type: "POST",
                    data: { _token: csrfToken, country: "{{ $country }}" }
                },
                columns: [
                    { data: "user_name", orderable: true },
                    { data: "message", orderable: true },
                    { data: "time", orderable: true },
                    { data: "week_day", orderable: true },
                    // { data: "created_at", orderable: false },
                ]
            });
        });

        $(document).on('click', '#add_auto_chat', function (){
            $("#applyUserModal").modal('show');
        })

        $(document).on('submit', '#AutoChatForm', function (e){
            e.preventDefault();
            var formData = new FormData($("#AutoChatForm")[0]);

            $.ajax({
                url: "{{ url('admin/chat-bot/message/store') }}",
                processData: false,
                contentType: false,
                type: 'POST',
                data: formData,
                success:function(response){
                    if(response.success == true){
                        $("#applyUserModal").modal('hide');
                        showToastMessage('Autochat message applied successfully.',true);
                        $('#Chat-bot-table').DataTable().ajax.reload();
                    }
                    else {
                        showToastMessage('Something went wrong!!',false);
                    }
                },
                error: function(response) {
                    showToastMessage('Something went wrong!!',false);
                },
            });
        })
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script src="https://webrtc.github.io/adapter/adapter-latest.js"></script>
    <script src="https://cdn.socket.io/4.4.1/socket.io.min.js" integrity="sha384-fKnu0iswBIqkjxrhQCTZ7qlLHOFEgNkRmK2vaO/LbTZSXdJfAu6ewRBdwHPhBo/H" crossorigin="anonymous"></script>
    <script language="javascript" type="text/javascript">
        // var wsUri = "ws://localhost:4000";
        var wsUri = "ws://3.37.85.155:4000";
        var websocket;

        window.addEventListener("load", init, false);

        function init() {
            testWebSocket();
        }

        function testWebSocket() {
            websocket = new WebSocket(wsUri);

            websocket.onopen = function(event) {
                const body = {"body": "CONNECTED"};
                console.log("connected to websocket server");
            };

            // message received - show the message in div#messages
            websocket.onmessage = function(event) {
                console.log(event);
                // let message = event.data;
                // console.log("message "+message);
                /*let messageElem = document.createElement('div');
                messageElem.textContent = message;
                document.getElementById('messages').prepend(messageElem);*/
            }

            websocket.onclose = function(event) {
                console.log("Error occurred.");
                console.log("Error: " + JSON.stringify(event));
                // Inform the user about the error.
                /*var label = document.getElementById("status-label");
                label.innerHTML = "Error: " + event;*/
            }
        }

        function sendMessage(){
            let country = $("select[name='country']").val();
            let from_user_id = $("select[name='user']").val();
            let outgoingMessage = $("input[name='text_message']").val();

            if(country==""){
                showToastMessage("Please select country.",false);
            }
            else if(from_user_id==""){
                showToastMessage("Please select user.",false);
            }
            else if(outgoingMessage==""){
                showToastMessage("Please add message.",false);
            }
            else {
                $.ajax({
                    url: "{{ url('admin/message/save') }}",
                    type: "POST",
                    // contentType: false,
                    // processData: false,
                    data: {
                        "country": country,
                        "from_user_id": from_user_id,
                        "message": outgoingMessage
                    },
                    beforeSend: function () {
                    },
                    success: function (response) {
                        // var data = JSON.parse(response);
                        if(response.success == true){
                            // console.log("receiver_user_ids: "+response.receiver_user_ids);
                            var obj = {};
                            obj.type = "groupchat";
                            obj.message = {
                                type: "receiveMessage",
                                data: response.message_data
                            };
                            // console.log("obj: "+JSON.stringify(obj));
                            websocket.send(JSON.stringify(obj));
                        }
                        else {
                            showToastMessage("Something went wrong.",false);
                        }
                    },
                    error: function (response, status) {
                        showToastMessage("Something went wrong.",false);
                    }
                });
            }
            // return false;
        }
    </script>
@endsection
