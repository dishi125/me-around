@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">

@endsection

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-pills mb-4" id="myTab3" role="tablist">
                    <li class="nav-item mr-3 mb-3">
                        <a class="nav-link active btn btn-primary " id="all-data" href="{{ route('admin.user.index') }}" >All</a>
                    </li>
                    <li class="nav-item mr-3 mb-3">
                        <a class="nav-link btn btn-success " id="active-data" href="{{ route('admin.user.index') }}" >Activate</a>
                    </li>
                    <li class="nav-item mr-3 mb-3">
                        <a class="nav-link btn btn-secondary " id="inactive-data" href="{{ route('admin.user.index') }}" >Not Activate</a>
                    </li>
                    <li class="nav-item mr-3 mb-3">
                        <a class="nav-link btn  " style="background-color: #fff700;" id="pending-data" href="{{ route('admin.user.index') }}" >Pending</a>
                    </li>
                    <li class="nav-item mr-3 mb-3">
                        <a class="nav-link btn btn-primary " id="user-data" href="{{ route('admin.user.index') }}" >Normal User</a>
                    </li>

                    <li class="nav-item mr-3 mb-3 ml-5 pl-5">
                        <a class="btn btn-primary" id="user-lost" href="javascript:void(0);" >
                            Lost Category Shop
                        </a>
                    </li>
                </ul>
                <div class="tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="all-table">
                                <thead>
                                    <tr>
                                        <th>User name</th>
                                        <th>Activate name</th>
                                        <th>Shop name</th>
                                        <th>Deleted Category</th>
                                        <th>View Shop</th>
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

@section('scripts')
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $("#all-table").DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        deferRender: true,
        order: [[0, "desc"]],
        ajax: {
            url: "{{route('admin.user.lost-category.table')}}",
            dataType: "json",
            type: "POST",
        },
        columns: [
            {data: "user_name", orderable: true},
            {data: "active_name", orderable: true},
            {data: "shop_name", orderable: true},
            {data: "category", orderable: false},
            {data: "view_shop", orderable: false},
            {data: "actions", orderable: false},
        ],
    });

    $(document).on('change', 'select[name="category_select"]', function () {
        var categoryID = $(this).val();
        var shop_id = $(this).attr('shop_id');
        $.ajax({
            url: baseUrl + "/admin/update/user/shop/category",
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                category: categoryID,
                shop_id: shop_id,
            },
            beforeSend: function () {
                $('.cover-spin').show();
            },
            success: function (response) {
                $('#all-table').DataTable().ajax.reload();
                $('.cover-spin').hide();
                if (response.success == true) {
                    iziToast.success({
                        title: '',
                        message: "Category Updated successfully.",
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });

                } else {
                    iziToast.error({
                        title: '',
                        message: 'Category has not been updated successfully.',
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1500,
                    });
                }
            }
        });

    });
</script>
@endsection
