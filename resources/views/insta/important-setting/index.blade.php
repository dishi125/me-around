@extends('insta-layouts.app')

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
                    <div class="table-responsive">
                        <table class="table table-striped" id="Important-settings-table">
                            <thead>
                            <tr>
                                <th>Field</th>
                                <th>Value</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="cover-spin"></div>
@endsection

@section('scripts')
    <script>
        var ImportantSettingTable = "{!! route('insta.important-setting.table') !!}";
        var csrfToken = "{{csrf_token()}}";
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script type="text/javascript">
        $(function() {
            var allHospital = $("#Important-settings-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                ajax: {
                    url: ImportantSettingTable,
                    dataType: "json",
                    type: "POST",
                    data: { _token: csrfToken}
                },
                columns: [
                    { data: "field", orderable: true },
                    { data: "value", orderable: true },
                    { data: "actions", orderable: false }
                ]
            });
        });
    </script>
@endsection

