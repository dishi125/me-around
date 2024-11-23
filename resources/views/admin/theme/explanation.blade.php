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
                @include('admin.important-setting.common-setting-menu', ['active' => 'explanation'])
            </div>
        </div>
    </div>
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
                                        <th>Value</th>
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
<div class="modal fade" id="PostPhotoModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header justify-content-center" style="border-bottom:none; padding: 8px;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center" style="padding: 0px;">
                <img src="{!! asset('img/logo-main.png') !!}" class="w-100 " id="modelImageEle" />
            </div>
        </div>
    </div>
</div>
@endsection


@section('scripts')
<script>
    var allUserTable = "{{ route('admin.explanation.all.table') }}";
    var csrfToken = "{{csrf_token()}}";
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/theme/index.js') !!}"></script>
@endsection
