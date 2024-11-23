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
                                    <th class="mr-3">Time</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($messages as $message)
                                    <tr>
                                        @if($message->type=="file")
                                        <td><img src="{{ url('chat-root/'.$message->message) }}" onclick="showImage({{ url('chat-root/'.$message->message) }})" alt="file" class="reported-client-images pointer m-1" width="50" height="50"></td>
                                        @else
                                        <td>{{ $message->message }}</td>
                                        @endif
                                        {{--@php
                                            $dateShow = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s',\Carbon\Carbon::parse(date("Y-m-d H:i:s", $message->created_at)), "UTC")->setTimezone($adminTimezone)->toDateTimeString();
                                            $created_at = \Carbon\Carbon::parse($dateShow)->format('Y-m-d H:i:s');
                                        @endphp
                                        <td>{{ $created_at }}</td>--}}
                                        <td>{{ \App\Http\Controllers\Controller::formatDateTimeCountryWise($message->created_at, $adminTimezone)}}</td>
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
    $("#message-data-table").DataTable({order: [[ 1, "desc" ]]});
</script>
