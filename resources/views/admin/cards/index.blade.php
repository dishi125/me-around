@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
<style>
.table-responsive button#show-profile {width: 130px;margin:5px;white-space:normal;}
.table-responsive .shops-date button#show-profile{width:180px;}
.table-responsive .shops-rate button#show-profile{width:80px;}
.table-responsive td span{margin:5px;}
</style>
@endsection

@section('header-content')
<div class="col-md-11">
    <h1>@if (@$title) {{ @$title }} @endif</h1>
</div>
<div class="col-md-1">
    <a href="{{ route('admin.cards.create') }}" class="btn btn-primary">Add rive</a>
</div>
@endsection

@section('content')
<div class="row">

    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                <ul class="nav nav-pills mb-4" id="myTab3" role="tablist">
                    <li class="nav-item mr-3">
                        <a class="nav-link active btn btn-primary filterButton mb-2" id="all-data" data-filter="all" data-tabid="" data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="true">All</a>
                    </li>
                    @if($cards_tabs)
                    @foreach($cards_tabs as $tabs)
                        <li class="nav-item mr-3">
                            <a class="nav-link active btn btn-primary filterButton mb-2" id="all-data" data-filter="all" data-tabid="{{ $tabs->id }}" data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="true">{{ $tabs->name}}</a>
                        </li>
                    @endforeach
                    @endif
                </ul>

                <div class="tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="all-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Range</th>
                                        <th>Order</th>
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
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    {{-- <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5>View Card</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center">
                <canvas id="background_canvas" width="400" height="300"></canvas>
                <canvas id="forground_canvas" width="400" height="300"></canvas>
            </div>
        </div>
    </div> --}}
</div>

@section('scripts')
<script src="{!! asset('plugins/rive/rive.min.js') !!}"></script>
<?php /* <script src="https://unpkg.com/rive-canvas@0.0.10/rive.js"></script> */ ?>
<script>
   /*  $(function() {
        new rive.Rive({
            src: 'https://chatsvc-2021.s3.ap-northeast-2.amazonaws.com/uploads/cards/character_rive/mearound_-_rotatelogo.riv',
            canvas: document.getElementById('canvas'),
            autoplay: true,
        });
        new rive.Rive({
            src: 'https://chatsvc-2021.s3.ap-northeast-2.amazonaws.com/uploads/cards/character_rive/profile_logostable_day.riv',
            canvas: document.getElementById('canvasnew'),
            autoplay: true,
        });
    }); */


    var cardsTable = "{{ route('admin.cards.table') }}";
    var csrfToken = "{{csrf_token()}}";
    var deleteUser = "{{ route('admin.community.delete.user') }}";
    var deleteModal = $("#deleteModal");
     // autoplays the first animation in the default artboard

</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/cards/cards.js') !!}"></script>

@endsection
