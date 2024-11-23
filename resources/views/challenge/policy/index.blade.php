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
    <div class="section-header-button">
        <a href="{{ route('challenge.policy.create') }}" class="btn btn-primary">Add New</a>
    </div>
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
                                <table class="table table-striped" id="all-table">
                                    <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Date</th>
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
<div class="modal fade" id="deletePageModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

@section('scripts')
    <script>
        var allTable = "{{ route('challenge.policy.get.data') }}";
        var csrfToken = "{{ csrf_token() }}";
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script>
        $(document).ready(function (){
            loadTableData();
        })

        function loadTableData() {
            $("#all-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                "order": [[ 0, "asc" ]],
                ajax: {
                    url: allTable,
                    dataType: "json",
                    type: "POST",
                    data: { _token: csrfToken }
                },
                columns: [
                    { data: "name", orderable: true },
                    { data: "date", orderable: true },
                    { data: "actions", orderable: false },
                ]
            });
        }

        function deletePageConfirmation(page_id){
            $.get(baseUrl + '/challenge/policy/get/delete/' + page_id, function (data, status) {
                $("#deletePageModal").html('');
                $("#deletePageModal").html(data);
                $("#deletePageModal").modal('show');
            });
        }

        function deletePage(page_id){
            if(page_id){
                $.ajax({
                    url: baseUrl + "/challenge/policy/delete",
                    method: 'POST',
                    data: {
                        _token: csrfToken,
                        page_id : page_id,
                    },
                    beforeSend: function(){ $(".cover-spin").show(); },
                    success: function(response) {
                        $(".cover-spin").hide();
                        $("#deletePageModal").modal('hide');
                        if(response.success == true){
                            showToastMessage(response.message,true);
                            $('#all-table').DataTable().ajax.reload();
                        }else {
                            showToastMessage(response.message,false);
                        }
                    }
                });
            }
        }
    </script>
@endsection
