@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
    <link rel="stylesheet" href="{!! asset('css/chocolat.css') !!}">
@endsection

@section('header-content')
    <h1>@if (@$title) {{ @$title }} @endif</h1>
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
                                        <th>Title</th>
                                        <th>Description</th>
                                        {{-- <th>Like Count</th> --}}
                                        <th>Comment Count</th>
                                        <th>View Count</th>
                                        <th>Images</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        {{-- <th>Action</th> --}}
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
        var allUserCommunityTable = "{!! route('admin.outside-community-user.user.community.list',[$id]) !!}";
        var csrfToken = csrfToken;
    </script>
    <script src="{!! asset('js/chocolat.js') !!}"></script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script src="{!! asset('js/pages/users/outside-community.js') !!}"></script>
@endsection
