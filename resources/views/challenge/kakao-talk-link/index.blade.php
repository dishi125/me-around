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
                                <table class="table table-striped" id="Kakao-talk-link-table">
                                    <thead>
                                        <tr>
                                            <th>Link</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td>{{ !empty($link) ? $link->link : "" }}</td>
                                        <td><a href="javascript:void(0)" role="button" onclick="editKakaoTalk()" class="btn btn-primary btn-sm mx-1" data-toggle="tooltip" data-original-title="Edit"><i class="fa fa-edit"></i></a></td>
                                    </tr>
                                    </tbody>
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
<div class="modal fade" id="kakaoTalkModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

@section('scripts')
    <script>
        var csrfToken = "{{ csrf_token() }}";
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script>
    $(document).ready(function (){
        $("#Kakao-talk-link-table").dataTable();
    })

    function editKakaoTalk() {
        $.get(baseUrl + '/challenge/kakao-talk-link/edit', function (data, status) {
            $("#kakaoTalkModal").html('');
            $("#kakaoTalkModal").html(data);
            $("#kakaoTalkModal").modal('show');
        });
    }

    $('#kakaoTalkModal').on('hidden.bs.modal', function () {
        $("#kakaoTalkForm")[0].reset();
        $(".text-danger").remove();
    })

    $(document).on('submit', '#kakaoTalkForm', function (e){
        e.preventDefault();
        $(".text-danger").remove();
        var formData = $(this).serialize();

        $.ajax({
            url: "{{ url('challenge/kakao-talk-link/update') }}",
            // processData: false,
            // contentType: false,
            type: 'POST',
            data: formData,
            success:function(response){
                $(".cover-spin").hide();
                if(response.success == true){
                    $("#kakaoTalkModal").modal('hide');
                    showToastMessage('Link updated successfully.',true);
                    location.reload();
                }
                else {
                    if(response.errors) {
                        var errors = response.errors;
                        $.each(errors, function (key, value) {
                            $('#' + key).after('<div class="text-danger">' + value[0] + '</div>');
                        });
                    }
                    else {
                        showToastMessage(response.message,false);
                    }
                }
            },
            beforeSend: function (){
                $(".cover-spin").show();
            },
            error: function (xhr) {
                $(".cover-spin").hide();
                showToastMessage('Something went wrong!!',false);
            },
        });
    })
    </script>
@endsection
