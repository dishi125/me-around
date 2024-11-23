@extends('layouts.app')
@section('header-content')
<div class="col-md-9">
  <h1>@if (@$title) {{ @$title }} @endif</h1>
</div>
<div class="col-md-3">
  <a href="{!! route('qr.code') !!}" target="_blank" class="btn btn-primary float-right">Qr-Code</a>
</div>

@endsection
@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
@endsection
@section('content')
<!-- Main Content -->
<div class="main-container">
<?php
  $currentYear = Carbon::now()->format('Y');
  $years = [];
  for ($year = $currentYear; $year > $currentYear-5; $year--) {
    $years[$year] = $year;
  }
?>
@role('Admin')
  <section class="section">
    <div class="row">
      <div class="col-lg-2 col-md-2 col-sm-2 pr-0">
        <a href="{!! route('admin.dashboard.year.detail',$type) !!}">
          <div class="card card-statistic-2">
            <div class="card-stats">
              <div class="card-stats-title">Year Income</div>
            </div>
            <div class="card-wrap">
              <div class="card-body">
                <h4>{{number_format($yearIncome)}}</h4>
              </div>
            </div>
          </div>
        </a>
      </div>
      <div class="col-lg-2 col-md-2 col-sm-2 pr-0">
        <a href="{!! route('admin.dashboard.month.detail',[$type,$current_date_encoded]) !!}">
          <div class="card card-statistic-2">
            <div class="card-stats">
              <div class="card-stats-title">Month Income</div>
            </div>
            <div class="card-wrap">
              <div class="card-body">
                <h4>{{number_format($monthIncome)}}</h4>
              </div>
            </div>
          </div>
        </a>
      </div>
      <div class="col-lg-2 col-md-2 col-sm-2 pr-0">
        <a href="{!! route('admin.dashboard.day.detail',[$type,$current_date_encoded]) !!}">
          <div class="card card-statistic-2">
            <div class="card-stats">
              <div class="card-stats-title">Day Income</div>
            </div>
            <div class="card-wrap">
              <div class="card-body">
                <h4>{{number_format($dayIncome)}}</h4>
              </div>
            </div>
          </div>
        </a>
      </div>
      <div class="col-lg-6 col-md-6 col-sm-6 mb-sm-4">
        <div class="card card-statistic-2">
          <div class="card-stats">
            <div class="card-stats-title">Manager recommend code income</div>
          </div>
          <div class="card-wrap">
            <div class="card-body">
              <a href="{!! route('admin.dashboard.index') !!}" class="btn btn-dark">All</a>
              <a href="{!! route('admin.dashboard.hospital.index') !!}" class="btn btn-dark">Hospital</a>
              <a href="{!! route('admin.dashboard.shop.index') !!}" class="btn btn-dark">Shop</a>

            </div>
          </div>
          <div class="card-body">
          </div>
        </div>
      </div>

      <div class="col-lg-12 col-md-12 col-sm-12 mb-sm-6">
        <div class="card card-statistic-2">
          <div class="card-stats">
            <div class="card-stats-title"><h6>Post Clicks</h6></div>
          </div>
          {!! Form::open(['method' => 'GET', 'id' =>"dashboardForm", 'enctype' => 'multipart/form-data']) !!}
            <div class="pl-4">
              {!!Form::select('year', $years, $selectedCurrentYear , ['class' => 'form-control col-lg-2 col-md-2 col-sm-2 pl-2'])!!}
            </div>
          {!! Form::close() !!}
          <div class="card-wrap">
            <div class="card-body" style="padding-bottom: 10px">
             <div class="tab-content" id="myTabContent2">
              <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
                <div class="table-responsive">
                  <table class="table table-striped" id="all-table">
                    <thead>
                      <tr>
                        <th></th>
                        @foreach($months as $mvalue)
                        <th><h4><small>{!! $mvalue !!}</small></h4></th>
                        @endforeach
                      </tr>
                    </thead>
                    <tbody>
                      @if (isset($call_data))
                        <tr>
                          <td><h4><small>Call</small></h4></td>
                          @foreach($call_data as $ovalue)
                          <td><h5 class="pointer" onclick="getPostClickView({{$selectedCurrentYear}},{{$ovalue['month']}},{{$ovalue['count']}},'call');"><small>{!! $ovalue['count'] !!}</small></h5></td>
                          @endforeach
                        </tr>
                      @endif
                      @if (isset($book_data))
                        <tr>
                          <td><h4><small>Book</small></h4></td>
                          @foreach($book_data as $ovalue)
                            <td><h5 class="pointer" onclick="getPostClickView({{$selectedCurrentYear}},{{$ovalue['month']}},{{$ovalue['count']}},'book');"><small>{!! $ovalue['count'] !!}</small></h5></td>
                          @endforeach
                        </tr>
                      @endif

                      <tr>
                        <td><h4><small>Outside</small></h4></td>
                        @foreach($outside_data as $ovalue)
                        <td><h5  class="pointer" onclick="getPostClickView({{$selectedCurrentYear}},{{$ovalue['month']}},{{$ovalue['count']}},'outside');"><small>{!! $ovalue['count'] !!}</small></h5></td>
                        @endforeach
                      </tr>
                      <tr>
                        <td><h4><small>Shop</small></h4></td>
                        @foreach($shop_data as $svalue)
                        <td><h5  class="pointer" onclick="getPostClickView({{$selectedCurrentYear}},{{$svalue['month']}},{{$svalue['count']}},'shop');"><small>{!! $svalue['count'] !!}</small></h5></td>
                          @endforeach
                      </tr>
                      <tr>
                        <td><h4><small>Hospital</small></h4></td>
                        @foreach($hospital_data as $ovalue)
                        <td><h5  class="pointer" onclick="getPostClickView({{$selectedCurrentYear}},{{$ovalue['month']}},{{$ovalue['count']}},'hospitals');"><small>{!! $ovalue['count'] !!}</small></h5></td>
                          @endforeach
                        </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <a class="btn btn-primary" href="{{ url('tattoocity') }}">Tattoocity</a>
            <a class="btn btn-primary" href="{{ url('spa') }}">Spa</a>
            <a class="btn btn-primary" href="{{ url('challenge') }}">Challenge</a>
            <a class="btn btn-primary" href="{{ url('insta') }}">Insta</a>
            <a class="btn btn-primary" href="{{ url('qr-code') }}">QR Code</a>
          </div>
        </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endrole
@endsection
<div class="modal fade" id="show-click-count" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
@section('page-script')
<script>
  $(document).on('change','select[name="year"]',function(){
    $('#dashboardForm').submit();
  });
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/home.js') !!}"></script>
@endsection
