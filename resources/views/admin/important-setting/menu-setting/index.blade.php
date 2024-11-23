@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/bootstrap-toggle/bootstrap4-toggle.min.css') !!}">
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
    <style>
        .select2-container--default .select2-results>.select2-results__options {
            max-height: 500px !important;
        }
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
                    @include('admin.important-setting.common-setting-menu', ['active' => 'menu_setting'])
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
                        </div>
                        <div class="tab-pane fade show active" id="menuData" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped" id="menu-setting-table">
                                    <thead>
                                    <tr>
                                        <th>Menu Name</th>
                                        <th>Show</th>
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
    <div class="modal fade" id="editMenuModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    </div>
    <div class="cover-spin"></div>
    <!-- Modal -->

@endsection

@section('scripts')
    <script>
        var SettingTable = "{!! route('admin.important-setting.menu-setting.table') !!}";
        var updateOrder = "{!! route('admin.menu-setting.update.order') !!}";
        var updateOnOff = "{!! route('admin.menu-setting.update') !!}";
        var updateCategoryOnOff = "{!! route('admin.menu-setting.updatecategory') !!}";
        var csrfToken = "{{csrf_token()}}";
    </script>
    <script src="{!! asset('plugins/bootstrap-toggle/bootstrap4-toggle.min.js') !!}"></script>
    <script src="{!! asset('plugins/jquery-ui/jquery-ui.js') !!}"></script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script src="{!! asset('js/pages/important-setting/menu-setting.js') !!}"></script>
@endsection

@section('page-script')

@endsection
