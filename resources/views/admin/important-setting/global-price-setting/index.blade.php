@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
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
                        <div class="row">
                            <div class="col-3">
                                <div class="form-group">
                                    <a href="{!! route('admin.important-setting.global-price-setting.price-category.create') !!}" class="btn btn-primary mt-1">Add Price Category</a>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade show active" id="categoryData" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped" id="global-price-setting-table">
                                    <thead>
                                    <tr>
                                        <th></th>
                                        <th>Category Name</th>
                                        <th>Korean Name</th>
                                        <th>Actions</th>
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
<div class="modal fade" id="profileModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

@section('scripts')
    <script>
        var SettingTable = "{!! route('admin.important-setting.global-price-setting.table') !!}";
        var profileModal = $("#profileModal");
        var csrfToken = "{{csrf_token()}}";
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script src="{!! asset('js/pages/important-setting/global-price-setting.js') !!}"></script>
@endsection

@section('page-script')

@endsection
