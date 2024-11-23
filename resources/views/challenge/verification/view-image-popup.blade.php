<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header justify-content-center" style="border-bottom:none; padding: 8px;">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center" style="padding: 0px;" id="modelImageShow">
            {{--                <img src="{!! asset('img/logo-main.png') !!}" class="w-100 " id="modelImageEle" />--}}
            <div id="imageSlider" class="carousel slide" data-ride="carousel">
                <div class="carousel-inner">
                    @foreach($images as $image)
                    <div class="carousel-item @if($image->image_url==$active_image) active @endif">
                        <img class="d-block w-100" src="{{ $image->image_url }}" alt="Verification Image">
                    </div>
                    @endforeach
                    <!-- Add more carousel items for additional images -->
                </div>
                <a class="carousel-control-prev" href="#imageSlider" role="button" data-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="sr-only">Previous</span>
                </a>
                <a class="carousel-control-next" href="#imageSlider" role="button" data-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="sr-only">Next</span>
                </a>
            </div>
        </div>
        <div class="modal-footer pr-0 mt-3 mr-2">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="reject_checkbox" value="1" name="reject" verify-id="{{ $challenge_verify->id }}" @if($challenge_verify->is_rejected==1) checked @endif>
                <label class="form-check-label" for="reject_checkbox">{{ __('datatable.reject') }}</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="verified_checkbox" value="1" name="verified" verify-id="{{ $challenge_verify->id }}" @if($challenge_verify->is_verified==1) checked @endif>
                <label class="form-check-label" for="verified_checkbox">{{ __('datatable.verify') }}</label>
            </div>
        </div>
    </div>
</div>
