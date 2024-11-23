@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
    <style>
        .table-responsive button#show-profile {
            width: auto;
            margin: 5px;
            white-space: normal;
        }

        .table-responsive .shops-date button#show-profile {
            width: 180px;
        }

        .table-responsive .shops-rate button#show-profile {
            width: 80px;
        }

        .table-responsive td span {
            margin: 5px;
        }

        .button-container {
            display: flex;
            flex-direction: row;
            /*justify-content: space-between;*/
            align-items: center;
        }

        .vertical-buttons {
            display: flex;
            flex-direction: column;
        }

        /*.button {
            margin-bottom: 10px;
        }*/
    </style>
@endsection

@section('header-content')
    <h1>
        @if (@$title)
            {{ @$title }}
        @endif
    </h1>
    <!-- <div class="section-header-button">
                                                                                        <a href="{{ route('admin.user.create') }}" class="btn btn-primary">Add New</a>
                                                                                    </div> -->
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3 row">
                        <div class="col-md-4">
                            <div class="font-weight-bold mb-1">Hospital - {{ $totalHospitals }} | Shop - {{ $totalShops }}
                                | Normal User - {{ $totalNormalUser }}</div>

                        </div>
                        <div class="col-md-2"> </div>
                        <div class="col-md-4 form-group d-flex align-items-center">
                            @if (count($category))
                                <label class="mb-0">Category Filter</label>
                                <select class="form-control w-50 ml-4" name="category-filter" id="category-filter">
                                    <option value="all">Select Category</option>
                                    @foreach ($category as $value)
                                        <option value="{{ $value->id }}">{{ $value->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('admin.user.create') }}" class="btn btn-primary">Add New</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-pills mb-4" id="myTab3" role="tablist">
                        <li class="nav-item mr-3 mb-3">
                            <a class="nav-link active btn btn-primary filterButton" id="all-data" data-filter="all"
                                data-toggle="tab" href="#" role="tab" aria-controls="shop"
                                aria-selected="true">All</a>
                        </li>
                        <li class="nav-item mr-3 mb-3">
                            <a class="nav-link btn btn-success filterButton" id="active-data" data-filter="active"
                                data-toggle="tab" href="#" role="tab" aria-controls="shop"
                                aria-selected="false">Activate</a>
                        </li>
                        <li class="nav-item mr-3 mb-3">
                            <a class="nav-link btn btn-secondary filterButton" id="inactive-data" data-filter="inactive"
                                data-toggle="tab" href="#" role="tab" aria-controls="shop"
                                aria-selected="false">Not Activate</a>
                        </li>
                        <li class="nav-item mr-3 mb-3">
                            <a class="nav-link btn  filterButton" style="background-color: #fff700;" id="pending-data"
                                data-filter="pending" data-toggle="tab" href="#" role="tab" aria-controls="shop"
                                aria-selected="false">Pending</a>
                        </li>
                        <li class="nav-item mr-3 mb-3">
                            <a class="nav-link btn btn-primary filterButton" id="user-data" data-filter="user"
                                data-toggle="tab" href="#" role="tab" aria-controls="shop"
                                aria-selected="false">Normal User</a>
                        </li>
                        <li class="nav-item mr-3 mb-3 position-relative is-unread-comment">
                            <a class="nav-link btn btn-primary filterButton" id="user-data" data-filter="referred-user"
                                data-toggle="tab" href="javascript:void(0);" role="tab" aria-controls="shop"
                                aria-selected="false">Referred user
                                @if ($unreadReferralCount && $unreadReferralCount > 0)
                                    <span class="unread_referral_count">{{ $unreadReferralCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item mr-3 mb-3">
                            <a class="nav-link btn btn-primary filterButton" id="user-data" data-filter="call"
                                data-toggle="tab" href="#" role="tab" aria-controls="shop"
                                aria-selected="false">Call</a>
                        </li>
                        <li class="nav-item mr-3 mb-3">
                            <a class="nav-link btn btn-primary filterButton" id="user-data" data-filter="naver_book"
                                data-toggle="tab" href="#" role="tab" aria-controls="shop"
                                aria-selected="false">Naver Book</a>
                        </li>
                        <li class="nav-item mr-3 mb-3">
                            <a class="nav-link btn btn-primary filterButton" id="user-data" data-filter="admin_user"
                               data-toggle="tab" href="#" role="tab" aria-controls="shop"
                               aria-selected="false">Admin User</a>
                        </li>
                        <li class="nav-item mr-3 mb-3">
                            <a class="nav-link btn btn-primary filterButton" id="user-data" data-filter="support_user"
                               data-toggle="tab" href="#" role="tab" aria-controls="shop"
                               aria-selected="false">Support User</a>
                        </li>

                        <li class="nav-item mr-3 mb-3 ml-5 pl-5">
                            <a class="btn btn-primary" id="user-lost" href="{{ route('admin.user.lost-category') }}">
                                Lost Category Shop
                            </a>
                        </li>
                    </ul>

                    <div class="custom-checkbox custom-control">
                        <input type="checkbox" data-checkboxes="mygroup" class="custom-control-input" id="checkbox-hide-other" checked>
                        <label for="checkbox-hide-other" class="custom-control-label">Hide other project user</label>
                    </div>

                    <div class="tab-content" id="myTabContent2">
                        <div class="tab-pane fade show active" id="allData" role="tabpanel"
                            aria-labelledby="all-data">
                            <div class="table-responsive">
                                <table class="table table-striped" id="all-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Business Profile</th>
                                            <th>Email Address</th>
                                            @if(Auth::user()->hasRole("Sub Admin"))
                                            <th></th>
                                            @else
                                            <th>Phone Number</th>
                                            @endif

                                            @if(Auth::user()->hasRole("Sub Admin"))
                                            <th></th>
                                            @else
                                            <th>Location</th>
                                            @endif

                                            @if(Auth::user()->hasRole("Sub Admin"))
                                            <th></th>
                                            @else
                                            <th>Service</th>
                                            @endif
                                            <th>SignUp date</th>
                                            <th>Business Type</th>
                                            <th>Portfolio Count</th>
                                            <th>Status</th>
                                            <th>Last access MeAround</th>
                                            <th>Love Count</th>
                                            <th>Level</th>
                                            <th>Referral</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="cover-spin"></div>
@endsection

<!-- Modal -->
<div class="modal fade" id="deletePostModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
<div class="modal fade" id="shopPostModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

<div class="modal fade" id="PostPhotoModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header justify-content-center" style="border-bottom:none; padding: 8px;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center" style="padding: 0px;" id="modelImageShow">
                <img src="{!! asset('img/logo-main.png') !!}" class="w-100 " id="modelImageEle" />
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="show-referral" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
<div class="modal fade" id="show-gifticon" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>
<div class="modal fade" id="editEmailModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>
<div class="modal fade" id="editPhoneModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>

<div class="modal fade" id="show-locations" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

<div class="modal fade" id="editUsernameModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>


@section('scripts')
    <script>
        var editModal = $("#editModal");
        var editEmailModal = $("#editEmailModal");
        var allUserTable = "{{ route('admin.user.all.table') }}";
        var profileModal = $("#deletePostModal");
        var csrfToken = "{{ csrf_token() }}";
        var shopPostModel = $('#shopPostModal');
        var editAccess = "{{ route('admin.business-client.edit.access') }}";
        var editSupporter = "{{ route('admin.business-client.edit.support-user') }}";
        var editLoveCountDaily = "{{ route('admin.business-client.edit.love-count-daily') }}";
        var editLoveCountCheckbox = "{{ route('admin.business-client.edit.increase-love-count') }}";
        var addCredits = "{{ route('admin.business-client.add.credit') }}";
        var editSupporterOption = "{{ route('admin.business-client.edit.support-type') }}";
        var saveSignupCode = "{{ route('admin.user.signup-code.save') }}";

        function instagramServicePopup(id) {
            $.get("{{ url('admin/instagram/service') }}" + "/" + id, function(data, status) {
                shopPostModel.html('');
                shopPostModel.html(data);
                shopPostModel.modal('show');
            });
        }
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script src="{!! asset('js/pages/users/users.js') !!}"></script>
    <script src="{!! asset('js/pages/business-client/common.js') !!}"></script>
@endsection
