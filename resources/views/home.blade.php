@extends('layouts.app')
@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection
@section('content')
<!-- Main Content -->
<div class="main-container">
  <section class="section">
    <div class="row">
      <div class="col-lg-2 col-md-2 col-sm-2 pr-0">
        <div class="card card-statistic-2">
          <div class="card-stats">
            <div class="card-stats-title">Year Income</div>
          </div>
          <div class="card-wrap">
            <div class="card-body">
              <h4>100,000,000</h4>
            </div>
          </div>
          <div class="card-body">
            <div class="text-small text-muted"><i class="fas fa-caret-down text-danger"></i> 13.8%</div>
          </div>
        </div>
      </div>
      <div class="col-lg-2 col-md-2 col-sm-2 pr-0">
        <div class="card card-statistic-2">
          <div class="card-stats">
            <div class="card-stats-title">Month Income</div>
          </div>
          <div class="card-wrap">
            <div class="card-body">
              <h4>40,000,000</h4>
            </div>
          </div>
          <div class="card-body">
            <div class="text-small text-muted"><i class="fas fa-caret-down text-danger"></i> 13.8%</div>
          </div>
        </div>
      </div>
      <div class="col-lg-2 col-md-2 col-sm-2 pr-0">
        <div class="card card-statistic-2">
          <div class="card-stats">
            <div class="card-stats-title">Day Income</div>
          </div>
          <div class="card-wrap">
            <div class="card-body">
              <h4>5,000,000</h4>
            </div>
          </div>
          <div class="card-body">
            <div class="text-small text-muted"><i class="fas fa-caret-down text-danger"></i> 13.8%</div>
          </div>
        </div>
      </div>
      <div class="col-lg-6 col-md-6 col-sm-6 mb-sm-4">
        <div class="card card-statistic-2">
          <div class="card-stats">
            <div class="card-stats-title">Manager recommend code income</div>
          </div>
          <div class="card-wrap">
            <div class="card-body">
              <button type="button" class="btn btn-dark">All</button>
              <button type="button" class="btn btn-dark">Hospital</button>
              <button type="button" class="btn btn-dark">Shop</button>
            </div>
          </div>
          <div class="card-body">
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-12">
        <div class="card">
          <div class="card-header">
            <h4>Statistics</h4>
          </div>
          <div class="card-body">
            <canvas id="myChart" height="158"></canvas>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
@section('page-script')
<script src="{!! asset('js/chart.min.js') !!}"></script>
<script src="{!! asset('js/pages/home.js') !!}"></script>
@endsection