<style>
    .selectformtd .select2-container .select2-selection--single .select2-selection__rendered {
        padding-top: 2px !important;
    }
</style>
<div class="modal-dialog " style="max-width: 70%">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Client Shops</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center pb-0">
            <div class="row align-items-xl-center mb-3">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th class="mr-3">&nbsp;</th>
                                <th class="mr-3">Activate Name</th>
                                <th class="mr-3">Shop Name</th>
                                <th class="mr-3">Speciality of</th>
                                <th class="mr-3">Category</th>
                                <th class="mr-3">Rate</th>
                                <th class="mr-3">Import Date</th>
                                <th class="mr-3">Activate</th>
                                <th class="mr-3">QR Code</th>
                                <th class="mr-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($shops as $shop)
                                <tr id="shop_id_{{ $shop->id }}">
                                    <td><a role='button'
                                            href="{{ route('admin.business-client.shop.show', $shop->id) }}"
                                            title='' data-original-title='View'
                                            class='btn btn-primary btn-lg mr-3' data-toggle='tooltip'>See Profile</a>
                                    </td>
                                    <td><button type="button" id="show-profile"
                                            class="btn btn-outline-primary btn-lg">{{ $shop->main_name ? $shop->main_name : '-' }}</button>
                                    </td>
                                    <td><button type="button" id="show-profile"
                                            class="btn btn-outline-primary btn-lg">{{ $shop->shop_name }}</button></td>
                                    <td><button type="button" id="show-profile"
                                            class="btn btn-outline-primary btn-lg">{{ $shop->speciality_of ?? '-' }}</button>
                                    </td>
                                    <?php /* <td><button type="button" id="show-profile" class="btn btn-outline-primary btn-lg">{{$shop->category}}</button></td> */ ?>
                                    @if (in_array($shop->category_id, $checkCustomCategory))
                                        <td class="selectformtd">{!! Form::select('category_select', $shopCustomCategory, $shop->category_id, [
                                            'shop_id' => $shop->id,
                                            'class' => 'form-control selectform',
                                            'id' => 'category_select',
                                        ]) !!}</td>
                                    @else
                                        <td class="selectformtd">{!! Form::select('category_select', $shopCategory, $shop->category_id, [
                                            'shop_id' => $shop->id,
                                            'class' => 'form-control selectform',
                                            'id' => 'category_select',
                                        ]) !!}</td>
                                    @endif
                                    <td class="shops-rate"><button type="button" id="show-profile"
                                            class="btn btn-outline-primary btn-lg">{{ $shop->avg_rating ? $shop->avg_rating : '-' }}</button>
                                    </td>
                                    <td class="shops-date"><button type="button" id="show-profile"
                                            class="btn btn-outline-primary btn-lg">{{ $shop->created_at }}</button></td>
                                    <td>
                                        @if ($shop['status_id'] == \App\Models\Status::ACTIVE)
                                            <span class="badge badge-success">{{ $shop->status_name }}</span>
                                        @elseif($shop['status_id'] == \App\Models\Status::PENDING)
                                            <span class="badge"
                                                style="background-color: #fff700;">{{ $shop->status_name }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $shop->status_name }}</span>
                                        @endif
                                    </td>
                                    <td><a target="_blank"
                                            href="{{ route('shop.qr.code', ['type' => 'shop_detail', 'id' => $shop->id]) }}"
                                            class="btn btn-primary btn-sm">See</a></td>
                                    <td><a class="btn btn-secondary p-1" shopid="{{ $shop->id }}"
                                            ajaxurl="{{ route('admin.business-client.remove.shop', [$shop->id]) }}"
                                            id="delete_user_shop"><i style="font-size: 20px;"
                                                class="fas fa-window-close d-block"></i></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row align-items-xl-center">
                <div class="col text-center">
                    <a href="javascript:void(0)" role="button" onclick="addShopProfile({{$id}})" class="btn btn-primary btn-lg" data-toggle="tooltip" data-original-title=""><i class="fa fa-plus" style="font-size: 20px;"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>
