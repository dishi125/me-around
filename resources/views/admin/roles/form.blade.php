@extends('layouts.app')
@section('styles')
<link rel="stylesheet" href="{!! asset('assets/css/custom.css') !!}">
@endsection
@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection
@section('content')
<?php
$data = (object)[];
if (!empty($rolesData)) {
  $data = $rolesData;
}
$id = !empty($data->id) ? $data->id : '' ;
$name= !empty($data->display_name) ? $data->display_name : '' ;
$rolePermissions = !empty($rolePermissions) ? $rolePermissions : null;
$checkId = !empty($data->id) ? $data->id : 0 ;
?>
<div class="row">
  <div class="col-md-12">
    <div class="card">
    <div class="col-md-12 col-lg-12">
      @if ( isset( $rolesData ) && $rolesData )
      <form id="roleForm" name="roleForm" action="{!! route('admin.roles.update', $id) !!}"
      method="POST">
      <input type="hidden" name="_method" value="PUT">
      @else
      <form id="roleForm" name="roleForm" method="POST" action="{{ route('admin.roles.store') }}">
        @endif
        @csrf
        <input type="hidden" value="{{$checkId}}" id="role_id"/>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>{{ __(Lang::get('forms.roles.name')) }}</label>
              <input type="text"
              class="form-control" value="{{$name}}"
              id="name" name="name"
              placeholder="{{ __(Lang::get('forms.roles.name')) }}">
              @if ($errors->has('name'))
              <span class="invalid-feedback" role="alert">
               <strong>{{ $errors->first('name') }}</strong>
             </span>
             @endif
           </div>
         </div>
       </div>
       <div class="row">
             @if(!empty($rolePermissions))
             @foreach($permission as $value)
                <label class="align-items-center col-md-3 d-flex">{{ Form::checkbox('permission[]', $value->id, in_array($value->id, $rolePermissions) ? true : false, array('class' => 'name mr-2')) }}
                {{ ucwords(str_replace("-", " ", strtolower($value->name))) }}</label>
            @endforeach
            @else
            @foreach($permission as $value)
                <label class="col-md-3">{{ Form::checkbox('permission[]', $value->id, false, array('class' => 'name')) }}
                {{ ucwords(str_replace("-", " ", strtolower($value->name))) }}</label>
            @endforeach
            @endif
       </div>
     </div>
     <div class="card-footer text-left">
      <button type="submit" id="submit" class="btn btn-primary">
        {{ __(Lang::get('general.save')) }}
      </button>
      <a href="{{ route('admin.roles')}}" class="btn btn-outline-danger">
        {{ __(Lang::get('general.cancel')) }}
      </a>
    </div>
  </form>
</div>
</div>
</div>
@endsection
@section('scripts')
<script type="text/javascript">
  var base_url = '{{ url('/admin') }}';
  $('#roleForm').validate({
    rules: {
      'name': {
        required: true,
        'remote': {
          url: base_url + '/roles/name/' + $('#role_id').val(),
          type: "get",
          async:false
        },
      },
    },
    messages: {
      'name':
      {
        'required': 'This field is required',
        'remote': 'Role name is already exists.'
      },
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
    submitHandler: function() {
        $("#submit").addClass("disabled btn-progress");
        $('#roleForm').submit();
    }
  });
</script>
@endsection
