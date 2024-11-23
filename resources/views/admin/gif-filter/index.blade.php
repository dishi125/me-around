@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
@endsection

@section('header-content')
    <h1>@if (@$title) {{ @$title }} @endif</h1>
    <div class="section-header-button">
        <?php $user = Auth::user();?>
        <a href="{{ route('admin.gif-filter.create') }}" class="btn btn-primary">Adding filter</a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="buttons">
                        <a href="{!! route('admin.music-track.index') !!}" class="btn btn-primary mt-2">Music track</a>
                        <a href="{!! route('admin.gif-filter.index') !!}" class="btn btn-primary mt-2 active">Gif filter</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="gif_filter_data">
                            <thead>
                            <tr>
                                <th>Title</th>
                                <th>File</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        var gifFilter = "{{ route('admin.gif-filter.table') }}";
        var csrfToken = "{{csrf_token()}}";
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script>
        $(function () {
            var dataTable = $('#gif_filter_data').DataTable({
                "responsive": true,
                "processing": true,
                "serverSide": true,
                "deferRender": true,
                "ajax": {
                    "url": gifFilter,
                    "dataType": "json",
                    "type": "POST",
                    "data": { _token: csrfToken }
                },
                "columns": [
                    { "data": "title", orderable: false },
                    { "data": "file", orderable: false },
                ]
            });
        });
    </script>
@endsection
