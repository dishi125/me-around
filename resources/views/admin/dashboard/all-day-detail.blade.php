@extends('layouts.app')
@section('header-content')
<div class="col-md-9">
  <h1>@if (@$title) {{ @$title }} @endif</h1>
</div>
<div class="col-md-3">
  <a href="{!! route('admin.qr.code') !!}" target="_blank" class="btn btn-primary float-right">Qr-Code</a>
</div>
@endsection
@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
@endsection
@section('content')
<!-- Main Content -->
<div class="main-container">
  <section class="section">
    <div class="row">
      <div class="col-md-12">
          <div class="card">
              <div class="card-body">                
                  <div class="buttons">
                      <a href="{!! route('admin.dashboard.year.detail',$type) !!}" class="btn btn-primary">Year</a>
                      <a href="{!! route('admin.dashboard.month.detail',[$type,$current_date_encoded]) !!}" class="btn btn-primary">Month</a>
                      <a href="{!! route('admin.dashboard.day.detail',[$type,$current_date_encoded]) !!}" class="btn btn-primary">Day</a>
                  </div>
              </div>
          </div>
      </div>
    </div>
    <div class="row">     
      <div class="col-lg-12">
          <div class="card">
            <div class="card-body">    
              <div class="table-responsive">
                <table class="table table-striped" id="all-day-data-table">
                    <thead>
                        <tr>                                        
                            <th>Client Name</th>
                            <th>Business Type</th>
                            <th>Reloaded Amount</th>
                            <th>Total Reloaded Amount</th>
                            <th>Join By</th>
                            <th>Phone Number</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($dayIncome as $income)
                      <tr>
                        <td>{{$income->client_name}}</td>
                        <td>{{$income->category_name}}</td>
                        <td>{{number_format($income->amount)}}</td>
                        <td>{{$income->user_total_amount}}</td>
                        <td>{{$income->manager_name}}</td>
                        <td>{{$income->mobile}}</td>
                        <td>{{$income->formatted_created_at}}</td>
                      </tr>
                    @endforeach
                    </tbody>
                </table>
              </div>
            </div>
          </div>
      </div>
    </div>
  </section>
</div>
@endsection

@section('scripts')
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script>      
  $("#all-day-data-table").DataTable();
</script>
@endsection