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
                      <label>Select Month</label>
                      <select class="form-control" id="select_month" name="select_month">
                          @for($i = 1; $i <= 12; $i++)
                            <option value="{{$i}}" @if($i == $current_month) selected @endif> {{$i}} </option>   
                          @endfor                      
                      </select>                    
                  </div>
                  </div>
                
              </div>
          </div>
      </div>
    </div>
    @foreach($months_data as $key => $month)
    <div class="row month-div month-{{$key}} <?php echo $key != $current_month ? ' month-div-hide' : ""; ?>">
      @foreach($month as $data)
      <a href="{!! route('admin.dashboard.all.day.detail',[$type,$data['date']]) !!}" class="col-lg-3 col-md-3 col-sm-3">
        <!-- <div > -->
            <div class="card card-statistic-2">
              <div class="card-stats">
                <div class="card-stats-title">{{$data['day']}}</div>
              </div>
              <div class="card-wrap">
                <div class="card-body">
                  <h4>{{$data['income']}}</h4>
                </div>
              </div>
            </div>
        <!-- </div> -->
      </a>
      @endforeach
    </div>
    @endforeach
      <!-- <div class="col-lg-6">
          <div class="card">
            <div class="card-body">    
              <div class="table-responsive">
                <table class="table table-striped" id="day-data-table">
                    <thead>
                        <tr>                                        
                            <th>Client Name</th>
                            <th>Business Type</th>
                            <th>Charge</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>name</td>
                        <td>tatto</td>
                        <td>200,000</td>
                        <td>19-06-29 03:59</td>
                      </tr>
                      <tr>
                        <td>name</td>
                        <td>tatto</td>
                        <td>200,000</td>
                        <td>19-06-29 03:59</td>
                      </tr>
                      <tr>
                        <td>name</td>
                        <td>tatto</td>
                        <td>200,000</td>
                        <td>19-06-29 03:59</td>
                      </tr>
                      <tr>
                        <td>name</td>
                        <td>hair</td>
                        <td>200,000</td>
                        <td>19-06-29 03:59</td>
                      </tr>
                      <tr>
                        <td>name</td>
                        <td>hospital</td>
                        <td>200,000</td>
                        <td>19-06-29 03:59</td>
                      </tr>
                      <tr>
                        <td>name</td>
                        <td>tatto</td>
                        <td>200,000</td>
                        <td>19-06-29 03:59</td>
                      </tr>
                      <tr>
                        <td>name</td>
                        <td>eyebrow</td>
                        <td>200,000</td>
                        <td>19-06-29 03:59</td>
                      </tr>
                      <tr>
                        <td>name</td>
                        <td>skin</td>
                        <td>200,000</td>
                        <td>19-06-29 03:59</td>
                      </tr>
                      <tr>
                        <td>name</td>
                        <td>fitness</td>
                        <td>200,000</td>
                        <td>19-06-29 03:59</td>
                      </tr>
                      <tr>
                        <td>name</td>
                        <td>fitness</td>
                        <td>200,000</td>
                        <td>19-06-29 03:59</td>
                      </tr>
                      <tr>
                        <td>name</td>
                        <td>fitness</td>
                        <td>200,000</td>
                        <td>19-06-29 03:59</td>
                      </tr>
                      <tr>
                        <td>name</td>
                        <td>fitness</td>
                        <td>200,000</td>
                        <td>19-06-29 03:59</td>
                      </tr>
                      <tr>
                        <td>name</td>
                        <td>fitness</td>
                        <td>200,000</td>
                        <td>19-06-29 03:59</td>
                      </tr>
                      <tr>
                        <td>name</td>
                        <td>fitness</td>
                        <td>200,000</td>
                        <td>19-06-29 03:59</td>
                      </tr>
                      <tr>
                        <td>name</td>
                        <td>fitness</td>
                        <td>200,000</td>
                        <td>19-06-29 03:59</td>
                      </tr>
                    </tbody>
                </table>
              </div>
            </div>
          </div>
      </div> -->
  </section>
</div>
@endsection

@section('scripts')
<script>      
   $("#select_month").change(function () {
       var selected_month = this.value;
       $('.month-div').addClass('month-div-hide');
       $(".month-"+selected_month).removeClass('month-div-hide');
   });
</script>
@endsection