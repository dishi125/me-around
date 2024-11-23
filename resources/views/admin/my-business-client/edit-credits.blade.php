<div class="modal-dialog" style="max-width: 550px;">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Edit Client Credits</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">        
            <div class=" align-items-xl-center mb-3">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label>Current Credits : {{number_format($userCredits->credits,0)}}</label>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-4">
                        <label>Reload Coin : </label>
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
                        <label>Add Credits</label>
                    </div>
                    <div class="col-md-6"><input type="text" id="user-credits" class="form-control" value="">
                        <label id="credit-error" class="error-msg" for="user-credits"></label>
                    </div>
                    <div class="col-md-2">
                    <input name="user-id" id="user-id" type="hidden" value="{{ $userCredits->user_id }}">
                        <button type="button" class="btn btn-primary" id="save-credits">Save</button>
                    </div>
                </div>
            </div>       
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" id="delete-business-profile">Delete Business Profile</button>
            <button type="button" id="delete-business-user" class="btn btn-danger">Delete User</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">{!! __(Lang::get('general.close')) !!}</button>
        </div>
    </div>
</div>