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
                            <table class="table table-striped" id="autoloveuser-data-table">
                                <thead>
                                <tr>
                                    <th>User name</th>
                                    <th>Daily love amount</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($auto_love_users as $auto_love_user)
                                    <tr>
                                        <td>{{ $auto_love_user->name }}</td>
                                        <td>{{ $auto_love_user->increase_love_count }}</td>
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
    $("#autoloveuser-data-table").DataTable();
</script>
