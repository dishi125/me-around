@extends('business-layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
@endsection

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
<div class="section-header-button">
    <a href="javascript:void(0);" onClick="$('#createPostModel').modal('show');" class="btn btn-primary">Add New</a>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                @if($category)
                <ul class="nav nav-pills mb-4" id="myTab3" role="tablist">
                    @foreach($category as $key => $cat)
                    <li class="nav-item mr-3">
                        <a class="nav-link btn btn-primary filterButton {!! ($key == 0) ? 'active' : '' !!}" id="all-data" data-filter="{{$cat->id}}" data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="true">{{$cat->name}}</a>
                    </li>
                    @endforeach
                </ul>
                @endif

                <div class="tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="all-table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>User</th>
                                        <th>View Count</th>
                                        <th>Comment Count</th>
                                        <th>Time ago</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="cover-spin"></div>
@endsection

<div class="modal fade" id="createPostModel" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5>Create Community</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            {!! Form::open(['route' => 'business.community.store', 'id' =>"communitypostform", 'enctype' => 'multipart/form-data']) !!}
            @csrf
            <div class="card">
                <div class="modal-body justify-content-center">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('title', __(Lang::get('forms.posts.title'))); !!}
                                    {!! Form::text('title', '', ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.posts.title')) ]); !!}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('title', __(Lang::get('forms.posts.category'))); !!}
                                    {{ Form::select('category_id', $categorySelect, '', ['id' => 'category_id', 'class' => 'form-control', 'placeholder' => __(Lang::get('forms.posts.category'))]) }}
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    {!! Form::label('description', __(Lang::get('forms.posts.description'))); !!}
                                    {!! Form::textarea('description', '', ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.posts.description')) ]); !!}
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="name">{!! __(Lang::get('forms.posts.image')); !!}</label>
                                    <div class="d-flex align-items-center">
                                        <div id="image_preview">

                                        </div>
                                        <div class="add-image-icon">
                                            {{ Form::file("main_image",[ 'accept'=>"image/jpg,image/png,image/jpeg", 'onchange'=>"imagesPreview(this, '#image_preview');", 'class' => 'main_image_file form-control', 'multiple' => 'multiple', 'id' => "main_image", 'hidden' => 'hidden' ]) }}
                                            <label class="pointer" for="main_image"><i class="fa fa-plus fa-4x"></i></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary communityButton">
                        {{ __(Lang::get('general.save')) }}
                    </button>
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">
                        {{ __(Lang::get('general.cancel')) }}
                    </button>
                </div>
            </div>
            {!! Form::close() !!}
        </div>
    </div>

</div>

@section('scripts')
<script>
    var allUserTable = "{{ route('business.community.all.table') }}";
    var noImage = "{{ asset('img/noImage.png') }}";
    var csrfToken = "{{csrf_token()}}";
    
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/business/community/index.js') !!}"></script>
@endsection