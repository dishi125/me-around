
<div class="modal-dialog" style="max-width: 50%;">
    <div class="modal-content">
        <form id="kakaoTalkForm" method="post">
            {{ csrf_field() }}
            <div class="modal-header justify-content-center">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center">
                <div class="align-items-xl-center mb-3">
                    <div class="row mb-2">
                        <div class="col-md-3">
                            <h5>Link</h5>
                        </div>
                        <div class="col-md-9">
                            <input type="text" name="link" id="link" class="form-control" value="{{ isset($link) ? $link->link : "" }}"/>
                            @error('link')
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
