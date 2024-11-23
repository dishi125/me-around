@extends('layouts.app')

@section('header-content')
    <h1>
        @if (@$title)
            {{ @$title }}
        @endif
    </h1>
@endsection

@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
    <link rel="stylesheet" href="{!! asset('css/chocolat.css') !!}">
{{--    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>--}}
{{--    <link rel="stylesheet" href="https://kenwheeler.github.io/slick/slick/slick-theme.css"/>--}}
    <link rel="stylesheet" href="{!! asset('plugins/slick_1.8.1/slick.css') !!}">
    <style type="text/css">
        .hidden {
            overflow: hidden;
            display: none;
            visibility: hidden;
        }
        .wrap-modal-slider {
            padding: 0 30px;
            opacity: 0;
            transition: all 0.3s;
        }

        .wrap-modal-slider.open {
            opacity: 1;
        }

        .slick-prev:before, .slick-next:before {
            color: red;
        }

        .select2-selection__choice{
            color: gray !important;
        }
    </style>
@endsection

@section('content')
    <?php
    $title_1 = isset($shop_info) ? $shop_info->title_1 : "";
    $title_2 = isset($shop_info) ? $shop_info->title_2 : "";
    $title_3 = isset($shop_info) ? $shop_info->title_3 : "";
    $title_4 = isset($shop_info) ? $shop_info->title_4 : "";
    $title_5 = isset($shop_info) ? $shop_info->title_5 : "";
    $title_6 = isset($shop_info) ? $shop_info->title_6 : "";
    ?>
    <div class="section-body">
        <div class="row mt-sm-4">
            <div class="col-12 col-md-12 col-lg-5">
                <div class="card profile-widget">
                    <div class="profile-widget-header">

                    </div>
                    <div class="profile-widget-description">
                        <div class="profile-widget-name">
                            {{ $shop->main_name }}
                            <div class="text-muted d-inline font-weight-normal"></div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6 col-6">
                                <div class="form-group">
                                    <label>Current Credits</label><br />
                                    <span> {{ number_format($shop_user->user_credits, 0, '.', ',') }}</span>
                                </div>
                                <div class="form-group">
                                    <label>Last Expiration Date</label><br />
                                    <span>{{ $shop->plan_expire_date }} </span>
                                    <span class="badge badge-secondary">{{ $shop->plan_expire_date_amount }}</span>
                                </div>
                                <div class="form-group">
                                    <label>Expiration Date</label><br />
                                    <span>{{ $shop->plan_expire_date_next }} </span>
                                    <span class="badge badge-secondary">{{ $shop->plan_expire_date_next_amount }}</span>
                                </div>
                            </div>
                            <div class="col-md-6 col-6">
                                <form method="POST" action="{{route('admin.instagram-service.update.shop',[$shop->id])}}" id="instagram_service">
                                    <input type="hidden" name="user_id" value="{{ $shop->user_id }}" />
                                    <h5 class="mb-2">Instagram service</h5>
                                    <div class="form-group mt-1">
                                        <label for="count_day">Day Count</label>
                                        <input id="count_day" value="{{$shop->count_days}}" type="number" name="count_day" value="" class="form-control" />
                                    </div>
                                    <div class="mb-2">
                                        <strong>Expiry Date : </strong> <span class="display-date-detail"> {{\Carbon::now()->addDays($shop->count_days)->format('Y-m-d')}}</span>
                                    </div>
                                    <div class="form-check d-flex">
                                        <input {{$shop->is_regular_service == 1 ? 'checked' : ''}} id="regular_service" type="checkbox" name="regular_service" value="1" class="form-check-input" />
                                        <label class="form-check-label regular_service_label" for="regular_service">
                                            Regular Service
                                        </label>
                                    </div>
                                    @if(!empty($insta_plans_categorywise) && count($insta_plans_categorywise)>0)
                                        @foreach($insta_plans_categorywise as $insta_category)
                                            @if(!empty($insta_category->categoryoption) && count($insta_category->categoryoption)>0)
                                                <div class="form-group m-0 p-0 mt-2">
                                                    <label class="mb-0">{{ $insta_category->title }}</label>
                                                    <select class="form-control insta_plan" name="insta_plan[{{$insta_category->id}}]" id="insta_plan">
                                                        @foreach($insta_category->categoryoption as $insta_plan)
                                                            <option value="{{ $insta_plan->id }}" @if(in_array($insta_plan->id,$subscribed_plans)) selected @endif>{{ $insta_plan->title }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif
                                        @endforeach
                                    @endif
                                    <div class="button mt-3">
                                        <input type="submit" class="btn btn-dark save_supporter_details" value="Save" />
                                    </div>
                                </form>
                            </div>
                        </div>


                        <div class="form-group col-md-6 col-12 ">
                            <label>Supporter Code</label>
                            {!! Form::open([
                                'route' => ['admin.business-client.save.supporter'],
                                'id' => 'savesupporter',
                                'method' => 'post',
                                'enctype' => 'multipart/form-data',
                            ]) !!}
                            @csrf
                            <div class="d-flex align-items-center">
                                <div class="field">
                                    <input type="hidden" name="user_id" value="{{ $shop->user_id }}" />
                                    <input type="text" name="supporter_code" value="{{ $recommended_code }}" />
                                </div>
                                <div class="button ml-4">
                                    <input type="submit" class="btn btn-dark save_supporter_details" value="Save" />
                                </div>
                            </div>
                            {!! Form::close() !!}


                        </div>
                        <div class="form-group col-md-12 col-12 ">
                            <strong>{{ $manager_name }}</strong>
                            @if ($manager_email)
                                - <strong>{{ $manager_email }}</strong>
                            @endif
                        </div>

                        @if (isset($instaData) && !empty($instaData->access_token))
                            <form action="{{ route('admin.disconnect.instagram', [$shop->id]) }}" method="post">
                                @csrf

                                <input type="hidden" value="{{ $instaData->id }}" name="insta_id" />
                                <button class="btn btn-primary btn-sm connect ml-3 mr-1 p-1 pl-2 pr-2 rounded"
                                    href="javascript:void(0);" onclick="">
                                    Disconnect Instagram
                                </button>
                                @if ($instaData->is_valid_token == false || $instaData->is_valid_token == 0)
                                    <span class="font-weight-600" data-toggle="tooltip" data-placement="bottom"
                                        title="User might change password or remove access for sync posts.">
                                        Something disconnected <i class="fas fa-question-circle"></i>
                                    </span>
                                @endif
                            </form>

                            @if (!empty($instaData->social_name))
                                <div class="mr-1 p-3 pl-3 pr-2">
                                    <a href="https://www.instagram.com/{{ $instaData->social_name }}" target="_blank">
                                        <img src="{{ asset('img/instagram.png') }}" alt="instagram" class="pr-1" />
                                        {{ $instaData->social_name }}
                                    </a>
                                </div>
                            @endif
                        @else
                            <a class="btn btn-primary btn-sm connect ml-3 mr-1 p-1 pl-2 pr-2 rounded"
                                href="javascript:void(0);"
                                onclick="connectInstagram('https://www.instagram.com/oauth/authorize?client_id={{ config('app.client_id') }}&redirect_uri={{ route('social-redirect') }}&scope=user_profile,user_media&response_type=code','{{ $shop->id }}');">
                                Connect Instagram
                            </a>
                            <a class="btn btn-primary btn-sm connect ml-3 mr-1 p-1 pl-2 pr-2 rounded" href="javascript:void(0);" onclick="copyTextLink('{{$shopConnectCopyLink}}')"> Instagram connect Link </a>
                        @endif


                        {{-- {{route('social-redirect')}} --}}
                        <div class="gallery gallery-md">
                            <div class="gallery-item" data-image="{!! $shop->best_portfolio_url !!}" data-title="Best Portfolio">
                            </div>
                            <div class="gallery-item" data-image="{!! $shop->business_licence_url !!}" data-title="Business Licence">
                            </div>
                            <div class="gallery-item" data-image="{!! $shop->identification_card_url !!}" data-title="Identification Card">
                            </div>
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
                <div class="card profile-widget">
                    <div class="card-header d-inline">
                        <h4 class="float-left">Price Setting </h4>
                        <a href="javascript:void(0)" onclick="viewShopPriceCategory({!! $shop->id !!},0)"
                            class="btn btn-primary btn-sm float-right rounded">Add Price Category</a>
                    </div>
                    <div class="card-body">
                        <ul class="list-group shopPriceCategoryBlock">
                            @foreach ($shop->shopPriceCategory as $shop_price_cat)
                                <li class="list-group-item" id="list_{!! $shop_price_cat->id !!}">
                                    <span class="name">{!! $shop_price_cat->name !!}</span>

                                    <a href="javascript:void(0)"
                                        onclick="viewShopPrice({!! $shop->id !!},{!! $shop_price_cat->id !!},0)"
                                        class="btn btn-primary btn-sm float-right rounded mr-1">Add Item</a>
                                    <a href="javascript:void(0)"
                                        onclick="deleteShopPrice({!! $shop_price_cat->id !!},'shop_category')"
                                        class="btn btn-danger btn-sm float-right rounded mr-1">Delete</a>
                                    <a href="javascript:void(0)"
                                        onclick="viewShopPriceCategory({!! $shop->id !!},{!! $shop_price_cat->id !!})"
                                        class="btn btn-primary btn-sm float-right rounded mr-1">Edit</a>


                                    @if ($shop_price_cat->shop_items)
                                        <ul class="list-group shopPriceBlock mt-3" id="shop_item_{!! $shop_price_cat->id !!}">
                                            @foreach ($shop_price_cat->shop_items as $shop_price_item)
                                                <li class="list-group-item" id="shop_price_{!! $shop_price_item->id !!}">
                                                    <span class="name">{!! $shop_price_item->name !!}</span><br />
                                                    <span class="price">{!! $shop_price_item->price !!}</span>

                                                    <div class="float-right">
                                                    <span class="display_price_image">
                                                    @if ($shop_price_item->images()->count())
                                                        <?php
                                                            if($shop_price_item->images()->first()->thumb_image){
                                                                $displayImage = $shop_price_item->images()->first()->thumb_image;
                                                            }else{
                                                                $displayImage = $shop_price_item->images()->first()->image_url;
                                                            }
                                                        ?>
                                                        <span class="mr-4 price-image-outer bgcoverimage" style="background-image: url({{$displayImage}});" shop-price-id="{{ $shop_price_item->id }}">
                                                            <img width="30" height="30" src="{{$displayImage}}" />
                                                        </span>
                                                    @endif
                                                    </span>

                                                    @if($shop_price_item->main_price_display==1)
                                                    <span>Main list price</span>
                                                    @endif
                                                    <a href="javascript:void(0)"
                                                    onclick="viewShopPrice({!! $shop->id !!},{!! $shop_price_cat->id !!},{!! $shop_price_item->id !!})"
                                                    class="btn btn-primary btn-sm  rounded mr-1">Edit</a>

                                                    <a href="javascript:void(0)"
                                                        onclick="deleteShopPrice({!! $shop_price_item->id !!},'shop_price')"
                                                        class="btn btn-danger btn-sm  rounded mr-1">Delete</a>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

            </div>
            <div class="col-12 col-md-12 col-lg-7 mt-sm-3 pt-3">
                <div class="card">
                    <div class="card-header d-inline">
                        <div class="row">
                            @if($all_status)
                                <div class="form-group col-md-4 col-4 align-items-center d-flex">
                                    <label class="mb-0">Shop Status</label>
                                    <select class="form-control w-50 ml-4" name="status_change" id="status_change">
                                        @foreach ($all_status as $item)
                                            <option {{($shop->status_id == $item->id) ? 'selected' : '' }}  value="{{$item->id}}">{{$item->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="form-group col-md-2 col-2 align-items-center d-flex">
                            <button type="button" class="btn btn-dark" id="btn_info">Info</button>
                            </div>
                            @if($shopCategory)
                                <div class="form-group col-md-6 col-6 align-items-center d-flex">
                                    <label class="mb-0">Shop Category</label>
                                    <select shop_id="{{$shop->id}}" class="form-control w-50 ml-4" name="category_select" id="category_select">
                                        @foreach ($shopCategory as $cat)
                                            <option {{($shop->category_id == $cat->id) ? 'selected' : '' }}  value="{{$cat->id}}">{{$cat->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {!! Form::open([
                    'route' => ['admin.business-client.update.shop', $shop->id],
                    'id' => 'saveShopDetailForm',
                    'method' => 'post',
                    'enctype' => 'multipart/form-data',
                ]) !!}
                @csrf
                <div class="card">
                    <div class="card-header d-inline">
                        <h4 class="float-left">Shop Details</h4>
                        <span class="float-left font-weight-bold pl-5 pr-2">{{ $userDetail->name ?? '' }}</span>
                        <span class="float-left font-weight-bold">{{ $shop_user->email }}</span>
                        @if(Auth::user()->hasRole("Sub Admin"))
                        @else
                        <span class="float-left font-weight-bold pl-2 pr-2 copy_clipboard">{{ $userDetail->mobile ?? '' }}</span>
                        @endif
                        @if ($shop->status_id == \App\Models\Status::ACTIVE)
                            <span class="badge badge-success float-left">&nbsp;</span>
                        @elseif($shop->status_id == \App\Models\Status::PENDING)
                            <span class="badge float-left" style="background-color: #fff700;">&nbsp;</span>
                        @else
                            <span class="badge badge-secondary float-left">&nbsp;</span>
                        @endif
                        <a href="https://admin.050bizcall.co.kr/" class="btn btn-primary float-left rounded ml-3" target="_blank">050 site</a>
                        <div class="form-group col-md-5 col-5 align-items-center d-flex m-0">
                            <label class="mb-0">MBTI</label>
                            <select class="form-control w-50 ml-2" name="user_mbti" id="user_mbti">
                                @foreach($mbti_options as $mbti_option)
                                <option value="{{ $mbti_option }}" @if($userDetail->mbti == $mbti_option) selected @endif>{{ $mbti_option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <a href="javascript:void(0)" class="btn btn-primary saveShopDetail float-right rounded"
                            id="saveShopDetail">Save</a>
                    </div>
                    <div class="card-body">Signup At: {{ $signup_date }}</div>

                    <div class="card-body">
                        {{ Form::hidden('shop_id', $shop->id, ['id' => 'shop_id']) }}
                        <div class="row">
                            <div class="form-group col-md-6 col-12">
                                <label>Main Name</label>
                                <input type="text" class="form-control" name="main_name" id="main_name"
                                    value="{{ $shop->main_name }}">
                            </div>
                            <div class="form-group col-md-6 col-12">
                                <label>Shop Name</label>
                                <input type="text" class="form-control" name="shop_name" id="shop_name"
                                    value="{{ $shop->shop_name }}">
                            </div>
                            <div class="form-group col-md-6 col-12">
                                <label>Business Licence Number</label>
                                <input type="text" class="form-control" name="business_license_number"
                                    value="{{ $shop->business_license_number }}" readonly>
                            </div>
                            <div class="form-group col-md-6 col-12">
                                <label>Speciality Of ~</label>
                                <input type="text" class="form-control" name="speciality_of" id="speciality_of"
                                    value="{{ $shop->speciality_of }}">
                            </div>
                        </div>
                    </div>
                    <div class="card-header">
                        <h4>Address Detail</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-12 col-12">

                                <div class="row">
                                    <div class="col-6 form-group">
                                        <label>Expose distance</label> <span>Default Expose Distance: {{ ($userDetail->km!=null)?$userDetail->km:$shop->first_plan }}KM</span>
                                        <input type="number" class="form-control" name="expose_distance" value="{{ $shop->expose_distance }}" placeholder="{{ (!empty($userDetail->km))?$userDetail->km:$shop->first_plan }}">
                                    </div>
                                    <div class="col-6 form-group">
                                        <label>Package Plan</label>
                                        <select class="form-control" name="credit_plan" id="credit_plan">
                                            @foreach($all_plans as $all_plan)
                                            <?php $selected_plan = ($userDetail->package_plan_id!=null) ? $userDetail->package_plan_id : $all_plans->first()->package_plan_id; ?>
                                            <option value="{{ $all_plan->package_plan_id }}" @if($all_plan->package_plan_id==$selected_plan) selected @endif>{{ $all_plan->package_plan_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group col-md-12 col-12">
                                <label for="address_address">Address</label>
                                <input type="text" id="address" name="address" class="form-control map-input"
                                    value="{{ $shop->address->address }}">
                                <input type="hidden" name="latitude" id="address-latitude"
                                    value="{{ $shop->address->latitude }}" />
                                <input type="hidden" name="longitude" id="address-longitude"
                                    value="{{ $shop->address->longitude }}" />
                            </div>
                            <div class="form-group col-md-12 col-12">
                                <input type="text" id="address_detail" name="address_detail" class="form-control"
                                    value="{{ $shop->address->address2 }}" placeholder="Address detail">
                            </div>
                            <div class="form-group col-md-12 col-12">
                                <div id="address-map-container" style="width:80%;height:350px; ">
                                    <div style="width: 100%; height: 100%" id="address-map"></div>
                                </div>
                            </div>
                            <div class="form-group col-md-6 col-12">
                                <!-- <label>City</label> -->
                                <input type="hidden" class="form-control" name="city_name" id="address-city"
                                    value="{{ $shop->address->city_name }}">
                            </div>
                            <div class="form-group col-md-6 col-12">
                                <!-- <label>State</label> -->
                                <input type="hidden" class="form-control" name="state_name" id="address-state"
                                    value="{{ $shop->address->state_name }}">
                            </div>
                            <div class="form-group col-md-6 col-12">
                                <!-- <label>Country</label> -->
                                <input type="hidden" class="form-control" name="country_name" id="address-country"
                                    value="{{ $shop->address->country_name }}">
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-12 col-12">
                                <label>Thumbnail Picture</label>
                                {!! Form::file('thumbnail_image', [
                                    'accept' => 'image/jpg,image/png,image/jpeg',
                                    'class' => 'form-control',
                                    'placeholder' => 'thumbnail_image',
                                ]) !!}

                                @if (!empty($shop->thumbnail_image->image))
                                    <img style="width:80px; height:80px;" id="thumbnail_image_src"
                                        src="{!! $shop->thumbnail_image->image !!}" />
                                @endif
                            </div>

                            {{--  <div class="form-group col-md-12 col-12">
                                {!! Form::label('outside_bussiness', 'Outside business?') !!}<br>
                                {{ Form::radio('outside_bussiness', 'yes', $shop->business_link != '' || $shop->booking_link != '' ? true : false, ['id' => 'yes']) }}
                                <label for="yes">Yes</label>
                                {{ Form::radio('outside_bussiness', 'no', $shop->business_link == '' && $shop->booking_link == '' ? true : false, ['id' => 'no']) }}<label
                                    for="no">No</label>
                            </div>  --}}


                            <div class="form-group col-md-12 col-12 business_link_block">
                                <div class="form-group mb-1">
                                    <label class="d-block">How would you like to chat? </label>
                                    {{ Form::radio('chat_option', 0, $shop->chat_option == 0 ? true : false, ['id' => 'chat_option_0']) }}
                                    <label for="chat_option_0">MeAround Chat</label>
                                    {{ Form::radio('chat_option', 1, $shop->chat_option == 1 ? true : false, ['id' => 'chat_option_1']) }}
                                    <label for="chat_option_1">3rd party Chat</label>
                                    {{ Form::radio('chat_option', 2, $shop->chat_option == 2 ? true : false, ['id' => 'chat_option_2']) }}
                                    <label for="chat_option_2">I dont want to use Chat </label>
                                </div>

                                <div class="form-group business_link_field" style="display:{!! $shop->chat_option == 1 ? 'block' : 'none' !!}">
                                    <label >Bussiness Link</label>
                                    <input type="text" class="form-control" name="business_link" id="business_link"
                                        value="{{ $shop->business_link }}">
                                </div>

                                <div class="form-group mb-1 mt-3">
                                    <label class="d-block">Do you have naver book link? </label>
                                    {{ Form::radio('naver_link', 'yes', $shop->booking_link != '' ? true : false, ['id' => 'naver_link_yes']) }}
                                    <label for="naver_link_yes">Yes</label>
                                    {{ Form::radio('naver_link', 'no', $shop->booking_link == '' ? true : false, ['id' => 'naver_link_no']) }}
                                    <label for="naver_link_no">No</label>
                                </div>

                                <div class="form-group naver_link_field" style="display:{!! $shop->booking_link != '' ? 'block' : 'none' !!}">
                                    <label class="mt-2">Booking Link</label>
                                    <input type="text" class="form-control" name="booking_link" id="booking_link"
                                        value="{{ $shop->booking_link }}">
                                </div>
                            </div>
                            <div class="form-group col-md-12 col-12 business_link_block">
                                <label>Shall we make a call button ?</label> <span class="copy_clipboard">{{ $userDetail->mobile }}</span><br />
                                {{ Form::radio('another', 'yes', $shop->another_mobile != '' ? true : false, ['id' => 'another_yes']) }}
                                <label for="another_yes">Yes</label>
                                {{ Form::radio('another', 'no', $shop->another_mobile == '' ? true : false, ['id' => 'another_no']) }}<label
                                    for="another_no">No</label>

                                <div class="business_another_mobile" style="display:{!! $shop->another_mobile != '' ? 'block' : 'none' !!}">
                                    <label>Phone Number </label>
                                    <div class="d-flex align-items-center">
                                        <input type="text" class="form-control" name="another_mobile" id="another_mobile" value="{{ $shop->another_mobile }}" />
                                        <button type="button" id="notification_050" class="btn btn-dark ml-1">Notification for 050 number</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group col-md-12 col-12">
                                <label>Do you want to add button for price list? </label> <br/>
                                {{ Form::radio('show_price', '1', $shop->show_price == '1' ? true : false, ['id' => 'show_price_yes']) }}
                                <label for="show_price_yes">Yes</label>
                                {{ Form::radio('show_price', '0', $shop->show_price == '0' ? true : false, ['id' => 'show_price_no']) }}<label
                                    for="show_price_no">No</label>
                            </div>
                            <div class="form-group col-md-12 col-12">
                                <label>Do you want to add button for map "address"? </label> <br/>
                                {{ Form::radio('show_address', '1', $shop->show_address == '1' ? true : false, ['id' => 'show_address_yes']) }}
                                <label for="show_address_yes">Yes</label>
                                {{ Form::radio('show_address', '0', $shop->show_address == '0' ? true : false, ['id' => 'show_address_no']) }}<label
                                    for="show_address_no">No</label>
                            </div>

                        </div>
                    </div>
                    @if ($shop->sns_link && $shop->sns_type)
                        <div class="card-header">
                            <h4>Social networking service</h4>
                        </div>
                        <div class="card-body">
                            <div class="row ml-0">
                                <div class="col-md-4 col-4">
                                    <a href="{{ $shop->sns_link }}" target="_blank"> <i
                                            class="fab fa-{{ $shop->sns_type }}"></i> {{ ucfirst($shop->sns_type) }} </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                {!! Form::close() !!}
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Interior/Work place photo</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-control float-right rounded">
                            <input type="file" class="upload_shop_images" shop-type="workplace" name="files[]"
                                id="uploadWorkPlaceImages" multiple>
                        </div>
                        <div class="gallery gallery-md pt-4" id="work_place_gallery">
                            @foreach ($shop->workplace_images as $wp)
                                <div style="display:inline-grid;cursor: pointer;" id="image_{!! $wp['id'] !!}">
                                    <div class="gallery-item" data-image="{!! $wp['image'] !!}"
                                        data-title="{!! $shop->main_name !!}"></div>
                                    <a class="deleteImages float-right text-danger pb-2 pl-3" type="workplace"
                                        id="{!! $wp['id'] !!}">
                                        <strong>Delete</strong>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>
            <div class="col-md-6">

                <div class="card">
                    <div class="card-header d-inline">
                        <h4 class="float-left">Main Profile (Selfie)</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-control float-right rounded">
                            <input type="file" class="upload_shop_images" shop-type="main_profile" name="files[]"
                                id="uploadMainProfileImages" multiple>
                        </div>
                        <div class="gallery gallery-md pt-4" id="main_profile_gallery">
                            @foreach ($shop->main_profile_images as $pi)
                                <div style="display:inline-grid;cursor: pointer;" id="image_{!! $pi['id'] !!}">
                                    <div class="gallery-item" data-image="{!! $pi['image'] !!}"
                                        data-title="{!! $shop->main_name !!}"></div>
                                    <a class="deleteImages float-right text-danger pb-2 pl-3" type="main_profile"
                                        id="{!! $pi['id'] !!}">
                                        <strong>Delete</strong>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-inline">
                        <h4 class="float-left">Portfolio ({{ count($shop->shopPostList) }})</h4>
                        <a href="{{ route('admin.business-client.shoppost.create', [$shop->id]) }}"
                            class="btn btn-primary saveShopDetail float-right rounded" id="saveShopDetail">Add
                            Portfolio</a>
                    </div>
                    <div class="card-body">
                        <div class="list">
                            @foreach ($shop->shopPostList as $pi)
                                <div class="item position-relative"
                                    style="width:80px; height:80px; float: left;margin: 5px 5px">
                                    @if ($pi['is_multiple'] == 1)
                                        <i class="fa-clone fas index-2 position-absolute" style="right: 0;"></i>
                                    @endif
                                    <a class="position-relative" href="{!! route('admin.business-client.shoppost.edit', [$pi['id']]) !!}">
                                        @if ($pi['type'] == 'image')
                                            <img style="width:80px; height:80px;" src="{!! $pi['post_item'] !!}" />
                                        @elseif($pi['type'] == 'video')
                                            <i class="fas fa-play-circle"
                                                style="font-size: 30px; top: 50%; left: 50%; position: absolute; transform: translate(-50%, -50%); margin-left: 2px;"></i>
                                            <img style="width:80px; height:80px;" src="{!! $pi['video_thumbnail'] !!}" />
                                        @endif
                                    </a>
                                </div>
                            @endforeach
                        </div>

                        <?php /* <div class="gallery gallery-md">
                    @foreach($shop->shopPostList as $pi)
                        @if($pi['type'] == 'image')
                            <div class="gallery-item" data-image="{!! $pi['post_item'] !!}" data-title="{!! $shop->main_name !!}"></div>
                        @else
                            <div class="gallery-item" data-image="{!! $pi['video_thumbnail'] !!}" data-title="{!! $shop->main_name !!}"></div>
                        @endif
                    @endforeach
                    </div>
                    */
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
    </div>
    <div class="modal fade" id="profileModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    </div>

    <div class="modal fade" id="ShopItemGalleryModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 730px;">
            <div class="modal-content">
                <div class="modal-header">
{{--                    <h5 class="modal-title" id="exampleModalCenterTitle">Shop Items</h5>--}}
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="wrap-modal-slider">
                        <div id="gallery_list">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="cover-spin"></div>
@endsection

<div class="modal fade" id="InfoModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="InfoForm" method="post">
                {{ csrf_field() }}
                <input type="hidden" name="shop_id" value="{{ $shop->id }}">
                <div class="modal-header justify-content-center">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body justify-content-center">
                    <div class="align-items-xl-center mb-3">
                        <div class="row mb-1">
                            <div class="col-md-2">
                                <label>Title</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="title_1" id="title_1" class="form-control" required/>
                            </div>
                        </div>
                        <div class="row mb-1">
                            <div class="col-md-2">
                                <label>Title</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="title_2" id="title_2" class="form-control" required/>
                            </div>
                        </div>
                        <div class="row mb-1">
                            <div class="col-md-2">
                                <label>Title</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="title_3" id="title_3" class="form-control" required/>
                            </div>
                        </div>
                        <div class="row mb-1">
                            <div class="col-md-2">
                                <label>Title</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="title_4" id="title_4" class="form-control" required/>
                            </div>
                        </div>
                        <div class="row mb-1">
                            <div class="col-md-2">
                                <label>Title</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="title_5" id="title_5" class="form-control" required/>
                            </div>
                        </div>
                        <div class="row mb-1">
                            <div class="col-md-2">
                                <label>Title</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="title_6" id="title_6" class="form-control" required/>
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
</div>

@section('scripts')
    <script>
        var updateShopDetail = "{{ route('admin.business-client.update.shop') }}";
        var uploadImages = "{{ route('admin.business-client.upload.shop.images') }}";
        var deleteImages = "{{ route('admin.business-client.delete.shop.images') }}";
        var allShopTable = activeShopTable = inactiveShopTable = "";
        var csrfToken = csrfToken;
        var profileModal = $("#profileModal");
        /*$(window).on( "load",function() {
            setTimeout(function(){
                $('#insta_plan').select2({
                    width: '100%',
                    multiple: true,
                    placeholder: "Instagram Plans",
                });
            },500);
        });*/
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script src="{!! asset('js/pages/business-client/shop.js') !!}"></script>
    <script src="{!! asset('js/chocolat.js') !!}"></script>
    <script type="text/javascript"
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDlfhV6gvSJp_TvqudE0z9mV3bBlexZo3M&&radius=100&&libraries=places&callback=initialize"
        async defer></script>
    <script src="{!! asset('js/mapInput.js') !!}"></script>
{{--    <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.js"></script>--}}
{{--    <script src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>--}}
    <script src="{!! asset('plugins/slick_1.8.1/slick.js') !!}"></script>
    <script>

        function dateToYMD(date) {
            var d = date.getDate();
            var m = date.getMonth() + 1; //Month from 0 to 11
            var y = date.getFullYear();
            return '' + y + '-' + (m<=9 ? '0' + m : m) + '-' + (d <= 9 ? '0' + d : d);
        }

        Date.prototype.addDays = function(days) {
            var date = new Date(this.valueOf());
            date.setDate(date.getDate() + days);
            return date;
        }

        $(document).on('keyup','input[name="count_day"]',function(){
            let day = parseInt($(this).val());
            var date = new Date();
            if(!day){
                day = 0;
            }

            let objectDate = date.addDays(day);
            let displayDate = dateToYMD(objectDate);

            $('.display-date-detail').text(displayDate);
        });

        $(document).on('submit', "#instagram_service", function(event) {
            event.preventDefault();
            $('label.error').remove();
            var formData = new FormData(this);

            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                contentType: false,
                processData: false,
                data: formData,
                beforeSend: function() {
                    $('.cover-spin').show();
                },
                success: function(response) {
                    $('.cover-spin').hide();
                    if (response.success == true) {
                        iziToast.success({
                            title: '',
                            message: response.message,
                            position: 'topRight',
                            progressBar: false,
                            timeout: 1000,
                        });

                    } else {
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
                error: function(response, status) {
                    $('.cover-spin').hide();
                }
            });
        });

        //$(window).on('load', function () {
        $(document).ready(function () {
            if($('input[name="expose_distance"]').val() == $('input[name="expose_distance"]').attr('placeholder')){
                $('input[name="expose_distance"]').css("color","#495057");
            }else{
                $('input[name="expose_distance"]').css("color","#ff1cb1");
            }
        });

        $('input[name="expose_distance"]').on('keyup',function(){
            if(this.value == $(this).attr('placeholder')){
              $(this).css("color","#495057");
            }else{
                $(this).css("color","#ff1cb1");
            }
        });

        $(document).on('change','select[name="status_change"]',function(){
            var status = $(this).val();
            $.ajax({
                url: "{{route('admin.business-client.shop-status-update',[$shop->id])}}",
                type: "POST",
                data: {"status":status},
                beforeSend: function() {
                    $('.cover-spin').show();
                },
                success: function(response) {
                    $('.cover-spin').hide();
                    showToastMessage(response.message,response.success);
                },
                error: function(response, status) {
                    $('.cover-spin').hide();
                }
            });

            //
        });

        $(document).on('keydown','select[name="status_change"]', function(e){
            if(e.keyCode === 38 || e.keyCode === 40 || e.keyCode === 39 || e.keyCode === 37) {
                e.preventDefault();
                return false;
            }
        });

        $(document).on('change','input[name="naver_link"]',function(){
            if($(this).val() == 'yes'){
                $('div.naver_link_field').show();
            }else{
                $('div.naver_link_field').hide();
            }
        });
        $(document).on('change','input[name="chat_option"]',function(){
            if($(this).val() == 1){
                $('div.business_link_field').show();
            }else{
                $('div.business_link_field').hide();
            }
        });

        $(document).on('change', 'select[name="category_select"]', function () {
            var categoryID = $(this).val();
            var shop_id = $(this).attr('shop_id');
            $.ajax({
                url: baseUrl + "/admin/update/user/shop/category",
                method: 'POST',
                data: {
                    _token: csrfToken,
                    category: categoryID,
                    shop_id: shop_id,
                },
                beforeSend: function () {
                    $('.cover-spin').show();
                },
                success: function (response) {
                    $('.cover-spin').hide();
                    if (response.success == true) {
                        iziToast.success({
                            title: '',
                            message: "Category Updated successfully.",
                            position: 'topRight',
                            progressBar: false,
                            timeout: 1000,
                        });

                    } else {
                        iziToast.error({
                            title: '',
                            message: 'Category has not been updated successfully.',
                            position: 'topRight',
                            progressBar: false,
                            timeout: 1500,
                        });
                    }
                }
            });
        });

        $(document).on('submit', "#savesupporter", function(event) {
            event.preventDefault();
            $('label.error').remove();
            var formData = new FormData(this);

            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                contentType: false,
                processData: false,
                data: formData,
                beforeSend: function() {
                    $('.cover-spin').show();
                },
                success: function(response) {
                    $('.cover-spin').hide();
                    if (response.success == true) {
                        iziToast.success({
                            title: '',
                            message: response.message,
                            position: 'topRight',
                            progressBar: false,
                            timeout: 1000,
                        });

                    } else {
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
                error: function(response, status) {
                    $('.cover-spin').hide();
                    if (response.responseJSON.success === false) {
                        var errors = response.responseJSON.errors;

                        $.each(errors, function(key, val) {
                            console.log(val)
                            var errorHtml = '<label class="error">' + val.join("<br />") +
                                '</label>';
                            $('#' + key.replaceAll(".", "_")).parent().append(errorHtml);
                        });
                    }
                }
            });
        });

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

        $(document).on('click', '.bgcoverimage', function (){
            var id = $(this).attr('shop-price-id');
            $.ajax({
                url: "{{ route('admin.business-client.get_shop_price') }}",
                method: 'POST',
                data: {
                    '_token': csrfToken,
                    'shopPriceId': id,
                },
                success: function (res) {
                    if(res.status == 1){
                        $(res.data).each(function (index, val){
                            // console.log(res.data);
                            if(val.thumb_image==""){
                                $("#gallery_list").append(`<div><img src="${val.image_url}" alt=""  ></div>`);
                            }
                            else{
                                $("#gallery_list").append(`<div><video poster="${val.thumb_image}" controls height="500" width="500"><source src="${val.image_url}"></video></div>`);
                            }
                        })
                        $("#ShopItemGalleryModal").modal('show');
                        // $('#gallery_list').slick();

                       // $('.wrap-modal-slider').addClass('open');
                    }
                }
            });
        })

        $( "#ShopItemGalleryModal" ).on('shown.shown.bs.modal', function(){
            $('#gallery_list').slick({
                draggable: true,
                accessibility: false,
                centerMode: true,
                variableWidth: true,
                slidesToShow: 1,
                arrows: true,
                dots: true,
                swipeToSlide: true,
                infinite: false,
                transformEnabled:true
            });
            $('.wrap-modal-slider').addClass('open');
        });

        $('#ShopItemGalleryModal').on('hidden.bs.modal', function () {
            $('#gallery_list').slick('destroy');
            // $('#gallery_list').slick('unslick');
            $("#gallery_list").html("");
            $('.wrap-modal-slider').removeClass('open');
        });

        $(document).on('click','#notification_050', function (){
            var phone_number = $("#another_mobile").val();

            if (phone_number!=""){
                $.ajax({
                    url: baseUrl + "/admin/send/notification/050_number",
                    method: 'POST',
                    data: {
                        '_token': $('meta[name="csrf-token"]').attr('content'),
                        'userId': "{{ $shop->user_id }}",
                        'phone_number': phone_number,
                    },
                    success: function (data) {
                        if(data.response==true){
                            showToastMessage(data.message,true);
                        }
                        else {
                            showToastMessage("Something went wrong!!",false);
                        }
                    }
                });
            }
            else {
                showToastMessage("Please enter phone number",false);
            }
        })

        $(document).on('click','#btn_info',function (){
            $("#InfoForm").find("#title_1").val("{{ $title_1 }}");
            $("#InfoForm").find("#title_2").val("{{ $title_2 }}");
            $("#InfoForm").find("#title_3").val("{{ $title_3 }}");
            $("#InfoForm").find("#title_4").val("{{ $title_4 }}");
            $("#InfoForm").find("#title_5").val("{{ $title_5 }}");
            $("#InfoForm").find("#title_6").val("{{ $title_6 }}");
            $("#InfoModal").modal('show');
        })

        /*$('#InfoModal').on('hidden.bs.modal', function () {
            $("#InfoForm")[0].reset();
        })*/

        $(document).on('submit', '#InfoForm', function (e){
            e.preventDefault();
            var formData = new FormData($("#InfoForm")[0]);

            $.ajax({
                url: "{{ url('admin/shop/add-info') }}",
                processData: false,
                contentType: false,
                type: 'POST',
                data: formData,
                success:function(response){
                    if(response.success == true){
                        $("#InfoModal").modal('hide');
                        showToastMessage('Info updated successfully.',true);
                        location.reload();
                    }
                    else {
                        showToastMessage('Something went wrong!!',false);
                    }
                },
                error: function(response) {
                    showToastMessage('Something went wrong!!',false);
                },
            });
        })
    </script>
@endsection
