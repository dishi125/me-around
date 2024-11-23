<div class="modal-dialog">
    <div class="modal-content">
        <form id="editForm" method="post">
            {{ csrf_field() }}
            <input type="hidden" name="category_id" value="{{ $category->id }}">
            <div class="modal-header justify-content-center">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center">
                <div class="align-items-xl-center mb-3">
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>Title</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="name" id="name" class="form-control" value="{{ $category->name }}"/>
                            @error('name')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>Type</label>
                        </div>
                        <div class="col-md-8">
                            <select name="challenge_type" class="form-control">
                                <option value="1" @if($category->challenge_type==1) selected @endif>Challenge</option>
                                <option value="2" @if($category->challenge_type==2) selected @endif>Period challenge</option>
                            </select>
                            @error('challenge_type')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>Order</label>
                        </div>
                        <div class="col-md-8">
                            <input type="number" name="challenge_order" id="challenge_order" class="form-control" value="{{ $category->order }}"/>
                            @error('challenge_order')
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
