@extends('layouts.app')

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
        <div class="col-12 col-md-12 col-lg-5">           
            <div class="card profile-widget">
                <div class="profile-widget-header">
                    <!-- <img alt="image"
                        src="{!! asset('img/hospital.png') !!}"
                        class="profile-widget-picture mr-3"> -->
                        <!-- <div class="font-weight-bold profile-widget-name text-md">Shop 1</div> -->
                    </div>
                    <div class="profile-widget-description">
                    <div class="profile-widget-name">{{$shop->main_name}}
                        <div class="text-muted d-inline font-weight-normal">
                        </div>
                    </div>
                    <div class="gallery gallery-md">
                        <div class="gallery-item"
                            data-image="{!! $shop->best_portfolio_url !!}"
                            data-title="Best Portfolio"></div>
                        <div class="gallery-item"
                            data-image="{!! $shop->business_licence_url !!}"
                            data-title="Business Licence"></div>
                        <div class="gallery-item"
                            data-image="{!! $shop->identification_card_url !!}"
                            data-title="Identification Card"></div>
                    </div>
                </div>
                </div>
                <div class="card profile-widget">
                    <div class="profile-widget-header">                    
                        <div class="profile-widget-items">
                            <div class="profile-widget-item">
                                <div class="profile-widget-item-label">Followers</div>
                                <div class="profile-widget-item-value">{{ $shop->followers }}</div>
                            </div>
                            <div class="profile-widget-item">
                                <div class="profile-widget-item-label">Work Complete</div>
                                <div class="profile-widget-item-value">{{ $shop->work_complete }}</div>
                            </div>
                            <div class="profile-widget-item">
                                <div class="profile-widget-item-label">Review</div>
                                <div class="profile-widget-item-value">{{ $shop->reviews }}</div>
                            </div>
                            <div class="profile-widget-item">
                                <div class="profile-widget-item-label">Portfolio</div>
                                <div class="profile-widget-item-value">{{ count($shop->portfolio_images) }}</div>
                            </div>
                        </div>
                    </div>   
                </div>
                <div class="section-header-button position-absolute" style="bottom:15px; right:0;">
                    <a href="{{ route('admin.business-client.shoppost.create',[$shop->id]) }}" class="btn btn-primary">Add Portfolio</a>
                </div>
        </div>
        <div class="col-12 col-md-12 col-lg-7 mt-sm-3 pt-3">
            <div class="card">
                <div class="card-header">
                    <h4>Shop Details</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-md-6 col-12">
                            <label>Main Name</label>
                            <input type="text" class="form-control" value="{{$shop->main_name}}" readonly>
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label>Shop Name</label>
                            <input type="text" class="form-control" value="{{$shop->shop_name}}" readonly>
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label>Business Licence Number</label>
                            <input type="text" class="form-control" value="{{$shop->business_license_number}}" readonly>
                        </div>
                    </div>                    
                </div>
                <div class="card-header">
                    <h4>Address Detail</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-md-6 col-12">
                            <label>Address</label>
                            <input type="text" class="form-control" value="{{$shop->address->address}}" readonly>
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label>City</label>
                            <input type="text" class="form-control" value="{{$shop->address->city_name}}" readonly>
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label>State</label>
                            <input type="text" class="form-control" value="{{$shop->address->state_name}}" readonly>
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label>Country</label>
                            <input type="text" class="form-control" value="{{$shop->address->country_name}}" readonly>
                        </div>
                    </div>
                </div>
                @if($shop->sns_link && $shop->sns_type)
                    <div class="card-header">
                        <h4>Social networking service</h4>
                    </div>
                    <div class="card-body">
                        <div class="row ml-0">
                            <div class="col-md-4 col-4">
                                <a href="{{$shop->sns_link}}" target="_blank"> <i class="fab fa-{{$shop->sns_type}}"></i> {{ ucfirst($shop->sns_type) }} </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Main Profile </h4>
                </div>
                <div class="card-body">                   
                    <div class="gallery gallery-md">   
                    @foreach($shop->main_profile_images as $pi)                    
                    <div class="gallery-item" data-image="{!! $pi['image'] !!}" data-title="{!! $shop->main_name !!}"></div>
                    @endforeach    
                    </div>                   
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Workplace </h4>
                </div>
                <div class="card-body">                   
                    <div class="gallery gallery-md">                       
                    @foreach($shop->workplace_images as $wp)                    
                    <div class="gallery-item" data-image="{!! $wp['image'] !!}" data-title="{!! $shop->main_name !!}"></div>
                    @endforeach   
                    </div>                   
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Portfolio</h4>
                </div>
                <div class="card-body">                   
                <div class="list">                        
                        @foreach($shop->shopPostList as $pi) 
                            <div class="item" style="width:80px; height:80px; float: left;margin: 5px 5px">
                                <a class="position-relative" href="{!! route('admin.business-client.shoppost.edit',[$pi['id']]) !!}" >
                                    @if($pi['type'] == 'image')                   
                                        <img style="width:80px; height:80px;" src="{!! $pi['post_item'] !!}" />
                                    @elseif($pi['type'] == 'video')
                                    <i class="fas fa-play-circle" style="font-size: 30px; top: 50%; left: 50%; position: absolute; transform: translate(-50%, -50%); margin-left: 2px;"></i>
                                        <img style="width:80px; height:80px;" src="{!! $pi['video_thumbnail'] !!}" />
                                    @endif
                                </a>
                            </div>
                        @endforeach
                    </div>             
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>
</div>
@endsection

@section('scripts')
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/chocolat.js') !!}"></script>
<script>
function myFunction() {
  /* Get the text field */
  var copyText = document.getElementById("shop_profile_link");
  /* Select the text field */
  copyText.select();
  copyText.setSelectionRange(0, 99999); /*For mobile devices*/
  /* Copy the text inside the text field */
  document.execCommand("copy");
  /* Alert the copied text */
  iziToast.success({
                    title: '',
                    message: 'Text Copied to Clipboard',
                    position: 'topRight',
                    progressBar: false,
                    timeout: 5000,
                });
//   alert("Copied the text: " + copyText.value);
}
</script>
@endsection