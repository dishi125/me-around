@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
    <style>
    </style>
    <link rel="stylesheet" href="{!! asset('css/chocolat.css') !!}">
@endsection

@section('header-content')
    <h1>@if (@$title) {{ @$title }} @endif</h1>
    <div class="section-header-button">
        <a href="{{ route('admin.user.community.create.view',[$id]) }}" class="btn btn-primary">Create New</a>
        <a href="{{ route('admin.user.view.community',[$id]) }}" class="ml-3 btn btn-primary">View Community</a>
    </div>
    <div class="checkbox-container">
        <input type="checkbox" name="outside-user" ajaxurl="{{route('admin.make.user.outside',[$id])}}"
               id="outside-user" {{$userDetails->is_outside == true ? 'checked' : ''}} />
        <label for="outside-user">Outside User</label>
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
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Like Count</th>
                                        <th>Comment Count</th>
                                        <th>View Count</th>
                                        <th>Images</th>
                                        <th>Type</th>
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
<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>

<div class="modal fade" id="deletePostModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>
<div class="modal fade" id="editEmailModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>

@section('scripts')
    <script>
        var editModal = $("#editModal");
        var editEmailModal = $("#editEmailModal");
        var allUserCommunityTable = "{{ route('admin.user.community.all.table',[$id]) }}";
        var profileModal = $("#deletePostModal");
        var csrfToken = "{{csrf_token()}}";
    </script>
    <script src="{!! asset('js/chocolat.js') !!}"></script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script src="{!! asset('js/pages/users/community.js') !!}"></script>
@endsection
