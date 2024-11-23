<div class="pl-4 pt-4 pr-4">
    <div class="review-comments">
        <div class="review-comments-detail ">
            <div class="user-img" style="background-image: url({{$community->user_avatar}});">
                <img src="{!! asset('img/avatar/avatar-1.png') !!}" alt="{{$community->user_name}}"/>
            </div>
            <div class="review-comments-text pt-0">
                <div class="name">
                    <div><strong>{{$community->user_name}}</strong></div>
                    <div>{{ date('Y/m/d H:i A',strtotime($community->created_at))}}</div>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="user_id" value="{{$id}}"/>

    @if($community->images && count($community->images))
        <div class="detail-slider pt-3">
            @foreach($community->images as $image)
                <div class="slide-background"
                     style="background-image: url('{{$image->image}}')">
                    <img
                        src="{{$image->image}}"/>
                </div>
            @endforeach
        </div>
    @endif

    @if($community->description)
        <div class="pt-2">
            <p>{{$community->description}}</p>
        </div>
    @endif

    <div class="detail-outer pt-3">
        <div class="d-flex community-count-detail">
            <div>
                LIKE {{$community->likes_count}}
            </div>
            <div>
                Comment {{$community->comments_count}}
            </div>
            <div>
                view {{$community->views_count}}
            </div>
        </div>
    </div>

    <div class="like-outer">
        <div class="d-flex community-count-detail">
            <div class="pointer"
                 onclick="likeByAdmin(`{{route('admin.user.community.like',['community_id' => $community->id])}}`,{{$community->id}},'community',{{$community->is_liked}});">
                <i class="fas fa-thumbs-up"
                   style="font-size: 30px; {{$community->is_liked == true ? 'color:#ea4c89;' : ''}}"></i>
            </div>
            <div class="pointer" onclick="focusOnComment(0,'no');">
                <i class="far fa-comment-alt" style="font-size: 30px;"></i>
            </div>
        </div>
    </div>

   
    <div class="community-comments review-comments">
        <ul>
            @foreach($community->comments as $comment)

                <li>
                    <div class="review-comments-detail">
                        <div class="user-img" style="background-image: url({{$comment->user_avatar}});">
                            <img src="{!! asset('img/avatar/avatar-1.png') !!}" alt="{{$comment->user_name}}"/>
                        </div>
                        <div class="review-comments-text">
                            <div class="name">
                                <strong>{{$comment->user_name}}</strong> <span>{{$comment->comment_time}}</span>
                            </div>
                            <div class="user-comment">
                                {{$comment->comment}}
                            </div>
                            <div class="comments-meta">
                                <span class="pointer"
                                      onclick="likeByAdmin(`{{route('admin.user.community.like',['community_id' => $community->id])}}`,{{$comment->id}},'comment',{{$comment->is_like}});"><i
                                        class="fas fa-thumbs-up"
                                        style="{{$comment->is_like == true ? 'color:#ea4c89;' : ''}}"></i> {{$comment->likes_count}}</span>
                                <span class="pointer" onclick="focusOnComment({{$comment->id}},'no');">Comment  {{$comment->comments_count}}</span>
                            </div>
                        </div>
                    </div>
                    @if($comment->comments_reply->toArray())
                        <ul class="reply-detail">
                            @foreach($comment->comments_reply as $replyComment)
                                <li>
                                    <div class="review-comments-detail">
                                        <div class="user-img"
                                             style="background-image: url({{$replyComment->user_avatar}});">
                                            <img src="{!! asset('img/avatar/avatar-1.png') !!}"
                                                 alt="{{$replyComment->user_name}}"/>
                                        </div>
                                        <div class="review-comments-text">
                                            <div class="name">
                                                <strong>{{$replyComment->user_name}}</strong>
                                                <span>{{$replyComment->comment_time}}</span>
                                            </div>
                                            <div class="user-comment">
                                                {{$replyComment->comment}}
                                            </div>
                                            <div class="comments-meta">
                                                <span class="pointer"
                                                      onclick="likeByAdmin(`{{route('admin.user.community.like',['community_id' => $community->id])}}`,{{$replyComment->id}},'comment',{{$replyComment->is_like ? 1 : 0}},'true');"><i
                                                        class="fas fa-thumbs-up mr-3"
                                                        style="{{$replyComment->is_like == true ? 'color:#ea4c89;' : ''}}"></i></span>
                                                <span class="pointer" onclick="focusOnComment({{$replyComment->id}},{{$comment->id}});">Comment</span>
                                            </div>
                                        </div>
                                    </div>
                                    @if($replyComment->comments_reply && $replyComment->comments_reply->toArray())
                                        <ul class="reply-detail">
                                            @foreach($comment->comments_reply as $replyComment)
                                                <li>
                                                    <div class="review-comments-detail">
                                                        <div class="user-img"
                                                             style="background-image: url({{$replyComment->user_avatar}});">
                                                            <img src="{!! asset('img/avatar/avatar-1.png') !!}"
                                                                 alt="{{$replyComment->user_name}}"/>
                                                        </div>
                                                        <div class="review-comments-text">
                                                            <div class="name">
                                                                <strong>{{$replyComment->user_name}}</strong>
                                                                <span>{{$replyComment->comment_time}}</span>
                                                            </div>
                                                            <div class="user-comment">
                                                                {{$replyComment->comment}}
                                                            </div>
                                                            <div class="comments-meta pb-2">
                                                                <?php /*<span class="pointer"
                                                      onclick="likeByAdmin(`{{route('admin.user.community.like',['community_id' => $community->id])}}`,{{$replyComment->id}},'comment',{{$replyComment->is_like ? 1 : 0}},'true');"><i
                                                        class="fas fa-thumbs-up mr-3"
                                                        style="{{$replyComment->is_like == true ? 'color:#ea4c89;' : ''}}"></i></span>
                                                                <span class="pointer" onclick="focusOnComment({{$replyComment->id}});">Comment</span>*/ ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
    <div class="post-comment">

        <form class="comment-form" id="comment-form" method="post" action="{{route('admin.user.community.comment',['community_id' => $community->id])}}">
            <div class="align-items-center d-flex form-group pt-4">
                <input type="hidden" name="entity_id" id="entity_id" value="{{$community->id}}" />
                <input type="hidden" name="type" id="type" value="community" />
                <input type="hidden" name="user_id" id="user_id" value="{{$id}}" />
                <input type="hidden" name="parent_id" id="parent_id" value="0" />
                <input type="hidden" name="is_reply_id" id="is_reply_id" value="0" />
                <input class="form-control" required name="comment" type="text" placeholder="Type here..."/>
                <button type="submit" class="ml-3 pointer border-0 submit-comment">
                    <i class="fas fa-paper-plane" style="font-size: 35px;"></i></button>
            </div>
        </form>
    </div>
</div>
