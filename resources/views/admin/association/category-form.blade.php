<?php

$id = !empty($data) ? $data->id : ''; 
$name = !empty($data) ? $data->name : '';
$order = !empty($data) ? $data->order : ''; 
$can_post = !empty($data) ? $data->can_post : 0; 

?>
<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>@if (@$title) {{ @$title }} @endif</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        {!! Form::open([ 'id' =>"associationCategoryForm", 'enctype' => 'multipart/form-data']) !!}
        @csrf
        <div class="modal-body justify-content-center">
            {{ Form::hidden('category_id', $id, array('id' => 'category_id')) }}
            {{ Form::hidden('association_id', $association, array('id' => 'association_id')) }}
            <div class="form-group">
                {{ Form::checkbox('can_post', 0,($can_post == 1 ? true : false)) }} {{__(Lang::get('forms.association-category.can_post'))}}

            </div>
            <div class="form-group">
                {!! Form::label('name', __(Lang::get('forms.association-category.name'))); !!}
                {!! Form::text('name', $name, ['class' => 'form-control'. ( $errors->has('name') ? ' is-invalid' : '' ), 'placeholder' => __(Lang::get('forms.association-category.name')) ]); !!}
            </div>
            <div class="form-group">
                {!! Form::label('order', __(Lang::get('forms.association-category.order'))); !!}
                {!! Form::text('order', $order, ['class' => 'form-control'. ( $errors->has('order') ? ' is-invalid' : '' ), 'placeholder' => __(Lang::get('forms.association-category.order')) ]); !!}
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary" id="saveAssociationCategory">Save</button>

        </div>
        {!! Form::close() !!}
    </div>
</div>