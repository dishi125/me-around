@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
<style>

</style>
@endsection

@section('header-content')
<h1>{{ @$title }}</h1>
<div class="section-header-button">
    <a href="{{ route('admin.wedding.create') }}" class="btn btn-primary">Add New</a>
</div>
<div class="section-header-button">
    <a href="{{ route('admin.wedding.settings') }}" class="btn btn-primary">Important Settings</a>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="all-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Address</th>
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
    var getJson = "{{ route('admin.wedding.get.data') }}";
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/wedding/index.js') !!}"></script>
@endsection
