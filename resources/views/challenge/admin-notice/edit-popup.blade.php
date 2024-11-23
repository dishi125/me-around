<div class="modal-dialog">
    <div class="modal-content">
        <form id="editnoticeForm" method="post">
            {{ csrf_field() }}
            <input type="hidden" name="admin_notice_id" value="{{ $ChallengeAdminNotice->id }}">
            <div class="modal-header justify-content-center">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center">
                <div class="align-items-xl-center mb-3">
                    <div class="row mb-2">
                        <div class="col-md-2">
                            <label>Title</label>
                        </div>
                        <div class="col-md-10">
                            <input type="text" name="edit_title" id="edit_title" class="form-control" value="{{ $ChallengeAdminNotice->title }}"/>
                            @error('edit_title')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-2">
                            <label>Notice</label>
                        </div>
                        <div class="col-md-10">
                            <textarea name="edit_notice" id="edit_notice" class="form-control" style="height: 300px;">{{ $ChallengeAdminNotice->notice }}</textarea>
                            @error('edit_notice')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{!! __(Lang::get('general.close')) !!}</button>
                <button type="submit" class="btn btn-primary" id="save_btn">Save</button>
            </div>
        </form>
    </div>
</div>
