@extends('challenge-layouts.app')

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
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="tab-content" id="myTabContent2">
                        <div class="tab-pane fade show active" id="allData" role="tabpanel"
                            aria-labelledby="all-data">
                            <div class="table-responsive">
                                <table class="table table-striped" id="Verification-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('datatable.verification.user_name') }}</th>
                                            <th>{{ __('datatable.verification.challenge_name') }}</th>
                                            <th>{{ __('datatable.verification.date') }}</th>
                                            <th>{{ __('datatable.verification.time') }}</th>
                                            <th>{{ __('datatable.verification.images') }}</th>
                                            <th>{{ __('datatable.verification.reject') }}</th>
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

<div class="modal fade" id="seeChallengeModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>

<div class="modal fade" id="PostPhotoModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

@section('scripts')
    <script>
        var allTable = "{{ route('challenge.verification.all.table') }}";
        var csrfToken = "{{ csrf_token() }}";
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script>
        $(document).ready(function (){
            loadTableData();
        })

        function loadTableData() {
            var allData = $("#Verification-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                // order: [[ 1, "ASC" ]],
                ajax: {
                    url: allTable,
                    dataType: "json",
                    type: "POST",
                    data: {
                        _token: csrfToken,
                    }
                },
                columns: [
                    {
                        data: "user_name",
                        orderable: false
                    },
                    {
                        data: "challenge_name",
                        orderable: false
                    },
                    {
                        data: "date",
                        orderable: false
                    },
                    {
                        data: "time",
                        orderable: false
                    },
                    {
                        data: "images",
                        orderable: false
                    },
                    {
                        data: "reject",
                        orderable: false
                    },
                    {
                        data: "action",
                        orderable: false
                    },
                ]
            });
        }

        function seeChallenge(challenge_id){
            $.get(baseUrl + '/challenge/challenge-page/view/' + challenge_id, function (data, status) {
                $('#seeChallengeModal').html('');
                $('#seeChallengeModal').html(data);
                $('#seeChallengeModal').modal('show');
            });
        }

        $(document).on('click','#verified_checkbox', function (){
            var checked;
            if (this.checked) {
                this.checked = true;
                checked = 1;
            }
            else {
                this.checked = false;
                checked = 0;
            }
            var verifyId = $(this).attr('verify-id');

            $.ajax({
                url: "{{ route('challenge.verification.update.verify') }}",
                method: 'POST',
                data: {
                    _token: csrfToken,
                    checked: checked,
                    verify_id: verifyId,
                },
                beforeSend: function() {
                    $('.cover-spin').show();
                },
                success: function(response) {
                    $('.cover-spin').hide();
                    if (response.success == true) {
                        $('#Verification-table').DataTable().destroy();
                        loadTableData();
                    } else {
                        showToastMessage("Something went wrong!!",false);
                    }
                },
                error: function (){
                    $('.cover-spin').hide();
                    showToastMessage("Something went wrong!!",false);
                }
            });
        });

        $(document).on('click','#reject_checkbox', function (){
            var checked;
            if (this.checked) {
                this.checked = true;
                checked = 1;
            }
            else {
                this.checked = false;
                checked = 0;
            }
            var verifyId = $(this).attr('verify-id');

            $.ajax({
                url: "{{ route('challenge.verification.update.reject') }}",
                method: 'POST',
                data: {
                    _token: csrfToken,
                    checked: checked,
                    verify_id: verifyId,
                },
                beforeSend: function() {
                    $('.cover-spin').show();
                },
                success: function(response) {
                    $('.cover-spin').hide();
                    if (response.success == true) {
                        $('#Verification-table').DataTable().destroy();
                        loadTableData();
                    } else {
                        showToastMessage("Something went wrong!!",false);
                    }
                },
                error: function (){
                    $('.cover-spin').hide();
                    showToastMessage("Something went wrong!!",false);
                }
            });
        });

        function showImage(imageSrc,id){
            $.ajax({
                url: "{{ route('challenge.verification.view-images') }}",
                method: 'POST',
                data: {
                    _token: csrfToken,
                    verify_id: id,
                    activeImage: imageSrc,
                },
                beforeSend: function() {
                    $('.cover-spin').show();
                },
                success: function(response) {
                    $('.cover-spin').hide();
                    if (response.success == true) {
                        $("#PostPhotoModal").html(response.html);
                        $("#PostPhotoModal").modal('show');
                    } else {
                        showToastMessage("Something went wrong!!",false);
                    }
                },
                error: function (){
                    $('.cover-spin').hide();
                    showToastMessage("Something went wrong!!",false);
                }
            });
        }

        $('#PostPhotoModal').on('shown.bs.modal', function () {
            $('#imageSlider').carousel();
        });
    </script>
@endsection
