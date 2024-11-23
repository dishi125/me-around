@extends('layouts.app')
@section('styles')
<link rel="stylesheet" href="{!! asset('css/custom.css') !!}">
@endsection
@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')

<?php
$shopCategory = \App\Models\CategoryTypes::SHOP;
$customCategory = \App\Models\CategoryTypes::CUSTOM;
$data = (object)[];
if (!empty($category)) {
    $data = $category;
}

$name = !empty($data->name) ? $data->name : '';
$category_type_id = !empty($data->category_type_id) ? $data->category_type_id : '';
$parent_id = !empty($data->parent_id) ? $data->parent_id : '';
$status_id = !empty($data->status_id) ? $data->status_id : '';
$order = !empty($data->order) ? $data->order : 0;
$route = $category_type_id != '' ? config('constant.category_url_' . $category_type_id) : config('constant.category_url_2');
?>
<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-12">
            <div class="card profile-widget">
                <div class="profile-widget-header">
                    @if (!empty($category) && ($category->category_type_id == $shopCategory || $category->category_type_id == $customCategory))
                    <img alt="image" src="@if($category->logo) {{ asset($category->logo) }} @else {!! asset('img/avatar/avatar-1.png') !!} @endif" class="rounded-circle profile-widget-picture">
                    @endif
                </div>
                <div class="profile-widget-description">
                    <div class="">
                        {!! Form::open(['route' => ['admin.important-setting.limit-custom.update.language',$id], 'id' =>"settingForm", 'method' => 'put', 'enctype' => 'multipart/form-data']) !!}
                        @csrf
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        {!! Form::label('key', __(Lang::get('forms.important-setting.key'))); !!}
                                        {!! Form::text('key', $settings->key, ['class' => 'form-control'. ( $errors->has('key') ? ' is-invalid' : '' ),'readonly' => true, 'placeholder' => __(Lang::get('forms.important-setting.key')) ]); !!}
                                        @error('key')
                                        <div class="invalid-feedback">
                                            {{ $errors->get('key') }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('value', __(Lang::get('forms.important-setting.value')).'(English)'); !!}
                                        {!! Form::text('value', $settings->value, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.category.name')).'(English)' ]); !!}
                                        @error('value')
                                        <div class="invalid-feedback">
                                            {{ $errors->get('value') }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>
                                @foreach($postLanguages as $postLanguage)
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <?php
                                        $label = __(Lang::get('forms.important-setting.value')) . '(' . $postLanguage->name . ')';
                                        $configValue = array_key_exists($postLanguage->id, $configLanguages) ? $configLanguages[$postLanguage->id] : '';
                                        ?>
                                        {!! Form::label('config_value', $label); !!}
                                        {!! Form::text('config_value['.$postLanguage->id.']', $configValue, ['class' => 'form-control', 'placeholder' => $label ]); !!}
                                        @error('config_value['.$postLanguage->id.']')
                                        <div class="invalid-feedback">
                                            {{ $errors->get('config_value['.$postLanguage->id.']') }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button type="submit" class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                            <a href="{{ route('admin.important-setting.limit-custom.index-links')}}" class="btn btn-default">{{ __(Lang::get('general.cancel')) }}</a>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')

<script>

</script>
@endsection