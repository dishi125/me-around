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
        <div class="col-12 col-md-12 col-lg-12 mt-sm-3 pt-3">
            <div class="card">
                <div class="card-header">
                    <h4>Review Details</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-md-6 col-12">
                            <label>Comment</label>
                            <input type="text" class="form-control" value="{{$review->review_comment}}" readonly>
                        </div>
                        
                    </div>                    
                </div>
                
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Before Images </h4>
                </div>
                <div class="card-body">                   
                    <div class="gallery gallery-md">   
                        @foreach($review->before_images as $ci)                    
                            <div class="gallery-item" data-image="{{ $ci }}" data-title="{{$review->user_name}}"></div>
                        @endforeach
                    </div>                   
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>After Images </h4>
                </div>
                <div class="card-body">                   
                    <div class="gallery gallery-md">   
                        @foreach($review->after_images as $ci)                    
                            <div class="gallery-item" data-image="{{ $ci }}" data-title="{{$review->user_name}}"></div>
                        @endforeach
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