@extends('business-layouts.app')
@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection
@section('content')
<!-- Main Content -->
<div class="main-container">
<section class="section">
    <div class="row">
        @if(in_array(\App\Models\EntityTypes::HOSPITAL, $user->all_entity_type_id) || in_array(\App\Models\EntityTypes::SHOP, $user->all_entity_type_id))
            <div class="col-lg-2 col-md-2 col-sm-2 pr-0">
                <div class="card card-statistic-2">
                    <div class="card-stats">
                        <div class="card-stats-title"><h5 class="card-title mb-0">Coin</h5></div>
                    </div>
                    <div class="card-wrap">
                        <div class="card-body">
                            <h4>{{number_format($coin)}}</h4>
                        </div>
                    </div>
                </div>
            </div>

            @if($hospital)
                <div class="col-lg-5 col-md-5 col-sm-5 mb-sm-5">
                    <div class="card">
                        <div class="card-body">
                            <div class="align-items-center d-flex hospital-title justify-content-between">
                                <h5 class="card-title">{{ $hospital->main_name }}</h5>
                                @if($hospital->status_id == 1)
                                    <span class="mb-2 badge badge-success">Activating</span>
                                @else
                                    <span class="mb-2 badge badge-secondary">Deactivate</span>
                                @endif
                            </div>
                            <div class="row">
                                <div class="col-md-4 border-right">
                                    <div class="card-wrap text-center">
                                        <div class="card-stats-title">Completed Work</div>
                                        <h4>{{$hospital->work_complete}}</h4>
                                    </div>
                                </div>
                                <div class="col-md-4 border-right">
                                    <div class="card-wrap text-center">
                                        <div class="card-stats-title">Review</div>
                                        <h4>{{$hospital->reviews}}</h4>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card-wrap text-center">
                                        <div class="card-stats-title">Activating Post</div>
                                        <h4>{{$hospital->activate_post}}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="col-lg-5 col-md-5 col-sm-5 mb-sm-5">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Your Recommended Code : <b style="text-decoration: underline;">{{Auth::user()->recommended_code}}</b></h5>
                        <p class="card-text">You will be rewarded with coins ( When signing up from you per user ) <br/> <b>Reward Coin : {{$recommended_coins}}</b></p>
                    </div>
                </div>
            </div>        
        @endif

    </div>
    
    @if($hospital)
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-5">
            <div class="card">
                <ul class="nav nav-tabs" id="postTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active show" id="active-tab" data-toggle="tab" href="#active" role="tab" aria-controls="active"
                            aria-selected="true">Activating</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="ready-tab" data-toggle="tab" href="#ready" role="tab"
                            aria-controls="ready" aria-selected="false">Ready</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pause-tab" data-toggle="tab" href="#pause" role="tab" aria-controls="pause"
                            aria-selected="false">Pause</a>
                    </li>
                    <a href="{{ route('business.posts.create',['page'=>'dashboard']) }}" class="btn btn-primary m-1 position-absolute" style="right:0;">Add New</a>
                    
                </ul>
                <div class="tab-content pl-4" id="postContent" style="min-height: 530px;">
                    <div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="movies-tab">
                        @if(count($activePosts))
                            @foreach($activePosts as $post)
                                <div class="d-flex mb-3 pb-2" style="border-bottom: solid 1px #80757538;">
                                    <div class="mr-2">
                                        <img src="{{$post->thumbnail_url ? $post->thumbnail_url->image : '' }}" alt="{{$post->title}}" class="requested-client-images m-1" />
                                    </div>
                                    <div>
                                        <div style="line-height: initial;">
                                            {{$post->from_date}} - {{$post->to_date}} <br/>
                                            <strong> {{$post->title}} </strong>
                                        </div> 
                                        <div>
                                            <span class="mr-2">HIT - {{$post->views_count}}</span>
                                            <span class="mr-2">Inquiry - {{$post->request}}</span>
                                            <span class="mr-2">Grade - {{$post->rating}}</span>
                                        </div> 
                                    </div>
                                    <div class="" style="right: 10px; position: absolute; margin-left: auto"><a href="{{ route('business.posts.edit',[$post->id,'page'=>'dashboard']) }}" ><i class="fas fa-edit" style="font-size: 20px;"></i></a></div>
                                </div>
                                
                            @endforeach
                        @else 
                            No Active Post found
                        @endif

                    

                    </div>
                    <div class="tab-pane fade" id="ready" role="tabpanel" aria-labelledby="ready-tab">
                    
                        @if(count($readyPosts))
                            @foreach($readyPosts as $post)
                                <div class="d-flex mb-3 pb-2" style="border-bottom: solid 1px #80757538;">
                                    <div class="mr-2">
                                        <img src="{{$post->thumbnail_url ? $post->thumbnail_url->image : '' }}" alt="{{$post->title}}" class="requested-client-images m-1" />
                                    </div>
                                    <div>
                                        <div style="line-height: initial;">
                                            {{$post->from_date}} - {{$post->to_date}} <br/>
                                            <strong> {{$post->title}} </strong>
                                        </div> 
                                        <div>
                                            <span class="mr-2">HIT - {{$post->views_count}}</span>
                                            <span class="mr-2">Inquiry - {{$post->request}}</span>
                                            <span class="mr-2">Grade - {{$post->rating}}</span>
                                        </div> 
                                    </div>
                                    <div class="" style="right: 10px; position: absolute; margin-left: auto"><a href="{{ route('business.posts.edit',[$post->id,'page'=>'dashboard']) }}" ><i class="fas fa-edit" style="font-size: 20px;"></i></a></div>
                                </div>
                            @endforeach
                        @else 
                            No Ready Post found
                        @endif
                    </div>
                    <div class="tab-pane fade" id="pause" role="tabpanel" aria-labelledby="pause-tab">
                    
                        @if(count($pendingPosts))
                            @foreach($pendingPosts as $post)
                                <div class="d-flex mb-3 pb-2" style="border-bottom: solid 1px #80757538;">
                                    <div class="mr-2">
                                        <img src="{{$post->thumbnail_url ? $post->thumbnail_url->image : '' }}" alt="{{$post->title}}" class="requested-client-images m-1" />
                                    </div>
                                    <div>
                                        <div style="line-height: initial;">
                                            {{$post->from_date}} - {{$post->to_date}} <br/>
                                            <strong> {{$post->title}} </strong>
                                        </div> 
                                        <div>
                                            <span class="mr-2">HIT - {{$post->views_count}}</span>
                                            <span class="mr-2">Inquiry - {{$post->request}}</span>
                                            <span class="mr-2">Grade - {{$post->rating}}</span>
                                        </div> 
                                    </div>
                                    <div class="" style="right: 10px; position: absolute; margin-left: auto"><a href="{{ route('business.posts.edit',[$post->id,'page'=>'dashboard']) }}" ><i class="fas fa-edit" style="font-size: 20px;"></i></a></div>
                                </div>
                            @endforeach
                        @else 
                            No Pause Post found
                        @endif
                </div>
            </div>
        </div>
    </div>
    @endif

  </section>
</div>
@endsection