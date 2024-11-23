@extends('layouts.app')

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
<link rel="stylesheet" href="{!! asset('css/chocolat.css') !!}">
@endsection

@section('content')
<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-12 mt-sm-3 pt-3">
            <div class="card">
                <div class="card-header">
                    <h3>Review Details</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-md-6 col-12">
                            <label>User Name</label>
                            <input type="text" class="form-control" value="{{$reviews->username}}" readonly>
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label>Review Description</label>
                            <textarea class="form-control" readonly>{{$reviews->review_comment}}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            @if($reviews->comments->toArray()['total'])
            <div class="card">
                <div class="card-header">
                    <h3>Review Comments</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="review-comments col-12">
                            <ul>
                                @foreach($reviews->comments as $comment)
                                <li>
                                    <div class="review-comments-detail">
                                        <div class="user-img" style="background-image: url({{$comment->user_avatar}});">
                                            <img src="{!! asset('img/avatar/avatar-1.png') !!}" alt="{{$comment->user_name}}" />
                                        </div>
                                        <div class="review-comments-text">
                                            <div class="user-name">
                                                <strong>{{$comment->user_name}}</strong> <span>{{$comment->comment_time}}</span>
                                            </div>
                                            <div class="user-comment">
                                                {{$comment->comment}}
                                            </div>
                                            <div class="comments-meta">
                                                <i class="fas fa-thumbs-up"></i> <span>{{$comment->likes_count}}</span>
                                                <i class="fas fa-comments"></i><span>{{$comment->comments_count}}</span>
                                            </div>
                                        </div>
                                    </div>
                                    @if($comment->comments_reply->toArray())
                                    <ul class="reply-detail">
                                        @foreach($comment->comments_reply as $replyComment)
                                        <li>
                                            <div class="review-comments-detail">
                                                <div class="user-img" style="background-image: url({{$replyComment->user_avatar}});">
                                                    <img src="{!! asset('img/avatar/avatar-1.png') !!}" alt="{{$replyComment->user_name}}" />
                                                </div>
                                                <div class="review-comments-text">
                                                    <div class="user-name">
                                                        <strong>{{$replyComment->user_name}}</strong> <span>{{$replyComment->comment_time}}</span>
                                                    </div>
                                                    <div class="user-comment">
                                                        {{$replyComment->comment}}
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
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
</div>
</div>
</div>
@endsection

@section('scripts')
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/chocolat.js') !!}"></script>

@endsection