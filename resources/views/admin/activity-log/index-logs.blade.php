@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
@endsection



@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')
<?php 
	$countrySelected = request()->has('countryId') ? request()->countryId : null;
	$dateSelected = request()->has('date') ? request()->date : '';
?>

<form name="filter-form" id="filter-form">
	<input type="hidden" name="dateFilter" value="all" />
	<input type="hidden" name="statusFilter" value="all" />
	<input type="hidden" name="countryFilter" value="all" />
	<input type="hidden" name="categoryFilter" value="all" />
	<input type="hidden" name="pageType" value="{{$pageType}}" />
</form>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body row">
                <div class="buttons col-md-8">
                    <a href="{!! route('admin.activity-log.index') !!}" class="btn btn-primary">All</a>
                    <a href="{!! route('admin.activity-log.hospital.index') !!}" class="btn btn-primary">Hospital</a>
                    <a href="{!! route('admin.activity-log.shop.index') !!}" class="btn btn-primary">Shop</a>
                    <a href="{!! route('admin.activity-log.custom.index') !!}" class="btn btn-primary">Suggest Business</a>
				</div>
				<div class="buttons col-md-4">
					<div class="row">
						<div class="col-md-6" >
							<select class="form-control select2 mr-2" name="country_id" id="country_id">
								<option value="all" <?php echo $countrySelected == '' ? 'selected' : '';  ?>>{{__(Lang::get('forms.top-post.select-country'))}}</option>
								@foreach($countries as $countryData)
								<option value="{{$countryData->code}}" <?php echo $countrySelected == $countryData->code ? 'selected' : '';  ?>> {{$countryData->name}} </option>
								@endforeach
							</select>
						</div>
						<div class="col-md-6">
							<select class="form-control select2" name="date-filter" id="date-filter">
								<option value="all" <?php echo $dateSelected == '' ? 'selected' : 'all';  ?>>All</option>
								<option value="1" <?php echo $dateSelected == 1 ? 'selected' : '';  ?>>Last 1 months</option>
								<option value="3" <?php echo $dateSelected == 3 ? 'selected' : '';  ?>>Last 3 months</option>
								<option value="6" <?php echo $dateSelected == 6 ? 'selected' : '';  ?>>Last 6 months</option>
								<option value="12" <?php echo $dateSelected == 12 ? 'selected' : '';  ?>>Last 12 months</option>						
							</select>
						</div>
					</div>
				</div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                  <div class="activity-tabs new-activity-tabs">
                  		<div class="filter-cat">
							<input type='radio' id='sub_cat_all' name='t' checked><label class="categories activity-label" data-categoryID='all' for='sub_cat_all'>All</label>
								@foreach($categories as $sc)
									<input type='radio' id='cat_{{$sc->id}}' name='t'><label class="categories activity-label" data-categoryID='{{$sc->id}}' for='cat_{{$sc->id}}' >{{$sc->name}}</label>
								@endforeach					  
								<div id='slider'></div>
						</div>

					  <div class='content'>
					  	<ul class="nav nav-pills mb-4 statusFilterData" id="myTab3" role="tablist">
							<li class="nav-item mr-2">
								<a data-value="all" class="nav-link active btn btn-primary" id="all-data" data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="true">All <span class="count"></span></a>
							</li>
							<li class="nav-item mr-2">
								<a data-value="inquire" class="nav-link btn btn-primary" id="inquire-data" data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="false">Inquire <span class="count"></span></a>
							</li>
							<li class="nav-item mr-2">
								<a data-value="book" class="nav-link btn btn-primary" id="book-data" data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="false">Book <span class="count"></span></a>
							</li>
							<li class="nav-item mr-2">
								<a data-value="visited" class="nav-link btn btn-primary" id="visited-data" data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="false">Visited <span class="count"></span></a>
							</li>
							<li class="nav-item mr-2">
								<a data-value="noshow" class="nav-link btn btn-primary" id="noshow-data" data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="false">No Show <span class="count"></span></a>
							</li>
							<li class="nav-item mr-2">
								<a data-value="cancelBusiness" class="nav-link btn btn-primary" id="cancel-business-data" data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="false">Cancelled by Business <span class="count"></span></a>
							</li>
							<li class="nav-item mr-2">
								<a data-value="cancelUser" class="nav-link btn btn-primary" id="cancel-user-data" data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="false">Cancelled by User <span class="count"></span></a>
							</li>
							<li class="nav-item mr-2">
								<a data-value="complete" class="nav-link btn btn-primary" id="complete-data" data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="false">Complete <span class="count"></span></a>
							</li>
							<li class="nav-item mr-2">
								<a data-value="reviews" class="nav-link btn btn-primary" id="reviews-data" data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="false">Review <span class="count"></span></a>
							</li>
						</ul>

						<div class="tab-content" id="myTabContent2">
							<div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
								<div class="table-responsive">
									<table class="table table-striped" id="all-table">
										<thead>
											<tr>
												<th>Log Name</th>
												<th>User Name</th>
												<th>Business User Name</th>
												<th>Business Name</th>                                        
												<th>Business Address</th>
												<th>Created At</th>
											</tr>
										</thead>
									</table>
								</div>
							</div>                     
                		</div>     
					  </div>      
					</div>         
            </div>
        </div>
    </div>
</div>
<div class="cover-spin"></div>
@endsection

@section('scripts')
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script>
	var allHospitalTable = "{!! route('admin.activity-log.shop-filter') !!}";
	var csrfToken = "{{csrf_token()}}";  

	var categories = <?php echo json_encode($categories) ?>;
   
</script>
<script src="{!! asset('js/pages/activity-log/index.js') !!}"></script>
@endsection
