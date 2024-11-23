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
                        class="profile-widget-picture mr-3">
                        <div class="font-weight-bold profile-widget-name text-md">Name 1</div>
                    </div>
                </div>
                <div class="card profile-widget">
                    <div class="profile-widget-header">                    
                        <div class="profile-widget-items">
                            <div class="profile-widget-item">
                                <div class="profile-widget-item-label">Followers</div>
                                <div class="profile-widget-item-value">100</div>
                            </div>
                            <div class="profile-widget-item">
                                <div class="profile-widget-item-label">Work Complete</div>
                                <div class="profile-widget-item-value">23</div>
                            </div>
                            <div class="profile-widget-item">
                                <div class="profile-widget-item-label">Review</div>
                                <div class="profile-widget-item-value">45</div>
                            </div>
                            <div class="profile-widget-item">
                                <div class="profile-widget-item-label">Portfolio</div>
                                <div class="profile-widget-item-value">12</div>
                            </div>
                        </div>
                    </div>   
                </div>
        </div>
        <div class="col-12 col-md-12 col-lg-7 mt-sm-3 pt-3">
            <div class="card">
                <div class="card-header">
                    <h4>Shop Details</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-md-6 col-12">
                            <label>Main Name</label>
                            <input type="text" class="form-control" value="name 1" readonly>
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label>Shop Name</label>
                            <input type="text" class="form-control" value="Name 1" readonly>
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
                            <input type="text" class="form-control" value="Abc Road" readonly>
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label>City</label>
                            <input type="text" class="form-control" value="Ahemdabad" readonly>
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label>State</label>
                            <input type="text" class="form-control" value="Gujarat" readonly>
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label>Country</label>
                            <input type="text" class="form-control" value="India" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Main Profile </h4>
                </div>
                <div class="card-body">                   
                    <div class="gallery gallery-md">                       
                        <div class="gallery-item" data-image="{!! asset('img/hospital.png') !!}" data-title="Shop 1"></div>
                        <div class="gallery-item" data-image="{!! asset('img/hospital.png') !!}" data-title="Shop 1"></div>
                        <div class="gallery-item" data-image="{!! asset('img/hospital.png') !!}" data-title="Shop 1"></div>
                        <div class="gallery-item" data-image="{!! asset('img/hospital.png') !!}" data-title="Shop 1"></div>
                    </div>                   
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Workplace </h4>
                </div>
                <div class="card-body">                   
                    <div class="gallery gallery-md">                       
                        <div class="gallery-item" data-image="{!! asset('img/hospital.png') !!}" data-title="Shop 1"></div>
                        <div class="gallery-item" data-image="{!! asset('img/hospital.png') !!}" data-title="Shop 1"></div>
                        <div class="gallery-item" data-image="{!! asset('img/hospital.png') !!}" data-title="Shop 1"></div>
                        <div class="gallery-item" data-image="{!! asset('img/hospital.png') !!}" data-title="Shop 1"></div>
                    </div>                   
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Portfolio</h4>
                </div>
                <div class="card-body">                   
                    <div class="gallery gallery-md">                       
                        <div class="gallery-item" data-image="{!! asset('img/hospital.png') !!}" data-title="Shop 1"></div>
                        <div class="gallery-item" data-image="{!! asset('img/hospital.png') !!}" data-title="Shop 1"></div>
                        <div class="gallery-item" data-image="{!! asset('img/hospital.png') !!}" data-title="Shop 1"></div>
                        <div class="gallery-item" data-image="{!! asset('img/hospital.png') !!}" data-title="Shop 1"></div>
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
<script>
function myFunction() {
  /* Get the text field */
  var copyText = document.getElementById("shop_profile_link");
  /* Select the text field */
  copyText.select();
  copyText.setSelectionRange(0, 99999); /*For mobile devices*/
  /* Copy the text inside the text field */
  document.execCommand("copy");
  /* Alert the copied text */
  iziToast.success({
                    title: '',
                    message: 'Text Copied to Clipboard',
                    position: 'topRight',
                    progressBar: false,
                    timeout: 5000,
                });
//   alert("Copied the text: " + copyText.value);
}
</script>
@endsection