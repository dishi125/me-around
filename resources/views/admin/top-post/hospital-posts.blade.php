<div class="col-md-6">
   <div class="form-group">
      <input class="form-control" type="text" placeholder="Search Eventname" id="event-search-box">
   </div>
   <div class="card">
      <div class="card-body">
         <div class="hospital-list d-flex owl-carousel">
            @foreach($hospitals as $hospital)
            <div class="hospital-box hospital-item" data-hospital-id="{{$hospital->id}}">
               <img src="{{$hospital->interior_photo_url}}" class="img-fluid" alt="hospital-img">
               <h5>{{$hospital->main_name}}</h5>
            </div>
            @endforeach
         </div>
      </div>
   </div>
   <div class="card" id="events-list-card">
      <ul class="card-body treatment-list-section scrollbar gallery" id="events-list">
         @foreach($posts as $post)
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
         </li>
         @endforeach                     
      </ul>
   </div>
</div>
  