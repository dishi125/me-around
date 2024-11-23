@extends('business-layouts.app')

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
<link rel="stylesheet" href="{!! asset('css/chocolat.css') !!}">
@endsection

@section('content')
<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-8">
            <div class="card profile-widget">
                <div class="profile-widget-header">
                    <img alt="image" src="{!! asset('img/hospital.png') !!}"
                        class="rounded-circle profile-widget-picture">
                    <div class="profile-widget-items">
                        <div class="profile-widget-item">
                            <div class="profile-widget-item-label">Followers</div>
                            <div class="profile-widget-item-value">0</div>
                        </div>
                        <div class="profile-widget-item">
                            <div class="profile-widget-item-label">Work Complete</div>
                            <div class="profile-widget-item-value">{{ $hospital->work_complete }}</div>
                        </div>
                        <div class="profile-widget-item">
                            <div class="profile-widget-item-label">Review</div>
                            <div class="profile-widget-item-value">{{ $hospital->reviews }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                {!! Form::open(['route' => ['admin.business-client.update.hodpital',$hospital->id], 'id'
                =>"saveHospitalDetailForm", 'method' => 'post', 'enctype' => 'multipart/form-data']) !!}
                @csrf

                <div class="card">
                    <div class="card-header d-inline">
                        <h4 class="float-left">Personal Detail</h4>
                        <a href="javascript:void(0)" class="btn btn-primary saveHospitalDetail float-right rounded"
                            id="saveHospitalDetail">Save</a>
                    </div>
                    <div class="card-body">
                        {{ Form::hidden('hospital_id', $hospital->id, array('id' => 'hospital_id')) }}
                        <div class="row">
                            <div class="form-group col-md-6 col-12">
                                <label>Name</label>
                                <input type="text" name="main_name" class="form-control"
                                    value="{{$hospital->main_name}}">
                            </div>
                            <div class="form-group col-md-6 col-12">
                                <label>Email Address</label>
                                <input type="email" name="email" class="form-control" value="{{$hospital->email}}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6 col-12">
                                <label>Business Licence Number</label>
                                <input type="text" readonly name="business_license_number" class="form-control"
                                    value="{{$hospital->business_license_number}}">
                            </div>
                            <div class="form-group col-md-6 col-12">
                                <label>Mobile Number</label>
                                <input type="text" name="mobile" class="form-control" value="{{$hospital->mobile}}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12 col-12">
                                <label>Hospital Introduce</label>
                                <input type="text" name="description" class="form-control"
                                    value="{{$hospital->description}}">
                            </div>
                        </div>
                    </div>
                    <div class="card-header">
                        <h4>Address Detail</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!--
                                    <div class="form-group col-md-6 col-12">
                                        <label>Address</label>
                                        <input type="text" class="form-control" name="address" value="{{$hospital->address->address}}" >
                                    </div> -->
                            <div class="form-group col-md-12 col-12">
                                <label for="address_address">Address</label>
                                <input type="text" id="address" name="address" class="form-control map-input"
                                    value="{{$hospital->address->address}}">
                                <input type="hidden" name="latitude" id="address-latitude"
                                    value="{{$hospital->address->latitude}}" />
                                <input type="hidden" name="longitude" id="address-longitude"
                                    value="{{$hospital->address->longitude}}" />
                            </div>
                            <div class="form-group col-md-12 col-12">
                                <input type="text" id="address_detail" name="address_detail" class="form-control"
                                    value="{{$hospital->address->address2}}" placeholder="Address detail">
                            </div>
                            <div class="form-group col-md-12 col-12">
                                <div id="address-map-container" style="width:80%;height:200px; ">
                                    <div style="width: 100%; height: 100%" id="address-map"></div>
                                </div>
                            </div>
                            <div class="form-group col-md-6 col-12  mb-0">
                                <!-- <label>City</label> -->
                                <input type="hidden" class="form-control" name="city_name" id="address-city"
                                    value="{{$hospital->address->city_name}}">
                            </div>
                            <div class="form-group col-md-6 col-12  mb-0">
                                <!-- <label>State</label> -->
                                <input type="hidden" class="form-control" name="state_name" id="address-state"
                                    value="{{$hospital->address->state_name}}">
                            </div>
                            <div class="form-group col-md-6 col-12  mb-0">
                                <!-- <label>Country</label> -->
                                <input type="hidden" class="form-control" name="country_name" id="address-country"
                                    value="{{$hospital->address->country_name}}">
                            </div>
                        </div>
                    </div>


                    <div class="form-group col-md-12 col-12">
                        {!! Form::label('outside_bussiness','Outside business?'); !!}<br>
                        {{ Form::radio('outside_bussiness', 'yes',($hospital->business_link != '' ? true : false)) }}Yes
                        {{ Form::radio('outside_bussiness', 'no',($hospital->business_link == '' ? true : false)) }}No
                    </div>

                    <div class="form-group col-md-12 col-12 business_link_block"
                        style="display:{!! ($hospital->business_link != '') ? 'block' : 'none' !!}">
                        <label>Bussiness Link</label>
                        <input type="text" class="form-control" name="business_link" id="business_link"
                            value="{{$hospital->business_link}}">
                    </div>
                </div>

                {!! Form::close() !!}

            </div>
        </div>


        <div class="col-md-4 mt-3 pt-4">
            <div class="card">
                <div class="card-header">
                    <h4>Hospital Interior</h4>
                </div>
                <div class="card-body">
                    <div class="form-control w-50 rounded">
                        <input type="file" accept="image/*" class="upload_hospital_images" name="files[]"
                            id="uploadWorkPlaceImages" multiple>
                    </div>
                    <div class="gallery gallery-md pt-4" id="work_place_gallery">
                        @foreach($hospital->images as $image)
                        <div style="display:inline-grid;cursor: pointer;" id="image_{!! $image->id !!}">
                            <div class="gallery-item" data-image="{!! $image->image !!}"
                                data-title="{!! $hospital->main_name !!}"></div>
                            <a class="deleteImages float-right text-danger pb-2 pl-3" id="{!! $image->id !!}">
                                <strong>Delete</strong>
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
<div class="cover-spin">
</div>

@section('scripts')
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/chocolat.js') !!}"></script>

<script type="text/javascript"
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDlfhV6gvSJp_TvqudE0z9mV3bBlexZo3M&&radius=100&&libraries=places&callback=initialize"
    async defer></script>
<script src="{!! asset('js/mapInput.js') !!}"></script>

<script>
    $(document).on('change','input[type=radio][name=outside_bussiness]',function(e){
        if (this.value == 'yes') {
            $('div.business_link_block').show();
        }
        else if (this.value == 'no') {
            $('div.business_link_block').hide();
        }
    });

    var uploadImages = "{{ route('admin.business-client.upload.hospital.images') }}";
    var deleteImages = "{{ route('admin.business-client.delete.hospital.images') }}";

    $(document).on('click', '#saveHospitalDetail', function(e) {
        var form = $('#saveHospitalDetailForm')[0];
        var formData = new FormData(form);


        $.ajax({
            url: $(form).attr('action'),
            processData: false,
            contentType: false,
            type: 'POST',
            data: formData,
            beforeSend: function(){ $(".cover-spin").show(); },
            success: function (data) {
                $(".cover-spin").hide();
                if(data.status_code == 200) {
                    iziToast.success({
                        title: '',
                        message: data.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });
                }else {
                    iziToast.error({
                        title: '',
                        message: data.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });
                }
            }
        });

    });

    $(document).on('click','.deleteImages',function(e){

        var id = $(this).attr('id');

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="token"]').attr('value')
            }
        });
        $.ajax({
            method: "POST",
            url: deleteImages,
            data: {'id' : id},
            beforeSend: function(){ $(".cover-spin").show(); },
            success: function (result) {
                $(".cover-spin").hide();
                if(result.status_code == 200) {
                    $('div#image_'+id).remove();
                    iziToast.success({
                        title: '',
                        message: result.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });
                }else {
                    iziToast.error({
                        title: '',
                        message: result.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });
                }
            }
        });
    });

    $(document).on('change','.upload_hospital_images',function(e){

        var fileData = new FormData();

        var fileAttr = $('#uploadWorkPlaceImages');

        let TotalFiles = fileAttr[0].files.length;
        for (let i = 0; i < TotalFiles; i++) {
            fileData.append('files[]', fileAttr.prop('files')[i]);
        }
        fileData.append('TotalFiles', TotalFiles);
        fileData.append('hospital_id', $('#hospital_id').val());
        fileData.append('main_name', $('#main_name').val());

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="token"]').attr('value')
            }
        });
        $.ajax({
            type: "POST",
            url: uploadImages,
            dataType: "json",
            contentType: "application/json; charset=utf-8",
            contentType: false,
            processData: false,
            data: fileData,
            beforeSend: function(){ $(".cover-spin").show(); },
            success: function (result) {

                $(".cover-spin").hide();
                if(result.status_code == 200) {

                    $('#work_place_gallery').append(result.uploadedFilesHtml);

                    iziToast.success({
                        title: '',
                        message: result.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });
                }else {
                    iziToast.error({
                        title: '',
                        message: result.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });
                }
            },
            error: function (result) {

                $(".cover-spin").hide();
                iziToast.error({
                    title: '',
                    message: "Image is not uploaded successfully",
                    position: 'topRight',
                    progressBar: false,
                    timeout: 1000,
                });
            }
        });
        });

    $(document).on('submit',"#savesupporter",function(event){
        event.preventDefault();
        $('label.error').remove();
        var formData = new FormData(this);

        $.ajax({
            url: $(this).attr('action'),
            type:"POST",
            contentType: false,
            processData: false,
            data: formData,
            beforeSend: function() {
                $('.cover-spin').show();
            },
            success:function(response) {
                $('.cover-spin').hide();
                if(response.success == true){
                    iziToast.success({
                        title: '',
                        message: response.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });

                }else {
                    iziToast.error({
                        title: '',
                        message: response.message,
                        //message: 'Suppor has not been updated successfully.',
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1500,
                    });
                }
            },
            error:function (response, status) {
                $('.cover-spin').hide();
                if( response.responseJSON.success === false ) {
                    var errors = response.responseJSON.errors;

                    $.each(errors, function (key, val) {
                        console.log(val)
                        var errorHtml = '<label class="error">'+    val.join("<br />")+'</label>';
                        $('#'+key.replaceAll(".", "_")).parent().append(errorHtml);
                    });
                }
            }
        });
    });
</script>
@endsection