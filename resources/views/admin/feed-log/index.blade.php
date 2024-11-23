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
                    <ul class="nav nav-pills mb-4 like-order-filter" id="myTab3" role="tablist">
                        <li class="nav-item mr-3 mb-3">
                            <a class="nav-link btn active btn-primary" id="auto-love-user" href="#" aria-controls="shop" aria-selected="true">
                                Auto love user
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" id="myTabContent2">
                        <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="comment-data">
                            <div class="table-responsive">
                                <table class="table table-striped" id="Feed-log-table">
                                    <thead>
                                        <tr>
                                            <th>User name</th>
                                            <th>Contact Number</th>
                                            <th>E-mail</th>
                                            <th>Business Profile</th>
                                            <th>Activate Name</th>
                                            <th>Shop Name</th>
                                            <th>Total love amount</th>
                                            <th>Feed time</th>
                                            <th>Character image</th>
                                            <th></th>
                                            <th>Note</th>
                                            <th></th>
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

<div class="modal fade" id="show-history" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

<div class="modal fade" id="add_more_love" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" style="max-width: 550px;">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5>Add More Love Amount</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center">
                <div class="align-items-xl-center mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" id="user-love-amount" class="numeric form-control" value="">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" id="btn_ok">Ok</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="show-auto-love-users" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>

@section('scripts')
    <script>
        var allTable = "{!! route('admin.feed-log.table') !!}";
        var csrfToken = "{{ csrf_token() }}";
        var editModal = $("#editModal");
        var editAccess = "{{ route('admin.business-client.edit.access') }}";
        var editSupporter = "{{ route('admin.business-client.edit.support-user') }}";
        var editLoveCountCheckbox = "{{ route('admin.business-client.edit.increase-love-count') }}";
        var addCredits = "{{ route('admin.business-client.add.credit') }}";
        var editLoveCountDaily = "{{ route('admin.business-client.edit.love-count-daily') }}";
        var deleteBusinessProfile = "{{ route('admin.business-client.delete.profile') }}";

        $(function() {
            var all = $("#Feed-log-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                "order": [[ 7, "desc" ]],
                ajax: {
                    url: allTable,
                    dataType: "json",
                    type: "POST",
                    data: { _token: csrfToken }
                },
                columns: [
                    { data: "username", orderable: true },
                    { data: "phone", orderable: false },
                    { data: "email", orderable: false },
                    { data: "see_profile", orderable: true },
                    { data: "main_name", orderable: true },
                    { data: "shop_name", orderable: true },
                    { data: "total_love", orderable: true },
                    { data: "feed_time", orderable: true },
                    { data: "char_image", orderable: false },
                    { data: "history", orderable: false },
                    { data: "note", orderable: false },
                    { data: "add_more", orderable: false },
                    { data: "action", orderable: false },
                ]
            });
        });

        $(document).on('click','.editnote',function (){
            var id = $(this).attr('data-id');
            var note = $(this).siblings('textarea[name="note"]').val();

            if(note==""){
                showToastMessage("Please enter your note",false);
            }
            else {
                $.ajax({
                    url: "{{ url('admin/feed-log/edit/note') }}",
                    method: 'POST',
                    data: {
                        '_token': csrfToken,
                        'id': id,
                        'note': note
                    },
                    success: function (data) {
                        if (data.status == 1) {
                            showToastMessage(data.message, true);
                            $('#Feed-log-table').DataTable().ajax.reload();
                        } else {
                            showToastMessage("Failed to edit note!!", false);
                        }
                    }
                });
            }
        })

        $(document).on('click', '.btnhistory', function (){
            var user_id = $(this).attr('user-id');
            $.get(baseUrl + '/admin/show/all-feed-logs/user/' + user_id, function (data, status) {
                $('#show-history').html('');
                $('#show-history').html(data);
                $('#show-history').modal('show');
            });
        })

        $(document).on('click', '#auto-love-user', function (){
            $.get(baseUrl + '/admin/show/auto-love-users', function (data, status) {
                console.log(data);
                $('#show-auto-love-users').html('');
                $('#show-auto-love-users').html(data);
                $('#show-auto-love-users').modal('show');
            });
        })

        $(document).on('click', '.btn_add_more', function (){
            var user_id = $(this).attr('user-id');
            $("#add_more_love").find('#btn_ok').attr('user-id',user_id);
            $("#add_more_love").find('#user-love-amount').val("");
            $("#add_more_love").modal('show');
        })

        $(document).on('click', '#btn_ok', function (){
            var user_id = $(this).attr('user-id');
            var love_amount = $("#user-love-amount").val();

            if(love_amount==""){
                showToastMessage("Please enter love amount",false);
            }
            else {
                $.ajax({
                    url: baseUrl + '/admin/user/add-more/love',
                    type: 'Post',
                    data: {
                        '_token': $("meta[name=csrf-token]").attr("content"),
                        'user_id': user_id,
                        'love_amount': love_amount
                    },
                    success: function(data) {
                        if(data.status==1){
                            $("#add_more_love").modal('hide');
                            showToastMessage(data.message,true);
                            $('#Feed-log-table').DataTable().ajax.reload();
                        }
                        else {
                            showToastMessage(data.message,false);
                        }
                    }
                })
            }
        })
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script src="{!! asset('js/pages/business-client/common.js') !!}"></script>
@endsection
