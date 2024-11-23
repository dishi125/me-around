<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>{{$shop->shop_name}}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">        
            <div class="row align-items-xl-center mb-3">
                <div class="col-md-12">
                    <img src="{{$shopImage->image}}" alt="{{$shop->shop_name}}" style="width:100%;" />
                </div>                              
            </div>   
        </div>        
    </div>
</div>