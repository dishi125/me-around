@if(count($bannerImages))
    <div class="community-slider-outer">
        <div class="community-slider">
            @foreach($bannerImages as $banner)
                <div class="slide-background"
                     style="background-image: url('{{Storage::disk('s3')->url($banner->image)}}')">
                    <img src="{{Storage::disk('s3')->url($banner->image)}}"/>
                </div>
            @endforeach
        </div>
    </div>
@endif
<div class="community-data pt-4">
    @if(count($communityData))
        @foreach($communityData as $key => $community)
            <div class="item-outer">
                <div class="item d-inline-flex pointer" onclick="loadCommunityDetail(`{{route('admin.user.community.detail',['id' => $id, 'community_id' => $community->id])}}`);">
                    <div class="left-content">
                        @if($key+1 < 4)
                            <div style="background-image: url('{{asset('img/king_icon.png')}}')"
                                 class="counter-img"> {{$key+1}}</div>
                        @else
                            <div class="counter"> {{$key+1}}</div>
                        @endif
                    </div>
                    <div class="right-content">
                        <div class="title">
                            {{$community->title}}
                        </div>
                        <div class="data d-flex">
                            <div class="category mr-3">
                                {{$community->category_name}}
                            </div>
                            <div class="username ml-2 mr-3">
                                {{$community->user_name}}
                            </div>
                            <div class="timeAgo ml-2">
                                <i class="fas fa-stopwatch mr-1"></i> {{$community->time_difference}}
                            </div>
                            <div class="view ml-2">
                                <i class="far fa-eye mr-1"></i> {{$community->views_count}}
                            </div>
                            <div class="comment ml-2">
                                <i class="fas fa-comment-alt mr-1"></i> {{$community->comments_count}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        No Community found.
    @endif
</div>

