@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
<link rel="stylesheet" href="{!! asset('plugins/owlcarousel2/dist/assets/owl.carousel.min.css') !!}">
<link rel="stylesheet" href="{!! asset('plugins/owlcarousel2/dist/assets/owl.theme.default.min.css') !!}">
<link rel="stylesheet" href="{!! asset('plugins/jquery-ui/jquery-ui.css') !!}">
@endsection

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')
<div class="top-post-section">
   <div class="row">
      <div class="col-md-12">
         <div class="card">
            <div class="card-body">
               <div class="buttons">
                  <a href="{!! route('admin.top-post.index') !!}" class="btn btn-primary">Slide Show</a>
                  <a href="{!! route('admin.top-post.hospital.index') !!}" class="btn btn-primary">Hospital Posts</a>
                  <a href="{!! route('admin.top-post.popup.index') !!}" class="btn btn-primary">Popup</a>
               </div>
            </div>
         </div>
      </div>
      <div class="col-md-7">
         <div class="form-group">
            <select class="form-control select2" name="country_id" id="country_id">
               <option>{{__(Lang::get('forms.top-post.select-country'))}}</option>
               @foreach($countries as $countryData)
               <option value="{{$countryData->id}}"> {{$countryData->name}} </option>
               @endforeach
            </select>
         </div>
      </div>
   </div>

   <div class="row country-data">
   </div>
</div>
<div class="modal fade" id="pageModel" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
<!-- <div class="modal fade" id="pageModel" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div> -->
<div class="cover-spin"></div>

@endsection
@section('scripts')
<script>
   var addTopPost = "{{ route('admin.top-post.add.post') }}";
   var storeTopPost = "{{ route('admin.top-post.store.post') }}";
   var updateTopPost = "{{ route('admin.top-post.update.post') }}";
   var updateRandomCheckbox = "{{ route('admin.top-post.update.checkbox') }}";
   var csrfToken = "{{csrf_token()}}";
   var pageModel = $("#pageModel");
   $("#country_id").change(function() {
      var country_id = this.value;

      $.ajax({
         url: "{{ route('admin.top-post.country-details') }}",
         method: 'POST',
         data: {
            '_token': "{{csrf_token()}}",
            'country_id': country_id,
         },
         beforeSend: function() {
            // setting a timeout
            $('.cover-spin').show();
         },
         success: function(data) {
            console.log(data);
            $('.cover-spin').hide();
            $('.country-data').html(data);

         }
      });
   });
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('plugins/owlcarousel2/dist/owl.carousel.min.js') !!}"></script>
<script src="{!! asset('plugins/jquery-ui/jquery-ui.min.js') !!}"></script>
<script src="{!! asset('js/pages/top-post/index.js') !!}"></script>
@endsection