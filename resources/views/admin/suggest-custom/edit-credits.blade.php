<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Edit Client Credits</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">        
            <div class="row align-items-xl-center mb-3">
            <div class="col-md-12">
                <label>Current Credits : 1500</label>
            </div>
            <div class="col-md-4">
                <label>Add Credits</label>
            </div>
                <div class="col-md-6"><input type="text" id="shop-credits" class="form-control" value=""></div>
                <div class="col-md-2">
                <input name="shop-id" id="shop-id" type="hidden" value="1">
                <input name="shop-member-id" id="shop-member-id" type="hidden" value="1}">
                    <button type="button" class="btn btn-primary" id="save-shop-credits" data-dismiss="modal">Save</button>
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