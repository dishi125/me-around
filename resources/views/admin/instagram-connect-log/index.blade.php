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
                                <table class="table table-striped" id="Instagram-connect-log-table">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Activate name</th>
                                            <th>Shop name</th>
                                            <th>Instagram account</th>
                                            <th>Contact Number</th>
                                            <th>E-mail</th>
                                            <th>Status</th>
                                            <th>Time</th>
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
    <script>
        var allTable = "{!! route('admin.instagram-connect-log.table') !!}";
        var csrfToken = "{{ csrf_token() }}";

        $(function() {
            var all = $("#Instagram-connect-log-table").DataTable({
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
                    { data: "see_profile", orderable: false },
                    { data: "main_name", orderable: true },
                    { data: "shop_name", orderable: true },
                    { data: "social_name", orderable: true },
                    { data: "phone", orderable: true },
                    { data: "email", orderable: true },
                    { data: "status", orderable: false },
                    { data: "time", orderable: true },
                ]
            });
        });

        function sendMail(insta_log_id){
            $.get(baseUrl + '/admin/instagram-connect-log/status/send-mail/' + insta_log_id, function (data, status) {
                if(data.success == true){
                    showToastMessage("Mail sent successfully.",true);
                }
            });
        }

        $(document).on('click', '.sendmail', function (){
            var insta_log_id = $(this).attr('data-id');
            var thi = $(this);

            $.get(baseUrl + '/admin/instagram-connect-log/status/send-mail/' + insta_log_id, function (data, status) {
                if(data.success == true){
                    $(thi).html(`Send Mail (${data.mail_count})`);
                    showToastMessage("Mail sent successfully.",true);
                }
            });
        })
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
@endsection
