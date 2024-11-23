<div class="modal-dialog" style="max-width: 70%;">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Address</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center pb-0">
            <div class="row">
                <div class="form-group col-md-12 col-12">
                    {{--                    <label for="address_address">Address</label>--}}
                    <input type="text" id="address" name="address" class="form-control map-input" value="">
                    <input type="hidden" name="latitude" id="address-latitude" value="" />
                    <input type="hidden" name="longitude" id="address-longitude" value="" />
                </div>
                <div class="form-group col-md-12 col-12">
                    <input type="number" class="form-control" name="expose_distance" value="" placeholder="Radius">
                </div>
                <div class="form-group col-md-12 col-12">
                    <div id="address-map-container" style="width:80%;height:350px; ">
                        <div style="width: 100%; height: 100%" id="address-map"></div>
                    </div>
                </div>
                <div class="form-group">
                    <!-- <label>City</label> -->
                    <input type="hidden" class="form-control" name="city_name" id="address-city" value="">
                </div>
                <div class="form-group">
                    <!-- <label>State</label> -->
                    <input type="hidden" class="form-control" name="state_name" id="address-state" value="">
                </div>
                <div class="form-group">
                    <!-- <label>Country</label> -->
                    <input type="hidden" class="form-control" name="country_name" id="address-country" value="">
                </div>
                <input type="hidden" name="circle-lat" id="circle-lat" value="" />
                <input type="hidden" name="circle-long" id="circle-long" value="" />
                <input type="hidden" name="circle-distance" id="circle-distance" value="" />
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" id="apply_address_btn">Apply</button>
        </div>
    </div>
</div>
