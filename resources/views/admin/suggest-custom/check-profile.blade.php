<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Client Shops</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">        
            @foreach($shops as $shop)
            <div class="row align-items-xl-center mb-3">
                <div class="col-md-12">
                    <a role='button' href="{{route('admin.business-client.shop.show', $shop->id)}}" title='' data-original-title='View' class='btn btn-primary btn-lg mr-3' data-toggle='tooltip'>See Profile</a>
                    <button type="button" id="show-profile" class="btn btn-outline-primary btn-lg">{{$shop->name}}</button>                
                </div>                              
            </div>   
            @endforeach                     
        </div>            
    </div>
</div>