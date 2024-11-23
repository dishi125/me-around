@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
@endsection

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
<div class="section-header-button">
    <?php $user = Auth::user();?>
    <a href="{{ route('admin.category.create') }}" class="btn btn-primary">Add New</a>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        @include('layouts.category-menu')
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="category_hospital_data">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Korean Name</th>
                                <th>Parent Category</th>
                                <th>Category Type</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="categoryDeleteModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
@endsection

@section('scripts')
<script>
        var pageModel = $("#categoryDeleteModal");
        var communityCategory = "{{ route('admin.category.community.table') }}";
        var csrfToken = "{{csrf_token()}}";    
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/category/community.js') !!}"></script>
@endsection