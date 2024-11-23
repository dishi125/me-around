<div class="modal-dialog" style="max-width: 70%;">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Referral Users</h5>
            @if($referralUser)
                <div class="ml-4 mt-1">
                    <strong>Name:</strong> {{$referralUser->display_name}}
                    <strong class="ml-2">Email:</strong> {{$referralUser->email}}
                    <strong class="ml-2">Number:</strong> <span class="copy_clipboard">{{$referralUser->mobile}}</span>
                </div>
            @endif
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">
            @if ($user_shops && count($user_shops))
                <div class="align-items-center border-bottom d-flex mb-3 pb-2">
                    <h5>Shop Profile :</h5>
                    @foreach ($user_shops as $shop)
                        <a target="_blank" href="{{ route('admin.business-client.shop.show', $shop->id) }}" class='btn btn-primary mb-2 ml-2'>{{$shop->shop_name}}</a>
                    @endforeach
                </div>
            @endif

            <div class="align-items-center border-bottom d-flex mb-3 pb-3">
                <p class="mb-0 pr-3">Total Referral: {{ $cnt_referral }}</p>
                <p class="mb-0 pr-3" id="processed_coffee">Processed Coffee: {{ $processed_coffee }}</p>
                <a target="_blank" class='btn btn-primary ml-2' @if($cnt_not_sent==0) style="pointer-events: none" @endif onclick="updateCoffeecnt({{ $id }})" id="coffee_count">{{ $cnt_not_sent }} Coffee</a>
                <a class="btn btn-primary ml-5" href="javascript:void(0);" onclick="getGifticonModal('{{$id}}');"> Giving Gifticon </a>
                <p class="mb-0 pr-3 pl-3" id="gifticon_cnt">Total Gifticon: {{ count($gifticons) }}</p>
                <a class="btn btn-primary ml-4" href="https://biz.giftishow.com/home/" target="_blank"> Gifticon Website </a>
            </div>

            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="referral-tab" data-toggle="tab" href="#referral-data" role="tab" aria-controls="referral" aria-selected="true">Referral Details</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="gifticon-tab" data-toggle="tab" href="#gifticon-data" role="tab" aria-controls="gifticon" aria-selected="false">Gifticon Details</a>
                </li>
            </ul>
<!--            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="referral" role="tabpanel" aria-labelledby="referral-tab">referral Page</div>
                <div class="tab-pane fade" id="gifticon" role="tabpanel" aria-labelledby="gifticon-tab">gifticon Page</div>
            </div>-->

            <div class="row align-items-xl-center mb-3">
                <div class="w-100 tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="referral-data" role="tabpanel" aria-labelledby="referral-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="referral-data-table">
                                <thead>
                                    <tr>
                                        <th class="mr-3">Name</th>
                                        <th class="mr-3">Shop Profile</th>
                                        <th class="mr-3">Email</th>
                                        <th class="mr-3">Phone Number</th>
                                        <th class="mr-3">SignUp Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($users as $user)
                                    <?php $shops = \App\Models\Shop::where('user_id', $user->id)->get(); ?>
                                    <tr>
                                        <td>{{$user->name}}</td>
                                        @if(!empty($shops) && count($shops)>0)
                                        <td>
                                            @foreach($shops as $shop)
                                                <a role="button" href="{{ route('admin.business-client.shop.show', [$shop->id]) }}" title="" data-original-title="View" class="mr-2 btn btn-primary btn-sm" data-toggle="tooltip"><i class="fas fa-eye mt-1"></i></a>
                                                @if($shop->main_name!=null && $shop->shop_name!=null)
                                                    <span>{{ $shop->main_name }} / {{ $shop->shop_name }}</span>
                                                @elseif($shop->main_name!=null)
                                                    <span>{{ $shop->main_name }}</span>
                                                @elseif($shop->shop_name!=null)
                                                    <span>{{ $shop->shop_name }}</span>
                                                @endif
                                                <br>
                                            @endforeach
                                        </td>
                                        @else
                                        <td>-</td>
                                        @endif
                                        <td>{{$user->email}} @if($user->deleted_at!=null) - <span style="color: deeppink;">Deleted</span> @endif</td>
                                        <td>{{$user->mobile}}</td>
                                        <td>{{ \App\Http\Controllers\Controller::formatDateTimeCountryWise($user->created_at, $adminTimezone)}}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="gifticon-data" role="tabpanel" aria-labelledby="gifticon-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="gifticon-data-table">
                                <thead>
                                <tr>
                                    <th class="mr-3">Title</th>
                                    <th class="mr-3">Image</th>
                                    <th class="mr-3">Created Date</th>
                                    <th class="mr-3">Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($gifticons as $gifticon)
                                    <tr>
                                        <td>{{$gifticon->title}}</td>
                                        <td>
                                            @if(isset($gifticon->attachments) && $gifticon->attachments->first())
                                                <img src="{{ $gifticon->attachments->first()->image_url }}" width="100" height="100" alt="Gift Icon">
                                            @endif
                                        </td>
                                        <td>{{ \App\Http\Controllers\Controller::formatDateTimeCountryWise($gifticon->created_at, $adminTimezone)}}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a role="button" data-original-title="Edit Gift Icon" class="mx-1 btn btn-primary btn-sm" data-toggle="tooltip" onclick="getGifticonModal('{{$id}}','{{$gifticon->id}}');">Edit</a>
                                            </div>
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
    </div>
</div>


<script>
    $("#referral-data-table").DataTable({order: [[ 0, "desc" ]]});
    $("#gifticon-data-table").DataTable({order: [[ 2, "desc" ]]});
</script>
