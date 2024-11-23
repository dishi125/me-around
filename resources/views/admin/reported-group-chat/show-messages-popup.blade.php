<div class="modal-dialog" style="max-width: 70%;">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
{{--            <h5>Messages</h5>--}}
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">
            <div class="row align-items-xl-center mb-3">
                <div class="w-100 tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="referral-data" role="tabpanel" aria-labelledby="referral-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="message-data-table">
                                <thead>
                                <tr>
                                    <th class="mr-3">Message</th>
                                    <th class="mr-3">Sent At</th>
                                    <th class="mr-3">Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($messages as $message)
                                    <tr>
                                        <td>{{ $message->message }}</td>
                                        @php
                                        $dateShow = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s',\Carbon\Carbon::parse(date("Y-m-d H:i:s", $message->created_at)), "UTC")->setTimezone($adminTimezone)->toDateTimeString();
                                        $created_at = \Carbon\Carbon::parse($dateShow)->format('Y-m-d H:i:s');
                                        @endphp
                                        <td>{{ $created_at }}</td>
                                        <td>
                                            <a href="javascript:void(0)" role="button" onclick="removeMessage({{$message->id}})" class="btn btn-danger" data-toggle="tooltip" data-original-title="Delete"><i class="fa fa-trash"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    $("#message-data-table").DataTable();
</script>
