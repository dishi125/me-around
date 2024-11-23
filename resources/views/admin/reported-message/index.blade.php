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
                                <table class="table table-striped" id="Reported-message-table">
                                    <thead>
                                        <tr>
                                            <th>Reporter Name</th>
                                            <th>Reported message</th>
                                            <th>Reported At</th>
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

<div class="modal fade" id="MessagePhotoModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header justify-content-center" style="border-bottom:none; padding: 8px;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center" style="padding: 0px;" id="modelImageShow">
                <img src="{!! asset('img/logo-main.png') !!}" class="w-100 " id="modelImageEle" />
            </div>
        </div>
    </div>
</div>

@section('scripts')
    <script>
        var allTable = "{!! route('admin.reported-message.table') !!}";
        var csrfToken = "{{ csrf_token() }}";

        $(function() {
            var allShop = $("#Reported-message-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                "order": [[ 2, "desc" ]],
                ajax: {
                    url: allTable,
                    dataType: "json",
                    type: "POST",
                    data: { _token: csrfToken }
                },
                columns: [
                    { data: "reporter_name", orderable: true },
                    { data: "reported_message", orderable: false },
                    { data: "reported_at", orderable: true },
                ]
            });
        });

        function showImage(imageSrc){
            // Get the modal
            console.log(imageSrc)
            $('#modelImageShow').html('');
            var validExtensions = ["jpg","jpeg","gif","png",'webp'];
            var extension = imageSrc.split('.').pop().toLowerCase();
            if(imageSrc){
                if($.inArray(extension, validExtensions) == -1){
                    $('#modelImageEle').remove();
                    $('#modelImageShow').html('<video width="100%" height="300" controls poster="" id="modelVideoEle"><source src="'+imageSrc+'" type="video/mp4">Your browser does not support the video tag.</video>');
                }else{
                    $('#modelVideoEle').remove();
                    $('#modelImageShow').html('<img src="'+imageSrc+'" class="w-100 " id="modelImageEle" />');
                }
                $("#MessagePhotoModal").modal('show');
            }
        }
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
@endsection
