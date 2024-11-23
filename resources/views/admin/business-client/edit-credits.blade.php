<div class="modal-dialog" style="max-width: 550px;">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Edit Client Credits</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">
            <input name="user-id" id="user-id" type="hidden" value="{{ $users_detail->user_id }}">
            <div class="align-items-xl-center mb-3">
            <div class="row mb-3">
                <div class="col-md-12">
                    <label>Current Credits : {{(isset($userCredits->credits)) ? number_format($userCredits->credits,0) : ""}}</label>
                </div>
                <div class="col-md-12">
                    <div class="custom-checkbox custom-control">
                        <input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input check-admin-access" id="checkbox-admin-access" @if(isset($users_detail->is_admin_access) && $users_detail->is_admin_access==1) checked @endif>
                        <label for="checkbox-admin-access" class="custom-control-label">Is Admin Access</label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="custom-checkbox custom-control d-flex">
                        <input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input check-supporter" id="checkbox-supporter" @if(isset($users_detail->is_support_user) && $users_detail->is_support_user==1) checked @endif>
                        <label for="checkbox-supporter" class="custom-control-label">Is Support User</label>
                        <div class="ml-3">
                        <input name="supporter_option" type="radio" value="platinum" class="supporter_option"> Platinum
                        <input name="supporter_option" type="radio" value="gold" class="ml-1 supporter_option"> Gold
                        <input name="supporter_option" type="radio" value="silver" class="ml-1 supporter_option"> Silver
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="custom-checkbox custom-control">
                        <input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input check-love-amount" id="checkbox-love-amount" @if(isset($users_detail->is_increase_love_count_daily) && $users_detail->is_increase_love_count_daily==1) checked @endif>
                        <label for="checkbox-love-amount" class="custom-control-label">Get Love Amount Daily</label>
                    </div>
                </div>
            </div>
            @if(isset($userCredits->user_id))
            <div class="row mb-2">
                <div class="col-md-4">
                    <label>Reload Coin:</label>
                </div>
                <div class="col-md-6">
                    <input type="text" name="reload-coin" id="reload-user-credits" class="numeric form-control" value="">
                    <label id="reload-credit-error" class="error-msg" for="reload-user-credits"></label>
                </div>
                <div class="col-md-2">
                    <a onclick="reloadCoinToUser(`{!! route('admin.business-client.reload.coin',['id' => $userCredits->user_id ]) !!}`);" class="btn btn-primary" id="reload-credits" href="javascript:void(0);">Reload</a>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <label>Add Credits:</label>
                </div>
                <div class="col-md-6"><input type="text" id="user-credits" class="numeric form-control" value="">
                    <label id="credit-error" class="error-msg" for="user-credits"></label>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary" id="save-credits">Save</button>
                </div>
            </div>
            @endif

            <div class="row">
                <div class="col-md-4">
                    <label>Love Amount:</label>
                </div>
                <div class="col-md-6">
                    <input type="text" id="user-love-amount" class="numeric form-control" value="{{ $users_detail->increase_love_count }}">
                    <label id="love-amount-error" class="error-msg" for="user-love-amount"></label>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary" id="save-love-amount" user-id="{{ $users_detail->user_id }}">Save</button>
                </div>
            </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" id="delete-business-profile">Delete Business Profile</button>
            <button type="button" id="deleteUserDetail" user-id="{{ $users_detail->user_id }}" class="btn btn-danger">Delete User</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">{!! __(Lang::get('general.close')) !!}</button>
        </div>
    </div>
</div>
