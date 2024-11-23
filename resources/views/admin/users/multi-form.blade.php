@extends('layouts.app')
@section('styles')
    <style>
        .new-form-group:nth-child(odd) {
            margin-right: 5%;
            /* border-right: solid 1px; */
        }

        .new-form-group:nth-child(even) {
            margin-left: 5%;
        }
    </style>
    <link rel="stylesheet" href="{!! asset('css/custom.css') !!}">
@endsection
@section('header-content')
    <h1>
        @if (@$title)
            {{ @$title }}
        @endif
    </h1>
@endsection

@section('content')
    <?php

    ?>
    <div class="section-body">
        <div class="row mt-sm-4">
            <div class="col-12 col-md-12 col-lg-12">
                <div class="card profile-widget">
                    <div class="profile-widget-header">

                    </div>
                    <div class="profile-widget-description">
                        <div class="">
                            {!! Form::open(['route' => 'admin.user.storemultiple', 'id' => 'userForm', 'enctype' => 'multipart/form-data']) !!}
                            @csrf
                            <div class="card-body">
                                {{ Form::hidden('index', '0') }}
                                <div class="row formappend">
                                    @include('admin.users.user-form', ['index' => 0])
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <a href="javascript:void(0);" addURL="{{ route('admin.get.user.form') }}" id="add_new_user"
                                    class="btn btn-default">
                                    <i class="fas fa-plus"></i>
                                </a>
                            </div>
                            <div class="card-footer text-right">
                                <a href="javascript:void(0);"
                                    onclick="saveAsBusinessUser(1, `{{ route('admin.user.storemultiple.business') }}`);"
                                    class="btn btn-sm btn-primary mb-3"> Save as Shop User </a>
                                <a href="javascript:void(0);"
                                    onclick="saveAsBusinessUser(2, `{{ route('admin.user.storemultiple.business') }}`);"
                                    class="btn btn-sm btn-primary mb-3"> Save as Hospital User </a>
                                <button type="submit"
                                    class="btn btn-sm btn-primary mb-3">{{ __(Lang::get('general.save')) }}</button>
                                <a href="{{ route('admin.user.index') }}"
                                    class="btn btn-sm btn-default mb-3">{{ __(Lang::get('general.cancel')) }}</a>
                            </div>
                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $(document).on('click', '#remove_new_user', function() {
            $(this).parent().remove();
        });

        $(document).on('click', '#add_new_user', function() {
            var data = new FormData();
            var index = parseInt($('input[name="index"]').val()) + 1;
            data.append('index', index);

            $.ajax({
                url: $(this).attr('addURL'),
                type: "POST",
                contentType: false,
                processData: false,
                data: data,
                beforeSend: function() {
                    $('.cover-spin').show();
                },
                success: function(response) {
                    $('.cover-spin').hide();
                    $('.formappend').append(response);
                    $('input[name="index"]').val(index);
                    /* $.validator.addClassRules({
                        required: {
                            required: true,
                        },
                        required_email: {
                            required: true,
                            email : true
                        }
                    }); */
                },
                error: function(response, status) {
                    $('.cover-spin').hide();
                }
            });
        });

        $(document).on('submit', "#userForm", function(event) {
            event.preventDefault();
            $('label.error').remove();
            var formData = new FormData(this);

            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                contentType: false,
                processData: false,
                data: formData,
                beforeSend: function() {
                    $('.cover-spin').show();
                },
                success: function(response) {
                    $('.cover-spin').hide();
                    if (response.success == true) {
                        iziToast.success({
                            title: '',
                            message: response.message,
                            position: 'topRight',
                            progressBar: false,
                            timeout: 1000,
                        });

                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 1000);

                    } else {
                        iziToast.error({
                            title: '',
                            message: response.message,
                            position: 'topRight',
                            progressBar: false,
                            timeout: 1500,
                        });
                    }
                },
                error: function(response, status) {
                    $('.cover-spin').hide();
                    if (response.responseJSON.success === false) {
                        var errors = response.responseJSON.errors;

                        $.each(errors, function(key, val) {
                            console.log(val)
                            var errorHtml = '<label class="error">' + val.join("<br />") +
                                '</label>';
                            $('#' + key.replaceAll(".", "_")).parent().append(errorHtml);
                        });
                    }
                }
            });
        });

        $('#userForm').validate({});

        $.validator.addClassRules({
            required: {
                required: true,
            },
            required_email: {
                required: true,
                email: true
            }
        });


        // Unused
        /* $('#userForm').validate({
            rules: {
                    'username[]': {
                        required: true,
                    },
                    'email': {
                        required: true,
                        email: true,
                    },
                    'phone_number': {
                        required: true
                    },
                    'password': {
                        required: true
                    },
                    'password_confirmation': {
                        required: true
                    },


                },
                messages: {
                    'username':  'This field is required',
                    //'email':'This field is required',
                    'phone_number':'This field is required',
                    'password':'This field is required',
                    'password_confirmation':'This field is required',
                },
                highlight: function (input) {
                    $(input).parents('.form-line').addClass('error');
                },
                unhighlight: function (input) {
                    $(input).parents('.form-line').removeClass('error');
                },
                errorPlacement: function (error, element) {
                    $(element).parents('.form-group').append(error);
                },
            });   */


        function saveAsBusinessUser(type, ajaxURL) {
            $('label.error').remove();
            var formData = new FormData($("#userForm")[0]);
            formData.append('type', type);

            $.ajax({
                url: ajaxURL,
                type: "POST",
                contentType: false,
                processData: false,
                data: formData,
                beforeSend: function() {
                    $('.cover-spin').show();
                },
                success: function(response) {
                    $('.cover-spin').hide();
                    if (response.success == true) {
                        iziToast.success({
                            title: '',
                            message: response.message,
                            position: 'topRight',
                            progressBar: false,
                            timeout: 1000,
                        });

                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 1000);

                    } else {
                        iziToast.error({
                            title: '',
                            message: response.message,
                            position: 'topRight',
                            progressBar: false,
                            timeout: 1500,
                        });
                    }
                },
                error: function(response, status) {
                    $('.cover-spin').hide();
                    if (response.responseJSON.success === false) {
                        var errors = response.responseJSON.errors;

                        $.each(errors, function(key, val) {
                            console.log(val)
                            var errorHtml = '<label class="error">' + val.join("<br />") + '</label>';
                            $('#' + key.replaceAll(".", "_")).parent().append(errorHtml);
                        });
                    }
                }
            });
        }
    </script>
@endsection
