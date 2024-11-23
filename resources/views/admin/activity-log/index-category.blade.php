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
								<option value="" <?php echo $countrySelected == '' ? 'selected' : '';  ?>>{{__(Lang::get('forms.top-post.select-country'))}}</option>
								@foreach($countries as $countryData)
								<option value="{{$countryData->code}}" <?php echo $countrySelected == $countryData->code ? 'selected' : '';  ?>> {{$countryData->name}} </option>
								@endforeach
							</select>
						</div>
						<div class="col-md-6">
							<select class="form-control select2" name="date-filter" id="date-filter">
								<option value="" <?php echo $dateSelected == '' ? 'selected' : '';  ?>>All</option>
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
                  <div class="activity-tabs">
					  <input type='radio' id='cat_all' name='t' checked><label class="activity-label" for='cat_all'>All</label>
					  <div class='content'>
					  	<ul class="nav nav-pills mb-4" id="myTab3" role="tablist">
							<li class="nav-item mr-2">
								<a class="nav-link active btn btn-primary" id="all-data" data-toggle="tab" href="#allData" role="tab" aria-controls="shop" aria-selected="true">All</a>
							</li>
							<li class="nav-item mr-2">
								<a class="nav-link btn btn-primary" id="inquire-data" data-toggle="tab" href="#inquireData" role="tab" aria-controls="shop" aria-selected="false">Inquire</a>
							</li>
							<li class="nav-item mr-2">
								<a class="nav-link btn btn-primary" id="book-data" data-toggle="tab" href="#bookData" role="tab" aria-controls="shop" aria-selected="false">Book</a>
							</li>
							<li class="nav-item mr-2">
								<a class="nav-link btn btn-primary" id="visited-data" data-toggle="tab" href="#visitedData" role="tab" aria-controls="shop" aria-selected="false">Visited</a>
							</li>
							<li class="nav-item mr-2">
								<a class="nav-link btn btn-primary" id="noshow-data" data-toggle="tab" href="#noshowData" role="tab" aria-controls="shop" aria-selected="false">No Show</a>
							</li>
							<li class="nav-item mr-2">
								<a class="nav-link btn btn-primary" id="cancel-business-data" data-toggle="tab" href="#cancelBusinessData" role="tab" aria-controls="shop" aria-selected="false">Cancelled by Business</a>
							</li>
							<li class="nav-item mr-2">
								<a class="nav-link btn btn-primary" id="cancel-user-data" data-toggle="tab" href="#cancelUserData" role="tab" aria-controls="shop" aria-selected="false">Cancelled by User</a>
							</li>
							<li class="nav-item mr-2">
								<a class="nav-link btn btn-primary" id="complete-data" data-toggle="tab" href="#completeData" role="tab" aria-controls="shop" aria-selected="false">Complete</a>
							</li>
							<li class="nav-item mr-2">
								<a class="nav-link btn btn-primary" id="reviews-data" data-toggle="tab" href="#reviewsData" role="tab" aria-controls="shop" aria-selected="false">Review</a>
							</li>
						</ul>

						<div class="tab-content" id="myTabContent2">
							<div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="all-data">
								<div class="table-responsive">
									<table class="table table-striped" id="all-table">
										<thead>
											<tr>
												<th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
														<label for="checkbox-active" class="custom-control-label">&nbsp;</label>
													</div>
                                            	</th>
												<th>Log Name</th>
												<th>User Name</th>
												<th>Business User Name</th>
												<th>Business Name</th>                                        
												<th>Business Address</th>
												<th>Created At</th>
											</tr>
										</thead>
										<tbody>
											@foreach($allLogs as $log)
											<tr>
												<td>
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-shop" id="{{$log->id}}" data-id="{{$log->id}}" value="{{$log->id}}" name="checkbox_id[]"><label for="{{$log->id}}" class="custom-control-label">&nbsp;</label>
													</div>
												</td>
												<td>{{$log->request_booking_status_name}}</td>
												<td>{{$log->user_name}}</td>
												<td>{{$log->business_user_name}}</td>
												<td>{{$log->business_name}}</td>                                        
												<td>{{$log->business_address}}</td>
												<td>{{$log->created_at}}</td>
											</tr>
											@endforeach											
										</tbody>
									</table>
								</div>
							</div>                    
							<div class="tab-pane fade" id="inquireData" role="tabpanel" aria-labelledby="inquire-data">
								<div class="table-responsive">
									<table class="table table-striped" id="inquire-table">
										<thead>
											<tr>
												<th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
														<label for="checkbox-active" class="custom-control-label">&nbsp;</label>
													</div>
                                            	</th>
												<th>Log Name</th>
												<th>User Name</th>
												<th>Business User Name</th>
												<th>Business Name</th>                                        
												<th>Business Address</th>
												<th>Created At</th>
											</tr>
										</thead>
										<tbody>
										@foreach($inquireLogs as $log)
											<tr>
												<td>
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-shop" id="{{$log->id}}" data-id="{{$log->id}}" value="{{$log->id}}" name="checkbox_id[]"><label for="{{$log->id}}" class="custom-control-label">&nbsp;</label>
													</div>
												</td>
												<td>{{$log->request_booking_status_name}}</td>
												<td>{{$log->user_name}}</td>
												<td>{{$log->business_user_name}}</td>
												<td>{{$log->business_name}}</td>                                        
												<td>{{$log->business_address}}</td>
												<td>{{$log->created_at}}</td>
											</tr>
											@endforeach	
										</tbody>
									</table>
								</div>
							</div>                    
							<div class="tab-pane fade" id="bookData" role="tabpanel" aria-labelledby="book-data">
								<div class="table-responsive">
									<table class="table table-striped" id="book-table">
										<thead>
											<tr>
												<th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
														<label for="checkbox-active" class="custom-control-label">&nbsp;</label>
													</div>
                                            	</th>
												<th>Log Name</th>
												<th>User Name</th>
												<th>Business User Name</th>
												<th>Business Name</th>                                        
												<th>Business Address</th>
												<th>Created At</th>
											</tr>
										</thead>
										<tbody>
										@foreach($bookLogs as $log)
											<tr>
												<td>
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-shop" id="{{$log->id}}" data-id="{{$log->id}}" value="{{$log->id}}" name="checkbox_id[]"><label for="{{$log->id}}" class="custom-control-label">&nbsp;</label>
													</div>
												</td>
												<td>{{$log->request_booking_status_name}}</td>
												<td>{{$log->user_name}}</td>
												<td>{{$log->business_user_name}}</td>
												<td>{{$log->business_name}}</td>                                        
												<td>{{$log->business_address}}</td>
												<td>{{$log->created_at}}</td>
											</tr>
											@endforeach	
										</tbody>
									</table>
								</div>
							</div>                    
							<div class="tab-pane fade" id="visitedData" role="tabpanel" aria-labelledby="visited-data">
								<div class="table-responsive">
									<table class="table table-striped" id="visited-table">
										<thead>
											<tr>
												<th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
														<label for="checkbox-active" class="custom-control-label">&nbsp;</label>
													</div>
                                            	</th>
												<th>Log Name</th>
												<th>User Name</th>
												<th>Business User Name</th>
												<th>Business Name</th>                                        
												<th>Business Address</th>
												<th>Created At</th>
											</tr>
										</thead>
										<tbody>
										@foreach($visitedLogs as $log)
											<tr>
												<td>
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-shop" id="{{$log->id}}" data-id="{{$log->id}}" value="{{$log->id}}" name="checkbox_id[]"><label for="{{$log->id}}" class="custom-control-label">&nbsp;</label>
													</div>
												</td>
												<td>{{$log->request_booking_status_name}}</td>
												<td>{{$log->user_name}}</td>
												<td>{{$log->business_user_name}}</td>
												<td>{{$log->business_name}}</td>                                        
												<td>{{$log->business_address}}</td>
												<td>{{$log->created_at}}</td>
											</tr>
											@endforeach	
										</tbody>
									</table>
								</div>
							</div>                    
							<div class="tab-pane fade" id="noshowData" role="tabpanel" aria-labelledby="noshow-data">
								<div class="table-responsive">
									<table class="table table-striped" id="noshow-table">
										<thead>
											<tr>
												<th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
														<label for="checkbox-active" class="custom-control-label">&nbsp;</label>
													</div>
                                            	</th>
												<th>Log Name</th>
												<th>User Name</th>
												<th>Business User Name</th>
												<th>Business Name</th>                                        
												<th>Business Address</th>
												<th>Created At</th>
											</tr>
										</thead>
										<tbody>
										@foreach($noshowLogs as $log)
											<tr>
												<td>
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-shop" id="{{$log->id}}" data-id="{{$log->id}}" value="{{$log->id}}" name="checkbox_id[]"><label for="{{$log->id}}" class="custom-control-label">&nbsp;</label>
													</div>
												</td>
												<td>{{$log->request_booking_status_name}}</td>
												<td>{{$log->user_name}}</td>
												<td>{{$log->business_user_name}}</td>
												<td>{{$log->business_name}}</td>                                        
												<td>{{$log->business_address}}</td>
												<td>{{$log->created_at}}</td>
											</tr>
											@endforeach	
										</tbody>
									</table>
								</div>
							</div>                    
							<div class="tab-pane fade" id="cancelBusinessData" role="tabpanel" aria-labelledby="cancel-business-data">
								<div class="table-responsive">
									<table class="table table-striped" id="cancel-business-table">
										<thead>
											<tr>
												<th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
														<label for="checkbox-active" class="custom-control-label">&nbsp;</label>
													</div>
                                            	</th>
												<th>Log Name</th>
												<th>User Name</th>
												<th>Business User Name</th>
												<th>Business Name</th>                                        
												<th>Business Address</th>
												<th>Created At</th>
											</tr>
										</thead>
										<tbody>
										@foreach($cancelBusinessLogs as $log)
											<tr>
												<td>
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-shop" id="{{$log->id}}" data-id="{{$log->id}}" value="{{$log->id}}" name="checkbox_id[]"><label for="{{$log->id}}" class="custom-control-label">&nbsp;</label>
													</div>
												</td>
												<td>{{$log->request_booking_status_name}}</td>
												<td>{{$log->user_name}}</td>
												<td>{{$log->business_user_name}}</td>
												<td>{{$log->business_name}}</td>                                        
												<td>{{$log->business_address}}</td>
												<td>{{$log->created_at}}</td>
											</tr>
											@endforeach	
										</tbody>
									</table>
								</div>
							</div>                    
							<div class="tab-pane fade" id="cancelUserData" role="tabpanel" aria-labelledby="cancel-user-data">
								<div class="table-responsive">
									<table class="table table-striped" id="cancel-user-table">
										<thead>
											<tr>
												<th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
														<label for="checkbox-active" class="custom-control-label">&nbsp;</label>
													</div>
                                            	</th>
												<th>Log Name</th>
												<th>User Name</th>
												<th>Business User Name</th>
												<th>Business Name</th>                                        
												<th>Business Address</th>
												<th>Created At</th>
											</tr>
										</thead>
										<tbody>
										@foreach($cancelUserLogs as $log)
											<tr>
												<td>
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-shop" id="{{$log->id}}" data-id="{{$log->id}}" value="{{$log->id}}" name="checkbox_id[]"><label for="{{$log->id}}" class="custom-control-label">&nbsp;</label>
													</div>
												</td>
												<td>{{$log->request_booking_status_name}}</td>
												<td>{{$log->user_name}}</td>
												<td>{{$log->business_user_name}}</td>
												<td>{{$log->business_name}}</td>                                        
												<td>{{$log->business_address}}</td>
												<td>{{$log->created_at}}</td>
											</tr>
											@endforeach	
										</tbody>
									</table>
								</div>
							</div>                    
							<div class="tab-pane fade" id="completeData" role="tabpanel" aria-labelledby="complete-data">
								<div class="table-responsive">
									<table class="table table-striped" id="complete-table">
										<thead>
											<tr>
												<th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
														<label for="checkbox-active" class="custom-control-label">&nbsp;</label>
													</div>
                                            	</th>
												<th>Log Name</th>
												<th>User Name</th>
												<th>Business User Name</th>
												<th>Business Name</th>                                        
												<th>Business Address</th>
												<th>Created At</th>
											</tr>
										</thead>
										<tbody>
										@foreach($completeLogs as $log)
											<tr>
												<td>
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-shop" id="{{$log->id}}" data-id="{{$log->id}}" value="{{$log->id}}" name="checkbox_id[]"><label for="{{$log->id}}" class="custom-control-label">&nbsp;</label>
													</div>
												</td>
												<td>{{$log->request_booking_status_name}}</td>
												<td>{{$log->user_name}}</td>
												<td>{{$log->business_user_name}}</td>
												<td>{{$log->business_name}}</td>                                        
												<td>{{$log->business_address}}</td>
												<td>{{$log->created_at}}</td>
											</tr>
											@endforeach	
										</tbody>
									</table>
								</div>
							</div>                    
							<div class="tab-pane fade" id="reviewsData" role="tabpanel" aria-labelledby="reviews-data">
								<div class="table-responsive">
									<table class="table table-striped" id="reviews-table">
										<thead>
											<tr>
												<th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
														<label for="checkbox-active" class="custom-control-label">&nbsp;</label>
													</div>
                                            	</th>
												<th>Log Name</th>
												<th>User Name</th>
												<th>Business User Name</th>
												<th>Business Name</th>                                        
												<th>Business Address</th>
												<th>Created At</th>
											</tr>
										</thead>
										<tbody>
										@foreach($reviewLogs as $log)
											<tr>
												<td>
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-shop" id="{{$log->id}}" data-id="{{$log->id}}" value="{{$log->id}}" name="checkbox_id[]"><label for="{{$log->id}}" class="custom-control-label">&nbsp;</label>
													</div>
												</td>
												<td>{{$log->request_booking_status_name}}</td>
												<td>{{$log->user_name}}</td>
												<td>{{$log->business_user_name}}</td>
												<td>{{$log->business_name}}</td>                                        
												<td>{{$log->business_address}}</td>
												<td>{{$log->created_at}}</td>
											</tr>
											@endforeach	
										</tbody>
									</table>
								</div>
							</div>                    
                		</div>     
					  </div>
					  @foreach($categories as $sc)
					  <input type='radio' id='cat_{{$sc->id}}' name='t'><label class="activity-label" for='cat_{{$sc->id}}'>{{$sc->name}}</label>
					  <div class='content'>
					  	<ul class="nav nav-pills mb-4" id="myTab3" role="tablist">
							<li class="nav-item mr-2">
								<a class="nav-link active btn btn-primary" id="all-data-{{$sc->id}}" data-toggle="tab" href="#allData_{{$sc->id}}" role="tab" aria-controls="shop" aria-selected="true">All</a>
							</li>
							<li class="nav-item mr-2">
								<a class="nav-link btn btn-primary" id="inquire-data-{{$sc->id}}" data-toggle="tab" href="#inquireData_{{$sc->id}}" role="tab" aria-controls="shop" aria-selected="false">Inquire</a>
							</li>
							<li class="nav-item mr-2">
								<a class="nav-link btn btn-primary" id="book-data-{{$sc->id}}" data-toggle="tab" href="#bookData_{{$sc->id}}" role="tab" aria-controls="shop" aria-selected="false">Book</a>
							</li>
							<li class="nav-item mr-2">
								<a class="nav-link btn btn-primary" id="visited-data-{{$sc->id}}" data-toggle="tab" href="#visitedData_{{$sc->id}}" role="tab" aria-controls="shop" aria-selected="false">Visited</a>
							</li>
							<li class="nav-item mr-2">
								<a class="nav-link btn btn-primary" id="noshow-data-{{$sc->id}}" data-toggle="tab" href="#noshowData_{{$sc->id}}" role="tab" aria-controls="shop" aria-selected="false">No Show</a>
							</li>
							<li class="nav-item mr-2">
								<a class="nav-link btn btn-primary" id="cancel-business-data-{{$sc->id}}" data-toggle="tab" href="#cancelBusinessData_{{$sc->id}}" role="tab" aria-controls="shop" aria-selected="false">Cancelled by Business</a>
							</li>
							<li class="nav-item mr-2">
								<a class="nav-link btn btn-primary" id="cancel-user-data-{{$sc->id}}" data-toggle="tab" href="#cancelUserData_{{$sc->id}}" role="tab" aria-controls="shop" aria-selected="false">Cancelled by User</a>
							</li>
							<li class="nav-item mr-2">
								<a class="nav-link btn btn-primary" id="complete-data-{{$sc->id}}" data-toggle="tab" href="#completeData_{{$sc->id}}" role="tab" aria-controls="shop" aria-selected="false">Complete</a>
							</li>
							<li class="nav-item mr-2">
								<a class="nav-link btn btn-primary" id="reviews-data-{{$sc->id}}" data-toggle="tab" href="#reviewsData_{{$sc->id}}" role="tab" aria-controls="shop" aria-selected="false">Review</a>
							</li>
						</ul>

						<div class="tab-content" id="myTabContent2">
							<div class="tab-pane fade show active" id="allData_{{$sc->id}}" role="tabpanel" aria-labelledby="all-data-{{$sc->id}}">
								<div class="table-responsive">
									<table class="table table-striped" id="all-table-{{$sc->id}}">
										<thead>
											<tr>
												<th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
														<label for="checkbox-active" class="custom-control-label">&nbsp;</label>
													</div>
                                            	</th>
												<th>Log Name</th>
												<th>User Name</th>
												<th>Business User Name</th>
												<th>Business Name</th>                                        
												<th>Business Address</th>
												<th>Created At</th>
											</tr>
										</thead>
										<tbody>
										@foreach($allLogs as $log)
											@if($log->category_id == $sc->id)
												<tr>
													<td>
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-shop" id="{{$log->id}}" data-id="{{$log->id}}" value="{{$log->id}}" name="checkbox_id[]"><label for="{{$log->id}}" class="custom-control-label">&nbsp;</label>
													</div>
												</td>
													<td>{{$log->request_booking_status_name}}</td>
													<td>{{$log->user_name}}</td>
													<td>{{$log->business_user_name}}</td>
													<td>{{$log->business_name}}</td>                                        
													<td>{{$log->business_address}}</td>
													<td>{{$log->created_at}}</td>
												</tr>
											@endif
										@endforeach	
										</tbody>
									</table>
								</div>
							</div>                    
							<div class="tab-pane fade" id="inquireData_{{$sc->id}}" role="tabpanel" aria-labelledby="inquire-data-{{$sc->id}}">
								<div class="table-responsive">
									<table class="table table-striped" id="inquire-table-{{$sc->id}}">
										<thead>
											<tr>
												<th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
														<label for="checkbox-active" class="custom-control-label">&nbsp;</label>
													</div>
                                            	</th>
												<th>Log Name</th>
												<th>User Name</th>
												<th>Business User Name</th>
												<th>Business Name</th>                                        
												<th>Business Address</th>
												<th>Created At</th>
											</tr>
										</thead>
										<tbody>
										@foreach($inquireLogs as $log)
											@if($log->category_id == $sc->id)
												<tr>
													<td>
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-shop" id="{{$log->id}}" data-id="{{$log->id}}" value="{{$log->id}}" name="checkbox_id[]"><label for="{{$log->id}}" class="custom-control-label">&nbsp;</label>
													</div>
												</td>
													<td>{{$log->request_booking_status_name}}</td>
													<td>{{$log->user_name}}</td>
													<td>{{$log->business_user_name}}</td>
													<td>{{$log->business_name}}</td>                                        
													<td>{{$log->business_address}}</td>
													<td>{{$log->created_at}}</td>
												</tr>
											@endif
										@endforeach	
										</tbody>
									</table>
								</div>
							</div>                    
							<div class="tab-pane fade" id="bookData_{{$sc->id}}" role="tabpanel" aria-labelledby="book-data-{{$sc->id}}">
								<div class="table-responsive">
									<table class="table table-striped" id="book-table-{{$sc->id}}">
										<thead>
											<tr>
												<th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
														<label for="checkbox-active" class="custom-control-label">&nbsp;</label>
													</div>
                                            	</th>
												<th>Log Name</th>
												<th>User Name</th>
												<th>Business User Name</th>
												<th>Business Name</th>                                        
												<th>Business Address</th>
												<th>Created At</th>
											</tr>
										</thead>
										<tbody>
										@foreach($bookLogs as $log)
											@if($log->category_id == $sc->id)
												<tr>
													<td>
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-shop" id="{{$log->id}}" data-id="{{$log->id}}" value="{{$log->id}}" name="checkbox_id[]"><label for="{{$log->id}}" class="custom-control-label">&nbsp;</label>
													</div>
												</td>
													<td>{{$log->request_booking_status_name}}</td>
													<td>{{$log->user_name}}</td>
													<td>{{$log->business_user_name}}</td>
													<td>{{$log->business_name}}</td>                                        
													<td>{{$log->business_address}}</td>
													<td>{{$log->created_at}}</td>
												</tr>
											@endif
										@endforeach	
										</tbody>
									</table>
								</div>
							</div>                    
							<div class="tab-pane fade" id="visitedData_{{$sc->id}}" role="tabpanel" aria-labelledby="visited-data-{{$sc->id}}">
								<div class="table-responsive">
									<table class="table table-striped" id="visited-table-{{$sc->id}}">
										<thead>
											<tr>
												<th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
														<label for="checkbox-active" class="custom-control-label">&nbsp;</label>
													</div>
                                            	</th>
												<th>Log Name</th>
												<th>User Name</th>
												<th>Business User Name</th>
												<th>Business Name</th>                                        
												<th>Business Address</th>
												<th>Created At</th>
											</tr>
										</thead>
										<tbody>
										@foreach($visitedLogs as $log)
											@if($log->category_id == $sc->id)
												<tr>
													<td>
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-shop" id="{{$log->id}}" data-id="{{$log->id}}" value="{{$log->id}}" name="checkbox_id[]"><label for="{{$log->id}}" class="custom-control-label">&nbsp;</label>
													</div>
												</td>
													<td>{{$log->request_booking_status_name}}</td>
													<td>{{$log->user_name}}</td>
													<td>{{$log->business_user_name}}</td>
													<td>{{$log->business_name}}</td>                                        
													<td>{{$log->business_address}}</td>
													<td>{{$log->created_at}}</td>
												</tr>
											@endif
										@endforeach	
										</tbody>
									</table>
								</div>
							</div>                    
							<div class="tab-pane fade" id="noshowData_{{$sc->id}}" role="tabpanel" aria-labelledby="noshow-data-{{$sc->id}}">
								<div class="table-responsive">
									<table class="table table-striped" id="noshow-table-{{$sc->id}}">
										<thead>
											<tr>
												<th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
														<label for="checkbox-active" class="custom-control-label">&nbsp;</label>
													</div>
                                            	</th>
												<th>Log Name</th>
												<th>User Name</th>
												<th>Business User Name</th>
												<th>Business Name</th>                                        
												<th>Business Address</th>
												<th>Created At</th>
											</tr>
										</thead>
										<tbody>
										@foreach($noshowLogs as $log)
											@if($log->category_id == $sc->id)
												<tr>
													<td>
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-shop" id="{{$log->id}}" data-id="{{$log->id}}" value="{{$log->id}}" name="checkbox_id[]"><label for="{{$log->id}}" class="custom-control-label">&nbsp;</label>
													</div>
												</td>
													<td>{{$log->request_booking_status_name}}</td>
													<td>{{$log->user_name}}</td>
													<td>{{$log->business_user_name}}</td>
													<td>{{$log->business_name}}</td>                                        
													<td>{{$log->business_address}}</td>
													<td>{{$log->created_at}}</td>
												</tr>
											@endif
										@endforeach	
										</tbody>
									</table>
								</div>
							</div>                    
							<div class="tab-pane fade" id="cancelBusinessData_{{$sc->id}}" role="tabpanel" aria-labelledby="cancel-business-data-{{$sc->id}}">
								<div class="table-responsive">
									<table class="table table-striped" id="cancel-business-table-{{$sc->id}}">
										<thead>
											<tr>
												<th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
														<label for="checkbox-active" class="custom-control-label">&nbsp;</label>
													</div>
                                            	</th>
												<th>Log Name</th>
												<th>User Name</th>
												<th>Business User Name</th>
												<th>Business Name</th>                                        
												<th>Business Address</th>
												<th>Created At</th>
											</tr>
										</thead>
										<tbody>
										@foreach($cancelBusinessLogs as $log)
											@if($log->category_id == $sc->id)
												<tr>
													<td>
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-shop" id="{{$log->id}}" data-id="{{$log->id}}" value="{{$log->id}}" name="checkbox_id[]"><label for="{{$log->id}}" class="custom-control-label">&nbsp;</label>
													</div>
												</td>
													<td>{{$log->request_booking_status_name}}</td>
													<td>{{$log->user_name}}</td>
													<td>{{$log->business_user_name}}</td>
													<td>{{$log->business_name}}</td>                                        
													<td>{{$log->business_address}}</td>
													<td>{{$log->created_at}}</td>
												</tr>
											@endif
										@endforeach	
										</tbody>
									</table>
								</div>
							</div>                    
							<div class="tab-pane fade" id="cancelUserData_{{$sc->id}}" role="tabpanel" aria-labelledby="cancel-user-data-{{$sc->id}}">
								<div class="table-responsive">
									<table class="table table-striped" id="cancel-user-table-{{$sc->id}}">
										<thead>
											<tr>
												<th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
														<label for="checkbox-active" class="custom-control-label">&nbsp;</label>
													</div>
                                            	</th>
												<th>Log Name</th>
												<th>User Name</th>
												<th>Business User Name</th>
												<th>Business Name</th>                                        
												<th>Business Address</th>
												<th>Created At</th>
											</tr>
										</thead>
										<tbody>
										@foreach($cancelUserLogs as $log)
											@if($log->category_id == $sc->id)
												<tr>
													<td>
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-shop" id="{{$log->id}}" data-id="{{$log->id}}" value="{{$log->id}}" name="checkbox_id[]"><label for="{{$log->id}}" class="custom-control-label">&nbsp;</label>
													</div>
												</td>
													<td>{{$log->request_booking_status_name}}</td>
													<td>{{$log->user_name}}</td>
													<td>{{$log->business_user_name}}</td>
													<td>{{$log->business_name}}</td>                                        
													<td>{{$log->business_address}}</td>
													<td>{{$log->created_at}}</td>
												</tr>
											@endif
										@endforeach	
										</tbody>
									</table>
								</div>
							</div>                    
							<div class="tab-pane fade" id="completeData_{{$sc->id}}" role="tabpanel" aria-labelledby="complete-data-{{$sc->id}}">
								<div class="table-responsive">
									<table class="table table-striped" id="complete-table-{{$sc->id}}">
										<thead>
											<tr>
												<th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
														<label for="checkbox-active" class="custom-control-label">&nbsp;</label>
													</div>
                                            	</th>
												<th>Log Name</th>
												<th>User Name</th>
												<th>Business User Name</th>
												<th>Business Name</th>                                        
												<th>Business Address</th>
												<th>Created At</th>
											</tr>
										</thead>
										<tbody>
										@foreach($completeLogs as $log)
											@if($log->category_id == $sc->id)
												<tr>
													<td>
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-shop" id="{{$log->id}}" data-id="{{$log->id}}" value="{{$log->id}}" name="checkbox_id[]"><label for="{{$log->id}}" class="custom-control-label">&nbsp;</label>
													</div>
												</td>
													<td>{{$log->request_booking_status_name}}</td>
													<td>{{$log->user_name}}</td>
													<td>{{$log->business_user_name}}</td>
													<td>{{$log->business_name}}</td>                                        
													<td>{{$log->business_address}}</td>
													<td>{{$log->created_at}}</td>
												</tr>
											@endif
										@endforeach	
										</tbody>
									</table>
								</div>
							</div>                    
							<div class="tab-pane fade" id="reviewsData_{{$sc->id}}" role="tabpanel" aria-labelledby="reviews-data-{{$sc->id}}">
								<div class="table-responsive">
									<table class="table table-striped" id="reviews-table-{{$sc->id}}">
										<thead>
											<tr>
												<th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-active">
														<label for="checkbox-active" class="custom-control-label">&nbsp;</label>
													</div>
                                            	</th>
												<th>Log Name</th>
												<th>User Name</th>
												<th>Business User Name</th>
												<th>Business Name</th>                                        
												<th>Business Address</th>
												<th>Created At</th>
											</tr>
										</thead>
										<tbody>
										@foreach($reviewLogs as $log)
											@if($log->category_id == $sc->id)
												<tr>
													<td>
													<div class="custom-checkbox custom-control">
														<input type="checkbox" data-checkboxes="mygroup" class="custom-control-input checkbox_id check-all-shop" id="{{$log->id}}" data-id="{{$log->id}}" value="{{$log->id}}" name="checkbox_id[]"><label for="{{$log->id}}" class="custom-control-label">&nbsp;</label>
													</div>
												</td>
													<td>{{$log->request_booking_status_name}}</td>
													<td>{{$log->user_name}}</td>
													<td>{{$log->business_user_name}}</td>
													<td>{{$log->business_name}}</td>                                        
													<td>{{$log->business_address}}</td>
													<td>{{$log->created_at}}</td>
												</tr>
											@endif
										@endforeach	
										</tbody>
									</table>
								</div>
							</div>                    
                		</div>     
					  </div>
					  @endforeach					  
					  <div id='slider'></div>
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
	var categories = <?php echo json_encode($categories) ?>;
    $(function() {
		$.each(categories, function (i, elem) {
			$("#all-table-"+ elem.id).DataTable();			
			$("#inquire-table-"+ elem.id).DataTable();
			$("#book-table-"+ elem.id).DataTable();
			$("#visited-table-"+ elem.id).DataTable();
			$("#noshow-table-"+ elem.id).DataTable();
			$("#cancel-business-table-"+ elem.id).DataTable();
			$("#cancel-user-table-"+ elem.id).DataTable();
			$("#complete-table-"+ elem.id).DataTable();
			$("#reviews-table-"+ elem.id).DataTable();
		});
		
		$("#all-table").DataTable();
		$("#inquire-table").DataTable();
		$("#book-table").DataTable();
		$("#visited-table").DataTable();
		$("#noshow-table").DataTable();
		$("#cancel-business-table").DataTable();
		$("#cancel-user-table").DataTable();
		$("#complete-table").DataTable();
		$("#reviews-table").DataTable();
	});
</script>
<script src="{!! asset('js/pages/activity-log/index.js') !!}"></script>
@endsection
