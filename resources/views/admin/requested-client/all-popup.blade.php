<!------ Confirm Popup ------------->
<div class="modal fade" id="confirmRequestModel" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5>Confirm Request</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center">
                <h6>Will you confirm ?</h6>
            </div>
            <div class="modal-footer">           
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger" id="confirm-request">Confirm</button>          
            </div>
        </div>
    </div>
    <div class="loader" style="display:none; "></div>
</div>

<!------ Reject Popup ------------->

<div class="modal fade" id="rejectRequestModel" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5>Reject Request</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center">
            <div class="form-group">
                        <label>Type Reject Reason</label>
                        <textarea name="reject_comment" id="reject_comment" class="form-control" style="height: 100px !important;">{!! $rejectMentionText !!}</textarea>
                    </div>
            </div>
            <div class="modal-footer">           
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger" id="reject-request">Ok</button>          
            </div>
        </div>
    </div>
    <div class="loader" style="display:none; "></div>
</div>

<!------ Basic Reject Mention Popup ------------->

<div class="modal fade" id="rejectMentionModel" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5>Reject Mention</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>            
            <div class="modal-body">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Basic mention</label>
                        <textarea name="reject_comment_basic" id="reject_comment_basic" class="form-control" style="height: 100px !important;">{!! $rejectMentionText !!}</textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger" id="reject-mention-btn">Ok</button>
            </div>
        </div>
    </div>
    <div class="loader" style="display:none; "></div>
</div>

<!-- Basic Confirm Mention Popup
<div class="modal fade" id="confirmationMentionModel" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5>Confirmation Mention</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>            
            <div class="modal-body">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Basic mention</label>
                        <textarea name="confirm_comment" id="confirm_comment" class="form-control" style="height: 100px !important;"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger" id="confirm-mention-btn">Ok</button>
            </div>
        </div>
    </div>
    <div class="loader" style="display:none; "></div>
</div> -->