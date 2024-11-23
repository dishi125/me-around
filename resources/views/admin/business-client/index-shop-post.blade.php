@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
    <style>
        .table-responsive button#show-profile {
            width: 130px;
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

        .pac-container {
            z-index: 10000 !important;
        }
    </style>
@endsection

@section('header-content')
    <h1>
        @if (@$title)
            {{ @$title }}
        @endif
    </h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap">
                        <div class="custom-checkbox custom-control">
                            <input type="checkbox" data-cat-id="all" data-checkboxes="mygroup" class="custom-control-input filter_shop_post_checkbox" id="all-checkbox" value="all" name="filter_shop_post_checkbox[]" checked><label for="all-checkbox" class="custom-control-label mr-5 text-dark">All</label>
                        </div>
                        @foreach($menuItem as $menu)
                        @if(isset($categories[$menu->menu_key]))
                            @foreach($categories[$menu->menu_key] as $category)
                            <div class="custom-checkbox custom-control">
                                <input type="checkbox" data-cat-id="{{ $category->id }}" data-checkboxes="mygroup" class="custom-control-input filter_shop_post_checkbox" id="{{ $category->name }}-{{ $category->id }}-checkbox" value="{{ $category->id }}" name="filter_shop_post_checkbox[]"><label for="{{ $category->name }}-{{ $category->id }}-checkbox" class="custom-control-label mr-5 text-dark">{{ $category->name }}</label>
                            </div>
                            @endforeach
                        @endif
                        @endforeach
                    </div>
{{--                    <div class="row mb-4">--}}
{{--                        <div class="col-md-4 "></div>--}}
                        <div class="d-flex mb-3 mt-2" style="float: right">
                            <button type="button" class="btn btn-primary mr-2" id="download_blogging_button">Download for blogging</button>
                            <button type="button" class="btn btn-primary mr-2" id="download_button">Download</button>
                            <button type="button" class="btn btn-primary mr-2" id="remove_text_button">Remove Text</button>
                            <button id="delete_submit" class="btn btn-danger">Delete Selected</button>
                        </div>
<!--                        <div class="responsive-button-block col-md-4">
                            <ul class="nav nav-pills " id="myTab3" role="tablist">
                                <li class="nav-item mr-3 ml-auto">
                                </li>
                            </ul>
                        </div>-->
{{--                    </div>--}}
                    <div class="d-flex flex-wrap mt-5">
                        <div class="custom-checkbox custom-control">
                            <input type="checkbox" data-cat-id="image" data-checkboxes="mygroup" class="custom-control-input filter_shop_post_checkbox" id="image-checkbox" value="image" name="filter_shop_post_checkbox[]"><label for="image-checkbox" class="custom-control-label mr-5 text-dark">Image</label>
                        </div>
                        <div class="custom-checkbox custom-control">
                            <input type="checkbox" data-cat-id="video" data-checkboxes="mygroup" class="custom-control-input filter_shop_post_checkbox" id="video-checkbox" value="video" name="filter_shop_post_checkbox[]"><label for="video-checkbox" class="custom-control-label mr-5 text-dark">Video</label>
                        </div>
                        <div class="custom-checkbox custom-control">
                            <input type="checkbox" data-cat-id="only-video" data-checkboxes="mygroup" class="custom-control-input filter_shop_post_checkbox" id="only-video-checkbox" value="only-video" name="filter_shop_post_checkbox[]"><label for="only-video-checkbox" class="custom-control-label mr-5 text-dark">Only video post</label>
                        </div>
                        <div class="custom-checkbox custom-control">
                            <input id="radius" class="form-control" name="radius" type="text" value="" placeholder="Radius">
                        </div>
                        <div class="custom-checkbox custom-control">
                            <input id="address_popup" class="form-control" name="address_popup" type="text" value="" placeholder="Address" onfocus="addressPopup()">
                        </div>
                        <div class="custom-checkbox custom-control">
                            <input type="date" id="filter-date" class="form-control">
                        </div>
                        <input type="hidden" name="final-lat" id="final-lat" value="">
                        <input type="hidden" name="final-long" id="final-long" value="">
                        <input type="hidden" name="final-distance" id="final-distance" value="">
                        <button type="button" class="btn btn-primary ml-4" id="save_filter_btn">Save</button>
                    </div>
                    <div class="tab-content" id="myTabContent2">
                        <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
                            <div class="table-responsive">
                                <table class="table table-striped" id="all-shop-post-table">
                                    <thead>
                                        <tr>
                                            <th>Business Name</th>
                                            <th>Business Phone Number</th>
                                            <th>Address</th>
                                            <th>Description</th>
                                            <th>Updated Date</th>
                                            <th>Service</th>
                                            <th>Images</th>
                                            <th>Action</th>
                                            <th></th>
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
<div class="modal fade" id="removeTextPostModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" style="max-width: 1100px;">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h2> Remove Text </h2>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center" id="modelShow">
                <div class="row">
                    <div class="col-md-6 d-flex">
                        <input type="text" placeholder="Enter Text to remove from the description"
                            autocomplete="false" name="remove_text" id="remove_text" class="form-control" />
                        <button type="button" class="btn btn-primary ml-4" id="remove_button">Remove</button>
                    </div>
                    <div class="col-md-12 mt-4">
                        <table class="table table-striped dataTable no-footer">
                            <thead>
                                <th>Select</th>
                                <th>Business Name</th>
                                <th>Description</th>
                            </thead>
                            <tbody id="searchposttabledata">

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


