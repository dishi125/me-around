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
                            <div class="col-3">
                                <div class="form-group">
                                    {!!Form::select('country', $countries, 'KR' , ['class' => 'form-control select2','placeholder' => __(Lang::get('forms.association.country'))])!!}
                                </div>
                            </div>

                            <div class="col-3">
                                <div class="form-group">
                                    {!!Form::select('user', $users, '' , ['class' => 'form-control select2','placeholder' => __(Lang::get('general.select-user')) ]) !!}
                                </div>
                            </div>

                            <div class="col-3">
                                <div class="form-group">
                                    {!! Form::text('text_message', '', ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.message.message')) ]); !!}
                                </div>
                            </div>

                            <div class="col-3">
                                <a class="btn btn-primary" onclick="sendMessage()">Send</a>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-2">
                                <button id="delete_messages" class="btn btn-danger">
                                    Delete Selected
                                </button>
                            </div>
                            <div class="col-2">
                                <button id="add_chat_bot" class="btn btn-primary">
                                   Add Chat Bot
                                </button>
                            </div>
                        </div>

                        <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="comment-data">
                            <div class="table-responsive">
                                <table class="table table-striped" id="Message-table">
                                    <thead>
                                    <tr>
                                        <th></th>
                                        <th>Message</th>
                                        <th>User Name</th>
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

<div class="modal fade" id="MessagePhotoModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header justify-content-center" style="border-bottom:none; padding: 8px;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center" style="padding: 0px;" id="modelImageShow">
                <img src="{!! asset('img/logo-main.png') !!}" class="w-100 " id="modelImageEle" />
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="MessageDeleteModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5>Delete Message</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center">
                <h6>Are you sure you want to delete this messages?</h6>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="delete_messages_submit">Confirm</button>
            </div>
        </div>
    </div>
</div>

@section('scripts')
    <script>
        var allTable = "{!! route('admin.message.table') !!}";
        var csrfToken = "{{ csrf_token() }}";

        $(function() {
            loadTableData($('select[name="country"]').val());

            $(document).on("change",'select[name="country"]',function (){
                var filter = $(this).val();
                $('#Message-table').DataTable().destroy();
                loadTableData(filter);
            });
        });

        function loadTableData(filter){
            var filter = filter || '';
            $("#Message-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                // order: [[ 2, "asc" ]],
                ajax: {
                    url: allTable,
                    dataType: "json",
                    type: "POST",
                    data: { _token: csrfToken,filter : filter  },
                    /*dataSrc: function ( json ) {
                        setTimeout(function() {
                            $('.toggle-btn').bootstrapToggle();
                        }, 300);
                        return json.data;
                    }*/
                },
                createdRow: function(row, data, dataIndex) {
                    $(row).attr('data-id', data.id).addClass('row1');
                    // $('.toggle-btn').bootstrapToggle();
                },
                columns: [
                    { data: "checkbox_delete", orderable: false },
                    { data: "message", orderable: true },
                    { data: "user_name", orderable: true },
                    { data: "time", orderable: true },
                    { data: "action", orderable: false },
                ],
            });
        }

        function showImage(imageSrc){
            console.log(imageSrc)
            $('#modelImageShow').html('');
            var validExtensions = ["jpg","jpeg","gif","png",'webp',"tiff"];
            var extension = imageSrc.split('.').pop().toLowerCase();
            if(imageSrc){
                if($.inArray(extension, validExtensions) == -1){
                    $('#modelImageEle').remove();
                    $('#modelImageShow').html('<video width="100%" height="300" controls poster="" id="modelVideoEle"><source src="'+imageSrc+'" type="video/mp4">Your browser does not support the video tag.</video>');
                }else{
                    $('#modelVideoEle').remove();
                    $('#modelImageShow').html('<img src="'+imageSrc+'" class="w-100 " id="modelImageEle" />');
                }
                $("#MessagePhotoModal").modal('show');
            }
        }

        function removeMessage(id) {
            var pageModel = $("#MessageDeleteModal");

            $.get("{{ url('admin/message/delete') }}" + "/" + id, function(data, status) {
                pageModel.html('');
                pageModel.html(data);
                pageModel.modal('show');
            });
        }

        $('#delete_messages').click(function(event) {
            var id = $('input[name="delete_message_id[]"]:checked').map(function(_, el) {
                return $(el).val();
            }).get();

            if (id.length == 0) {
                iziToast.error({
                    title: '',
                    message: 'Please select at least one checkbox',
                    position: 'topRight',
                    progressBar: true,
                    timeout: 5000
                });
            }
            else {
                $("#MessageDeleteModal").modal('show');
            }
        });

        $('#delete_messages_submit').click(function(event) {
            var id = $('input[name="delete_message_id[]"]:checked').map(function(_, el) {
                return $(el).val();
            }).get();

            if (id.length == 0) {
                iziToast.error({
                    title: '',
                    message: 'Please select at least one checkbox',
                    position: 'topRight',
                    progressBar: true,
                    timeout: 5000
                });
            }
            else {
                $.ajax({
                    url: "{{ route('admin.message.multiple.remove') }}",
                    method: 'POST',
                    data: {
                        _token: csrfToken,
                        ids: id,
                    },
                    beforeSend: function() {
                        $('.cover-spin').show();
                    },
                    success: function(response) {
                        $('.cover-spin').hide();
                        if (response.success == true) {
                            $("#MessageDeleteModal").modal('hide');

                            iziToast.success({
                                title: '',
                                message: response.message,
                                position: 'topRight',
                                progressBar: false,
                                timeout: 1000,
                            });

                            /*setTimeout(function() {
                                window.location.href = response.redirect;
                            }, 1000);*/

                            var filter = $('select[name="country"]').val();
                            $('#Message-table').DataTable().destroy();
                            loadTableData(filter);
                        } else {
                            iziToast.error({
                                title: '',
                                message: 'Messages has not been deleted successfully.',
                                position: 'topRight',
                                progressBar: false,
                                timeout: 1500,
                            });
                        }
                    }
                });
            }
        });

        $(document).on('click', '#add_chat_bot', function (){
            var country = $('select[name="country"]').val();
            if(country=="") {
                showToastMessage("Please select country.",false);
            }
            else {
                location.href = "{{ url('admin/chat-bot') }}" + "/" + country;
            }
        })
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script src="https://webrtc.github.io/adapter/adapter-latest.js"></script>
    <script src="https://cdn.socket.io/4.4.1/socket.io.min.js" integrity="sha384-fKnu0iswBIqkjxrhQCTZ7qlLHOFEgNkRmK2vaO/LbTZSXdJfAu6ewRBdwHPhBo/H" crossorigin="anonymous"></script>
{{--    <script src="{{ url('chat-root/server.js') }}" type="text/javascript"></script>--}}
    <script language="javascript" type="text/javascript">
        // var wsUri = "ws://localhost:4000";
        var wsUri = "wss://3.37.85.155:4000";
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
