@extends('layouts.app')
@section('styles')
<link rel="stylesheet" href="{!! asset('css/custom.css') !!}">
<style>
    .removeIcon{
        color: #ff0000;
    }
</style>
@endsection
@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')
<?php
$data = (object)array();
$disabled = false;
if (!empty($card)) {
    $data = $card;
    $disabled = true;
}
$id = !empty($data->id) ? $data->id : '' ;
$card_name = !empty($data->card_name) ? $data->card_name : '' ;
$usd_price = !empty($data->usd_price) ? $data->usd_price : '' ;
$japanese_price = !empty($data->japanese_yen_price) ? $data->japanese_yen_price : '' ;
$chinese_price = !empty($data->chinese_yuan_price) ? $data->chinese_yuan_price : '' ;
$korean_price = !empty($data->korean_won_price) ? $data->korean_won_price : '' ;
$default_card_id = !empty($data->default_card_id) ? $data->default_card_id : '' ;
$feeding_rive = !empty($data->feeding_rive) ? $data->feeding_rive_url : '' ;
$background_rive = !empty($data->background_rive) ? $data->background_rive_url : '' ;
$background_thumbnail = !empty($data->background_thumbnail) ? $data->background_thumbnail_url : '' ;
$character_rive = !empty($data->character_rive) ? $data->character_rive_url : '' ;
$character_thumbnail = !empty($data->character_thumbnail) ? $data->character_thumbnail_url : '' ;
$download_file = !empty($data->download_file) ? $data->download_file_url : '' ;
$required_love_in_days = !empty($data->required_love_in_days) ? $data->required_love_in_days : 1 ;
$love_amount = !empty($first_card_level->range) ? $first_card_level->range : 0 ;
$order = !empty($data->order) ? $data->order : 0 ;
//application/octet-stream
$accept = '.mp4, .riv, .rive, .jpg, .png, .jpeg';
$accept = 'image/*, video/*';
$accept = '';
?>
<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-12">
            <div class="card profile-widget">
                <div class="profile-widget-description">
                    <div class="">
                        @if ( isset($card))
                        {!! Form::open(['route' => ['admin.cards.update', $id], 'id' =>"cardsUpdateForm", 'method' => 'post', 'enctype' => 'multipart/form-data']) !!}
                        @else
                        {!! Form::open(['route' => 'admin.cards.store', 'id' =>"cardsForm", 'enctype' => 'multipart/form-data']) !!}
                        @endif
                        @csrf
                        <div class="card-body">

                                <div class="common-form">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                {!! Form::label('card_id', __(Lang::get('forms.cards.card_id'))); !!}
                                                {!! Form::select('card_id', $cards_tabs, $default_card_id,  ['class' => 'form-control','disabled' => $isDefaultCard], $disabledOptions ?? []); !!}
                                                @error('card_id')
                                                <div class="invalid-feedback">
                                                    {{ $errors->get('card_id') }}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                {!! Form::label('order', __(Lang::get('forms.cards.order'))); !!}
                                                {!! Form::text('order', $order, ['class' => 'form-control numeric', 'placeholder' => __(Lang::get('forms.cards.order')) ]); !!}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <div class="repeat-form">
                                <h5 class="border-bottom border-top mt-2 p-2">{{$first_card_level->level_name}}</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('card_name', __(Lang::get('forms.cards.card_name'))); !!}
                                            {!! Form::text('card_name', $card_name, ['readonly' => $isDefaultCard, 'class' => 'form-control', 'placeholder' => __(Lang::get('forms.cards.card_name')) ]); !!}
                                            @error('card_name')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('card_name') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('background_rive', __(Lang::get('forms.cards.background_rive'))); !!}
                                            {!! Form::file('background_rive', ['class' => 'form-control','accept' => $accept]); !!}
                                            @if($background_rive)
                                            <div class="ml-auto mt-2">
                                                <strong>Uploaded File : </strong>
                                                <a href="{{$background_rive}}" target="_blank">{!! basename($background_rive) !!}</a>
                                                <span><i remove-key="background_rive" remove_id="1" class="removeImageIcon pointer removeIcon fa fa-times-circle fa-2x ml-2" style="vertical-align: middle;"></i></span>
                                            </div>
                                            @endif
                                            @error('background_rive')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('background_rive') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('background_thumbnail', __(Lang::get('forms.cards.background_thumbnail'))); !!}
                                            {!! Form::file('background_thumbnail', ['class' => 'form-control','accept' => 'image/*']); !!}
                                            @if($background_thumbnail)
                                            <div class="ml-auto mt-2">
                                                <strong>Uploaded File : </strong>
                                                <a href="{{$background_thumbnail}}" target="_blank">{!! basename($background_thumbnail) !!}</a>
                                                <span><i remove-key="background_thumbnail" remove_id="1" class="removeImageIcon pointer removeIcon fa fa-times-circle fa-2x ml-2" style="vertical-align: middle;"></i></span>
                                            </div>
                                            @endif
                                            @error('background_thumbnail')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('background_thumbnail') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('character_rive', __(Lang::get('forms.cards.character_rive'))); !!}
                                            {!! Form::file('character_rive', ['class' => 'form-control','accept' => $accept]); !!}
                                            @if($character_rive)
                                                <div class="ml-auto mt-2">
                                                    <strong>Uploaded File : </strong>
                                                    <a href="{{$character_rive}}" target="_blank">{!! basename($character_rive) !!}</a>
                                                    <span><i remove-key="character_rive" remove_id="1" class="removeImageIcon pointer removeIcon fa fa-times-circle fa-2x ml-2" style="vertical-align: middle;"></i></span>
                                                    <a href='javascript:void(0);' onClick='viewCard({{$id}},`{{$character_rive}}`,`1`)' class='viewCardBtn btn btn-primary ml-2'><i class='fas fa-eye'></i></a>
                                                </div>
                                            @endif
                                            @error('character_rive')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('character_rive') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('character_thumbnail', __(Lang::get('forms.cards.character_thumbnail'))); !!}
                                            {!! Form::file('character_thumbnail', ['class' => 'form-control','accept' => 'image/png']); !!}
                                            @if($character_thumbnail)
                                                <div class="ml-auto mt-2">
                                                    <strong>Uploaded File : </strong>
                                                    <a href="{{$character_thumbnail}}" target="_blank">{!! basename($character_thumbnail) !!}</a>
                                                    <span><i remove-key="character_thumbnail" remove_id="1" class="removeImageIcon pointer removeIcon fa fa-times-circle fa-2x ml-2" style="vertical-align: middle;"></i></span>
                                                    <a href='javascript:void(0);' onClick='viewCard({{$id}},`{{$character_thumbnail}}`,`1`)' class='viewCardBtn btn btn-primary ml-2'><i class='fas fa-eye'></i></a>
                                                </div>
                                            @endif
                                            @error('character_thumbnail')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('character_thumbnail') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            {!! Form::label('usd_price', __(Lang::get('forms.cards.usd_price'))); !!}
                                            {!! Form::text('usd_price', $usd_price, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.cards.usd_price')) ]); !!}
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            {!! Form::label('japanese_price', __(Lang::get('forms.cards.japanese_price'))); !!}
                                            {!! Form::text('japanese_price', $japanese_price, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.cards.japanese_price')) ]); !!}
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            {!! Form::label('chinese_price', __(Lang::get('forms.cards.chinese_price'))); !!}
                                            {!! Form::text('chinese_price', $chinese_price, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.cards.chinese_price')) ]); !!}
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            {!! Form::label('korean_price', __(Lang::get('forms.cards.korean_price'))); !!}
                                            {!! Form::text('korean_price', $korean_price, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.cards.korean_price')) ]); !!}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('download_file', __(Lang::get('forms.cards.download_file'))); !!}
                                            {!! Form::file('download_file', ['class' => 'form-control','accept' => $accept]); !!}
                                            @if($download_file)
                                                <div class="ml-auto mt-2">
                                                    <strong>Uploaded File : </strong>
                                                    <a href="{{$download_file}}" target="_blank">{!! basename($download_file) !!}</a>
                                                    <span><i remove-key="download_file" remove_id="1" class="removeImageIcon pointer removeIcon fa fa-times-circle fa-2x ml-2" style="vertical-align: middle;"></i></span>
                                                </div>
                                            @endif
                                            @error('download_file')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('download_file') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('feeding_rive', __(Lang::get('forms.cards.feeding'))); !!}
                                            {!! Form::file('feeding_rive', ['class' => 'form-control','accept' => $accept]); !!}
                                            @if($feeding_rive)
                                                <div class="ml-auto mt-2">
                                                    <strong>Uploaded File : </strong>
                                                    <a href="{{$feeding_rive}}" target="_blank">{!! basename($feeding_rive) !!}</a>
                                                    <span><i remove-key="feeding_rive" remove_id="1" class="removeImageIcon pointer removeIcon fa fa-times-circle fa-2x ml-2" style="vertical-align: middle;"></i></span>
                                                    <a href='javascript:void(0);' onClick='viewCard({{$id}},`{{$feeding_rive}}`,`1`)' class='viewCardBtn btn btn-primary ml-2'><i class='fas fa-eye'></i></a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            {!! Form::label('required_love_in_days', __(Lang::get('forms.cards.required_love_in_days'))); !!}
                                            {!! Form::number('required_love_in_days', $required_love_in_days, ['min'=>1, 'class' => 'form-control', 'placeholder' => __(Lang::get('forms.cards.required_love_in_days')) ]); !!}
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            {!! Form::label('love_amount', __(Lang::get('forms.cards.love_amount'))); !!}
                                            {!! Form::text('love_amount', $love_amount, ['readonly'=>true, 'class' => 'form-control', 'placeholder' => __(Lang::get('forms.cards.love_amount')) ]); !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if($other_level)
                                @foreach($other_level as $key => $level)
                                    <?php
                                        $levelDetailData = isset($card) ? $card->cardLevels()->firstWhere('card_level',$level->id) : '';
                                        $levelDetail = (!empty($levelDetailData)) ? $levelDetailData : new App\Models\CardLevelDetail;

                                    ?>
                                    <div class="repeat-form">
                                        <h5 class="border-bottom border-top mt-2 p-2">{{$level->level_name}}</h5>
                                        {!! Form::hidden("level[$key][card_level]",$level->id) !!}
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    {!! Form::label("level[$key][card_name]", __(Lang::get('forms.cards.card_name'))); !!}
                                                    {!! Form::text("level[$key][card_name]", $levelDetail->card_name, ['readonly' => $isDefaultCard, 'class' => 'form-control required', 'placeholder' => __(Lang::get('forms.cards.card_name')) ]); !!}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    {!! Form::label("level[$key][background_rive]", __(Lang::get('forms.cards.background_rive'))); !!}
                                                    {!! Form::file("level[$key][background_rive]", ['class' => 'form-control','accept' => $accept]); !!}
                                                    @if($levelDetail->background_rive_url)
                                                        <div class="ml-auto mt-2">
                                                            <strong>Uploaded File : </strong>
                                                            <a href="{{$levelDetail->background_rive_url}}" target="_blank">{!! basename($levelDetail->background_rive_url) !!}</a>
                                                            <span><i remove-key="background_rive" remove_id="{{$level->id}}" class="removeImageIcon pointer removeIcon fa fa-times-circle fa-2x ml-2" style="vertical-align: middle;"></i></span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    {!! Form::label("level[$key][background_thumbnail]", __(Lang::get('forms.cards.background_thumbnail'))); !!}
                                                    {!! Form::file("level[$key][background_thumbnail]", ['class' => 'form-control','accept' => 'image/*']); !!}
                                                    @if($levelDetail->background_thumbnail_url)
                                                        <div class="ml-auto mt-2">
                                                            <strong>Uploaded File : </strong>
                                                            <a href="{{$levelDetail->background_thumbnail_url}}" target="_blank">{!! basename($levelDetail->background_thumbnail_url) !!}</a>
                                                            <span><i remove-key="background_thumbnail" remove_id="{{$level->id}}" class="removeImageIcon pointer removeIcon fa fa-times-circle fa-2x ml-2" style="vertical-align: middle;"></i></span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    {!! Form::label("level[$key][character_rive]", __(Lang::get('forms.cards.character_rive'))); !!}
                                                    {!! Form::file("level[$key][character_rive]", ['class' => 'form-control','accept' => $accept]); !!}
                                                    @if($levelDetail->character_rive_url)
                                                        <div class="ml-auto mt-2">
                                                            <strong>Uploaded File : </strong>
                                                            <a href="{{$levelDetail->character_rive_url}}" target="_blank">{!! basename($levelDetail->character_rive_url) !!}</a>
                                                            <span><i remove-key="character_rive" remove_id="{{$level->id}}" class="removeImageIcon pointer removeIcon fa fa-times-circle fa-2x ml-2" style="vertical-align: middle;"></i></span>
                                                            <a href='javascript:void(0);' onClick='viewCard({{$id}},`{{$levelDetail->character_rive_url}}`,`{{$level->id}}`)' class='viewCardBtn btn btn-primary ml-2'><i class='fas fa-eye'></i></a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    {!! Form::label("level[$key][character_thumbnail]", __(Lang::get('forms.cards.character_thumbnail'))); !!}
                                                    {!! Form::file("level[$key][character_thumbnail]", ['class' => 'form-control','accept' => 'image/png']); !!}
                                                    @if($levelDetail->character_thumbnail_url)
                                                        <div class="ml-auto mt-2">
                                                            <strong>Uploaded File : </strong>
                                                            <a href="{{$levelDetail->character_thumbnail_url}}" target="_blank">{!! basename($levelDetail->character_thumbnail_url) !!}</a>
                                                            <span><i remove-key="character_thumbnail" remove_id="{{$level->id}}" class="removeImageIcon pointer removeIcon fa fa-times-circle fa-2x ml-2" style="vertical-align: middle;"></i></span>
                                                            <a href='javascript:void(0);' onClick='viewCard({{$id}},`{{$levelDetail->character_thumbnail_url}}`,`{{$level->id}}`)' class='viewCardBtn btn btn-primary ml-2'><i class='fas fa-eye'></i></a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    {!! Form::label("level[$key][usd_price]", __(Lang::get('forms.cards.usd_price'))); !!}
                                                    {!! Form::text("level[$key][usd_price]", $levelDetail->usd_price, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.cards.usd_price')) ]); !!}
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    {!! Form::label("level[$key][japanese_price]", __(Lang::get('forms.cards.japanese_price'))); !!}
                                                    {!! Form::text("level[$key][japanese_price]", $levelDetail->japanese_yen_price, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.cards.japanese_price')) ]); !!}
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    {!! Form::label("level[$key][chinese_price]", __(Lang::get('forms.cards.chinese_price'))); !!}
                                                    {!! Form::text("level[$key][chinese_price]", $levelDetail->chinese_yuan_price, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.cards.chinese_price')) ]); !!}
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    {!! Form::label("level[$key][korean_price]", __(Lang::get('forms.cards.korean_price'))); !!}
                                                    {!! Form::text("level[$key][korean_price]", $levelDetail->korean_won_price, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.cards.korean_price')) ]); !!}
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    {!! Form::label("level[$key][download_file]", __(Lang::get('forms.cards.download_file'))); !!}
                                                    {!! Form::file("level[$key][download_file]", ['class' => 'form-control','accept' => $accept]); !!}
                                                    @if($levelDetail->download_file_url)
                                                        <div class="ml-auto mt-2">
                                                            <strong>Uploaded File : </strong>
                                                            <a href="{{$levelDetail->download_file_url}}" target="_blank">{!! basename($levelDetail->download_file_url) !!}</a>
                                                            <span><i remove-key="download_file" remove_id="{{$level->id}}" class="removeImageIcon pointer removeIcon fa fa-times-circle fa-2x ml-2" style="vertical-align: middle;"></i></span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    {!! Form::label("level[$key][feeding_rive]", __(Lang::get('forms.cards.feeding'))); !!}
                                                    {!! Form::file("level[$key][feeding_rive]", ['class' => 'form-control','accept' => $accept]); !!}
                                                    @if($levelDetail->feeding_rive_url)
                                                        <div class="ml-auto mt-2">
                                                            <strong>Uploaded File : </strong>
                                                            <a href="{{$levelDetail->feeding_rive_url}}" target="_blank">{!! basename($levelDetail->feeding_rive_url) !!}</a>
                                                            <span><i remove-key="feeding_rive" remove_id="{{$level->id}}" class="removeImageIcon pointer removeIcon fa fa-times-circle fa-2x ml-2" style="vertical-align: middle;"></i></span>
                                                            <a href='javascript:void(0);' onClick='viewCard({{$id}},`{{$levelDetail->feeding_rive_url}}`,`{{$level->id}}`)' class='viewCardBtn btn btn-primary ml-2'><i class='fas fa-eye'></i></a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    {!! Form::label("level[$key][required_love_in_days]", __(Lang::get('forms.cards.required_love_in_days'))); !!}
                                                    {!! Form::number("level[$key][required_love_in_days]", $levelDetail->required_love_in_days, ['min'=>1, 'class' => 'form-control', 'placeholder' => __(Lang::get('forms.cards.required_love_in_days')) ]); !!}
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    {!! Form::label("level[$key][love_amount]", __(Lang::get('forms.cards.love_amount'))); !!}
                                                    {!! Form::text("level[$key][love_amount]", $level->range, ['readonly'=>true, 'class' => 'form-control', 'placeholder' => __(Lang::get('forms.cards.love_amount')) ]); !!}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
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
    </div>
</div>
<div class="cover-spin"></div>
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>
@endsection
@section('scripts')
<script src="{!! asset('plugins/rive/rive.min.js') !!}"></script>
<script>
    $(document).on("click",".removeImageIcon",function(){
        var removeKey = $(this).attr("remove-key");
        var remove_id = $(this).attr("remove_id");
        var currentObject = $(this);
        if(removeKey){
            $.ajax({
                url: "{{route('admin.remove.cards.image',['card' => $id])}}",
                type:"POST",
                data: {
                    remove_key : removeKey,
                    remove_id : remove_id,
                },
                beforeSend: function() {
                    $('.cover-spin').show();
                },
                success:function(response) {
                    $('.cover-spin').hide();
                    if(response.success == true){
                        $(currentObject).parent().parent().remove();
                    }else {

                    }
                },
                error:function (response, status) {
                    $('.cover-spin').hide();
                }
            });
        }
    });

    $('#cardsForm').validate({
        rules: {
            'card_name': {
                required: true
            },
            'card_id' :{
                required: true
            },
        },
        messages: {
            'card_name':'This field is required',
            'card_id':'This field is required',
        },
        highlight: function (input) {
            $(input).parents('.form-line').addClass('error');
        },
        unhighlight: function (input) {
            $(input).parents('.form-line').removeClass('error');
        },
        errorPlacement: function (error, element) {
            $(element).parents('.form-group').append(error);
        },
    });

    $('#cardsUpdateForm').validate({
        rules: {
            'card_name': {
                required: true
            },
            'card_id' :{
                required: true
            },
            /* 'background_rive': {
                required: true, 
                accept: "image/jpeg, image/pjpeg, riv"
            } */
        },
        messages: {
            'card_name':'This field is required',
            'card_id':'This field is required',
        },
        highlight: function (input) {
            $(input).parents('.form-line').addClass('error');
        },
        unhighlight: function (input) {
            $(input).parents('.form-line').removeClass('error');
        },
        errorPlacement: function (error, element) {
            $(element).parents('.form-group').append(error);
        },
    });
</script>
@endsection
