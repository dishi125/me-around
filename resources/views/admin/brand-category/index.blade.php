@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
<style>
    .table-responsive{
        overflow-x: hidden;
    }
</style>
@endsection

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
<div class="section-header-button">
    <a href="{{ route('admin.brand-category.create') }}" class="btn btn-primary">Add New</a>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-3">
                        <div class="form-group">
                            {{--{!! Form::label('country', __(Lang::get('forms.association.country'))); !!}--}}
                            {!!Form::select('country', $countries, '' , ['class' => 'form-control select2','placeholder' => __(Lang::get('forms.association.country'))])!!}
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped" id="category_data">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Korean Name</th>
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

<!-- Modal -->
<div class="modal fade" id="categoryDeleteModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
@endsection

@section('scripts')
<script>
        var pageModel = $("#categoryDeleteModal");
        var category = "{{ route('admin.brand-category.table') }}";
        var updateOrder = "{!! route('admin.brand-category.update.order') !!}";
        var csrfToken = "{{csrf_token()}}";    
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('plugins/jquery-ui/jquery-ui.js') !!}"></script>
<script src="{!! asset('js/pages/brand-category/index.js') !!}"></script>
@endsection