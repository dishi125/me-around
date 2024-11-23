<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Instagram service</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">×</span></button>
        </div>
        <form method="POST" action="javascript:void(0)" id="instagram_service">
            @csrf
            <div class="modal-body justify-content-center">
                <h5 class="mb-2"></h5>
                <div class="form-group mt-1">
                    <label for="count_day">Day Count</label>
                    <input id="count_day" value="{{ $shop->count_days }}" type="number" name="count_day" value=""
                        class="form-control" />
                </div>
                <div class="mb-2">
                    <strong>Expiry Date : </strong> <span class="display-date-detail">
                        {{ \Carbon::now()->addDays($shop->count_days)->format('Y-m-d') }}</span>
                </div>
                <div class="form-check d-flex">
                    <input {{ $shop->is_regular_service == 1 ? 'checked' : '' }} id="regular_service" type="checkbox"
                        name="regular_service" value="1" class="form-check-input" />
                    <label class="form-check-label regular_service_label" for="regular_service">
                        Regular Service
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <div class="button mt-3">
                    <input type="submit" shop_id="{{ $shop->id }}" class="btn btn-dark save_supporter_details"
                        value="Save" />
                </div>
            </div>
        </form>
    </div>
</div>
