@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
<style>

</style>
@endsection

@section('header-content')
<h1>{{ @$title }}</h1>
<div class="section-header-button">
    <a href="{{ route('admin.create.card.music',['card' => $card]) }}" class="btn btn-primary">Add New</a>
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
                                        <th>Date</th>
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
<!-- Modal -->
<div class="modal fade" id="deleteMusicModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
@endsection

@section('scripts')
<script>
    var csrfToken = "{{csrf_token()}}";
    var getJson = "{{ route('admin.card.music.get.data',[$card]) }}";
    var updateOrder = "{!! route('admin.card.music.update.order') !!}";
</script>
<script src="{!! asset('plugins/jquery-ui/jquery-ui.js') !!}"></script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/cards/music/index.js') !!}"></script>
@endsection
