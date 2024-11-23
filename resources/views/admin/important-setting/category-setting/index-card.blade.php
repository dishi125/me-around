@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/bootstrap-toggle/bootstrap4-toggle.min.css') !!}">
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
    <style>
        #sortable > div { float: left; }
    </style>
@endsection
@section('header-content')
    <h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    @include('admin.important-setting.common-setting-menu', ['active' => 'category_setting'])
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">

                    <div class="tab-content" id="myTabContent2">
                        <div class="row">
                            <div class="col-3">
                                <div class="form-group">
                                    {{--{!! Form::label('country', __(Lang::get('forms.association.country'))); !!}--}}
                                    {!!Form::select('country', $countries, 'KR' , ['class' => 'form-control select2','placeholder' => __(Lang::get('forms.association.country'))])!!}
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="form-group">
                                    <a href="{!! route('admin.important-setting.category-setting.create') !!}" class="btn btn-primary mt-1">Add Category</a>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div id="sortable_big_cats" class="ui-state-default">
                                @foreach($big_categories as $big_category)
                                <div class="ui-state-default badge badge-light mt-1 ml-2" id="item-{{ $big_category->id }}">{{ $big_category->name }}</div>
                                @endforeach
                            </div>
                        </div>
                        <div class="tab-pane fade show active" id="categoryData" role="tabpanel">
                            <div class="card-container d-flex">

                                @foreach ($menuItem as $menuCard)
                                    <div class="card-outer">
                                        <h4 id="title_{{$menuCard->menu_key}}">{{$menuCard->menu_name}}</h4>
                                        <div class="column card-column" id="{{$menuCard->menu_key}}">

                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="cover-spin"></div>
    <!-- Modal -->

@endsection

@section('scripts')
    <script src="{!! asset('plugins/Sortable/Sortable.min.js') !!}"></script>
    <script>
        var SettingTable = "{!! route('admin.important-setting.category-setting.table') !!}";
        var updateOrder = "{!! route('admin.category-setting.update.order') !!}";
        var updateOnOff = "{!! route('admin.category-setting.update') !!}";
        var updateOnOffHidden = "{!! route('admin.category-setting.update.hidden') !!}";
        var updateOrderBigCategory = "{!! route('admin.category-setting.update.big-category.order') !!}";
        var getBigCategory = "{!! route('admin.category-setting.display.big-category') !!}";
        var csrfToken = "{{csrf_token()}}";

        $(document).on("change", 'select[name="country"]', function () {
            var filter = $(this).val();
            loadCardDetails(filter);
            load_big_category(filter);
        });

        loadCardDetails($('select[name="country"]').val());

        /*$(function (){
            $(".column").sortable({
                items: ".list-group-item",
                cursor: "move",
                opacity: 0.6,
                update: function (event, ui) {
                    // POST to server using $.post or $.ajax
                    /!*$.ajax({
                        data: {"country": country, "orders": data},
                        type: 'POST',
                        url: updateOrderBigCategory,
                        beforeSend: function() {
                            $('.cover-spin').show();
                        },
                        success:function(response) {
                            $('.cover-spin').hide();
                        },
                        error:function (response, status) {
                            $('.cover-spin').hide();
                        }
                    });*!/
                    var order = [];
                    $(this).children('.list-group-item').each(function (index, element) {
                        order.push({
                            id: $(this).attr("data-cat-id"),
                            position: index + 1,
                        });
                    });
                }
            });
        })*/

        function loadCardDetails(country){
            $.ajax({
                type: "POST",
                dataType: "json",
                url: "{!! route('admin.important-setting.category-setting.card') !!}",
                data: {
                    country: country,
                    _token: csrfToken,
                },
                success: function (response) {
                    $('.column').empty();

                    $.each(response.title, function (key, val) {
                        $("#title_"+key).text(val);
                    });

                    $.each(response.html, function (key, val) {
                        $("#"+key).html(val);
                    });

                    setTimeout(function () {
                        $('.toggle-btn-display').bootstrapToggle();
                    }, 300);

                    const items = document.querySelectorAll('.list-group-item');
                    const columns = document.querySelectorAll('.column');
                    columns.forEach((column) => {
                         new Sortable(column, {
                             group: "shared",
                             animation: 150,
                             ghostClass: "blue-background-class",
                             draggable : '.list-group-item',
                            onStart: function (evt) {
                                setTimeout(() => evt.item.className = 'invisible', 0)
                            },
                            onEnd: function (evt) {

                                evt.item.className = 'list-group-item';
                                console.log("onEnd");

                                console.log(evt.from.id)
                                console.log(evt.to.id)

                                console.log($(evt.item).attr('data-cat-id')); // dragged HTMLElement
                                console.log(evt.newIndex);
                                console.log(evt.oldIndex);

                                //code start- dishita
                                var from_order = [];
                                var to_order = [];
                                $("#"+evt.from.id).children('.list-group-item').each(function (index, element) {
                                    from_order.push({
                                        id: $(this).attr("data-cat-id"),
                                        position: index + 1,
                                    });
                                });
                                if(evt.from.id!=evt.to.id){
                                    $("#"+evt.to.id).children('.list-group-item').each(function (index, element) {
                                        to_order.push({
                                            id: $(this).attr("data-cat-id"),
                                            position: index + 1,
                                        });
                                    });
                                }
                                //code end - dishita
                                $.ajax({
                                    type: "POST",
                                    dataType: "json",
                                    url: "{!! route('admin.important-setting.category-setting.update-card-order') !!}",
                                    data: {
                                        country: $('select[name="country"]').val(),
                                        from_card: evt.from.id,
                                        to_card: evt.to.id,
                                        item_id: $(evt.item).attr('data-cat-id'),
                                        new_index: evt.newIndex,
                                        old_index: evt.oldIndex,
                                        _token: csrfToken,
                                        from_order: from_order,
                                        to_order: to_order
                                    },
                                    beforeSend: function(){ $(".cover-spin").show(); },
                                    success: function (response) {
                                        $(".cover-spin").hide();
                                    },
                                    error: function (){
                                        $(".cover-spin").hide();
                                    }
                                });

                                console.log(evt);
                            },
                         });
                    });


                },
            });
        }
    </script>

    <script src="{!! asset('plugins/bootstrap-toggle/bootstrap4-toggle.min.js') !!}"></script>
    <script src="{!! asset('plugins/jquery-ui/jquery-ui.js') !!}"></script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script src="{!! asset('js/pages/important-setting/category-setting.js') !!}"></script>
@endsection

@section('page-script')

@endsection
