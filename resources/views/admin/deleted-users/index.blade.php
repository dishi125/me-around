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
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="tab-content" id="myTabContent2">
                        <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="comment-data">
                            <div class="table-responsive">
                                <table class="table table-striped" id="Deleted-User-table">
                                    <thead>
                                        <tr>
                                            <th>User Name</th>
                                            <th>Gender</th>
                                            <th>E-mail</th>
                                            <th>Phone Number</th>
                                            <th>Deleted At</th>
                                            <th>Signup Date</th>
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

@section('scripts')
    <script>
        var allCommentTable = "{!! route('admin.deleted-users.table') !!}";
        var profileModal = $("#deletePostModal");
        var csrfToken = "{{ csrf_token() }}";

        $(function() {
            var allShop = $("#Deleted-User-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                "order": [[ 4, "desc" ]],
                ajax: {
                    url: allCommentTable,
                    dataType: "json",
                    type: "POST",
                    data: { _token: csrfToken }
                },
                columns: [
                    { data: "username", orderable: true },
                    { data: "gender", orderable: false },
                    { data: "email", orderable: true },
                    { data: "phone_number", orderable: false },
                    { data: "deleted_at", orderable: true },
                    { data: "signup_date", orderable: true },
                    { data: "actions", orderable: false },
                ]
            });
        });

        function viewShopProfile(id) {
            $.get(baseUrl + '/admin/deleted-users/view/shop/profile/' + id, function (data, status) {
                profileModal.html('');
                profileModal.html(data);
                profileModal.modal('show');
                $('.selectform').select2({
                    width: '150' ,
                });
            });
        }
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
@endsection
