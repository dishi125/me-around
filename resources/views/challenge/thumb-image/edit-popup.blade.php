<div class="modal-dialog">
    <div class="modal-content">
        <form id="editThumbForm" method="post">
            {{ csrf_field() }}
            <input type="hidden" name="thumb_id" value="{{ $thumb->id }}">
            <div class="modal-header justify-content-center">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center">
                <div class="align-items-xl-center mb-3">
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>{{ __('forms.thumb.image') }}</label>
                        </div>
                        <div class="col-md-8">
                            <input type="file" name="edit_image" id="edit_image" class="form-control"/>
                            @error('edit_image')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>{{ __('forms.thumb.order') }}</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="edit_order" id="edit_order" class="form-control" value="{{ $thumb->order }}"/>
                            @error('edit_order')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>{{ __('forms.thumb.type') }}</label>
                        </div>
                        <div class="col-md-8">
                            <select name="edit_challenge_type" class="form-control challenge_type" id="edit_challenge_type">
                                <option selected disabled>Select...</option>
                                <option value="1" @if($thumb->challenge_type==\App\Models\ChallengeCategory::CHALLENGE) selected @endif>{{ __('general.challenge') }}</option>
                                <option value="2" @if($thumb->challenge_type==\App\Models\ChallengeCategory::PERIODCHALLENGE) selected @endif>{{ __('general.period_challenge') }}</option>
                            </select>
                            @error('edit_challenge_type')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-2" id="category_div">
                        @if(isset($challenge_cats) && count($challenge_cats)>0)
                        <div class="col-md-4">
                            <label>{{ __('forms.thumb.select_category') }}</label>
                        </div>
                        <div class="col-md-8">
                            <select name="category" class="form-control" id="category">
                            <option selected disabled>Select...</option>
                            @foreach($challenge_cats as $cat)
                            <option value="{{ $cat->id }}" @if($cat->id==$thumb->category_id) selected @endif>{{ $cat->name }}</option>
                            @endforeach
                            </select>
                        </div>

                        @elseif(isset($period_challenge_cats) && count($period_challenge_cats)>0)
                        <div class="col-md-4">
                            <label>{{ __('forms.thumb.select_category') }}</label>
                        </div>
                        <div class="col-md-8">
                            <select name="category" class="form-control" id="category">
                            <option selected disabled>Select...</option>
                            @foreach($period_challenge_cats as $cat)
                            <option value="{{ $cat->id }}" @if($cat->id==$thumb->category_id) selected @endif>{{ $cat->name }}</option>
                            @endforeach
                            </select>
                        </div>
                        @endif
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
