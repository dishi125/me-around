@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/bootstrap-datepicker/bootstrap-datepicker.min.css') !!}">
<link rel="stylesheet" href="{!! asset('plugins/bootstrap-datepicker/bootstrap.min.css') !!}">

<style>
    #weddingForm textarea.form-control {
        height: 80px !important;
    }

</style>
@endsection

@section('header-content')
<h1>{{ @$title }}</h1>
@endsection

<?php
$id = $id ?? '';
$imageURL = $imagesfile = '';
?>

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card profile-widget">
            <div class="profile-widget-description">
                    @if($id)
                        {!! Form::open(['route' => ['admin.wedding.update',$id], 'id' =>"weddingForm", 'enctype' => 'multipart/form-data']) !!}
                    @else
                        {!! Form::open(['route' => 'admin.wedding.store', 'id' =>"weddingForm", 'enctype' => 'multipart/form-data']) !!}
                    @endif
                    @csrf

                    <div class="row">
                        @foreach($fields as $fieldKey => $field)
                            <div class="col-md-{{$field['col']}}">
                                <div class="form-group">
                                    @if($field['type'] !== "hidden")
                                        {!! Form::label($fieldKey, __($field['label'])); !!}
                                    @endif
                                    @if($field['type'] == "text")
                                        {!! Form::text($fieldKey, getMetaData($id,$fieldKey), ['required' => $field['required'] ?? false,'id' => $fieldKey, 'class' => 'required form-control', 'placeholder' => __($field['label']) ]); !!}
                                    @elseif($field['type'] == "select")
                                        {!! Form::select($fieldKey, getWeddingSettingOptions($fieldKey),getMetaData($id,$fieldKey), ['required' => $field['required'] ?? false,'id' => $fieldKey, 'class' => 'h-100 required form-control' ]); !!}
                                    @elseif($field['type'] == "date")
                                        {!! Form::text($fieldKey, App\Http\Controllers\Controller::formatDateTimeCountryWise(getMetaData($id,$fieldKey),$adminTimezone), ['required' => $field['required'] ?? false,'id' => $fieldKey,  'class' => 'select_date required form-control', 'placeholder' => __($field['label']) ]); !!}
                                    @elseif($field['type'] == "file")
                                        <div class="d-flex align-items-center">
                                            <div id="image_preview_{{$fieldKey}}" class="d-flex">
                                                @if($id)
                                                    <?php
                                                        $images = [];
                                                        if($field['is_multiple']){
                                                            $imageData = getMetaData($id,$fieldKey,false);
                                                            $images = $imageData ? unserialize($imageData->meta_value) : [];
                                                            $imagesfile = json_encode($images);
                                                        }else{
                                                            $imageData = getMetaData($id,$fieldKey,false);
                                                            $images[] = $imageData ? $imageData->meta_value : '';
                                                        }
                                                        if($images){
                                                        foreach ($images as $key => $image) {
                                                            $imageURL = $image ? Storage::disk('s3')->url($image) : '';
                                                        if($imageURL){
                                                    ?>

                                                        <div class="removeImage">
                                                            <span class="pointer" data-index={{$key}} data-imageid="{{$imageData->id}}"><i class="fa fa-times-circle fa-2x"></i></span>
                                                            <div style="background-image: url({{$imageURL}});" class="bgcoverimage">
                                                                <img src="{!! asset('img/noImage.png') !!}">
                                                            </div>
                                                        </div>
                                                    <?php }}} ?>
                                                @endif
                                            </div>
                                        </div>
                                        @if($field['is_multiple'] && $imagesfile)
                                            {{ Form::hidden('imagesFile',$imagesfile, array('id' => 'imagesFile')) }}
                                        @endif
                                        <div class="add-image-icon" style="display: {{($imageURL && !$field['is_multiple']) ? 'none' : 'flex'}};" >
                                            {{ Form::file($fieldKey,[ 'accept'=>"image/jpg,image/png,image/jpeg", 'onchange'=>"imagesPreview(this, '#image_preview_$fieldKey', '$fieldKey', ".$field['is_multiple'].");", 'class' => 'main_image_file form-control', "multiple" => $field['is_multiple'], 'id' => "$fieldKey", 'hidden' => 'hidden' ]) }}
                                            <label class="pointer" for="{{$fieldKey}}"><i class="fa fa-plus fa-4x"></i></label>
                                        </div>
                                    @elseif($field['type'] == "textarea")
                                        {!! Form::textarea($fieldKey, getMetaData($id,$fieldKey), ['id' => $fieldKey, 'class' => 'form-control', 'rows' => 4 ]) !!}
                                    @elseif($field['type'] == "repeater")
                                        <?php
                                            $repeaterData = unserialize(getMetaData($id,$fieldKey));
                                        ?>
                                            <div class="repeater-content">
                                                @if($repeaterData && count($repeaterData) > count($field['field_group']))
                                                    @foreach($repeaterData as $index => $data)
                                                        <div class="row index_{{$fieldKey}}">
                                                            @foreach($field['field_group'][0] as $childKey => $childField)
                                                                <div class="col-md-{{$childField['col']}}">
                                                                    <div class="form-group">
                                                                        @if($childField['type'] == "text")
                                                                            {!! Form::text("$fieldKey"."[".$index."]"."[$childKey]", $repeaterData[$index][$childKey], ['id' => $childKey, 'class' => 'required form-control', 'placeholder' => __($childField['label']) ]); !!}
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                            @if($field['is_dynamic'])
                                                                <div class="col-md-2">
                                                                    @if($index == 0)
                                                                        <button type="button" class="btn btn-default" field="{{$fieldKey}}" id="add_more" >
                                                                            <i class="fas fa-plus"></i>
                                                                        </button>
                                                                    @else
                                                                        <button type="button" class="btn btn-default"  id="remove_more" >
                                                                            <i class="fas fa-minus"></i>
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                @else
                                                    @foreach($field['field_group'] as $key => $field_group)
                                                        <div class="row index_{{$fieldKey}}">
                                                            @foreach($field_group as $childKey => $childField)
                                                                <div class="col-md-{{$childField['col']}}">
                                                                    <div class="form-group">
                                                                        @if($childField['type'] == "text")
                                                                            {!! Form::text("$fieldKey"."[".$childField['index']."]"."[$childKey]", $repeaterData[$key][$childKey] ?? '', ['id' => $childKey, 'class' => 'required form-control', 'placeholder' => __($childField['label']) ]); !!}
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                            @if($field['is_dynamic'])
                                                                <div class="col-md-2">
                                                                    <button type="button"  field="{{$fieldKey}}" id="add_more" class="btn btn-default">
                                                                        <i class="fas fa-plus"></i>
                                                                    </button>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                    @elseif($field['type'] == "address")
                                        {!! Form::text($fieldKey, getMetaData($id,$fieldKey), ['id' => $fieldKey, 'class' => 'required form-control map-input', 'placeholder' => __($field['label']) ]); !!}
                                        <div id="address-map-container" class="mt-2" style="width:100%;height:200px; ">
                                            <div style="width: 100%; height: 100%" id="address-map"></div>
                                        </div>
                                    @elseif($field['type'] == "hidden")
                                        {{ Form::hidden($fieldKey, getMetaData($id,$fieldKey), array('id' => $fieldKey)) }}
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit"
                            class="btn btn-primary mr-2">{{ __(Lang::get('general.save')) }}</button>
                        <a href="{{ route('admin.wedding.index')}}"
                            class="btn btn-default">{{ __(Lang::get('general.cancel')) }}</a>
                    </div>
                    {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>
<div class="cover-spin"></div>

@endsection

@section('scripts')
<script src="{!! asset('plugins/bootstrap-datepicker/bootstrap-datepicker.min.js') !!}"></script>
<script src="{!! asset('js/pages/wedding/form.js') !!}"></script>
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDlfhV6gvSJp_TvqudE0z9mV3bBlexZo3M&&radius=100&&libraries=places&callback=initialize" async defer></script>
<script src="{!! asset('js/mapInput.js') !!}"></script>

<script>
    var csrfToken = "{{csrf_token()}}";
    $(function () {
        $('.select_date').datetimepicker({
            format:'YYYY-MM-DD HH:mm:ss',
        });
    });

</script>
@endsection
