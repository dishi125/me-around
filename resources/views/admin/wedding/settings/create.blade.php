@extends('layouts.app')

@section('styles')
<style>

</style>
@endsection

@section('header-content')
<h1>{{ @$title }}</h1>
@endsection

<?php
$id = $id ?? '';
$imageURL = $imagesfile = '';
?>

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card profile-widget">
            <div class="profile-widget-description">
                    @if($id)
                        {!! Form::open(['route' => ['admin.wedding.settings.update',$id], 'id' =>"weddingForm", 'enctype' => 'multipart/form-data']) !!}
                    @else
                        {!! Form::open(['route' => 'admin.wedding.settings.store', 'id' =>"weddingForm", 'enctype' => 'multipart/form-data']) !!}
                    @endif
                    @csrf

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('key', __('Settings Type')); !!}
                                {!!Form::select('key', $settingTypes, '' , ['class' => 'form-control'])!!}
                            </div>
                        </div>
                        <div class="col-md-6 field_data_append" >
                            @include('admin.wedding.settings.field', ['field_name' => 'video_file','field_label' => 'Video Animation','field_type' => 'file', 'accept' => 'video/mp4,video/*'])
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit"
                            class="btn btn-primary mr-2">{{ __(Lang::get('general.save')) }}</button>
                        <a href="{{ route('admin.wedding.settings')}}"
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
        $(document).on('submit',"#weddingForm",function(event){
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
                        iziToast.success({
                            title: '',
                            message: response.message,
                            position: 'topRight',
                            progressBar: false,
                            timeout: 1000,
                        });

                        setTimeout(function(){
                            window.location.href = response.redirect;
                        },1000);

                    }else {
                        iziToast.error({
                            title: '',
                            message: 'Wedding has not been created successfully.',
                            position: 'topRight',
                            progressBar: false,
                            timeout: 1500,
                        });
                    }
                },
                error:function (response, status) {
                    $('.cover-spin').hide();
                    iziToast.error({
                        title: '',
                        message: 'Wedding has not been created successfully.',
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1500,
                    });
                }
            });
        });

        $(document).on('change','select[name="key"]',function(e){
            e.preventDefault();
            const selectType = $(this).val();
            if(selectType){
                $.ajax({
                    url: "{{route('admin.wedding.change.field')}}",
                    type:"POST",
                    data: {select_type : selectType},
                    beforeSend: function() {
                        $('.cover-spin').show();
                    },
                    success:function(response) {
                        $('.cover-spin').hide();
                        if(response.success == true){
                            $(".field_data_append").html(response.html);
                        }else {
                          
                        }
                    },
                    error:function (response, status) {
                        $('.cover-spin').hide();
                    }
                });
            }
        });
    });

</script>
@endsection
