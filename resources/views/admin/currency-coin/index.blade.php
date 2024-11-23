@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
@endsection

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
<div class="section-header-button">
    <?php $user = Auth::user();?>
    <a href="{{ route('admin.currency.coin.create.currency') }}" class="btn btn-primary">Add New</a>
</div>
@endsection

@section('content')
<div class="row">    
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">                
                <div class="buttons">
                    <a href="{!! route('admin.currency-coin.index') !!}" class="btn btn-primary">Reload Coin Currency</a>
                    <a href="{!! route('admin.currency-coin.coin-index') !!}" class="btn btn-primary">Currency Coin</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="currency_data">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Name</th>
                                <th>Bank Name</th>
                                <th>Account Number</th>
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
        var currencyList = "{{ route('admin.currency.coin.list.table') }}";
        var csrfToken = "{{csrf_token()}}";    
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/currency-coin/index.js') !!}"></script>
@endsection