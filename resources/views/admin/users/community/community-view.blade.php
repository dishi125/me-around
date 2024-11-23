@extends('layouts.app')

@section('styles')
    <style>
        .tab-content {
            margin-top: -1px;
        }

        .order-button {
            text-align: center;
            vertical-align: middle;
            font-weight: 600;
            font-size: 12px;
            box-shadow: 0 2px 6px #43425d;
            background-color: #43425D;
            border-color: #43425D;
            color: #fff;
            border-radius: 20px;
            line-height: 35px;
            padding: 2px 3px;
        }

        .order-button > div {
            padding: 0 10px 0 10px;
        }

        .order-button > div.active {
            background-color: #fff;
            border-radius: 20px;
            color: #000;
        }

        .category-block > select.form-control, .category-block > select.form-control:focus {
            background-color: #43425D;
            color: #fff;
            border-radius: 20px;
            line-height: 35px;
        }

        .community-slider-outer {
            margin: 30px 0 0 0;
        }

        .community-slider > img {
            height: 50px;
            width: 500px;
        }

        .slide-background {
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }

        .detail-slider .slick-list .slick-track .slick-slide {
            margin-right: 15px;
        }

        .detail-slider .slick-list .slick-track{ margin-left: 0; }

        .slick-slide {
            height: 140px;
        }

        .slick-slide img {
            height: 150px;
            opacity: 0;
        }

        .right-content .data {
            align-items: center;
            justify-content: center;
            padding-top: 4px;
        }

        .community-data .item-outer {
            border-bottom: solid 1px #00000030;
        }

        .community-data .item {
            vertical-align: middle;
            align-items: center;
            padding: 15px 0;
        }

        .community-data .item .title {
            font-size: 18px;
            font-weight: 600;
        }

        .community-data .item .category {
            color: #ea4c89;
        }

        .community-data .item .left-content {
            margin-right: 20px;
        }

        .community-data .item .left-content .counter-img {
            background-position: center;
            background-size: contain;
            background-repeat: no-repeat;
            height: 32px;
            width: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            padding-top: 10px;
        }

        .community-data .item .left-content .counter {
            height: 30px;
            width: 30px;
            background-color: #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .detail-slider img {
            width: 100%;
        }

        .community-count-detail {
            text-transform: uppercase;
            justify-content: space-between;
            max-width: 60%;
        }

        .detail-outer .community-count-detail{
            margin: auto;
            max-width: 90%;
        }
        .like-outer {
            margin: 10px 0;
            border-top: solid 1px #0000004a;
            border-bottom: solid 1px #0000004a;
        }
        .like-outer .community-count-detail{
            margin: auto;
            padding: 12px 0;
        }

        .community-comments.review-comments{
            max-width: 100%;
            max-height: 500px;
            overflow: auto;
        }
      /*  .community-comments.review-comments::-webkit-scrollbar {
            display: none;
        }
*/
        .community-comments.review-comments::-webkit-scrollbar {
            width: 8px;
        }

        /* Track */
        .community-comments.review-comments::-webkit-scrollbar-track {
            box-shadow: inset 0 0 5px grey;
            border-radius: 10px;
        }

        /* Handle */
        .community-comments.review-comments::-webkit-scrollbar-thumb {
            background: #43425d;
            border-radius: 10px;
        }

        /* Handle on hover */
        .community-comments.review-comments::-webkit-scrollbar-thumb:hover {
            background: #383765;
        }

        .community-comments.review-comments .review-comments-detail{
            padding: 0;
            align-items: flex-start;
        }
        .community-comments.review-comments ul.reply-detail{
            padding: 8px;
        }
        .community-comments.review-comments .review-comments-detail .review-comments-text .name span{
            font-size: 10px;
        }

        .community-comments.review-comments .review-comments-detail .review-comments-text .user-comment{
            line-height: 1;
        }
        .community-comments.review-comments .review-comments-detail .review-comments-text{
            padding-top:0;
        }

        .community-comments.review-comments .user-img, .community .user-img{
            height: 40px;
            width: 40px;
        }

        .community-comments.review-comments > ul > li{
            padding: 0 0 8px;
            margin: 0 0 8px;
        }

        .post-comment .form-control{
            border-color: #6c757d;
        }

        .submit-comment{
            background-color: transparent;
            outline: none !important;
        }
    </style>
    <link rel="stylesheet" href="<?php use App\Models\Community;echo asset('plugins/slick/slick.css'); ?>">
@endsection

@section('header-content')
    <h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection


@section('content')

    <div class="cover-spin" style=""></div>
    <div class="form-group col-2">
        {!! Form::label('country', __(Lang::get('forms.association.country'))); !!}
        {!!Form::select('country', $countries, 'KR' , ['class' => 'form-control','placeholder' => __(Lang::get('forms.association.country'))])!!}
    </div>
    <ul class="nav nav-tabs" id="postTab" role="tablist">
        @foreach($community_tabs as $key => $tab)
            <li class="nav-item">
                <a tab_id="{{$tab['id']}}" tab_type="{{$tab['type']}}"
                   class="community-tab nav-link {{($key == 0) ? 'active show' : ''}}" id="{{$tab['name']}}-tab"
                   data-toggle="tab"
                   href="#{{$tab['id']}}" role="tab"
                   aria-selected="false">{{$tab['name']}}</a>
            </li>
        @endforeach
    </ul>

    <div class="card tab-content pl-4">
        <div class="row" style="min-height: 350px;">
            <div class="col-6 mb-5">

                <div class="com-content pt-4 d-flex">
                    <div class="order-button d-inline-flex mr-5">
                        <div class="orderBtn pointer active" ordervalue="popular">Popular</div>
                        <div class="orderBtn pointer" ordervalue="recent">Recent</div>
                    </div>
                    <div class="category-block">
                        <select class="form-control" name="category">
                            <option value="">Select</option>
                        </select>
                    </div>
                </div>
                <div id="postContent"></div>
            </div>


            <div class="col-6" id="postDetailContent">

            </div>
        </div>
    </div>
@endsection


@section('scripts')
    <script>
        var ajaxURL = "{{ route('admin.load.user.community',[$id]) }}";
        var csrfToken = "{{csrf_token()}}";
    </script>
    <script src="<?php echo asset('plugins/slick/slick.js'); ?>"></script>
    <script src="{!! asset('js/pages/users/view-community.js') !!}"></script>
@endsection
