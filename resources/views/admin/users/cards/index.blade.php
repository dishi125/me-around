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
        <a href="javascript:void(0);" onclick="giveEXP();" class="btn btn-primary">Give EXP</a>
        <a href="javascript:void(0);" onclick="giveCards({{$id}});" class="ml-2 btn btn-primary">Give Card</a>
        <?php /*
        <a href="{{ route('admin.user.community.create.view',[$id]) }}" class="btn btn-primary">Create New</a>
        <a href="{{ route('admin.user.view.community',[$id]) }}" class="ml-3 btn btn-primary">View Community</a>
       */  ?>
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
                                <table class="table table-striped" id="card-table">
                                    <thead>
                                    <tr>
                                        <th>Card Title</th>
                                        <th>Range</th>
                                        <th>Background Rive</th>
                                        <th>Character Rive</th>
                                        <th>Date</th>
                                        <th>Card Level</th>
                                        <th>Love Count</th>
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
<div class="modal fade" id="deletePostModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>
<div class="modal fade" id="giveCardModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>

<div class="modal fade" id="giveExpModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" style="max-width: 550px;">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5>Give EXP</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center">
                <div class="align-items-xl-center mb-3">

                <div class="row">
                    <div class="col-md-2 align-items-center d-flex">
                        <label>EXP</label>
                    </div>
                    <div class="col-md-7"><input name="exp" type="text" id="user-exp" class="numeric form-control" value="">
                        <label id="credit-error" class="error-msg" for="user-exp"></label>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary" id="save-user-exp">Save</button>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>
@section('scripts')
    <script>
        var cardsTable = "{{ route('admin.user.cards.table',[$id]) }}";
        var giveEXPCoin = "{{ route('admin.user.give.exp',[$id]) }}";
        var giveCard = "{{ route('admin.give.user.card',[$id]) }}";
        var removeCard = "{{ route('admin.remove.user.card',[$id]) }}";
        var profileModal = $("#deletePostModal");
        var csrfToken = "{{csrf_token()}}";
    </script>
    <script src="{!! asset('js/chocolat.js') !!}"></script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script src="{!! asset('js/pages/users/cards.js') !!}"></script>
@endsection
