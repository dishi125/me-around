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
                            <table class="table table-striped" id="challenges-data-table">
                                <thead>
                                <tr>
                                    <th>Challenge Title</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($challenges as $challenge)
                                    <tr>
                                        <td>{{ $challenge->title }}</td>
                                        <td>
                                            <a href="javascript:void(0)" role="button" onclick="seeChallenge({{ $challenge->id }})" class="btn btn-primary btn-sm mx-1" data-toggle="tooltip" data-original-title=""><i class="fa fa-eye" style="font-size: 15px;"></i></a>
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
    $("#challenges-data-table").DataTable({
        // "order": [[ 3, "desc" ]]
    });
</script>
