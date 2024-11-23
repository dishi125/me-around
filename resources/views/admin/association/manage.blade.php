<?php

$associationId = !empty($associationData) ? $associationData->id : ''; 
$type = !empty($associationData) ? $associationData->type : 'public'; 
$associationCode = !empty($associationData) ? $associationData->code : ''; 
$country_id = !empty($associationData) ? $associationData->country_id : ''; 
$association_name = !empty($associationData) ? $associationData->association_name : ''; 
$description = !empty($associationData) ? $associationData->description : ''; 
$managers = !empty($managers) ? $managers : [];
$supporter = !empty($supporter) ? $supporter : [];
$members = !empty($members) ? $members : [];
$president = !empty($president) ? $president : '';
$images = !empty($images) ? $images : [];
$imagesfile = json_encode($images);

?>

@extends('layouts.app')

<style type="text/css">
    li.select2-selection__choice {
        COLOR: black !important;
    }  
</style>

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        {!! Form::open([ 'id' =>"associationForm", 'enctype' => 'multipart/form-data']) !!}
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        {{ Form::hidden('association_id', $associationId, array('id' => 'association_id')) }}

                        {{ Form::hidden('imagesFile',$imagesfile, array('id' => 'imagesFile')) }}

                        <div class="form-group">
                            {!! Form::label('country', __(Lang::get('forms.association.country'))); !!}
                            {!!Form::select('country', $countries, $country_id , ['class' => 'form-control','placeholder' => __(Lang::get('forms.association.country'))])!!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('association_name', __(Lang::get('forms.association.association_name'))); !!}
                            {!! Form::text('association_name', $association_name, ['class' => 'form-control'. ( $errors->has('association_name') ? ' is-invalid' : '' ), 'placeholder' => __(Lang::get('forms.association.association_name')) ]); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('description', __(Lang::get('forms.association.description'))); !!}
                            {!! Form::textarea('description', $description, ['class' => 'form-control'. ( $errors->has('description') ? ' is-invalid' : '' ), 'placeholder' => __(Lang::get('forms.association.description')) ]); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('president', __(Lang::get('forms.association.president'))); !!}

                            {!!Form::select('president', $allUser, $president , ['class' => 'form-control','placeholder' => __(Lang::get('forms.association.president'))])!!}

                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('manager', __(Lang::get('forms.association.manager'))); !!}

                            {!!Form::select('manager[]', $allUser, $managers , ['class' => 'form-control','id' => 'manager', 'multiple' => true])!!}

                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('member', __(Lang::get('forms.association.member'))); !!}
                            {!!Form::select('member[]', $allUser, $members , ['class' => 'form-control','id' => 'member', 'multiple' => true])!!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('supporter', __(Lang::get('forms.association.supporter'))); !!}
                            {!!Form::select('supporter[]', $allSupperters, $supporter , ['class' => 'form-control','id' => 'member', 'multiple' => true])!!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('association_code', __(Lang::get('forms.association.association_code'))); !!}
                            {!! Form::text('association_code', $associationCode, ['class' => 'form-control'. ( $errors->has('association_code') ? ' is-invalid' : '' ),'maxlength' => 4, 'placeholder' => __(Lang::get('forms.association.association_code')) ]); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('type', __(Lang::get('forms.association.type'))); !!}<br>
                            {{ Form::radio('type', 'public',($type == 'public' ? true : false)) }}{{__(Lang::get('forms.association.public'))}}
                            {{ Form::radio('type', 'private',($type == 'private' ? true : false)) }}{{__(Lang::get('forms.association.private'))}}
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="d-flex align-items-center">
                            @include('layouts.media-upload-multi',['imagesData' => $images])
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="{!! route('admin.association.index') !!}" class="btn btn-outline-danger" >Close</a>
            <button type="submit" class="btn btn-primary" id="saveAssociation">Save</button>

        </div>
        {!! Form::close() !!}
    </div>
</div>
<div class="cover-spin"></div>
@endsection

@section('scripts')
<script type="text/javascript">
    var associationTableData = "{{ route('admin.association.data') }}";
    var listURL = "{{ route('admin.association.index') }}";
    var csrfToken = "{{csrf_token()}}";    
</script>
<script src="{!! asset('js/file-upload.js') !!}"></script>
<script src="{!! asset('js/pages/association/manage.js') !!}"></script>
@endsection