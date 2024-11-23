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
                <div class="buttons">
                    <a href="{!! route('admin.important-setting.index') !!}" class="btn btn-primary mt-2 active">Credit Rating Custom</a>
                    <a href="{!! route('admin.important-setting.limit-custom.index') !!}" class="btn btn-primary mt-2">Limit Custom</a>
                    <a href="{!! route('admin.important-setting.limit-custom.index-links') !!}" class="btn btn-primary mt-2">Links</a>
                    <input type="submit" class="btn btn-primary mt-2" id="important_setting_notification_shop" name="important_setting_notification_shop" value="Send Notification to Shop Users">
                    <input type="submit" class="btn btn-primary mt-2" id="important_setting_notification_hospital" name="important_setting_notification_hospital" value="Send Notification to Hospital Users">
                    <a href="{!! route('admin.explanation.index') !!}" class="btn btn-primary mt-2">Explanation</a>
                    <a href="{!! route('admin.important-setting.show-hide.index') !!}" class="btn btn-primary mt-2">Show & Hide</a>
                    <a href="{!! route('admin.important-setting.menu-setting.index') !!}" class="btn btn-primary mt-2">Menu Settings</a>
                    <a href="{!! route('admin.important-setting.category-setting.index') !!}" class="btn btn-primary mt-2">Category Settings</a>
                    <a href="{!! route('admin.important-setting.app-version.index') !!}" class="btn btn-primary mt-2">Manage App Version</a>
                    <a href="{!! route('admin.important-setting.policy-pages.index') !!}" class="btn btn-primary mt-2">Policy Pages</a>
                    <a href="{!! route('admin.important-setting.instagram-settings') !!}" class="btn btn-primary mt-2">Instagram Settings</a>
                    <a href="{!! route('admin.important-setting.payple-setting.index') !!}" class="btn btn-primary mt-2">Payple Settings</a>
                    <a href="{!! route('admin.important-setting.global-price-setting.index') !!}" class="btn btn-primary mt-2">Global Price Settings</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="hospitalData" role="tabpanel" aria-labelledby="hospital-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="hospital-setting-table">
                                <thead>
                                    <tr>
                                        <th>Package Name</th>
                                        <th>Package Type</th>
                                        <th>Deducting Rate</th>
                                        <th>Regular Payment</th>
                                        <th>Post</th>
                                        <th>KM</th>
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

@section('scripts')
<script>
var hospitalSettingTable = "{!! route('admin.important-setting.all.hospital.table') !!}";
var shopSettingTable = "{!! route('admin.important-setting.all.shop.table') !!}";
var sendNotification = "{!! route('admin.important-setting.send.notification') !!}";
var csrfToken = "{{csrf_token()}}";
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/important-setting/credit-rating-custom.js') !!}"></script>
@endsection

@section('page-script')
@endsection
