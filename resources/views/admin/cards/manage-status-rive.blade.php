@extends('layouts.app')
@section('header-content')
    <h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('styles')
    <style>

    </style>
@endsection

<?php
$statusArray = [
  //  App\Models\UserCards::NORMAL_STATUS,
    App\Models\UserCards::HAPPY_STATUS,
    App\Models\UserCards::SAD_STATUS,
    App\Models\UserCards::DEAD_STATUS
];
?>

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card profile-widget">
                <div class="profile-widget-description">
                    {!! Form::open(['route' => ['admin.cards.level.rive.update', $card], 'id' =>"cardsStatusUpdateForm", 'method' => 'post', 'enctype' => 'multipart/form-data']) !!}

                    <div class="card-body">
                        <div class="common-form">
                            @foreach($cardLevel as $key => $level)
                                <div class="row">
                                    <h5 class="border-bottom border-top mt-2 p-2 w-100">
                                        {{$level->level_name}}
                                    </h5>
                                    @foreach($statusArray as $statusKey => $status)
                                        @php
                                            $levelDetailData = isset($statusRives) ? $statusRives->where('card_level_id',$level->id)->where('card_level_status',$status)->first() : '';
                                            $levelDetail = (!empty($levelDetailData)) ? $levelDetailData : new App\Models\CardStatusRives;

                                            $levelDetailDataThumb = isset($statusThumbs) ? $statusThumbs->where('card_level_id',$level->id)->where('card_level_status',$status)->first() : '';
                                            $levelDetailThumb = (!empty($levelDetailDataThumb)) ? $levelDetailDataThumb : new App\Models\CardStatusThumbnails;
                                        @endphp
                                        <div class="col-3">
                                            {!! Form::hidden("detail[$key][$statusKey][level_id]",$level->id) !!}
                                            {!! Form::hidden("detail[$key][$statusKey][status]",$status) !!}
                                            <div class="form-group">
                                                {!! Form::label("detail[$key][$statusKey][file]", __(Lang::get('forms.cards.'.$status))." (riv)"); !!}
                                                {!! Form::file("detail[$key][$statusKey][file]", ['class' => 'form-control']); !!}
                                                @if($levelDetail->character_riv_url)
                                                    <div class="ml-auto mt-2">
                                                        <strong>Uploaded File : </strong>
                                                        <a href="{{$levelDetail->character_riv_url}}"
                                                           target="_blank">{!! basename($levelDetail->character_riv_url) !!}</a>
                                                           <a href='javascript:void(0);' onClick='viewCard({{$card}},`{{$levelDetail->character_riv_url}}`,`{{$level->id}}`)' class='viewCardBtn btn btn-primary ml-2'><i class='fas fa-eye'></i></a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="col-3">
{{--                                            {!! Form::hidden("detail[$key][$statusKey][level_id]",$level->id) !!}--}}
{{--                                            {!! Form::hidden("detail[$key][$statusKey][status]",$status) !!}--}}
                                            <div class="form-group">
                                                {!! Form::label("detail[$key][$statusKey][thumb_file]", __(Lang::get('forms.cards.'.$status))." (thumbnail)"); !!}
                                                {!! Form::file("detail[$key][$statusKey][thumb_file]", ['class' => 'form-control']); !!}
                                                @if($levelDetailThumb->character_thumb_url)
                                                    <div class="ml-auto mt-2">
                                                        <strong>Uploaded File : </strong>
                                                        <a href="{{$levelDetailThumb->character_thumb_url}}"
                                                           target="_blank">{!! basename($levelDetailThumb->character_thumb_url) !!}</a>
                                                        <a href='javascript:void(0);' onClick='viewCard({{$card}},`{{$levelDetailThumb->character_thumb_url}}`,`{{$level->id}}`)' class='viewCardBtn btn btn-primary ml-2'><i class='fas fa-eye'></i></a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit"
                                class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                        <a href="{{ route('admin.cards.index')}}"
                           class="btn btn-default">{{ __(Lang::get('general.cancel')) }}</a>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
    <div class="cover-spin"></div>
    <div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>

@endsection
@section('page-script')
<script src="{!! asset('plugins/rive/rive.min.js') !!}"></script>
    <script>
        var csrfToken = "{{csrf_token()}}";

        $(document).on("submit", "#cardsStatusUpdateForm", function (e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                contentType: false,
                processData: false,
                data: formData,
                beforeSend: function () {
                    $(".cover-spin").show();
                },
                success: function (response) {
                    $('.cover-spin').hide();
                    showToastMessage(response.message, response.success);

                    if (response.redirect) {
                        setTimeout(function () {
                            window.location.href = response.redirect;
                        }, 1000);
                    }

                },
                error: function (response, status) {
                    console.log(response);
                    $('.cover-spin').hide();
                    showToastMessage("Error in update status rive", false)
                }
            });
        });
    </script>
@endsection
