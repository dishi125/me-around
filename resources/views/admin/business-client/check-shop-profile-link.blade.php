<style>
.selectformtd .select2-container .select2-selection--single .select2-selection__rendered {
    padding-top: 2px !important;
}
.table-responsive button#show-profile {
    width: 220px;
}

#profileLinkModal table tr td{
    text-align: center;
}
</style>
<div class="modal-dialog modal-lg" style="max-width: 50%;">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Client Shops</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">        
            <div class="row align-items-xl-center mb-3">
                <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th class="mr-3">Activate Name</th>
                            <th class="mr-3">Shop Name</th>
                            <th class="mr-3">Speciality of</th>
                            <th class="mr-3">Shop Profile</th>
                        </tr>                        
                    </thead>
                    <tbody>
                    @foreach($shops as $shop)
                        <tr id="shop_id_{{$shop->id}}">
                            <td><button type="button" id="show-profile" class="btn btn-outline-primary btn-lg">{{$shop->main_name ? $shop->main_name : '-'}}</button></td>
                            <td><button type="button" id="show-profile" class="btn btn-outline-primary btn-lg">{{$shop->shop_name}}</button></td>
                            <td><button type="button" id="show-profile" class="btn btn-outline-primary btn-lg">{{$shop->speciality_of ?? '-' }}</button></td>
                            <td>
                                @if($shop->uuid)
                                <a href="javascript:void(0);" onClick="copyTextLink(`{{route('shop.view', $shop->uuid)}}`)" class="btn-sm mx-1 btn btn-primary"><i class="fas fa-copy"></i></a>
                                @else
                                    - 
                                @endif
                            </td>
                        </tr>                        
                    @endforeach                     
                    </tbody>
                </table>     
                </div>                      
            </div>   
        </div>        
    </div>
</div>
