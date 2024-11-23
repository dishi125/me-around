@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
@endsection

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')
<form id="announcementForm" name="announcementForm" action="{!! route('admin.announcement.store') !!}" method="POST">
    @csrf
    <div class="row">
        <div class="col-md-4">
            <div class="mt-2 mb-4">
                <select class="form-control select2" name="language_id" id="language_id">
                    <option value="all">All</option>
                    @foreach($supportLanguage as $language)
                    <option value="{{$language->id}}" {{ $language->id == 4 ? "selected" : "" }}> {{$language->name}} </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="announcement">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
                                        <div class="custom-checkbox custom-control">
                                            <input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-all">
                                            <label for="checkbox-all" class="custom-control-label">&nbsp;</label>
                                        </div>
                                    </th>
                                    <th>Where do you want to send ?</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-center pt-2">
                                        <div class="custom-checkbox custom-control">
                                            <input type="checkbox" data-cat-id="0" data-entity-id="{{ \App\Models\EntityTypes::NORMALUSER}}" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-announcement" id="announcement-checkbox-user" value="normal-user" name="announcement_checkbox[]"><label for="announcement-checkbox-user" class="custom-control-label">&nbsp;</label>
                                        </div>
                                    <td>Normal User</td>
                                </tr>
                                <tr>
                                    <td class="text-center pt-2">
                                        <div class="custom-checkbox custom-control">
                                            <input type="checkbox" data-cat-id="0" data-entity-id="{{ \App\Models\EntityTypes::HOSPITAL}}" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-announcement" id="announcement-checkbox-hospital" value="hospital" name="announcement_checkbox[]"><label for="announcement-checkbox-hospital" class="custom-control-label">&nbsp;</label>
                                        </div>
                                    <td>Hospital</td>
                                </tr>
                                @if (!(empty($beautyCategory)))
                                @foreach($beautyCategory as $key => $value)
                                <tr>
                                    <td class="text-center pt-2">
                                        <div class="custom-checkbox custom-control">
                                            <input type="checkbox" data-cat-id="{{$key}}" data-entity-id="{{ \App\Models\EntityTypes::SHOP}}" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-announcement" id="announcement-checkbox-{{$key}}" value="{{$key}}" name="announcement_checkbox[]"><label for="announcement-checkbox-{{$key}}" class="custom-control-label">&nbsp;</label>
                                        </div>
                                    <td>{{$value}}</td>
                                </tr>
                                @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="profile-widget-description">
                        <div class="">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>{{ __(Lang::get('forms.announcement.link')) }}</label>
                                            <input type="url" class="form-control" value="" id="link" name="link" placeholder="{{ __(Lang::get('forms.announcement.link')) }}">
                                            @error('link')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('link') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>{{ __(Lang::get('forms.announcement.text')) }}</label>
                                            <textarea id="text" name="text" class="form-control" placeholder="{{ __(Lang::get('forms.announcement.text')) }}"></textarea>
                                            @error('text')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('text') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button type="submit" class="btn btn-primary" id="annoucement_submit">{{ __(Lang::get('general.save')) }}</button>
                                <button type="reset" class="btn btn-primary">{{ __(Lang::get('general.cancel')) }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<div class="cover-spin"></div>

@endsection
@section('scripts')
<script>
    $('#checkbox-all').click(function(event) {
        if (this.checked) {
            $('.check-all-announcement').each(function() {
                this.checked = true;
            });
        } else {
            $('.check-all-announcement').each(function() {
                this.checked = false;
            });
        }
    });
    $('#annoucement_submit').click(function(event) {
        var id = [];
        $('.check-all-announcement:checked').each(function() {
            id.push($(this).attr('data-cat-id'));
        });
        if (id.length == 0) {
            iziToast.error({
                title: '',
                message: 'Please select at least one checkbox',
                position: 'topRight',
                progressBar: true,
                timeout: 5000
            });
        }
    });
    $('#announcementForm').validate({
        rules: {
            'link': {
                required: true
            },
            'text': {
                required: true
            },
        },
        highlight: function(input) {
            $(input).parents('.form-line').addClass('error');
        },
        unhighlight: function(input) {
            $(input).parents('.form-line').removeClass('error');
        },
        errorPlacement: function(error, element) {
            $(element).parents('.form-group').append(error);
        },
    });
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
@endsection