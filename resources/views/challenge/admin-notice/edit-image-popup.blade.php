<div class="modal-dialog">
    <div class="modal-content">
        <form id="editImageForm" method="post">
            {{ csrf_field() }}
            <div class="modal-header justify-content-center">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center">
                <div class="align-items-xl-center mb-3">
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>Image</label>
                        </div>
                        <div class="col-md-8">
                            <input type="file" name="admin_image" id="admin_image" class="form-control"/>
                            @error('admin_image')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
{{--                            <img src="{{ ($admin) ? $admin->image : '#' }}" id="image_preview" width="200px" />--}}
                        </div>
                        <div class="col-md-12 mb-2">
                            <img id="image_preview" src="{{ ($admin) ? $admin->image : '#' }}"
                                 alt="preview image" style="max-height: 250px;" class="mt-2">
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
