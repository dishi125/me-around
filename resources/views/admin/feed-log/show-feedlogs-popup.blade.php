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
                            <table class="table table-striped" id="feedlog-data-table">
                                <thead>
                                <tr>
                                    <th class="mr-3">Love Amount</th>
                                    <th class="mr-3">Time</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($feed_logs as $feed_log)
                                    <tr>
                                        <td>{{ $feed_log->love_count }}</td>
                                        <?php
                                        $created_at = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s',\Carbon\Carbon::parse($feed_log->created_at), "UTC")->setTimezone($adminTimezone)->toDateTimeString();
                                        ?>
                                        <td>{{ $created_at }}</td>
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
    $("#feedlog-data-table").DataTable({
        "order": [[ 1, "desc" ]]
    });
</script>
