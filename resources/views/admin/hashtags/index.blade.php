@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
    <link rel="stylesheet" href="{!! asset('plugins/bootstrap-toggle/bootstrap4-toggle.min.css') !!}">
    <style>
        .table-responsive button#show-profile {
            width: auto;
            margin: 5px 5px 5px 0;
            white-space: normal;
        }

        .table-responsive .shops-date button#show-profile {
            width: 180px;
        }

        .table-responsive .shops-rate button#show-profile {
            width: 80px;
        }

        .table-responsive td span {
            margin: 5px;
        }
    </style>
@endsection

@section('header-content')
    <h1>
        @if (@$title)
            {{ @$title }}
        @endif
    </h1>
@endsection

@section('content')
    <form name="filter-form" id="filter-form">
        <input type="hidden" name="categoryFilter" value="{{ $first_cat }}" />
    </form>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="activity-tabs new-activity-tabs">
                        <div class="filter-cat">
                            @foreach($categories as $sc)
                                <?php
                                $shop_ids = \App\Models\Shop::where('category_id',$sc->id)->where('status_id', \App\Models\Status::ACTIVE)->pluck('id')->toArray();
                                $shop_posts = \App\Models\ShopPost::whereIn('shop_id',$shop_ids)->count();
                                ?>
                                <input type='radio' id='cat_{{$sc->id}}' name='t'><label class="categories activity-label" data-categoryID='{{$sc->id}}' for='cat_{{$sc->id}}' >{{$sc->name}} ({{ $sc->shops_count }}) <br>{{ $shop_posts }}</label>
                            @endforeach
                            <div id='slider'></div>
                        </div>

                        <div class='content'>
                            <div class="tab-content" id="myTabContent2">
                                <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="all-table">
                                            <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Hashtag</th>
                                                <th></th>
                                                <th>Posts</th>
                                                <th>Category Name</th>
                                                <th>Recent Post</th>
                                                <th></th>
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
        </div>
    </div>
    <div class="cover-spin"></div>
@endsection

@section('scripts')
    <script>
        var csrfToken = "{{ csrf_token() }}";
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script src="{!! asset('plugins/bootstrap-toggle/bootstrap4-toggle.min.js') !!}"></script>
    <script src="{!! asset('plugins/jquery-ui/jquery-ui.js') !!}"></script>
    <script>
        $(document).ready(function (){
            loadDataTable();
        });

        $(document).on('click','.categories',function(){
            var categoryFilter = $(this).attr('data-categoryID');
            $('input[name="categoryFilter"]').val(categoryFilter);
            $('#all-table').DataTable().destroy();
            loadDataTable();
        });

        function loadDataTable(){
            var categoryFilter = $('input[name="categoryFilter"]').val();

            var allHospital = $("#all-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                // "order": [[ 5, "desc" ]],
                ajax: {
                    url: "{{ route('admin.hashtags.table') }}",
                    dataType: "json",
                    type: "POST",
                    data: { _token: csrfToken, categoryFilter: categoryFilter },
                    dataSrc: function ( json ) {
                        setTimeout(function() {
                            $('.toggle-btn').bootstrapToggle();
                        }, 300);
                        return json.data;
                    }
                },
                createdRow: function(row, data, dataIndex) {
                    $(row).attr('data-id', data.id).addClass('row1');
                    $('.toggle-btn').bootstrapToggle();
                },
                columns: [
                    { data: "rank", orderable: false },
                    { data: "hashtag_name", orderable: false },
                    { data: "hide_show", orderable: false },
                    { data: "posts", orderable: false },
                    { data: "category", orderable: false },
                    { data: "recent_post_image", orderable: false },
                    { data: "action", orderable: false },
                ]
            });
        }

        $(document).on('change','.hide-show-toggle-btn',function(e){
            var dataID = $(this).attr('data-id');
            $.ajax({
                type: "POST",
                dataType: "json",
                url: "{{ route('admin.hashtags.update.show-hide') }}",
                data: {
                    data_id: dataID,
                    checked: e.target.checked,
                    _token: csrfToken,
                },
                success: function (response) {
                    $("#all-table").dataTable().api().ajax.reload();
                },
            });
        });
    </script>
@endsection
