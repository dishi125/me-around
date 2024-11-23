<div class="col-md-6 country-div">
   <div class="row top-post-tabs pb-5">
      <div class="col-4">
         <div class="card p-0">
            <div class="card-body p-0">
               <ul class="nav nav-pills flex-column">
                  <li class="nav-item">
                     <a class="nav-link active show" data-toggle="list" href="#list-home-{{$country_id}}">Home</a>
                  </li>
                  <li class="nav-item">
                     <a class="nav-link " data-toggle="list" href="#list-category-{{$country_id}}">Category</a>
                  </li>
                  <li class="nav-item">
                     <a class="nav-link nav-link-title">Beauty</a>
                  </li>
                  <li class="nav-item">
                     <a class="nav-link" data-toggle="list" href="#shop-home-{{$country_id}}">No Category</a>
                  </li>

                  @foreach($beautyCategory as $key => $value)
                  <li class="nav-item">
                     <a class="nav-link" data-toggle="list" href="#list-{{$country_id}}-{{$key}}">{{$value}}</a>
                  </li>
                  @endforeach

                  <li class="nav-item">
                     <a class="nav-link nav-link-title">Community</a>
                  </li>
                  @foreach($communityCategory as $key => $value)
                  <li class="nav-item">
                     <a class="nav-link" data-toggle="list" href="#list-{{$country_id}}-{{$key}}">{{$value}}</a>
                  </li>
                  @endforeach
               </ul>
            </div>
         </div>
      </div>
      <div class="col-8">
         <div class="tab-content">
            <!-- Home Slide -->
            <div class="tab-pane fade active show" id="list-home-{{$country_id}}">
               <div class="card">
                  <div class="card-header justify-content-between">
                     <h4>Slide Show</h4>
                     <div class="custom-checkbox custom-control">
                        <?php
                        $checked = '';
                        if (count($homeSlides) > 0 && !empty($homeSlides) && $homeSlides->contains('category_id', null) && $homeSlides->where('category_id', null)->where('is_random', 1)->count() >= 1) {
                           $checked = "checked";
                        }
                        ?>
                        <input type="checkbox" class="custom-control-input is-random-check" id="checkbox-home" {{$checked}} data-cat-id="0" data-entity-id="0" data-section="home" data-country-code="{{$countryData->code}}">
                        <label for="checkbox-home" class="custom-control-label"> Side mix and random</label>
                     </div>
                  </div>
                  <div class="card-body">
                     <ul class="slide-show-list">
                        @if(count($homeSlides) > 0 && !empty($homeSlides))
                        @if ($homeSlides->contains('category_id', null))
                        @foreach($homeSlides as $homeSlide )
                        @if(!empty($homeSlide) && $homeSlide->category_id == null && count($homeSlide->bannerDetail) > 0 )
                        @foreach($homeSlide->bannerDetail as $detail)
                        <li>
                           <div class="custom-file custom-file-img">
                              <img src="{{$detail->image_url}}" alt="img01" class="img-fluid">
                           </div>

                           <div class="form-group">
                              <span class="badge badge-primary">Info</span>
                              <a href='javascript:void(0)' role='button' onclick="editPosts(`{{$detail->id}}`)" class="aaaaaaaaa badge badge-primary mr-1">Edit</a>
                              <a href='javascript:void(0)' role='button' onclick="deletePost(`{{$detail->id}}`)" class="badge badge-primary mr-1">Delete</a>
                              <input type="text" class="form-control" readOnly value="{{$detail->link}}">
                           </div>
                        </li>
                        @endforeach
                        <?php $count = (5 - count($homeSlide->bannerDetail)); ?>
                        @else
                        <?php $count = 5; ?>
                        @endif
                        @endforeach
                        @else
                        <?php $count = 5; ?>
                        @endif
                        @for($i = 1; $i <= $count;$i++) <li>
                           <div class="custom-file addBanner" data-cat-id="0" data-entity-id="0" data-section="home">
                              <i class="fa fa-plus"></i>
                           </div>
                           <div class="form-group">
                              <span class="badge badge-primary">Info</span>
                           </div>
                           </li>
                           @endfor
                           @else
                           @for($i = 1; $i <= 5;$i++) <li>
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

            <!-- Category Slide -->
            <div class="tab-pane fade" id="list-category-{{$country_id}}">
               <div class="card">
                  <div class="card-header justify-content-between">
                     <h4>Slide Show</h4>
                     <div class="custom-checkbox custom-control">
                        <?php
                        $checked = '';
                        if (count($categorySlides) > 0 && !empty($categorySlides) && $categorySlides->contains('category_id', null) && $categorySlides->where('category_id', null)->where('is_random', 1)->count() >= 1) {
                           $checked = "checked";
                        }
                        ?>
                        <input type="checkbox" class="custom-control-input is-random-check" id="checkbox-home-category" {{$checked}} data-cat-id="0" data-entity-id="0" data-section="category" data-country-code="{{$countryData->code}}">
                        <label for="checkbox-home-category" class="custom-control-label"> Side mix and random</label>
                     </div>
                  </div>
                  <div class="card-body">
                     <ul class="slide-show-list">
                        @if(count($categorySlides) > 0 && !empty($categorySlides))
                        @if ($categorySlides->contains('category_id', null) )
                        @foreach($categorySlides as $homeSlide )
                        @if(!empty($homeSlide) && $homeSlide->category_id == null && count($homeSlide->bannerDetail) > 0 )
                        @foreach($homeSlide->bannerDetail as $detail)
                        <li>
                           <div class="custom-file custom-file-img">
                              <img src="{{$detail->image_url}}" alt="img01" class="img-fluid">
                           </div>

                           <div class="form-group">
                              <span class="badge badge-primary">Info</span>
                              <a href='javascript:void(0)' role='button' onclick="editPosts(`{{$detail->id}}`)" class="badge badge-primary mr-1">Edit</a>
                              <a href='javascript:void(0)' role='button' onclick="deletePost(`{{$detail->id}}`)" class="badge badge-primary mr-1">Delete</a>
                              <input type="text" class="form-control" readOnly value="{{$detail->link}}">
                           </div>
                        </li>
                        @endforeach
                        <?php $count = (5 - count($homeSlide->bannerDetail)); ?>
                        @else
                        <?php $count = 5; ?>
                        @endif
                        @endforeach
                        @else
                        <?php $count = 5; ?>
                        @endif
                        @for($i = 1; $i <= $count;$i++) <li>
                           <div class="custom-file addBanner" data-cat-id="0" data-entity-id="0" data-section="category">
                              <i class="fa fa-plus"></i>
                           </div>
                           <div class="form-group">
                              <span class="badge badge-primary">Info</span>
                           </div>
                           </li>
                           @endfor
                           @else
                           @for($i = 1; $i <= 5;$i++) <li>
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

            <!-- Shop Home Slide -->
            <div class="tab-pane fade" id="shop-home-{{$country_id}}">
               <div class="card">
                  <div class="card-header justify-content-between">
                     <h4>Slide Show</h4>
                     <div class="custom-checkbox custom-control">
                        <?php
                        $checked = '';
                        if (count($shopHomeSlides) > 0 && !empty($shopHomeSlides) && $shopHomeSlides->contains('category_id', null) && $shopHomeSlides->where('category_id', null)->where('is_random', 1)->count() >= 1) {
                           $checked = "checked";
                        }
                        ?>
                        <input type="checkbox" class="custom-control-input is-random-check" id="checkbox-home-no-cat" {{$checked}} data-cat-id="0" data-entity-id="0" data-section="home" data-country-code="{{$countryData->code}}">
                        <label for="checkbox-home-no-cat" class="custom-control-label"> Side mix and random</label>
                     </div>
                  </div>
                  <div class="card-body">
                     <ul class="slide-show-list">
                        @if(count($shopHomeSlides) > 0 && !empty($shopHomeSlides))
                        @if ($shopHomeSlides->contains('category_id', null))
                        @foreach($shopHomeSlides as $shopHomeSlide )
                        @if(!empty($shopHomeSlide) && $shopHomeSlide->category_id == null && count($shopHomeSlide->bannerDetail) > 0 )
                        @foreach($shopHomeSlide->bannerDetail as $detail)
                        <li>
                           <div class="custom-file custom-file-img">
                              <img src="{{$detail->image_url}}" alt="img01" class="img-fluid">
                           </div>

                           <div class="form-group">
                              <span class="badge badge-primary">Info</span>
                              <a href='javascript:void(0)' role='button' onclick="editPosts(`{{$detail->id}}`)" class="badge badge-primary mr-1">Edit</a>
                              <a href='javascript:void(0)' role='button' onclick="deletePost(`{{$detail->id}}`)" class="badge badge-primary mr-1">Delete</a>
                              <input type="text" class="form-control" readOnly value="{{$detail->link}}">
                           </div>
                        </li>
                        @endforeach
                        <?php $count = (5 - count($shopHomeSlide->bannerDetail)); ?>
                        @else
                        <?php $count = 5; ?>
                        @endif
                        @endforeach
                        @else
                        <?php $count = 5; ?>
                        @endif
                        @for($i = 1; $i <= $count;$i++) <li>
                           <div class="custom-file addBanner" data-cat-id="0" data-entity-id="{{ \App\Models\EntityTypes::SHOP}}" data-section="home">
                              <i class="fa fa-plus"></i>
                           </div>
                           <div class="form-group">
                              <span class="badge badge-primary">Info</span>
                           </div>
                           </li>
                           @endfor
                           @else
                           @for($i = 1; $i <= 5;$i++) <li>
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

            <!-- All Category -->
            @foreach($beautyCategory as $key => $value)
            <div class="tab-pane fade" id="list-{{$country_id}}-{{$key}}">
               <div class="card">
                  <div class="card-header justify-content-between">
                     <h4>Slide Show</h4>
                     <div class="custom-checkbox custom-control">
                        <?php
                        $checked = '';
                        if (count($shopSlides) > 0 && !empty($shopSlides) && $shopSlides->contains('category_id', $key) && $shopSlides->where('category_id', $key)->where('is_random', 1)->count() >= 1) {
                           $checked = "checked";
                        }
                        ?>
                        <input type="checkbox" class="custom-control-input is-random-check" id="checkbox-{{$key}}" {{$checked}} data-cat-id="{{ $key }}" data-entity-id="{{ \App\Models\EntityTypes::SHOP}}" data-section="home" data-country-code="{{$countryData->code}}">
                        <label for="checkbox-{{$key}}" class="custom-control-label"> Side mix and random</label>
                     </div>
                  </div>
                  <div class="card-body">
                     <ul class="slide-show-list">
                        @if(count($shopSlides) > 0 && !empty($shopSlides))
                        @if ($shopSlides->contains('category_id', $key) )
                        @foreach($shopSlides as $shopSlide )
                        @if(!empty($shopSlide) && $shopSlide->category_id == $key && count($shopSlide->bannerDetail) > 0 )
                        @foreach($shopSlide->bannerDetail as $detail)
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
                        <?php $count = (5 - count($shopSlide->bannerDetail)); ?>
                        @else
                        <?php $count = 5; ?>
                        @endif

                        @endforeach
                        @else
                        <?php $count = 5; ?>
                        @endif
                        @for($i = 1; $i <= $count;$i++) <li>
                           <div class="custom-file addBanner" data-cat-id="{{ $key }}" data-entity-id="{{ \App\Models\EntityTypes::SHOP}}" data-section="home">
                              <i class="fa fa-plus"></i>
                           </div>
                           <div class="form-group">
                              <span class="badge badge-primary">Info</span>
                           </div>
                           </li>
                           @endfor
                           @else
                           @for($i = 1; $i <= 5;$i++) <li>
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
            <div class="tab-pane fade" id="list-{{$country_id}}-{{$key}}">
               <div class="card">
                  <div class="card-header justify-content-between">
                     <h4>Slide Show</h4>
                     <div class="custom-checkbox custom-control">
                        <?php
                        $checked = '';
                        if (count($communitySlides) > 0 && !empty($communitySlides) && $communitySlides->contains('category_id', $key) && $communitySlides->where('category_id', $key)->where('is_random', 1)->count() >= 1) {
                           $checked = "checked";
                        }
                        ?>
                        <input type="checkbox" class="custom-control-input is-random-check" id="checkbox-{{$key}}" {{$checked}} data-cat-id="{{ $key }}" data-entity-id="{{ \App\Models\EntityTypes::COMMUNITY}}" data-section="home"  data-country-code="{{$countryData->code}}">
                        <label for="checkbox-{{$key}}" class="custom-control-label"> Side mix and random</label>
                     </div>
                  </div>
                  <div class="card-body">
                     <ul class="slide-show-list">
                        @if(count($communitySlides) > 0 && !empty($communitySlides))
                        @if ($communitySlides->contains('category_id', $key) )
                        @foreach($communitySlides as $communitySlide )
                        @if(!empty($communitySlide) && $communitySlide->category_id == $key && count($communitySlide->bannerDetail) > 0 )
                        @foreach($communitySlide->bannerDetail as $detail)
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
                        <?php $count = (5 - count($communitySlide->bannerDetail)); ?>
                        @else
                        <?php $count = 5; ?>
                        @endif
                        @endforeach
                        @else
                        <?php $count = 5; ?>
                        @endif
                        @for($i = 1; $i <= $count;$i++) <li>
                           <div class="custom-file addBanner" data-cat-id="{{ $key }}" data-entity-id="{{ \App\Models\EntityTypes::COMMUNITY}}" data-section="home">
                              <i class="fa fa-plus"></i>
                           </div>
                           <div class="form-group">
                              <span class="badge badge-primary">Info</span>
                           </div>
                           </li>
                           @endfor
                           @else
                           @for($i = 1; $i <= 5;$i++) <li>
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

<div class="col-md-6 country-div ">    
   @include('admin.top-post-new.profile-posts')
</div>