<div class="modal-dialog modal-lg">
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
                            <th class="mr-3">&nbsp;</th>
                            <th class="mr-3">Activate Name</th>
                            <th class="mr-3">Shop Name</th>
                            <th class="mr-3">Category</th>
                            <th class="mr-3">Rate</th>
                            <th class="mr-3">Import Date</th>
                            <th class="mr-3">Activate</th>
                        </tr>                        
                    </thead>
                    <tbody>
                    @foreach($shops as $shop)
                        <tr>
                            <td><a role='button' href="{{route('admin.my-business-client.shop.show', $shop->id)}}" title='' data-original-title='View' class='btn btn-primary btn-lg mr-3' data-toggle='tooltip'>See Profile</a></td>
                            <td><button type="button" id="show-profile" class="btn btn-outline-primary btn-lg">{{$shop->main_name ? $shop->main_name : '-'}}</button></td>
                            <td><button type="button" id="show-profile" class="btn btn-outline-primary btn-lg">{{$shop->shop_name}}</button></td>
                            <td><button type="button" id="show-profile" class="btn btn-outline-primary btn-lg">{{$shop->category}}</button></td>
                            <td class="shops-rate"><button type="button" id="show-profile" class="btn btn-outline-primary btn-lg">{{$shop->avg_rating ? $shop->avg_rating : '-'}}</button></td>
                            <td class="shops-date"><button type="button" id="show-profile" class="btn btn-outline-primary btn-lg">{{$shop->created_at}}</button></td>
                            <td>@if ($shop['status_id'] == \App\Models\Status::ACTIVE) 
                                   <span class="badge badge-success">{{$shop->status_name}}</span>
                                 @else 
                                   <span class="badge badge-secondary">{{$shop->status_name}}</span>
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