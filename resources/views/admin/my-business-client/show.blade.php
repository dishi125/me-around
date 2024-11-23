@extends('layouts.app')

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
<link rel="stylesheet" href="{!! asset('css/chocolat.css') !!}">
@endsection

@section('content')
<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-5">
            <div class="card profile-widget">
                <div class="profile-widget-header">
                    <img alt="image"
                        src="{!! asset('img/hospital.png') !!}"
                        class="rounded-circle profile-widget-picture">
                    <div class="profile-widget-items">
                        <div class="profile-widget-item">
                            <div class="profile-widget-item-label">Followers</div>
                            <div class="profile-widget-item-value">0</div>
                        </div>
                        <div class="profile-widget-item">
                            <div class="profile-widget-item-label">Work Complete</div>
                            <div class="profile-widget-item-value">0</div>
                        </div>
                        <div class="profile-widget-item">
                            <div class="profile-widget-item-label">Review</div>
                            <div class="profile-widget-item-value">12</div>
                        </div>
                    </div>
                </div>
                <div class="profile-widget-description">
                    <div class="profile-widget-name">{{$hospital->main_name}}
                        <div class="text-muted d-inline font-weight-normal">
                        </div>
                    </div>
                    <div class="gallery gallery-md">
                        <div class="gallery-item"
                            data-image="{!! $hospital->interior_photo_url !!}"
                            data-title="Image 1"></div>
                        <div class="gallery-item"
                            data-image="{!! $hospital->business_licence_url !!}"
                            data-title="Image 2"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-12 col-lg-7 mt-sm-3 pt-3">
            <div class="card">
                <div class="card-header">
                    <h4>Personal Detail</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-md-6 col-12">
                            <label>Name</label>
                            <input type="text" class="form-control" value="{{$hospital->main_name}}" readonly>
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label>Email Address</label>
                            <input type="email" class="form-control" value="{{$hospital->email}}" readonly>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6 col-12">
                            <label>Business Licence Number</label>
                            <input type="text" class="form-control" value="{{$hospital->business_license_number}}" readonly>
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label>Mobile Number</label>
                            <input type="text" class="form-control" value="{{$hospital->mobile}}" readonly>
                        </div>
                    </div>
                </div>
                <div class="card-header">
                    <h4>Address Detail</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-md-6 col-12">
                            <label>Address</label>
                            <input type="text" class="form-control" value="{{$hospital->address->address}}"
                                readonly>
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label>City</label>
                            <input type="text" class="form-control" value="{{$hospital->address->city_name}}"
                                readonly>
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label>State</label>
                            <input type="text" class="form-control" value="{{$hospital->address->state_name}}"
                                readonly>
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label>Country</label>
                            <input type="text" class="form-control" value="{{$hospital->address->country_name}}"
                                readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Event List</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="hospital-table">
                            <thead>
                                <tr>
                                    <th class="text-center">
                                        #
                                    </th>
                                    <th>Title</th>
                                    <th>Banner</th>
                                    <th>Status</th>
                                    <th>City</th>
                                    <th>Country</th>
                                    <th>From Date</th>
                                    <th>To Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($hospital->posts_list as $post)
                                <tr>
                                    <td class="text-center">1</td>
                                    <td>{{$post->title}}</td>
                                    <td><img src="{{$post->thumbnail_url}}" alt="{{$post->title}}" class="requested-client-images m-1" /></td>
                                    <td>{{$post->status_name}}</td>
                                    <td>{{$post->location->city_name}}</td>
                                    <td>{{$post->location->country_name}}</td>
                                    <td>{{$post->from_date}}</td>
                                    <td>{{$post->to_date}}</td>
                                </tr>
                                @endforeach
                            </tbody>
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
@endsection

@section('scripts')
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/chocolat.js') !!}"></script>
@endsection