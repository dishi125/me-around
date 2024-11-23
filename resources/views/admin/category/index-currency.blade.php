@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
@endsection

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
<div class="section-header-button">
    <?php $user = Auth::user();?>
    <a href="{{ route('admin.currency.create') }}" class="btn btn-primary">Add New</a>
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
                    <table class="table table-striped" id="currency_data">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Created At</th>
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
<div class="modal fade" id="currencyDeleteModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
@endsection

@section('scripts')
<script>
        var pageModel = $("#currencyDeleteModal");
        var currencyCategory = "{{ route('admin.currency.table') }}";
        var csrfToken = "{{csrf_token()}}";    
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/category/currency.js') !!}"></script>
@endsection