</div>
<div class="modal fade" id="shopPostModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

<div class="modal fade" id="PostPhotoModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header justify-content-center" style="border-bottom:none; padding: 8px;">
                <h5 id="shop_names_data"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center" style="padding: 0px;" id="modelImageShow">
                <img src="{!! asset('img/logo-main.png') !!}" class="w-100 " id="modelImageEle" />
            </div>
            <div class="modal-footer pr-0 mt-3 mr-2">
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addressModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

<div class="cover-spin"></div>
@section('scripts')
    <script>
        var shopPostTable = "{{ route('admin.business-client.shop.post.table') }}";
        var csrfToken = "{{ csrf_token() }}";
        var shopPostModel = $('#shopPostModal');
        var filters = [];
        var downloadShopPost = "{{ route('admin.download.shop-posts') }}";
        var getCheckedShopPosts = "{{ route('admin.get.shop-posts-url') }}";
        var hashtag_id = "{{ $hashtag_id }}";

        $('#delete_submit').click(function(event) {
            var id = $('input[name="checkbox_id[]"]:checked').map(function(_, el) {
                return $(el).val();
            }).get();

            if (id.length == 0) {
                iziToast.error({
                    title: '',
                    message: 'Please select at least one checkbox',
                    position: 'topRight',
                    progressBar: true,
                    timeout: 5000
                });
            }
            else {
                $.ajax({
                    url: "{{ route('admin.shoppost-image.all.remove') }}",
                    method: 'POST',
                    data: {
                        _token: csrfToken,
                        ids: id,
                    },
                    beforeSend: function() {
                        $('.cover-spin').show();
                    },
                    success: function(response) {
                        $('.cover-spin').hide();
                        if (response.success == true) {
                            iziToast.success({
                                title: '',
                                message: response.message,
                                position: 'topRight',
                                progressBar: false,
                                timeout: 1000,
                            });

                            setTimeout(function() {
                                window.location.href = response.redirect;
                            }, 1000);

                        } else {
                            iziToast.error({
                                title: '',
                                message: 'Portfolio has not been deleted successfully.',
                                position: 'topRight',
                                progressBar: false,
                                timeout: 1500,
                            });
                        }
                    }
                });
            }
        });

        function instagramServicePopup(id) {
            $.get("{{ url('admin/instagram/service') }}" + "/" + id, function(data, status) {
                shopPostModel.html('');
                shopPostModel.html(data);
                shopPostModel.modal('show');
            });
        }

        $(document).on('click', '.save_supporter_details', function(e) {
            $shop_id = $(this).attr('shop_id');
            e.preventDefault();
            var actionurl = baseUrl + '/admin/instagram-service/update/shop/' + $shop_id;
            $.ajax({
                url: actionurl,
                method: 'POST',
                data: $('#instagram_service').serialize(),
                beforeSend: function() {
                    $('.cover-spin').show();
                },
                success: function(data) {
                    $('.cover-spin').hide();
                    if (data.status_code == 200) {
                        shopPostModel.modal('hide');

                        iziToast.success({
                            title: '',
                            message: data.message,
                            position: 'topRight',
                            progressBar: false,
                            timeout: 1000,
                        });
                        $('#all-shop-post-table').dataTable().api().ajax.reload();

                    }
                }
            });

        });
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script src="{!! asset('js/pages/business-client/shop-post.js') !!}"></script>
    <script type="text/javascript"
            src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDlfhV6gvSJp_TvqudE0z9mV3bBlexZo3M&&radius=100&&libraries=places&callback=initialize"
            async defer></script>
    <script src="{!! asset('js/mapInput.js') !!}"></script>
@endsection
