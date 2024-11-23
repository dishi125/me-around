@extends('layouts.app')
@section('header-content')
<div class="col-md-9">
  <h1>@if (@$title) {{ @$title }} @endif</h1>
</div>
<div class="col-md-3">
  <a href="{!! route('admin.qr.code') !!}" target="_blank" class="btn btn-primary float-right">Qr-Code</a>
</div>
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

                  <div class="mt-3 col-md-4">
                    <div class="form-group">
                      <label>Select Year</label>
                      <select class="form-control" id="select_year" name="select_year">
                          @foreach($years as $year)
                          <option value="{{$year}}"  @if($year == $current_year) selected @endif> {{$year}} </option>   
                          @endforeach                     
                                                
                      </select>                    
                    </div>
                  </div>                
              </div>
          </div>
      </div>
    </div>    
    @foreach($years_data as $key => $year)
      <div class="row year-div year-{{$key}} <?php echo $key != $current_year ? ' year-div-hide' : ""; ?>">
        @foreach($year as $key => $data)
          <a class="col-lg-2 col-md-2 col-sm-2" href="{!! route('admin.dashboard.day.detail',[$type,$data['date']]) !!}">
            <div class="card card-statistic-2">
              <div class="card-stats">
                <div class="card-stats-title">{{$data['month']}}</div>
              </div>
              <div class="card-wrap">
                <div class="card-body">
                  <h4>{{$data['income']}}</h4>
                </div>
              </div>            
            </div>
          </a>
        @endforeach
      </div>
    @endforeach
  </section>
</div>
@endsection
@section('scripts')
<script>      
   $("#select_year").change(function () {
       var selected_year = this.value;
       $('.year-div').addClass('year-div-hide');
       $(".year-"+selected_year).removeClass('year-div-hide');
   });
</script>
@endsection