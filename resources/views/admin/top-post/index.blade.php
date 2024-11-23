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
                           <a class="nav-link active show" data-toggle="list" href="#list-home-{{$countryData->id}}">Home</a>
                        </li>
                        <li class="nav-item">
                           <a class="nav-link " data-toggle="list" href="#list-category-{{$countryData->id}}">Category</a>
                        </li>
                        <li class="nav-item">
                           <a class="nav-link nav-link-title">Beauty</a>
                        </li>
                        <li class="nav-item">
                           <a class="nav-link" data-toggle="list" href="#shop-home-{{$countryData->id}}">No Category</a>
                        </li>

                        @foreach($beautyCategory as $key => $value)
                           <li class="nav-item">
                              <a class="nav-link" data-toggle="list" href="#list-{{$countryData->id}}-{{$key}}">{{$value}}</a>
                           </li>
                        @endforeach
                        
                        <li class="nav-item">
                           <a class="nav-link nav-link-title">Community</a>
                        </li>
                        @foreach($communityCategory as $key => $value)
                           <li class="nav-item">
                              <a class="nav-link" data-toggle="list" href="#list-{{$countryData->id}}-{{$key}}">{{$value}}</a>
                           </li>
                        @endforeach
                     </ul>
                  </div>               
               </div>
            </div>
            <div class="col-8">               
               <div class="tab-content">
                  <div class="tab-pane fade active show" id="list-home-{{$countryData->id}}">
                     <div class="card">
                        <div class="card-header justify-content-between">
                           <h4>Slide Show</h4>
                           <div class="custom-checkbox custom-control">                              
                           <?php 
                           $checked = '';
                           if(count($homeSlides) > 0 && !empty($homeSlides) && $homeSlides->contains('category_id', null) && $homeSlides->where('category_id', null)->where('country_code',$countryData->code)->where('is_random', 1)->count() >= 1) {
                              $checked = "checked";
                           }
                           ?> 
                              <input type="checkbox" class="custom-control-input is-random-check" id="checkbox-home" {{$checked}} data-cat-id="0" data-entity-id="0" data-section="home">
                              <label for="checkbox-home" class="custom-control-label"> Side mix and random</label>
                           </div>
                        </div>
                        <div class="card-body">
                           <ul class="slide-show-list">
                           @if(count($homeSlides) > 0 && !empty($homeSlides))                           
                                 @if ($homeSlides->contains('category_id', null) && $homeSlides->contains('country_code', $countryData->code))
                                    @foreach($homeSlides as $homeSlide )
                                       @if(!empty($homeSlide) && $homeSlide->category_id == null && count($homeSlide->bannerDetail) > 0 && $homeSlide->country_code == $countryData->code)
                                          @foreach($homeSlide->bannerDetail as $detail)
                                          <li>
                                             <div class="custom-file custom-file-img">
                                                <img src="{{$detail->image_url}}" alt="img01" class="img-fluid">
                                             </div>
                                             
                                             <div class="form-group">
                                                <span class="badge badge-primary">Info</span>
                                                <a href='javascript:void(0)' role='button' onclick="editPosts(`{{$detail->id}}`)" class="badge badge-primary mr-1" >Edit</a>
                                                <a href='javascript:void(0)' role='button' onclick="deletePost(`{{$detail->id}}`)" class="badge badge-primary mr-1" >Delete</a>
                                                <input type="text" class="form-control" readOnly value="{{$detail->link}}">
                                             </div>
                                          </li>
                                          @endforeach
                                          <?php $count = (5- count($homeSlide->bannerDetail)); ?>   
                                       @else
                                          <?php $count = 5; ?>                                                                   
                                       @endif
                                    @endforeach                                    
                                 @else
                                    <?php $count = 5; ?>
                                 @endif
                                 @for($i = 1; $i <= $count;$i++)
                                    <li>
                                       <div class="custom-file addBanner" data-cat-id="0" data-entity-id="0" data-section="home">
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
                                       <div class="custom-file addBanner" data-cat-id="0" data-entity-id="0" data-section="home">
                                          <i class="fa fa-plus"></i>
                                       </div>
                                       <div class="form-group">
                                          <span class="badge badge-primary">Info</span>
                                       </div>
                                    </li>
                                 @endfor 
                              @endif
                              <a href="javascript:void(0);" data-cat-id="0" data-entity-id="0" data-section="home" class="add_button btn btn-primary" title="Add field">Add More</a>
                           </ul>
                        </div>
                     </div>
                  </div>
                  <div class="tab-pane fade" id="list-category-{{$countryData->id}}">
                     <div class="card">
                        <div class="card-header justify-content-between">
                           <h4>Slide Show</h4>
                           <div class="custom-checkbox custom-control">                              
                           <?php 
                           $checked = '';
                           if(count($categorySlides) > 0 && !empty($categorySlides) && $categorySlides->contains('category_id', null) && $categorySlides->where('category_id', null)->where('country_code',$countryData->code)->where('is_random', 1)->count() >= 1) {
                              $checked = "checked";
                           }
                           ?> 
                              <input type="checkbox" class="custom-control-input is-random-check" id="checkbox-home" {{$checked}} data-cat-id="0" data-entity-id="0" data-section="category">
                              <label for="checkbox-home" class="custom-control-label"> Side mix and random</label>
                           </div>
                        </div>
                        <div class="card-body">
                           <ul class="slide-show-list">
                           @if(count($categorySlides) > 0 && !empty($categorySlides))                           
                                 @if ($categorySlides->contains('category_id', null) && $categorySlides->contains('country_code', $countryData->code))
                                    @foreach($categorySlides as $homeSlide )
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
                                             </div>
                                          </li>
                                          @endforeach
                                          <?php $count = (5- count($homeSlide->bannerDetail)); ?>   
                                       @else
                                          <?php $count = 5; ?>                                                                   
                                       @endif
                                    @endforeach                                    
                                 @else
                                    <?php $count = 5; ?>
                                 @endif
                                 @for($i = 1; $i <= $count;$i++)
                                    <li>
                                       <div class="custom-file addBanner" data-cat-id="0" data-entity-id="0" data-section="category">
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
                                       <div class="custom-file addBanner" data-cat-id="0" data-entity-id="0" data-section="category">
                                          <i class="fa fa-plus"></i>
                                       </div>
                                       <div class="form-group">
                                          <span class="badge badge-primary">Info</span>
                                       </div>
                                    </li>
                                 @endfor 
                              @endif
                              <a href="javascript:void(0);" data-cat-id="0" data-entity-id="0" data-section="category" class="add_button btn btn-primary" title="Add field">Add More</a>
                           </ul>
                        </div>
                     </div>
                  </div>
                  <div class="tab-pane fade active show" id="shop-home-{{$countryData->id}}">
                     <div class="card">
                        <div class="card-header justify-content-between">
                           <h4>Slide Show</h4>
                           <div class="custom-checkbox custom-control">                              
                           <?php 
                           $checked = '';
                           if(count($shopHomeSlides) > 0 && !empty($shopHomeSlides) && $shopHomeSlides->contains('category_id', null) && $shopHomeSlides->where('category_id', null)->where('country_code',$countryData->code)->where('is_random', 1)->count() >= 1) {
                              $checked = "checked";
                           }
                           ?> 
                              <input type="checkbox" class="custom-control-input is-random-check" id="checkbox-home" {{$checked}} data-cat-id="0" data-entity-id="0" data-section="home">
                              <label for="checkbox-home" class="custom-control-label"> Side mix and random</label>
                           </div>
                        </div>
                        <div class="card-body">
                           <ul class="slide-show-list">
                           @if(count($shopHomeSlides) > 0 && !empty($shopHomeSlides))                           
                                 @if ($shopHomeSlides->contains('category_id', null) && $shopHomeSlides->contains('country_code', $countryData->code))
                                    @foreach($shopHomeSlides as $shopHomeSlide )
                                       @if(!empty($shopHomeSlide) && $shopHomeSlide->category_id == null && count($shopHomeSlide->bannerDetail) > 0 && $shopHomeSlide->country_code == $countryData->code)
                                          @foreach($shopHomeSlide->bannerDetail as $detail)
                                          <li>
                                             <div class="custom-file custom-file-img">
                                                <img src="{{$detail->image_url}}" alt="img01" class="img-fluid">
                                             </div>
                                             
                                             <div class="form-group">
                                                <span class="badge badge-primary">Info</span>
                                                <a href='javascript:void(0)' role='button' onclick="editPosts({{$detail->id}})" class="badge badge-primary mr-1" >Edit</a>
                                                <a href='javascript:void(0)' role='button' onclick="deletePost({{$detail->id}})" class="badge badge-primary mr-1" >Delete</a>
                                                <input type="text" class="form-control" readOnly value="{{$detail->link}}">
                                             </div>
                                          </li>
                                          @endforeach
                                          <?php $count = (5- count($shopHomeSlide->bannerDetail)); ?>  
                                       @else
                                          <?php $count = 5; ?>                                                                    
                                       @endif
                                    @endforeach                                    
                                 @else
                                    <?php $count = 5; ?>
                                 @endif
                                 @for($i = 1; $i <= $count;$i++)
                                    <li>
                                       <div class="custom-file addBanner" data-cat-id="0" data-entity-id="{{ \App\Models\EntityTypes::SHOP}}" data-section="home">
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
                                       <div class="custom-file addBanner" data-cat-id="0" data-entity-id="{{ \App\Models\EntityTypes::SHOP}}" data-section="home">
                                          <i class="fa fa-plus"></i>
                                       </div>
                                       <div class="form-group">
                                          <span class="badge badge-primary">Info</span>
                                       </div>
                                    </li>
                                 @endfor 
                              @endif
                              <a href="javascript:void(0);" data-cat-id="0" data-entity-id="{{ \App\Models\EntityTypes::SHOP}}" data-section="home" class="add_button btn btn-primary" title="Add field">Add More</a>
                           </ul>
                        </div>
                     </div>
                  </div>

                  @foreach($beautyCategory as $key => $value)
                  <div class="tab-pane fade" id="list-{{$countryData->id}}-{{$key}}">
                     <div class="card">
                        <div class="card-header justify-content-between">
                           <h4>Slide Show</h4>
                           <div class="custom-checkbox custom-control">
                              <?php 
                              $checked = '';
                              if(count($shopSlides) > 0 && !empty($shopSlides) && $shopSlides->contains('category_id', $key) && $shopSlides->where('category_id', $key)->where('country_code',$countryData->code)->where('is_random', 1)->count() >= 1) {
                                 $checked = "checked";
                              }
                              ?>
                              <input type="checkbox" class="custom-control-input is-random-check" id="checkbox-{{$key}}" {{$checked}} data-cat-id="{{ $key }}" data-entity-id="{{ \App\Models\EntityTypes::SHOP}}" data-section="home">
                              <label for="checkbox-{{$key}}" class="custom-control-label"> Side mix and random</label>
                           </div>
                        </div>
                        <div class="card-body">
                           <ul class="slide-show-list">
                              @if(count($shopSlides) > 0 && !empty($shopSlides))
                                 @if ($shopSlides->contains('category_id', $key) && $shopSlides->contains('country_code', $countryData->code))
                                    @foreach($shopSlides as $shopSlide )
                                       @if(!empty($shopSlide) && $shopSlide->category_id == $key && count($shopSlide->bannerDetail) > 0 && $shopSlide->country_code == $countryData->code)
                                          @foreach($shopSlide->bannerDetail as $detail)
                                          <li>
                                             <div class="custom-file custom-file-img">
                                                <img src="{{$detail->image_url}}" alt="img01" class="img-fluid">
                                             </div>
                                             
                                             <div class="form-group">
                                                <span class="badge badge-primary">Info</span>
                                                <a href='javascript:void(0)' role='button' onclick="editPosts({{$detail->id}})" class="badge badge-primary mr-1" >Edit</a>
                                                <a href='javascript:void(0)' role='button' onclick="deletePost({{$detail->id}})" class="badge badge-primary mr-1" >Delete</a>
                                                <input type="text" class="form-control" readOnly value="{{$detail->link}}">
                                             </div>
                                          </li>
                                          @endforeach
                                          <?php $count = (5- count($shopSlide->bannerDetail)); ?>   
                                       @else
                                          <?php $count = 5; ?>                                                                  
                                       @endif
                                       
                                    @endforeach
                                 @else
                                    <?php $count = 5; ?>
                                 @endif
                                 @for($i = 1; $i <= $count;$i++)
                                    <li>
                                       <div class="custom-file addBanner" data-cat-id="{{ $key }}" data-entity-id="{{ \App\Models\EntityTypes::SHOP}}" data-section="home">
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
                                       <div class="custom-file addBanner" data-cat-id="{{ $key }}" data-entity-id="{{ \App\Models\EntityTypes::SHOP}}" data-section="home">
                                          <i class="fa fa-plus"></i>
                                       </div>
                                       <div class="form-group">
                                          <span class="badge badge-primary">Info</span>
                                       </div>
                                    </li>
                                 @endfor 
                              @endif
                              <a href="javascript:void(0);" data-cat-id="{{ $key }}" data-entity-id="{{ \App\Models\EntityTypes::SHOP}}" data-section="home" class="add_button btn btn-primary" title="Add field">Add More</a>
                           </ul>
                        </div>
                     </div>
                  </div>
                  @endforeach
                  @foreach($communityCategory as $key => $value)
                  <div class="tab-pane fade" id="list-{{$countryData->id}}-{{$key}}">
                     <div class="card">
                        <div class="card-header justify-content-between">
                           <h4>Slide Show</h4>
                           <div class="custom-checkbox custom-control">
                           <?php 
                              $checked = '';
                              if(count($communitySlides) > 0 && !empty($communitySlides) && $communitySlides->contains('category_id', $key) && $communitySlides->where('category_id', $key)->where('country_code',$countryData->code)->where('is_random', 1)->count() >= 1) {
                                 $checked = "checked";
                              }
                           ?>
                              <input type="checkbox" class="custom-control-input is-random-check" id="checkbox-{{$key}}" {{$checked}} data-cat-id="{{ $key }}" data-entity-id="{{ \App\Models\EntityTypes::COMMUNITY}}" data-section="home">
                              <label for="checkbox-{{$key}}" class="custom-control-label"> Side mix and random</label>
                           </div>
                        </div>
                        <div class="card-body">
                           <ul class="slide-show-list">
                              @if(count($communitySlides) > 0 && !empty($communitySlides))
                                 @if ($communitySlides->contains('category_id', $key) && $communitySlides->contains('country_code', $countryData->code))
                                    @foreach($communitySlides as $communitySlide )
                                       @if(!empty($communitySlide) && $communitySlide->category_id == $key && count($communitySlide->bannerDetail) > 0 && $communitySlide->country_code == $countryData->code)
                                          @foreach($communitySlide->bannerDetail as $detail)
                                          <li>
                                             <div class="custom-file custom-file-img">
                                                <img src="{{$detail->image_url}}" alt="img01" class="img-fluid">
                                             </div>                                             
                                             <div class="form-group">
                                                <span class="badge badge-primary">Info</span>
                                                <a href='javascript:void(0)' role='button' onclick="editPosts({{$detail->id}})" class="badge badge-primary mr-1" >Edit</a>
                                                <a href='javascript:void(0)' role='button' onclick="deletePost({{$detail->id}})" class="badge badge-primary mr-1" >Delete</a>
                                                <input type="text" class="form-control" readOnly value="{{$detail->link}}">
                                             </div>
                                          </li>
                                          @endforeach
                                          <?php $count = (5- count($communitySlide->bannerDetail)); ?>   
                                       @else
                                          <?php $count = 5; ?>                                                                   
                                       @endif
                                    @endforeach
                                 @else
                                    <?php $count = 5; ?>
                                 @endif
                                    @for($i = 1; $i <= $count;$i++)
                                       <li>
                                          <div class="custom-file addBanner" data-cat-id="{{ $key }}" data-entity-id="{{ \App\Models\EntityTypes::COMMUNITY}}" data-section="home">
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
                                       <div class="custom-file addBanner" data-cat-id="{{ $key }}" data-entity-id="{{ \App\Models\EntityTypes::COMMUNITY}}" data-section="home">
                                          <i class="fa fa-plus"></i>
                                       </div>
                                       <div class="form-group">
                                          <span class="badge badge-primary">Info</span>
                                       </div>
                                    </li>
                                 @endfor 
                              @endif
                              <a href="javascript:void(0);" data-cat-id="{{ $key }}" data-entity-id="{{ \App\Models\EntityTypes::COMMUNITY}}" data-section="home" class="add_button btn btn-primary" title="Add field">Add More</a>
                           </ul>
                        </div>
                     </div>
                  </div>
                  @endforeach
               </div>
            </div>
         </div>         
      </div>  
      <div class="col-md-6 country-div country-post country-{{$countryData->id}}">    
         @include('admin.top-post.profile-posts')
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
<script src="{!! asset('js/pages/top-post/index.js') !!}"></script>
@endsection

