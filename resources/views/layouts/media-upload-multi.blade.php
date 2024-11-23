<div class="form-group">
    <label for="name">{!! __(Lang::get('forms.posts.image')); !!}</label>
    <div class="d-flex align-items-center">
        <div id="image_preview">
         @if(isset($imagesData))
         @foreach($imagesData as $photo)
         <div class="removeImage">
            <span class="pointer" data-imageid="{{$photo->id}}"><i class="fa fa-times-circle fa-2x"></i></span>
            <div style="background-image: url({{$photo->image_url}});" class="bgcoverimage">
                <img src="{!! asset('img/noImage.png') !!}">
            </div>
        </div>
        @endforeach
        @endif
        </div>
        <div class="add-image-icon">
            {{ Form::file("main_image",[ 'accept'=>"image/jpg,image/png,image/jpeg", 'onchange'=>"imagesPreview(this, '#image_preview');", 'class' => 'main_image_file form-control', 'multiple' => 'multiple', 'id' => "main_image", 'hidden' => 'hidden' ]) }}
            <label class="pointer" for="main_image"><i class="fa fa-plus fa-4x"></i></label>
        </div>
    </div>
</div>