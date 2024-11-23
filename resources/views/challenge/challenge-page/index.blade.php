@extends('challenge-layouts.app')

@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
    <style>
        .table-responsive button#show-profile {
            width: auto;
            margin: 5px;
            white-space: normal;
        }

        .table-responsive .shops-date button#show-profile {
            width: 180px;
        }

        .table-responsive .shops-rate button#show-profile {
            width: 80px;
        }

        .table-responsive td span {
            margin: 5px;
        }

        .button-container {
            display: flex;
            flex-direction: row;
            /*justify-content: space-between;*/
            align-items: center;
        }

        .vertical-buttons {
            display: flex;
            flex-direction: column;
        }

        .select2-container--default .select2-results__option[aria-selected=true] {
            color: black !important;
        }
        .select2-selection__choice {
            color: dimgrey !important;
        }

        .image-item {
            cursor: pointer;
        }
    </style>
@endsection

@section('header-content')
    <h1>
        @if (@$title)
            {{ @$title }}
        @endif
    </h1>
    <div class="section-header-button">
        <button class="btn btn-primary mr-2" id="add_period_challenge">{{ __('general.period_challenge') }} ({{ $count_today_period_challenges }})</button>
        <button class="btn btn-primary mr-2" id="add_challenge">{{ __('general.challenge') }} ({{ $count_today_challenges }})</button>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="tab-content" id="myTabContent2">
                        <div class="tab-pane fade show active" id="allData" role="tabpanel"
                            aria-labelledby="all-data">
                            <div class="table-responsive">
                                <table class="table table-striped" id="all-table">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Title</th>
                                            <th></th>
                                            <th>{{ __('datatable.challenge.no_participants') }}</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="cover-spin"></div>
@endsection

