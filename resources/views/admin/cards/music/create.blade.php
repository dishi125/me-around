@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/bootstrap-datepicker/bootstrap-datepicker.min.css') !!}">
<link rel="stylesheet" href="{!! asset('plugins/bootstrap-datepicker/bootstrap.min.css') !!}">

@endsection

@section('header-content')
<h1>{{ @$title }}</h1>
@endsection


@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card profile-widget">
            <div class="profile-widget-description">
                    {!! Form::open(['route' => ['admin.card.music.store',$card], 'id' =>"musicForm", 'enctype' => 'multipart/form-data']) !!}
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            {!! Form::label('music_file','Card Music') !!}
                            {!! Form::file('music_file', ['required' => true, 'accept' => ".mp3,audio/*", 'class' => 'form-control', 'placeholder' => __('Card Music') ]); !!}
                        </div>
                        <div class="col-md-6">
                        </div>
                    </div>
                    <div class="card-footer pl-0 pt-5">
                        <button type="submit"
                            class="btn btn-primary mr-2">{{ __(Lang::get('general.save')) }}</button>
                        <a href="{{ route('admin.cards.index')}}"
                            class="btn btn-default">{{ __(Lang::get('general.cancel')) }}</a>
                    </div>
                    {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>
<div class="cover-spin"></div>

@endsection

@section('scripts')
<script src="{!! asset('plugins/bootstrap-datepicker/bootstrap-datepicker.min.js') !!}"></script>

<script>
    var csrfToken = "{{csrf_token()}}";
    $(function () {
        $(document).on('submit',"#musicForm",function(event){
            event.preventDefault();
            $('label.error').remove();
            var formData = new FormData(this);
            $.ajax({
                url: $(this).attr('action'),
                type:"POST",
                contentType: false, 
                processData: false,
                data: formData,
                beforeSend: function() {
                    $('.cover-spin').show();
                },
                success:function(response) {
                    $('.cover-spin').hide();
                    if(response.success == true){
                        showToastMessage(response.message,true);

                        if(response.redirect){
                            setTimeout(function(){
                                window.location.href = response.redirect;
                            },1000);
                        }

                    }else {
                        showToastMessage(response.message,false);
                    }
                },
                error:function (response, status) {
                    $('.cover-spin').hide();
                    showToastMessage('Wedding has not been created successfully',false);
                }
            });
        });
    });

</script>
@endsection
