<!------ Delete User Popup ------------->

<div class="modal fade" id="deleteUserModel" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5>Delete All Post</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center">
                <h6>Will you confirm ?</h6>
            </div>
            <div class="modal-footer">           
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger" id="delete-user">Ok</button>          
            </div>
        </div>
    </div>
    <div class="loader" style="display:none; "></div>
</div>

<!------ Warning Mention Popup ------------->

<div class="modal fade" id="warningMentionModel" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5>Warning Mention</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>            
            <div class="modal-body">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Shop</label>
                        <textarea name="shop_warning_comment" id="shop_warning_comment" class="form-control">{{$basic_mentions['reported_shop_warning_comment']}}</textarea>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Hospital</label>
                        <textarea name="hospital_warning_comment" id="hospital_warning_comment" class="form-control">{{$basic_mentions['reported_hospital_warning_comment']}}</textarea>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Community</label>
                        <textarea name="community_warning_comment" id="community_warning_comment" class="form-control">{{$basic_mentions['reported_community_warning_comment']}}</textarea>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Review</label>
                        <textarea name="review_warning_comment" id="review_warning_comment" class="form-control">{{$basic_mentions['reported_review_warning_comment']}}</textarea>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>User from Shop</label>
                        <textarea name="shop_user_warning_comment" id="shop_user_warning_comment" class="form-control">{{$basic_mentions['reported_shop_user_warning_comment']}}</textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger" id="warning-mention-btn">Ok</button>
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