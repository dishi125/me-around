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
                            <button class="btn btn-primary mb-2" id="save_user_btn" challenge-id="{{ $id }}">Save</button>
                            <table class="table table-striped" id="users-data-table">
                                <thead>
                                <tr>
                                    <th>User Name</th>
                                    <th>E-mail</th>
                                    <th>Phone Number</th>
                                    <th>Signup Date</th>
                                    <th>Last Access</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($users as $user)
                                    <tr>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->mobile }}</td>
                                        <?php
                                        $created_at = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s',\Carbon\Carbon::parse($user->signup_date), "UTC")->setTimezone($adminTimezone)->toDateTimeString();
                                        $last_access = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s',\Carbon\Carbon::parse($user->last_access), "UTC")->setTimezone($adminTimezone)->toDateTimeString();
                                        ?>
                                        <td>{{ $created_at }}</td>
                                        <td>{{ $last_access }}</td>
                                        <td>
                                            <div class="custom-checkbox custom-control">
                                                <input type="checkbox" data-checkboxes="mygroup" class="custom-control-input checkbox_select_user" id="checkbox_select_user_{{ $user->id }}" data-id="{{ $user->id }}" value="{{ $user->id }}" name="select_user[]" @if($user->selected_count > 0) checked @endif>
                                                <label for="checkbox_select_user_{{ $user->id }}" class="custom-control-label"> </label>
                                            </div>
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
    $("#users-data-table").DataTable({
        "order": [[ 3, "desc" ]]
    });
</script>