<!-- Modal -->
<div class="modal fade" id="periodChallengeModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" style="max-width: 50%;">
        <div class="modal-content">
            <form id="periodChallengeForm" method="post">
                {{ csrf_field() }}
                <div class="modal-header justify-content-center">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body justify-content-center">
                    <div class="align-items-xl-center mb-3">
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>{{ __('forms.period_challenge.challenge_title') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="title" id="title" class="form-control"/>
                                @error('title')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>{{ __('forms.period_challenge.select_category') }}</label>
                            </div>
                            <div class="col-md-8">
                                <select class="form-control" name="category_id" id="category_id">
                                    <option selected disabled>Select...</option>
                                    @foreach($period_challenge_cats as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>{{ __('forms.period_challenge.select_day') }}</label>
                            </div>
                            <div class="col-md-8" id="div_day">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="sun_checkbox" value="su" name="day[]">
                                    <label class="form-check-label" for="sun_checkbox">{{ __('general.sun') }}</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="mon_checkbox" value="mo" name="day[]">
                                    <label class="form-check-label" for="mon_checkbox">{{ __('general.mon') }}</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="tue_checkbox" value="tu" name="day[]">
                                    <label class="form-check-label" for="tue_checkbox">{{ __('general.tue') }}</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="wed_checkbox" value="we" name="day[]">
                                    <label class="form-check-label" for="wed_checkbox">{{ __('general.wed') }}</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="thu_checkbox" value="th" name="day[]">
                                    <label class="form-check-label" for="thu_checkbox">{{ __('general.thu') }}</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="fri_checkbox" value="fr" name="day[]">
                                    <label class="form-check-label" for="fri_checkbox">{{ __('general.fri') }}</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="sat_checkbox" value="sa" name="day[]">
                                    <label class="form-check-label" for="sat_checkbox">{{ __('general.sat') }}</label>
                                </div>
                                @error('day')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>{{ __('forms.period_challenge.verify_time') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="time" name="time" id="time" class="form-control">
                                @error('time')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>{{ __('forms.period_challenge.how_much_deal') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="number" name="deal_amount" id="deal_amount" class="form-control"/>
                                @error('deal_amount')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>{{ __('forms.period_challenge.description') }}</label>
                            </div>
                            <div class="col-md-8">
                                <textarea name="description" id="description" class="form-control"></textarea>
                                @error('description')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="d-flex align-items-center">
                                    <div id="image_preview_period_challenge" class="d-flex flex-wrap"></div>
                                </div>
                                <div class="add-image-icon  mt-2" style="display:flex;" >
                                    {{ Form::file('period_challenge_images',[ 'accept'=>"image/*", 'onchange'=>"imagesPreview(this, '#image_preview_period_challenge', 'period_challenge_images');", 'class' => 'main_image_file form-control', "multiple" => true, 'id' => "period_challenge_images", 'hidden' => 'hidden' ]) }}
                                    <label class="pointer period_challenge_images" for="period_challenge_images"><i class="fa fa-plus fa-4x"></i></label>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>{{ __('forms.period_challenge.starting_date') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="date" name="start_date" id="start_date" class="form-control"/>
                                @error('start_date')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>{{ __('forms.period_challenge.end_date') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="date" name="end_date" id="end_date" class="form-control"/>
                                @error('end_date')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row thumb_image_list">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{!! __(Lang::get('general.close')) !!}</button>
                    <button type="submit" class="btn btn-primary" id="save_btn">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="challengeModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" style="max-width: 50%;">
        <div class="modal-content">
            <form id="challengeForm" method="post">
                {{ csrf_field() }}
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
                                <input type="text" name="challenge_title" id="challenge_title" class="form-control"/>
                                @error('challenge_title')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>{{ __('forms.challenge.select_category') }}</label>
                            </div>
                            <div class="col-md-8">
                                <select class="form-control" name="category_id" id="category_id">
                                    <option selected disabled>Select...</option>
                                    @foreach($challenge_cats as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>{{ __('forms.challenge.how_much_deal') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="number" name="challenge_deal_amount" id="challenge_deal_amount" class="form-control"/>
                                @error('challenge_deal_amount')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>{{ __('forms.challenge.description') }}</label>
                            </div>
                            <div class="col-md-8">
                                <textarea name="desc" id="desc" class="form-control"></textarea>
                                @error('desc')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="d-flex align-items-center">
                                    <div id="image_preview_challenge" class="d-flex flex-wrap"></div>
                                </div>
                                <div class="add-image-icon  mt-2" style="display:flex;" >
                                    {{ Form::file('challenge_images',[ 'accept'=>"image/*", 'onchange'=>"imagesPreview(this, '#image_preview_challenge', 'challenge_images');", 'class' => 'main_image_file form-control', "multiple" => true, 'id' => "challenge_images", 'hidden' => 'hidden' ]) }}
                                    <label class="pointer challenge_images" for="challenge_images"><i class="fa fa-plus fa-4x"></i></label>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>{{ __('forms.challenge.verify_date') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="date" name="date" id="date" class="form-control"/>
                                @error('date')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>{{ __('forms.challenge.verify_time') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="time" name="challenge_time" id="challenge_time" class="form-control">
                                @error('challenge_time')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row thumb_image_list">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{!! __(Lang::get('general.close')) !!}</button>
                    <button type="submit" class="btn btn-primary" id="save_btn">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>

<div class="modal fade" id="userListModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>

<div class="modal fade" id="seeChallengeModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>

@section('scripts')
    <script>
        var allDataTable = "{{ route('challenge.challenge-page.all.table') }}";
        var csrfToken = "{{ csrf_token() }}";
        var mainImagesFiles = [];
        var removableImages = [];
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script>
        $(document).ready(function (){
            loadTableData();
        })

        function loadTableData() {
            var all = $("#all-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                // order: [[ 3, "desc" ]],
                ajax: {
                    url: allDataTable,
                    dataType: "json",
                    type: "POST",
                    data: {
                        _token: csrfToken,
                    }
                },
                columns: [
                    {
                        data: "thumb",
                        orderable: false
                    },
                    {
                        data: "title",
                        orderable: true
                    },
                    {
                        data: "mark",
                        orderable: false
                    },
                    {
                        data: "participants",
                        orderable: false
                    },
                    {
                        data: "action",
                        orderable: false
                    },
                ]
            });
        }

        $(document).on('click', '#add_period_challenge', function (){
            $("#periodChallengeModal").modal('show');
        })

        $(document).on('click', '#add_challenge', function (){
            $("#challengeModal").modal('show');
        })

        $('#periodChallengeModal').on('hidden.bs.modal', function () {
            $("#periodChallengeForm")[0].reset();
            mainImagesFiles = [];
            $('#periodChallengeModal').find("#image_preview_period_challenge").html("");
            $(".text-danger").remove();
            $(".thumb_image_list").html("");
            // $('#periodChallengeModal').find("#day").select2('destroy');
            // $('#periodChallengeModal').find("#day").select2({});
        })

        $('#challengeModal').on('hidden.bs.modal', function () {
            $("#challengeForm")[0].reset();
            mainImagesFiles = [];
            $('#challengeModal').find("#image_preview_challenge").html("");
            $(".text-danger").remove();
            $(".thumb_image_list").html("");
        })

        $(document).on('submit', '#periodChallengeForm', function (e){
            e.preventDefault();
            $(".text-danger").remove();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            var formData = new FormData($('form#periodChallengeForm')[0]);
            if(mainImagesFiles){
                $.map(mainImagesFiles, function(file, index) {
                    formData.append('main_images[]', file);
                });
            }

            $.ajax({
                url: "{{ url('challenge/period-challenge/save') }}",
                processData: false,
                contentType: false,
                cache: false,
                enctype: 'multipart/form-data',
                type: 'POST',
                data: formData,
                success:function(response){
                    $(".cover-spin").hide();
                    if(response.success == true){
                        $("#periodChallengeModal").modal('hide');
                        showToastMessage('Period challenge added successfully.',true);
                        $('#all-table').DataTable().destroy();
                        loadTableData();
                    }
                    else {
                        if(response.errors) {
                            var errors = response.errors;
                            $.each(errors, function (key, value) {
                                if(key=="day"){
                                    $("#periodChallengeModal").find('#div_day').append('<div class="text-danger">' + value[0] + '</div>');
                                }
                                else {
                                    $("#periodChallengeModal").find('#' + key).after('<div class="text-danger">' + value[0] + '</div>');
                                }
                            });
                        }
                        else {
                            showToastMessage(response.message,false);
                        }
                    }
                },
                beforeSend: function(){ $(".cover-spin").show(); },
                error: function (xhr) {
                    $(".cover-spin").hide();
                    showToastMessage('Something went wrong!!',false);
                },
            });
        })

        $(document).on('submit', '#challengeForm', function (e){
            e.preventDefault();
            $(".text-danger").remove();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            var formData = new FormData($('form#challengeForm')[0]);
            if(mainImagesFiles){
                $.map(mainImagesFiles, function(file, index) {
                    formData.append('main_images[]', file);
                });
            }

            $.ajax({
                url: "{{ url('challenge/challenge/save') }}",
                processData: false,
                contentType: false,
                cache: false,
                enctype: 'multipart/form-data',
                type: 'POST',
                data: formData,
                success:function(response){
                    $(".cover-spin").hide();
                    if(response.success == true){
                        $("#challengeModal").modal('hide');
                        showToastMessage('Challenge added successfully.',true);
                        $('#all-table').DataTable().destroy();
                        loadTableData();
                    }
                    else {
                        if(response.errors) {
                            var errors = response.errors;
                            $.each(errors, function (key, value) {
                                $("#challengeModal").find('#' + key).next('.text-danger').remove();
                                $("#challengeModal").find('#' + key).after('<div class="text-danger">' + value[0] + '</div>');
                            });
                        }
                        else {
                            showToastMessage(response.message,false);
                        }
                    }
                },
                beforeSend: function(){ $(".cover-spin").show(); },
                error: function (xhr) {
                    $(".cover-spin").hide();
                    showToastMessage('Something went wrong!!',false);
                },
            });
        })

        function imagesPreview(input, placeToInsertImagePreview, fieldname){
            var noImage = baseUrl + "/public/img/noImage.png";
            mainImagesFiles = mainImagesFiles || [];

            if (input.files) {
                Array.from(input.files).forEach(async (file,index) => {
                    const validImageTypes = ['image/gif', 'image/jpeg', 'image/jpg', 'image/png', 'image/svg', 'image/svg+xml'];
                    console.log(file.type)
                    var currentTimestemp = new Date().getTime()+''+index;
                    file.timestemp = currentTimestemp;
                    if (validImageTypes.includes(file.type)) {
                        var reader = await new FileReader(file);
                        reader.onload = function(event) {
                            var bgImage = $($.parseHTML('<div>')).attr('style', 'background-image: url('+event.target.result+')').addClass("bgcoverimage").wrapInner("<img src='"+noImage+"' />");
                            var container = jQuery("<div></div>",{class: "removeImage", html:'<span fieldname="'+fieldname+'" data-timestemp="'+currentTimestemp+'" class="pointer"><i class="fa fa-times-circle fa-2x"></i></span>'});
                            container.append(bgImage);
                            container.appendTo(placeToInsertImagePreview);
                            mainImagesFiles.push(file);
                        }
                        reader.readAsDataURL(file);
                    }
                    else {
                        showToastMessage("Invalid image type!!")
                    }
                });
            }
        }

        $(document).on('click','.removeImage > span',function(){
            var timestemp = $(this).attr('data-timestemp');
            var fieldname = $(this).attr('fieldname');
            var imageid = $(this).attr('data-imageid');
            var index = $(this).attr('data-index');

            //For remove images when edit
            if(imageid!=undefined){
                removableImages.push(imageid);
            }

            mainImagesFiles.splice( $.inArray(imageid, mainImagesFiles), 1 );

            $("#"+fieldname).val('');
            console.log($(this).closest( ".add-image-icon" ))
            $(this).parent().parent().parent().next( ".add-image-icon" ).show();
            $(this).parent().remove();
            $('.maxerror').remove();
        });

        function editChallenge(id) {
            $.get(baseUrl + '/challenge/challenge-page/edit/' + id, function (data, status) {
                $("#editModal").html('');
                $("#editModal").html(data);
                $("#editModal").modal('show');
            });
        }

        $('#editModal').on('hidden.bs.modal', function () {
            mainImagesFiles = [];
            removableImages = [];
        })

        $(document).on('submit', '#editForm', function (e){
            e.preventDefault();
            $(".text-danger").remove();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            var formData = new FormData($('form#editForm')[0]);
            if(mainImagesFiles){
                $.map(mainImagesFiles, function(file, index) {
                    formData.append('main_images[]', file);
                });
            }
            if(removableImages){
                $.map(removableImages, function(val, index) {
                    formData.append('remove_images[]', val);
                });
            }

            $.ajax({
                url: "{{ url('challenge/challenge-page/update') }}",
                processData: false,
                contentType: false,
                cache: false,
                enctype: 'multipart/form-data',
                type: 'POST',
                data: formData,
                success:function(response){
                    $(".cover-spin").hide();
                    if(response.success == true){
                        $("#editModal").modal('hide');
                        showToastMessage('Challenge updated successfully.',true);
                        $('#all-table').DataTable().destroy();
                        loadTableData();
                    }
                    else {
                        if(response.errors) {
                            var errors = response.errors;
                            $.each(errors, function (key, value) {
                                if(key=="day"){
                                    $("#editModal").find('#div_day').append('<div class="text-danger">' + value[0] + '</div>');
                                }
                                else {
                                    $("#editModal").find('#' + key).after('<div class="text-danger">' + value[0] + '</div>');
                                }
                            });
                        }
                        else {
                            showToastMessage(response.message,false);
                        }
                    }
                },
                beforeSend: function(){ $(".cover-spin").show(); },
                error: function (xhr) {
                    $(".cover-spin").hide();
                    showToastMessage('Something went wrong!!',false);
                },
            });
        })

        function showUserList(challenge_id){
            $.get(baseUrl + '/challenge/challenge-page/users/' + challenge_id, function (data, status) {
                $('#userListModal').html('');
                $('#userListModal').html(data);
                $('#userListModal').modal('show');
            });
        }

        $(document).on('click','.checkbox_select_user', function (){
            if (this.checked) {
                this.checked = true;
            }
            else {
                this.checked = false;
            }
        });

        $(document).on('click','#save_user_btn', function (){
            var ids = $('input[name="select_user[]"]:checked').map(function(_, el) {
                return $(el).val();
            }).get();
            var challenge_id = $(this).attr('challenge-id');

            $.ajax({
                url: "{{ route('challenge.challenge-page.select-users') }}",
                method: 'POST',
                data: {
                    _token: csrfToken,
                    user_ids: ids,
                    challenge_id: challenge_id,
                },
                beforeSend: function() {
                    $('.cover-spin').show();
                },
                success: function(response) {
                    $('.cover-spin').hide();
                    if (response.success == true) {
                        $('#userListModal').modal('hide');
                        showToastMessage("Users participation updated.",true);
                        $('#all-table').DataTable().destroy();
                        loadTableData();
                    } else {
                        showToastMessage("Something went wrong!!",false);
                    }
                },
                error: function (){
                    $('.cover-spin').hide();
                    showToastMessage("Something went wrong!!",false);
                }
            });
        });

        function seeChallenge(challenge_id){
            $.get(baseUrl + '/challenge/challenge-page/view/' + challenge_id, function (data, status) {
                $('#seeChallengeModal').html('');
                $('#seeChallengeModal').html(data);
                $('#seeChallengeModal').modal('show');
            });
        }

        $(document).on('change','#category_id',function (){
            var challenge_type = $(this).val();
            $(".thumb_image_list").html("");
            var category_id = $(this).val();

            $.ajax({
                url: "{{ url('challenge/get/thumb-list') }}",
                type: 'POST',
                data: {category_id: category_id},
                success:function(response){
                    $(".thumb_image_list").html(response.html);
                },
                error: function (xhr) {
                },
            });
        })
    </script>
@endsection
