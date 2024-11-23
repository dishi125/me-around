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
                            {!!Form::select('product_id', $products, '' , ['class' => 'form-control select2','placeholder' => __(Lang::get('forms.brands.product'))])!!}
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped" id="category_data">
                        <thead>
                            <tr>
                                <th>User Name</th>
                                <th>Product Name</th>
                                <th>Price</th>
                                <th>Phone Number</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
        var category = "{{ route('admin.product-orders.table') }}";
        var csrfToken = "{{csrf_token()}}";    
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script type="text/javascript" src="{!! asset('plugins/datatables/jszip.min.js') !!}"></script>
<script src="{!! asset('plugins/jquery-ui/jquery-ui.js') !!}"></script>
<script src="{!! asset('js/pages/product-orders/index.js') !!}"></script>
@endsection