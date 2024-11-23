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
                            <div class="table-responsive">
                                <table class="table table-striped" id="category-setting-table">
                                    <thead>
                                    <tr>
                                        <th>Category Name</th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        {{--  <th>Show</th>
                                        <th>Icon</th>
                                        <th>Order</th>
                                        <th>Default Hidden Category</th>
                                        <th>Actions</th>  --}}
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

@endsection

@section('scripts')
    <script>
        var SettingTable = "{!! route('admin.important-setting.category-setting.table') !!}";
        var updateOrder = "{!! route('admin.category-setting.update.order') !!}";
        var updateOnOff = "{!! route('admin.category-setting.update') !!}";
        var updateOnOffHidden = "{!! route('admin.category-setting.update.hidden') !!}";
        var updateOrderBigCategory = "{!! route('admin.category-setting.update.big-category.order') !!}";
        var getBigCategory = "{!! route('admin.category-setting.display.big-category') !!}";
        var csrfToken = "{{csrf_token()}}";
    </script>
    <script src="{!! asset('plugins/bootstrap-toggle/bootstrap4-toggle.min.js') !!}"></script>
    <script src="{!! asset('plugins/jquery-ui/jquery-ui.js') !!}"></script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script src="{!! asset('js/pages/important-setting/category-setting.js') !!}"></script>
@endsection

@section('page-script')

@endsection
