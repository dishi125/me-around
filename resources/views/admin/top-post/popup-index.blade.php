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
         <select
            class="form-control select2"
            name="country_id" id="country_id">
            <option>{{__(Lang::get('forms.top-post.select-country'))}}</option>
            @foreach($countries as $countryData)
            <option value="{{$countryData->id}}"> {{$countryData->name}} </option>
            @endforeach
         </select>
      </div>
   </div>
   @foreach($countries as $countryData)
      <div class="col-md-6 country-div country-post country-{{$countryData->id}}">        
         <div class="row top-post-tabs pb-5">
            <div class="col-4">
               <div class="card p-0">
                  <div class="card-body p-0">
                     <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                           <a class="nav-link active show" data-toggle="list" href="#list-popup-{{$countryData->id}}">Popup Slides</a>
                        </li>                        
                     </ul>
                  </div>               
               </div>
            </div>
            <div class="col-8">               
               <div class="tab-content">
                  <div class="tab-pane fade active show" id="list-popup-{{$countryData->id}}">
                     <div class="card">
                        <div class="card-header justify-content-between">
                           <h4>Slide Show</h4>
                           <div class="custom-checkbox custom-control">                              
                           <?php 
                           $checked = '';
                           if(count($popupSlides) > 0 && !empty($popupSlides) && $popupSlides->contains('category_id', null) && $popupSlides->where('category_id', null)->where('country_code',$countryData->code)->where('is_random', 1)->count() >= 1) {
                              $checked = "checked";
                           }
                           ?> 
                              <input type="checkbox" class="custom-control-input is-random-check" id="checkbox-popup" {{$checked}} data-cat-id="0" data-entity-id="0" data-section="popup">
                              <label for="checkbox-popup" class="custom-control-label"> Side mix and random</label>
                           </div>
                        </div>
                        <div class="card-body">
                           <ul class="slide-show-list">
                           @if(count($popupSlides) > 0 && !empty($popupSlides))                           
                                 @if ($popupSlides->contains('category_id', null) && $popupSlides->contains('country_code', $countryData->code))
                                    @foreach($popupSlides as $homeSlide )
                                       @if(!empty($homeSlide) && $homeSlide->category_id == null && count($homeSlide->bannerDetail) > 0 && $homeSlide->country_code == $countryData->code)
                                          @foreach($homeSlide->bannerDetail as $detail)
                                          <li>
                                             <div class="custom-file custom-file-img">
                                                <img src="{{$detail->image_url}}" alt="img01" class="img-fluid">
                                             </div>
                                             
                                             <div class="form-group">
                                                <span class="badge badge-primary">Info</span>
                                                <a href='javascript:void(0)' role='button' onclick="editPosts({{$detail->id}})" class="badge badge-primary mr-1" >Edit</a>
                                                <a href='javascript:void(0)' role='button' onclick="deletePost({{$detail->id}})" class="badge badge-primary mr-1" >Delete</a>
                                                <input type="text" class="form-control" readOnly value="{{$detail->link}}">
                                                <!-- <input type="text" class="form-control" readOnly value="{{$detail->from_date.'-'.$detail->to_date}}"> -->
                                                <p style="font-size: 13px;font-weight: 600;">{{$detail->from_date.' to '.$detail->to_date}} </p>
                                             </div>
                                          </li>
                                          @endforeach
                                          <?php $count = (5- count($homeSlide->bannerDetail)); ?>                                                                     
                                       @endif
                                    @endforeach    
                                    <?php $count = 5; ?>                                
                                 @else
                                    <?php $count = 5; ?>
                                 @endif
                                 @for($i = 1; $i <= $count;$i++)
                                    <li>
                                       <div class="custom-file addBanner" data-cat-id="0" data-entity-id="0" data-section="popup">
                                          <i class="fa fa-plus"></i>
                                       </div>
                                       <div class="form-group">
                                          <span class="badge badge-primary">Info</span>
                                       </div>
                                    </li>
                                 @endfor 
                              @else
                                 @for($i = 1; $i <= 5;$i++)
                                    <li>
                                       <div class="custom-file addBanner" data-cat-id="0" data-entity-id="0" data-section="popup">
                                          <i class="fa fa-plus"></i>
                                       </div>
                                       <div class="form-group">
                                          <span class="badge badge-primary">Info</span>
                                       </div>
                                    </li>
                                 @endfor 
                              @endif
                              <a href="javascript:void(0);" data-cat-id="0" data-entity-id="0" data-section="popup" class="add_button btn btn-primary" title="Add field">Add More</a>
                           </ul>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>         
      </div> 
   @endforeach
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
   $("#country_id").change(function () {
       var country_id = this.value;
       $('.country-div').addClass('country-post');
       $(".country-"+country_id).removeClass('country-post');
   });
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('plugins/owlcarousel2/dist/owl.carousel.min.js') !!}"></script>
<script src="{!! asset('plugins/jquery-ui/jquery-ui.min.js') !!}"></script>
<script src="{!! asset('js/pages/top-post/index-popup.js') !!}"></script>
@endsection

