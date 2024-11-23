<div class="row" data-language="{{$id}}">
    <div class="col-md-12">
        <div class="form-group">
            <label for="name">{!! __(Lang::get('forms.posts.main_image')); !!} ({{$text}})</label>
            <div class="d-flex align-items-center">
                <div id="image_preview_{{$id}}">
                    @if(isset($photos))
                        @foreach($photos as $photo)
                            <div class="removeImage">
                                <span class="pointer" data-languageID="{{$photo->post_language_id}}" data-postid="{{$photo->post_id}}" data-imageid="{{$photo->id}}"><i class="fa fa-times-circle fa-2x"></i></span>
                                <div style="background-image: url({{$photo->image}});" class="bgcoverimage">
                                    <img src="{!! asset('img/noImage.png') !!}">
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
                <div class="add-image-icon">
                    {{ Form::file("main_image[$id][]",['accept'=>"image/jpg,image/png,image/jpeg", 'onchange'=>"imagesPreview(this, '#image_preview_".$id."', $id);", 'data-languageid' => $id, 'class' => 'main_image_file form-control', 'multiple' => 'multiple', 'id' => "main_image_$id", 'hidden' => 'hidden' ]) }}
                    <label class="pointer" for="main_image_{{$id}}"><i class="fa fa-plus fa-4x"></i></label>
                </div>
            </div>
            @error('main_image')
            {!! $errors->first('main_image', '<label class="error">:message</label>') !!}
            @enderror
        </div>
    </div>
</div>