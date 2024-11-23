<div class="row top-post-tabs">
   <div class="col-4">
      <div class="card p-0">
         <div class="card-body p-0">
            <ul class="nav nav-pills flex-column">
               <li class="nav-item">
                  <a class="nav-link active show" data-toggle="list" href="#profile-list-normal-user-{{$country_id}}">Normal user</a>
               </li>
               <li class="nav-item">
                  <a class="nav-link" data-toggle="list" href="#profile-list-hopsital-{{$country_id}}">Hospital</a>
               </li>

               @foreach($beautyCategory as $key => $value)
               <li class="nav-item">
                  <a class="nav-link" data-toggle="list" href="#profile-list-{{$country_id}}-{{$key}}">{{$value}}</a>
               </li>
               @endforeach
            </ul>
         </div>
      </div>
   </div>
   <div class="col-8">
      <div class="tab-content">
         <div class="tab-pane fade active show" id="profile-list-normal-user-{{$country_id}}">
            <div class="card">
               <div class="card-header justify-content-between">
                  <h4>Profile slide</h4>
                  <div class="custom-checkbox custom-control">
                     <?php
                     $checked = '';
                     if (count($normalUserSlides) > 0 && !empty($normalUserSlides) && $normalUserSlides->where('is_random', 1)->count() >= 1) {
                        $checked = "checked";
                     }
                     ?>
                     <input type="checkbox" class="custom-control-input is-random-check" id="checkbox-normal-user" {{$checked}} data-cat-id="0" data-entity-id="{{ \App\Models\EntityTypes::NORMALUSER}}" data-section="profile" data-country-code="{{$countryData->code}}">
                     <label for="checkbox-normal-user" class="custom-control-label"> Side mix and random</label>
                  </div>
               </div>
               <div class="card-body">
                  <ul class="slide-show-list">
                     @if(count($normalUserSlides) > 0 && !empty($normalUserSlides))
                     @foreach($normalUserSlides as $normalUserSlide )
                     @if(!empty($normalUserSlide) && count($normalUserSlide->bannerDetail) > 0 )
                     @foreach($normalUserSlide->bannerDetail as $detail)
                     <li>
                        <div class="custom-file custom-file-img">
                           <img src="{{$detail->image_url}}" alt="img01" class="img-fluid">
                        </div>

                        <div class="form-group">
                           <span class="badge badge-primary">Info</span>
                           <a href='javascript:void(0)' role='button' onclick="editPosts({{$detail->id}})" class="badge badge-primary mr-1">Edit</a>
                           <a href='javascript:void(0)' role='button' onclick="deletePost({{$detail->id}})" class="badge badge-primary mr-1">Delete</a>
                           <input type="text" class="form-control" readOnly value="{{$detail->link}}">
                        </div>
                     </li>
                     @endforeach
                     <?php $count = (5 - count($normalUserSlide->bannerDetail)); ?>
                     @endif
                     @endforeach
                     @for($i = 1; $i <= $count;$i++) <li>
                        <div class="custom-file addBanner" data-cat-id="0" data-entity-id="{{ \App\Models\EntityTypes::NORMALUSER}}" data-section="profile">
                           <i class="fa fa-plus"></i>
                        </div>
                        <div class="form-group">
                           <span class="badge badge-primary">Info</span>
                        </div>
                        </li>
                        @endfor
                        @else
                        @for($i = 1; $i <= 5;$i++) <li>
                           <div class="custom-file addBanner" data-cat-id="0" data-entity-id="{{ \App\Models\EntityTypes::NORMALUSER}}" data-section="profile">
                              <i class="fa fa-plus"></i>
                           </div>
                           <div class="form-group">
                              <span class="badge badge-primary">Info</span>
                           </div>
                           </li>
                           @endfor
                           @endif
                           <a href="javascript:void(0);" data-cat-id="0" data-entity-id="{{ \App\Models\EntityTypes::NORMALUSER}}" data-section="profile" class="add_button btn btn-primary" title="Add field">Add More</a>
                  </ul>
               </div>
            </div>
         </div>
         <div class="tab-pane fade" id="profile-list-hopsital-{{$country_id}}">
            <div class="card">
               <div class="card-header justify-content-between">
                  <h4>Profile slide</h4>
                  <div class="custom-checkbox custom-control">
                     <?php
                     $checked = '';
                     if (count($hospitalUserSlides) > 0 && !empty($hospitalUserSlides) && $hospitalUserSlides->where('is_random', 1)->count() >= 1) {
                        $checked = "checked";
                     }
                     ?>
                     <input type="checkbox" class="custom-control-input is-random-check" id="checkbox-hospital" {{$checked}} data-cat-id="0" data-entity-id="{{ \App\Models\EntityTypes::HOSPITAL}}" data-section="profile" data-country-code="{{$countryData->code}}">
                     <label for="checkbox-hospital" class="custom-control-label"> Side mix and random</label>
                  </div>
               </div>
               <div class="card-body">
                  <ul class="slide-show-list">

                     @if(count($hospitalUserSlides) > 0 && !empty($hospitalUserSlides))
                     @foreach($hospitalUserSlides as $hospitalUserSlide )
                     @if(!empty($hospitalUserSlide) && count($hospitalUserSlide->bannerDetail) > 0 )
                     @foreach($hospitalUserSlide->bannerDetail as $detail)
                     <li>
                        <div class="custom-file custom-file-img">
                           <img src="{{$detail->image_url}}" alt="img01" class="img-fluid">
                        </div>

                        <div class="form-group">
                           <span class="badge badge-primary">Info</span>
                           <a href='javascript:void(0)' role='button' onclick="editPosts({{$detail->id}})" class="badge badge-primary mr-1">Edit</a>
                           <a href='javascript:void(0)' role='button' onclick="deletePost({{$detail->id}})" class="badge badge-primary mr-1">Delete</a>
                           <input type="text" class="form-control" readOnly value="{{$detail->link}}">
                        </div>
                     </li>
                     @endforeach
                     <?php $count = (5 - count($hospitalUserSlide->bannerDetail)); ?>
                     @endif
                     @endforeach
                     @for($i = 1; $i <= $count;$i++) <li>
                        <div class="custom-file addBanner" data-cat-id="0" data-entity-id="{{ \App\Models\EntityTypes::HOSPITAL}}" data-section="profile">
                           <i class="fa fa-plus"></i>
                        </div>
                        <div class="form-group">
                           <span class="badge badge-primary">Info</span>
                        </div>
                        </li>
                        @endfor
                        @else
                        @for($i = 1; $i <= 5;$i++) <li>
                           <div class="custom-file addBanner" data-cat-id="0" data-entity-id="{{ \App\Models\EntityTypes::HOSPITAL}}" data-section="profile">
                              <i class="fa fa-plus"></i>
                           </div>
                           <div class="form-group">
                              <span class="badge badge-primary">Info</span>
                           </div>
                           </li>
                           @endfor
                           @endif
                           <a href="javascript:void(0);" data-cat-id="0" data-entity-id="{{ \App\Models\EntityTypes::HOSPITAL}}" data-section="profile" class="add_button btn btn-primary" title="Add field">Add More</a>
                  </ul>
               </div>
            </div>
         </div>
         @foreach($beautyCategory as $key => $value)
         <div class="tab-pane fade" id="profile-list-{{$country_id}}-{{$key}}">
            <div class="card">
               <div class="card-header justify-content-between">
                  <h4>Slide Show</h4>
                  <div class="custom-checkbox custom-control">
                     <?php
                     $checked = '';
                     if (count($shopProfileSlides) > 0 && !empty($shopProfileSlides) && $shopProfileSlides->contains('category_id', $key) && $shopProfileSlides->where('category_id', $key)->where('is_random', 1)->count() >= 1) {
                        $checked = "checked";
                     }
                     ?>
                     <input type="checkbox" class="custom-control-input is-random-check" id="checkbox-profile-{{$key}}" {{$checked}} data-cat-id="{{ $key }}" data-entity-id="{{ \App\Models\EntityTypes::SHOP}}" data-section="profile"  data-country-code="{{$countryData->code}}">
                     <label for="checkbox-profile-{{$key}}" class="custom-control-label"> Side mix and random</label>
                  </div>
               </div>
               <div class="card-body">
                  <ul class="slide-show-list">
                     @if(count($shopProfileSlides) > 0 && !empty($shopProfileSlides))
                     @if ($shopProfileSlides->contains('category_id', $key) )
                     @foreach($shopProfileSlides as $shopProfileSlide )
                     @if(!empty($shopProfileSlide) && $shopProfileSlide->category_id == $key && count($shopProfileSlide->bannerDetail) > 0 )
                     @foreach($shopProfileSlide->bannerDetail as $detail)
                     <li>
                        <div class="custom-file custom-file-img">
                           <img src="{{$detail->image_url}}" alt="img01" class="img-fluid">
                        </div>

                        <div class="form-group">
                           <span class="badge badge-primary">Info</span>
                           <a href='javascript:void(0)' role='button' onclick="editPosts({{$detail->id}})" class="badge badge-primary mr-1">Edit</a>
                           <a href='javascript:void(0)' role='button' onclick="deletePost({{$detail->id}})" class="badge badge-primary mr-1">Delete</a>
                           <input type="text" class="form-control" readOnly value="{{$detail->link}}">
                        </div>
                     </li>
                     @endforeach
                     <?php $count = (5 - count($shopProfileSlide->bannerDetail)); ?>
                     @endif
                     @endforeach
                     @else
                     <?php $count = 5; ?>
                     @endif
                     @for($i = 1; $i <= $count;$i++) <li>
                        <div class="custom-file addBanner" data-cat-id="{{ $key }}" data-entity-id="{{ \App\Models\EntityTypes::SHOP}}" data-section="profile">
                           <i class="fa fa-plus"></i>
                        </div>
                        <div class="form-group">
                           <span class="badge badge-primary">Info</span>
                        </div>
                        </li>
                        @endfor
                        @else
                        @for($i = 1; $i <= 5;$i++) <li>
                           <div class="custom-file addBanner" data-cat-id="{{ $key }}" data-entity-id="{{ \App\Models\EntityTypes::SHOP}}" data-section="profile">
                              <i class="fa fa-plus"></i>
                           </div>
                           <div class="form-group">
                              <span class="badge badge-primary">Info</span>
                           </div>
                           </li>
                           @endfor
                           @endif
                           <a href="javascript:void(0);" data-cat-id="{{ $key }}" data-entity-id="{{ \App\Models\EntityTypes::SHOP}}" data-section="profile" class="add_button btn btn-primary" title="Add field">Add More</a>
                  </ul>
               </div>
            </div>
         </div>
         @endforeach
      </div>
   </div>
</div>