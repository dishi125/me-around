<?php
$timeArr = explode(":",$challenge->verify_time);
$time = isset($timeArr[2]) ? $timeArr[0].":".$timeArr[1] : $challenge->verify_time; //remove seconds
$dbTime = \Carbon\Carbon::createFromFormat('H:i', $time, "UTC");
$adminTime = $dbTime->setTimezone("Asia/Seoul");
$adminTime = $adminTime->format('H:i');
?>
<div class="modal-dialog" style="max-width: 95%;">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            {{--            <h5>Messages</h5>--}}
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">
            <a class="btn btn-primary mr-2" style="float: right">{{ __('datatable.edit') }}</a>
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link active" id="info-tab" data-toggle="tab" href="#info-data" role="tab">{{ __('general.info') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="verified-tab" data-toggle="tab" href="#verified-data" role="tab">{{ __('general.verified') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="participants-tab" data-toggle="tab" href="#participants-data" role="tab">{{ __('general.participants') }}</a>
                </li>
            </ul>

            <div class="align-items-xl-center mb-3">
                <div class="w-100 tab-content">
                    <div class="tab-pane fade show active" id="info-data" role="tabpanel">
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>{{ __('forms.challenge.challenge_title') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" value="{{ $challenge->title }}" readonly/>
                            </div>
                        </div>

                        @if($challenge->is_period_challenge==1)
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <label>{{ __('forms.challenge.day') }}</label>
                                </div>
                                <div class="col-md-8">
                                    <?php $added_days = $challenge->challengedays()->pluck('day')->toArray(); ?>
                                    @foreach($added_days as $day)
                                    <span class="badge badge-pill" style="background-color: lightgrey">
                                        @if($day=="su") {{ __('general.sun') }}
                                        @elseif($day=="mo") {{ __('general.mon') }}
                                        @elseif($day=="tu") {{ __('general.tue') }}
                                        @elseif($day=="we") {{ __('general.wed') }}
                                        @elseif($day=="th") {{ __('general.thu') }}
                                        @elseif($day=="fr") {{ __('general.fri') }}
                                        @elseif($day=="sa") {{ __('general.sat') }}
                                        @endif
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>{{ __('forms.challenge.how_much_deal') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="number" class="form-control" value="{{ $challenge->deal_amount }}" readonly/>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>{{ __('forms.challenge.description') }}</label>
                            </div>
                            <div class="col-md-8">
                                <textarea class="form-control" readonly>{{ $challenge->description }}</textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="d-flex align-items-center">
                                    <div class="d-flex flex-wrap">
                                        @if($challenge && $challenge->challengeimages()->count())
                                            @foreach($challenge->challengeimages()->get() as $key => $imageData)
                                                <div class="removeImage">
                                                    <div style="background-image: url({{$imageData->image_url}});" class="bgcoverimage">
                                                        <img src="{!! asset('img/noImage.png') !!}">
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($challenge->is_period_challenge==1)
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <label>{{ __('forms.period_challenge.starting_date') }}</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="date" class="form-control" value="{{ $challenge->start_date }}" readonly/>
                                </div>
                            </div>
                        @endif

                        @if($challenge->is_period_challenge==1)
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <label>{{ __('forms.period_challenge.end_date') }}</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="date" class="form-control" value="{{ $challenge->end_date }}" readonly/>
                                </div>
                            </div>
                        @endif

                        @if($challenge->is_period_challenge==0)
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <label>{{ __('forms.challenge.verify_date') }}</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="date" class="form-control" value="{{ $challenge->date }}" readonly/>
                                </div>
                            </div>
                        @endif

                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>{{ __('forms.challenge.verify_time') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="time" class="form-control" value="{{ $adminTime }}" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="participants-data" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-striped" id="participants-data-table">
                                <thead>
                                <tr>
                                    <th class="mr-3">User Name</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($participants as $participant)
                                    <tr>
                                        <td>{{$participant->name}}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="verified-data" role="tabpanel">
                        <ul class="nav nav-tabs">
                            <li class="nav-item">
                                <a class="nav-link active" id="not-verified-tab" data-toggle="tab" href="#not-verified-data" role="tab">({{ count($notVerifiedData) }}) Not verified</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="verified-images-tab" data-toggle="tab" href="#verified-images-data" role="tab">({{ count($VerifiedData) }}) Verified images</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="all-tab" data-toggle="tab" href="#all-data" role="tab">({{ count($allData) }}) All</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="align-items-xl-center mb-3" style="display: none" id="verified_tab_div">
                <div class="w-100 tab-content">
                    <div class="tab-pane fade show active" id="not-verified-data" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-striped" id="not-verified-data-table">
                                <thead>
                                <tr>
                                    <th class="mr-3">User Name</th>
                                    <th class="mr-3">Date</th>
                                    <th class="mr-3">Images</th>
                                    <th class="mr-3">Uploaded At</th>
                                    <th class="mr-3"></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($notVerifiedData as $notVerified)
                                    <tr>
                                        <td>{{$notVerified->name}}</td>
                                        <td>{{$notVerified->date}}</td>
                                        <td>
                                        <?php $time = ""; ?>
                                        @foreach($notVerified->verifiedimages as $key=>$img)
                                            <?php
                                            if ($key==0){
                                                $time = \App\Http\Controllers\Controller::formatDateTimeCountryWise($img->created_at,$adminTimezone);
                                            }
                                            ?>
                                            <img src="{{ $img->image_url }}" class="reported-client-images pointer m-1" width="50" height="50">
                                        @endforeach
                                        </td>
                                        <td>{{ $time }}</td>
                                        <td>Not verified</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="verified-images-data" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-striped" id="verified-images-data-table">
                                <thead>
                                <tr>
                                    <th class="mr-3">User Name</th>
                                    <th class="mr-3">Date</th>
                                    <th class="mr-3">Images</th>
                                    <th class="mr-3">Uploaded At</th>
                                    <th class="mr-3"></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($VerifiedData as $Verified)
                                    <tr>
                                        <td>{{$Verified->name}}</td>
                                        <td>{{$Verified->date}}</td>
                                        <td>
                                            <?php $time = ""; ?>
                                            @foreach($Verified->verifiedimages as $key=>$img)
                                                <?php
                                                if ($key==0){
                                                    $time = \App\Http\Controllers\Controller::formatDateTimeCountryWise($img->created_at,$adminTimezone);
                                                }
                                                ?>
                                                <img src="{{ $img->image_url }}" class="reported-client-images pointer m-1" width="50" height="50">
                                            @endforeach
                                        </td>
                                        <td>{{ $time }}</td>
                                        <td>Verified</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="all-data" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-striped" id="all-data-table">
                                <thead>
                                <tr>
                                    <th class="mr-3">User Name</th>
                                    <th class="mr-3">Date</th>
                                    <th class="mr-3">Images</th>
                                    <th class="mr-3">Uploaded At</th>
                                    <th class="mr-3"></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($allData as $all)
                                    <tr>
                                        <td>{{$all->name}}</td>
                                        <td>{{$all->date}}</td>
                                        <td>
                                            <?php $time = ""; ?>
                                            @foreach($all->verifiedimages as $key=>$img)
                                                <?php
                                                if ($key==0){
                                                    $time = \App\Http\Controllers\Controller::formatDateTimeCountryWise($img->created_at,$adminTimezone);
                                                }
                                                ?>
                                                <img src="{{ $img->image_url }}" class="reported-client-images pointer m-1" width="50" height="50">
                                            @endforeach
                                        </td>
                                        <td>{{ $time }}</td>
                                        <td>{{ ($all->is_verified==1) ? "Verified" : "Not verified" }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    $("#participants-data-table").DataTable();
    $("#not-verified-data-table").DataTable();

    $(document).on('click','#verified-tab',function (){
        $("#verified_tab_div").show();
    })

    $(document).on('click','#info-tab',function (){
        $("#verified_tab_div").hide();
    })

    $(document).on('click','#participants-tab',function (){
        $("#verified_tab_div").hide();
    })
</script>

