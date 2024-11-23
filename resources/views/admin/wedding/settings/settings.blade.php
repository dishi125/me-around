@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
<style>

</style>
@endsection

@section('header-content')
<h1>{{ @$title }}</h1>
<div class="section-header-button">
    <a href="{{ route('admin.wedding.settings.create') }}" class="btn btn-primary">Add New</a>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <ul class="nav nav-pills m-4" id="myTab3" role="tablist">
                <li class="nav-item mr-3">
                    <a class="nav-link active btn btn-primary filterButton" id="all-data" data-filter="video_file" data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="true">Video Animation</a>
                </li>
                <li class="nav-item mr-3">
                    <a class="nav-link btn btn-primary filterButton" id="active-data" data-filter="audio_file" data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="false">Audio</a>
                </li>
            </ul>
            <div class="card-body">
                <div class="tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="setting-table">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>File</th>
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
<!-- Modal -->
<div class="modal fade" id="deleteWeddingModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
@endsection

@section('scripts')
<script>
    var csrfToken = "{{csrf_token()}}";
    var getJson = "{{ route('admin.wedding.setting.get.data') }}";
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/wedding/settings.js') !!}"></script>
@endsection
