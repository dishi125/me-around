<div class="modal-dialog">
    <div class="modal-content">
        <form id="editForm" method="post">
            {{ csrf_field() }}
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <div class="modal-header justify-content-center">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center">
                <div class="align-items-xl-center mb-3">
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>User Name</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="edit_user_name" id="edit_user_name" class="form-control" value="{{ $user->name }}"/>
                            @error('edit_user_name')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>E-mail</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="edit_email" id="edit_email" class="form-control" value="{{ $user->email }}"/>
                            @error('edit_email')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>Phone Number</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="edit_phone" id="edit_phone" class="form-control" value="{{ $user->mobile }}"/>
                            @error('edit_phone')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>Gender</label>
                        </div>
                        <div class="col-md-8">
                            <select id="gender" name="gender" class="form-control">
                                <option value="Female" @if($user->gender=="Female") selected @endif>Female</option>
                                <option value="Male" @if($user->gender=="Male") selected @endif>Male</option>
                            </select>
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
