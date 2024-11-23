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
      <div class="col-md-12">
         <div class="row">
            <div class="col-md-6 mb-3">
               <select class="form-control"  id="hospital-post-list" name="hospital-post-list">
                  <option value="all">All</option>                    
                     @foreach($hospitalParentCategory as $parentCategory)
                        <optgroup label="{{$parentCategory->name}}">
                           @foreach($hospitalCategory as $category)
                              @if(!empty($category))
                                 @if($parentCategory->id == $category->parent_id)
                                 <option                                
                                       value="{{ $category->id }}">{{$category->name}}
                                 </option>
                                 @endif
                              @endif
                           @endforeach
                        </optgroup>                           
                     @endforeach
               </select>               
            </div>
         </div>
         <div class="row">
            <div class="col-md-6 block" id="post-list-all">
               <div class="card">
                  <div class="card-body">
                     <ul class="nav nav-pills mb-4">
                        <li class="nav-item">
                           <a class="nav-link active show" data-toggle="list" href="#all-list-home" >Home</a>
                        </li>
                        <li class="nav-item">
                           <a class="nav-link" data-toggle="list" href="#all-list-top">Top</a>
                        </li>
                     </ul>
                     <div class="tab-content">
                        <div class="tab-pane fade active show" id="all-list-home">
                           <div class="tab-pane fade active show droppable-area ui-widget-content ui-state-default" id="all-list-home-drop" data-section="home" data-category="0">
                              <h4> Drop Items </h4>
                              <ul class='ui-helper-reset' id="ul-all-list-home-drop">
                                 @foreach($allHomeHospitalPost as $post)
                                 <li class="treatment-list " id="{{$post->id}}">
                                    <div class="treatment-img">
                                       <img src="{{$post->thumbnail_url}}" class="img-fluid" alt="treatment-img">
                                    </div>
                                    <div class="treatment-text">
                                       <p>{{$post->location->city_name}}. {{$post->hospital_name}}</p>
                                       <h4>{{$post->title}}</h4>
                                       <p>{{$post->sub_title}}</p>
                                       <p class="star-rating"><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star-half-alt"></i></p>
                                       <h4>
                                          <span class="percentage">{{$post->discount_percentage}}%</span>
                                          <span class="price">{{$post->before_price}}USD</span>
                                          <span class="original-price">{{$post->final_price}}USD</span>
                                       </h4>
                                    </div>
                                    <a href="javascript:void(0);" title="Delete this item" class="ui-icon ui-icon-trash delete-hospital-post">Delete item</a>
                                 </li>
                                 @endforeach   
                              </ul> 
                           </div>
                           <a href="javascript:void(0);" data-section="home" data-category="0" class="add-hospital-post btn btn-primary mt-2 float-right" title="Add field">Save</a>
                        </div>
                        <div class="tab-pane fade" id="all-list-top" >
                           <div class="tab-pane fade droppable-area ui-widget-content ui-state-default" id="all-list-top-drop" data-section="top" data-category="0">
                              <h4> Drop Items</h4>
                              <ul class='ui-helper-reset' id="ul-all-list-top-drop">
                                 @foreach($allTopHospitalPost as $post)
                                 <li class="treatment-list " id="{{$post->id}}">
                                    <div class="treatment-img">
                                       <img src="{{$post->thumbnail_url}}" class="img-fluid" alt="treatment-img">
                                    </div>
                                    <div class="treatment-text">
                                       <p>{{$post->location->city_name}}. {{$post->hospital_name}}</p>
                                       <h4>{{$post->title}}</h4>
                                       <p>{{$post->sub_title}}</p>
                                       <p class="star-rating"><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star-half-alt"></i></p>
                                       <h4>
                                          <span class="percentage">{{$post->discount_percentage}}%</span>
                                          <span class="price">{{$post->before_price}}USD</span>
                                          <span class="original-price">{{$post->final_price}}USD</span>
                                       </h4>
                                    </div>
                                    <a href="javascript:void(0);" title="Delete this item" class="ui-icon ui-icon-trash delete-hospital-post">Delete item</a>
                                 </li>
                                 @endforeach   
                              </ul> 
                           </div>
                           <a href="javascript:void(0);" data-section="top" data-category="0"  class="add-hospital-post btn btn-primary mt-2 float-right" title="Add field">Save</a>
                        </div>
                  </div>
                  </div>               
               </div>
            </div>
            @foreach($hospitalCategory as $category)
               <div class="col-md-6 block" id="post-list-{{$category->id}}">
                  <div class="card">
                     <div class="card-body">
                        <ul class="nav nav-pills mb-4">
                           <li class="nav-item">
                              <a class="nav-link active show" data-toggle="list" href="#list-home-{{$category->id}}" id="#list-home-{{$category->id}}-drop">Home</a>
                           </li>
                           <li class="nav-item">
                              <a class="nav-link" data-toggle="list" href="#list-top-{{$category->id}}" id="#list-top-{{$category->id}}">Top</a>
                           </li>
                        </ul>
                        <div class="tab-content">
                           <div class="tab-pane fade active show" id="list-home-{{$category->id}}">
                              <div class="droppable-area ui-widget-content ui-state-default" data-section="home" data-category="{{$category->id}}" id="list-home-{{$category->id}}-drop">
                                 <h4> Drop Items</h4>
                                 <ul class='ui-helper-reset' id="ul-list-home-{{$category->id}}-drop">
                                    @foreach($categoryHospitalPost as $post)
                                       @if($post->sliderCategoryId == $category->id && $post->sliderSection == 'home')
                                          <li class="treatment-list " id="{{$post->id}}">
                                             <div class="treatment-img">
                                                <img src="{{$post->thumbnail_url}}" class="img-fluid" alt="treatment-img">
                                             </div>
                                             <div class="treatment-text">
                                                <p>{{$post->location->city_name}}. {{$post->hospital_name}}</p>
                                                <h4>{{$post->title}}</h4>
                                                <p>{{$post->sub_title}}</p>
                                                <p class="star-rating"><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star-half-alt"></i></p>
                                                <h4>
                                                   <span class="percentage">{{$post->discount_percentage}}%</span>
                                                   <span class="price">{{$post->before_price}}USD</span>
                                                   <span class="original-price">{{$post->final_price}}USD</span>
                                                </h4>
                                             </div>
                                             <a href="javascript:void(0);" title="Delete this item" class="ui-icon ui-icon-trash delete-hospital-post">Delete item</a>
                                          </li>
                                       @endif
                                    @endforeach   
                                 </ul> 
                              </div>
                              <a href="javascript:void(0);" data-category="{{$category->id}}" data-section="home" class="add-hospital-post btn btn-primary mt-2 float-right" title="Add field">Save</a>
                           </div>
                           <div class="tab-pane fade " id="list-top-{{$category->id}}">
                              <div class="droppable-area ui-widget-content ui-state-default" data-section="top" data-category="{{$category->id}}" id="list-top-{{$category->id}}-drop">
                                 <h4> Drop Items</h4>
                                 <ul class='ui-helper-reset' id="ul-list-top-{{$category->id}}-drop">
                                    @foreach($categoryHospitalPost as $post)
                                       @if($post->sliderCategoryId == $category->id && $post->sliderSection == 'top')
                                          <li class="treatment-list " id="{{$post->id}}">
                                             <div class="treatment-img">
                                                <img src="{{$post->thumbnail_url}}" class="img-fluid" alt="treatment-img">
                                             </div>
                                             <div class="treatment-text">
                                                <p>{{$post->location->city_name}}. {{$post->hospital_name}}</p>
                                                <h4>{{$post->title}}</h4>
                                                <p>{{$post->sub_title}}</p>
                                                <p class="star-rating"><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star-half-alt"></i></p>
                                                <h4>
                                                   <span class="percentage">{{$post->discount_percentage}}%</span>
                                                   <span class="price">{{$post->before_price}}USD</span>
                                                   <span class="original-price">{{$post->final_price}}USD</span>
                                                </h4>
                                             </div>
                                             <a href="javascript:void(0);" title="Delete this item" class="ui-icon ui-icon-trash delete-hospital-post">Delete item</a>
                                          </li>
                                       @endif
                                    @endforeach   
                                 </ul>
                              </div>
                              <a href="javascript:void(0);" data-category="{{$category->id}}" data-section="top" class="add-hospital-post btn btn-primary mt-2 float-right" title="Add field">Save</a>
                           </div>
                     </div>
                     </div>               
                  </div>
               </div>
            @endforeach
            @include('admin.top-post.hospital-posts')
         </div>
      </div>
   </div>
</div>
<div class="cover-spin"></div>

@endsection
@section('scripts')
<script>  
   var updateHopitalPost = "{{ route('admin.top-post.update.hospital-post') }}";
   var getEvents = "{{ route('admin.top-post.get.events') }}";
   var csrfToken = "{{csrf_token()}}";  
   var hospitalCategoryArray = <?php echo json_encode($hospitalCategory) ?>;
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('plugins/owlcarousel2/dist/owl.carousel.min.js') !!}"></script>
<script src="{!! asset('plugins/jquery-ui/jquery-ui.min.js') !!}"></script>
<script src="{!! asset('js/pages/top-post/index-hospital.js') !!}"></script>
<script>

</script>
@endsection

