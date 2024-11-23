<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Edit Post</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <!-- <form name="add-post-form" id="add-post-form" enctype="multipart/form-data">      -->
        <div class="modal-body justify-content-center">   
            <div class="row align-items-xl-center mb-3">           
                <div class="col-md-12">
                    <img src="{{ $bannerImage->image_url }}" style="width:100px; height:100px" />
                </div>
                            
            </div>       
            <div class="row align-items-xl-center mb-3">           
                <div class="col-md-4">
                    <label>Image</label>
                </div>
                <div class="col-md-8">
                    <input type="file" id="post-image" class="form-control" accept="image/*" required>
                    <label id="image-error" class="error-msg" for="post-image"></label>
                    <input name="banner-image-id" id="banner-image-id" type="hidden" value="{{$bannerImage->id}}">                    
                </div>                
            </div>       
            <div class="row align-items-xl-center mb-3">           
                <div class="col-md-4">
                    <label>Link</label>
                </div>
                <div class="col-md-8">
                    <input type="text" id="post-link" placeholder="type link or suggest category"  class="form-control" value="{{$bannerImage->link}}">
                </div>                
            </div>    
            @if($is_popup == 0)  
            <div class="row align-items-xl-center mb-3">           
                <div class="col-md-4">
                    <label>Slider Duration (in seconds)</label>
                </div>
                <div class="col-md-8">
                    <input type="number" id="post-slide-duration" value="{{$bannerImage->slide_duration}}" class="form-control">
                </div>                
            </div>       
            <div class="row align-items-xl-center mb-3">           
                <div class="col-md-4">
                    <label>Display Order</label>
                </div>
                <div class="col-md-8">
                    <input type="number" id="post-display-order" value="{{$bannerImage->order}}" class="form-control">
                </div>                
            </div> 
            @endif  
            
            @if($is_popup == 1)  
            <div class="row align-items-xl-center mb-3">           
                <div class="col-md-4">
                    <label>From Date</label>
                </div>
                <div class="col-md-8">
                    <input type="date" id="from-date" min="{{$today}}" value="{{$bannerImage->from_date}}" class="form-control">
                </div>                
            </div>       
            <div class="row align-items-xl-center mb-3">           
                <div class="col-md-4">
                    <label>To Date</label>
                </div>
                <div class="col-md-8">
                    <input type="date" id="to-date" min="{{$today}}" value="{{$bannerImage->to_date}}" class="form-control">
                </div>                
            </div>  
            @endif  
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" id="edit-post-btn">Save</button>
            <button type="button" class="btn btn-danger" data-dismiss="modal">{!! __(Lang::get('general.close')) !!}</button>
        </div>
        <!-- </form> -->
    </div>
</div>