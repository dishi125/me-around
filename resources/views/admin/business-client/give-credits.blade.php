<div class="modal-dialog" style="max-width: 550px;">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Give Client Credits</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">        
            <div class="align-items-xl-center mb-3">

            <div class="row">
                <div class="col-md-3">
                    <label>Add Credits</label>
                </div>
                <div class="col-md-7"><input type="text" id="user-credits" class="numeric form-control" value="">
                    <label id="credit-error" class="error-msg" for="user-credits"></label>
                </div>
                <div class="col-md-2">
                    <input name="give_type" id="give_type" type="hidden" value="{{ $type }}">
                    <button type="button" class="btn btn-primary" id="save-business-credits">Save</button>
                </div>
            </div>       
            </div>       
        </div>
    </div>
</div>
