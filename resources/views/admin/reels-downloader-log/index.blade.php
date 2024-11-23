@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
    <style>
        .table-responsive button#show-profile {
            width: auto;
            margin: 5px 5px 5px 0;
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
    </style>
@endsection

@section('header-content')
    <h1>
        @if (@$title)
            {{ @$title }}
        @endif
    </h1>
<!--    <div class="section-header-button">
        <button class="btn btn-primary mr-2" id="add_coupon">Add Coupon</button>
    </div>-->
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="tab-content" id="myTabContent2">
                        <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="comment-data">
<!--                            <div class="table-responsive">
                                <table class="table table-striped" id="Coupon-table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Image</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>-->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="cover-spin"></div>
@endsection

@section('scripts')
    <script>
        var csrfToken = "{{ csrf_token() }}";
        var allTable = "{!! route('admin.coupon.table') !!}";
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script>
        $(function() {
            var all = $("#Coupon-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                // "order": [[ 4, "desc" ]],
                ajax: {
                    url: allTable,
                    dataType: "json",
                    type: "POST",
                    data: { _token: csrfToken }
                },
                columns: [
                    { data: "title", orderable: false },
                    { data: "image", orderable: false },
                    { data: "copy_link", orderable: false },
                ]
            });
        });
    </script>
@endsection
