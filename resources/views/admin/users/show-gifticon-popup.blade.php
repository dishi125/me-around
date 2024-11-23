<div class="modal-dialog" style="max-width: 40%;">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            @if(isset($gifticon))
                <h5>Edit Gifticon</h5>
            @else
                <h5>Giving Gifticon</h5>
            @endif
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">
            <div class="row align-items-xl-center mb-3">
                <div class="w-100" id="myTabContent2">
                    @if(isset($gifticon))
                    <form id="editgifticon" class="m-auto" style="width: 90%;" method="POST" action="{{route('admin.gifticon.update', [$gifticon->id])}}" enctype="multipart/form-data">
                    @else
                    <form id="storegifticon" class="m-auto" style="width: 90%;" method="POST" action="{{route('admin.gifticon.store')}}" enctype="multipart/form-data">
                    @endif
                        <input type="hidden" name="user_id" value="{{$id}}" />
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mt-3">
                                    <label for="gifticon-textarea"> Title </label>
                                    <textarea name="title" required id="gifticon-textarea" class="gifticon-textarea form-control" rows="6">{{ isset($gifticon) ? $gifticon->title : '' }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="d-flex align-items-center">
                                    <div id="image_preview_gifticon" class="d-flex flex-wrap">
                                        @if(isset($gifticon) && isset($gifticon->attachments->first()->attachment_item))
                                            <div class="removeImage"><span fieldname="gifticon_images" data-timestemp="" class="pointer" data-imageid="{{ $gifticon->attachments->first()->id }}"><i class="fa fa-times-circle fa-2x"></i></span><div style="background-image: url('{{ $gifticon->attachments->first()->image_url }}')" class="bgcoverimage"><img src="{{ asset('img/noImage.png') }}"></div></div>
                                        @endif
                                    </div>
                                </div>
                                <div class="add-image-icon  mt-2" style="@if(isset($gifticon) && isset($gifticon->attachments->first()->attachment_item)) display:none; @else display:flex; @endif" >
                                    {{ Form::file('gifticon_images',[ 'accept'=>"image/*", 'onchange'=>"imagesPreview(this, '#image_preview_gifticon', 'gifticon_images');", 'class' => 'main_image_file form-control', "multiple" => false, 'id' => "gifticon_images", 'hidden' => 'hidden' ]) }}
                                    <label class="pointer gifticon_images" for="gifticon_images"><i class="fa fa-plus fa-4x"></i></label>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Close</button>
                        <button type="submit" id="saveShopgifticon" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="cover-spin"></div>

