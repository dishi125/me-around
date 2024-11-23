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
                                <table class="table table-striped" id="Order-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('datatable.order.depositor') }}</th>
                                            <th>{{ __('datatable.order.user_name') }}</th>
                                            <th>{{ __('datatable.order.challenge') }}</th>
                                            <th>{{ __('datatable.order.amount') }}</th>
                                            <th>{{ __('datatable.order.created_at') }}</th>
                                            <th></th>
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
        var allTable = "{{ route('challenge.order.all.table') }}";
        var csrfToken = "{{ csrf_token() }}";
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script>
        $(document).ready(function (){
            loadTableData();
        })

        function loadTableData() {
            var allData = $("#Order-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                order: [[ 4, "DESC" ]],
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
                        data: "depositor_name",
                        orderable: true
                    },
                    {
                        data: "user_name",
                        orderable: true
                    },
                    {
                        data: "challenge_name",
                        orderable: true
                    },
                    {
                        data: "amount",
                        orderable: false
                    },
                    {
                        data: "created_at",
                        orderable: true
                    },
                    {
                        data: "action",
                        orderable: false
                    },
                ]
            });
        }
    </script>
@endsection
