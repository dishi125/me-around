@extends('layouts.app')

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
                    @if ($message = Session::get('success'))
                        <div class="alert alert-info alert-block">
                            <button type="button" class="close" data-dismiss="alert">×</button>
                            <strong>{{ $message }}</strong>
                        </div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-striped" id="role_data">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    </div>
@endsection
@section('scripts')
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
@endsection
@section('page-script')
        <script>
            var model = $("#deleteModal");
            var dataTable;
           function deleteRecord(id) {
                $.get(baseUrl + '/admin/roles/delete/' + id, function (data, status) {
                    model.html('');
                    model.html(data);
                    model.modal('show');
                });
            }
            $(function () {
                dataTable = $('#role_data').DataTable({
                    "responsive": true,
                    "processing": true,
                    "serverSide": true,
                    "deferRender": true,
                    "ajax": {
                        "url": "{{ route('admin.roles.table') }}",
                        "dataType": "json",
                        "type": "POST",
                        "data": {_token: "{{csrf_token()}}"}
                    },
                    "columns": [
                        {"data": "id", orderable: true },
                        {"data": "name", orderable: true },
                        {"data": "actions", orderable: false }
                    ]
                });
            });
        </script>
@endsection
