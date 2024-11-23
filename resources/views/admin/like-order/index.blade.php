@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
    <style>
        .table-responsive td span {
            margin: 5px;
        }

        .update_service,
        .service {
            cursor: pointer
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
                    <ul class="nav nav-pills mb-4 like-order-filter" id="myTab3" role="tablist">
                        <li class="nav-item mr-3 mb-3">
                            <a class="nav-link btn active btn-primary filterButton" id="today-real-data"
                                data-filter="today-real" data-toggle="tab" href="#" role="tab"
                                aria-controls="shop" aria-selected="true">
                                Today's Post (Real)
                            </a>
                        </li>
                        <li class="nav-item mr-3 mb-3">
                            <a class="nav-link  btn btn-primary filterButton" id="today-data" data-filter="today"
                                data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="true">
                                Today's Post
                            </a>
                        </li>
                        <li class="nav-item mr-3 mb-3">
                            <a class="nav-link btn btn-primary filterButton" id="all-data" data-filter="all"
                                data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="true">
                                All Post
                            </a>
                        </li>
                        <li class="nav-item mr-3 mb-3">
                            <a class="nav-link btn btn-primary filterButton" id="expired-user-data"
                                data-filter="expired-user" data-toggle="tab" href="#" role="tab"
                                aria-controls="shop" aria-selected="true">
                                Expired user
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content" id="myTabContent2">
                        <div class="tab-pane fade show active" id="allData" role="tabpanel"
                            aria-labelledby="comment-data">
                            <div class="table-responsive">
                                <table class="table table-striped" id="like-order-table">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Shop Name</th>
                                            <th>Instagram Link</th>
                                            <th>Service</th>
                                            <th>Images</th>
                                            <th>Description</th>
                                            <th>Date</th>
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

@section('scripts')
    <script>
        var allTable = "{!! route('admin.like-order.table') !!}";
        var allExpiredUserTable = "{!! route('admin.like-order.expired-user.table') !!}";
        var csrfToken = "{{ csrf_token() }}";
        var shopPostModel = $('#shopPostModal');

        function instagramServicePopup(id) {
            $.get("{{ url('admin/instagram/service') }}" + "/" + id, function(data, status) {
                shopPostModel.html('');
                shopPostModel.html(data);
                shopPostModel.modal('show');
            });
        }

        $(document).on('click', '.filterButton', function() {
            var filter = $(this).attr('data-filter');
            $('#like-order-table').DataTable().destroy();
            $('#like-order-table thead').empty();
            $('#like-order-table tbody').empty();
            if(filter == "expired-user"){
                var html = `<tr>
                                <th>Name</th>
                                <th>E-mail Address</th>
                                <th>Phone Number</th>
                                <th>Signup Date</th>
                                <th>Last Access MeAround</th>
                            </tr>`;
                $('#like-order-table thead').html(html);
                loadExpiredUserTableData();
            }
            else {
                var html = `<tr>
                                <th></th>
                                <th>Shop Name</th>
                                <th>Instagram Link</th>
                                <th>Service</th>
                                <th>Images</th>
                                <th>Description</th>
                                <th>Date</th>
                            </tr>`;
                $('#like-order-table thead').html(html);
                loadTableData(filter);
            }
        });

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
                        $('#like-order-table').dataTable().api().ajax.reload();

                    }
                }
            });

        });

        function loadTableData(filter) {
            var filter = filter || 'today-real';
            var shopPost = $("#like-order-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                "lengthMenu": [25, 50, 100, 200, 500],
                "pageLength": 100,
                "order": [
                    [2, "desc"]
                ],
                ajax: {
                    url: allTable,
                    dataType: "json",
                    type: "POST",
                    data: {
                        _token: csrfToken,
                        filter: filter
                    },
                },
                columns: [{
                        data: "business_profile",
                        orderable: false,
                        width: "2%",
                    }, {
                        data: "business_name",
                        orderable: true,
                        width: "14%",
                    },
                    {
                        data: "insta_link",
                        orderable: true,
                        width: "30%",
                    },
                    {
                        data: "service",
                        orderable: false,
                        width: "10%",
                    },
                    {
                        data: "images",
                        orderable: false,
                        width: "12%",
                    },
                    {
                        data: "description",
                        orderable: false,
                        width: "25%",
                    },
                    {
                        data: "update_date",
                        orderable: true,
                        visible: false
                    },
                ],
            });
        }

        function loadExpiredUserTableData(){
            var users = $("#like-order-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                // "lengthMenu": [25, 50, 100, 200, 500],
                "pageLength": 100,
                "order": [
                    [3, "desc"]
                ],
                ajax: {
                    url: allExpiredUserTable,
                    dataType: "json",
                    type: "POST",
                    data: {
                        _token: csrfToken,
                    },
                },
                columns: [
                    {
                        data: "name",
                        orderable: true
                    },
                    {
                        data: "email",
                        orderable: true
                    },
                    {
                        data: "phone",
                        orderable: false
                    },
                    {
                        data: "signup",
                        orderable: true
                    },
                    {
                        data: "last_access",
                        orderable: true
                    },
                ]
            });
        }

        $(function() {
            loadTableData('today-real');
        });

        function showImage(imageSrc) {
            // Get the modal
            $('#modelImageShow').html('');
            var validExtensions = ["jpg", "jpeg", "gif", "png", 'webp'];
            var extension = imageSrc.split('.').pop().toLowerCase();
            if (imageSrc) {
                if ($.inArray(extension, validExtensions) == -1) {
                    $('#modelImageEle').remove();
                    $('#modelImageShow').html(
                        '<video width="100%" height="300" controls poster="" id="modelVideoEle"><source src="' +
                        imageSrc + '" type="video/mp4">Your browser does not support the video tag.</video>');
                } else {
                    $('#modelVideoEle').remove();
                    $('#modelImageShow').html('<img src="' + imageSrc + '" class="w-100 " id="modelImageEle" />');
                }
                $("#PostPhotoModal").modal('show');
            }
        }

        function giveLikeToPost(copyURL, id, shop_id) {
            if (id) {
                $.ajax({
                    url: baseUrl + '/admin/give/shop/post/like',
                    method: 'POST',
                    data: {
                        _token: csrfToken,
                        post_id: id,
                        shop_id: shop_id,
                    },
                    beforeSend: function() {
                        $('.cover-spin').show();
                    },
                    success: function(data) {
                        $('.cover-spin').hide();
                        $('#like-order-table').dataTable().api().ajax.reload();
                        //showToastMessage(data.message, data.success);
                        copyTextLink(copyURL);
                    }
                });
            }
        }
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
@endsection
