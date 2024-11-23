<div class="modal-dialog" style="max-width: 50%;">
    <div class="modal-content">
        <form id="editForm" method="post">
            {{ csrf_field() }}
            <input type="hidden" name="challenge_id" value="{{ $challenge->id }}">
            <div class="modal-header justify-content-center">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center">
                <div class="align-items-xl-center mb-3">
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>{{ __('forms.challenge.challenge_title') }}</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="title" id="title" class="form-control" value="{{ $challenge->title }}"/>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>{{ __('forms.challenge.select_category') }}</label>
                        </div>
                        <div class="col-md-8">
                            @if($challenge->is_period_challenge==1)
                            <select class="form-control" name="category_id" id="category_id">
                                <option selected disabled>Select...</option>
                                @foreach($period_challenge_cats as $cat)
                                <option value="{{ $cat->id }}" @if($challenge->category_id==$cat->id) selected @endif>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @else
                            <select class="form-control" name="category_id" id="category_id">
                                <option selected disabled>Select...</option>
                                @foreach($challenge_cats as $cat)
                                <option value="{{ $cat->id }}" @if($challenge->category_id==$cat->id) selected @endif>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @endif
                            @error('category_id')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                @if($challenge->is_period_challenge==1)
                    <?php $added_days = $challenge->challengedays()->pluck('day')->toArray(); ?>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>{{ __('forms.period_challenge.select_day') }}</label>
                        </div>
                        <div class="col-md-8" id="div_day">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="sun_checkbox" value="su" name="day[]" @if(in_array('su',$added_days)) checked @endif>
                                <label class="form-check-label" for="sun_checkbox">{{ __('general.sun') }}</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="mon_checkbox" value="mo" name="day[]" @if(in_array('mo',$added_days)) checked @endif>
                                <label class="form-check-label" for="mon_checkbox">{{ __('general.mon') }}</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="tue_checkbox" value="tu" name="day[]" @if(in_array('tu',$added_days)) checked @endif>
                                <label class="form-check-label" for="tue_checkbox">{{ __('general.tue') }}</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="wed_checkbox" value="we" name="day[]" @if(in_array('we',$added_days)) checked @endif>
                                <label class="form-check-label" for="wed_checkbox">{{ __('general.wed') }}</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="thu_checkbox" value="th" name="day[]" @if(in_array('th',$added_days)) checked @endif>
                                <label class="form-check-label" for="thu_checkbox">{{ __('general.thu') }}</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="fri_checkbox" value="fr" name="day[]" @if(in_array('fr',$added_days)) checked @endif>
                                <label class="form-check-label" for="fri_checkbox">{{ __('general.fri') }}</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="sat_checkbox" value="sa" name="day[]" @if(in_array('sa',$added_days)) checked @endif>
                                <label class="form-check-label" for="sat_checkbox">{{ __('general.sat') }}</label>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>{{ __('forms.challenge.how_much_deal') }}</label>
                        </div>
                        <div class="col-md-8">
                            <input type="number" name="deal_amount" id="deal_amount" class="form-control" value="{{ $challenge->deal_amount }}"/>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>{{ __('forms.challenge.description') }}</label>
                        </div>
                        <div class="col-md-8">
                            <textarea name="description" id="description" class="form-control">{{ $challenge->description }}</textarea>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="d-flex align-items-center">
                                <div id="image_preview_edit_challenge" class="d-flex flex-wrap">
                                    @if($challenge && $challenge->challengeimages()->count())
                                        @foreach($challenge->challengeimages()->get() as $key => $imageData)
                                            <div class="removeImage">
                                                <span class="pointer" data-index={{$key}} data-imageid="{{$imageData->id}}"><i class="fa fa-times-circle fa-2x"></i></span>
                                                <div style="background-image: url({{$imageData->image_url}});" class="bgcoverimage">
                                                    <img src="{!! asset('img/noImage.png') !!}">
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                            <div class="add-image-icon  mt-2" style="display:flex;" >
                                {{ Form::file('edit_challenge_images',[ 'accept'=>"image/*", 'onchange'=>"imagesPreview(this, '#image_preview_edit_challenge', 'edit_challenge_images');", 'class' => 'main_image_file form-control', "multiple" => true, 'id' => "edit_challenge_images", 'hidden' => 'hidden' ]) }}
                                <label class="pointer edit_challenge_images" for="edit_challenge_images"><i class="fa fa-plus fa-4x"></i></label>
                            </div>
                        </div>
                    </div>

                    @if($challenge->is_period_challenge==1)
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>{{ __('forms.period_challenge.starting_date') }}</label>
                        </div>
                        <div class="col-md-8">
                            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $challenge->start_date }}"/>
                        </div>
                    </div>
                    @endif

                    @if($challenge->is_period_challenge==1)
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>{{ __('forms.period_challenge.end_date') }}</label>
                        </div>
                        <div class="col-md-8">
                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $challenge->end_date }}"/>
                        </div>
                    </div>
                    @endif

                    @if($challenge->is_period_challenge==0)
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>{{ __('forms.challenge.verify_date') }}</label>
                        </div>
                        <div class="col-md-8">
                            <input type="date" name="date" id="date" class="form-control" value="{{ $challenge->date }}"/>
                        </div>
                    </div>
                    @endif

                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>{{ __('forms.challenge.verify_time') }}</label>
                        </div>
                        <div class="col-md-8">
                            <input type="time" name="time" id="time" class="form-control" value="{{ $adminTime }}">
                        </div>
                    </div>

                    <div class="row thumb_image_list">
                        @if(count($thumbs->toArray()) > 0)
                        <div class="d-flex align-items-center">
                            <div class="d-flex flex-wrap">
                                @foreach($thumbs as $thumb)
                                <div class="removeImage">
                                    <div style="background-image: url({{ $thumb->image }});" class="bgcoverimage image-item">
                                        <img src="{{ asset('img/noImage.png') }}">
                                    </div>
                                    <input type="radio" name="thumb_image" value="{{ $thumb->id }}" style="margin-right: 5px" @if($challenge->challenge_thumb_id==$thumb->id) checked @endif/>
                                </div>
                                @endforeach
                            </div>
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
