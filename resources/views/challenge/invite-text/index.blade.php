@extends('challenge-layouts.app')

@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
    <style>
        .table-responsive button#show-profile {
            width: auto;
            margin: 5px;
            white-space: normal;
        }

        .table-responsive .shops-date button#show-profile {
            width: 180px;
        }

        .table-responsive .shops-rate button#show-profile {
            width: 80px;
        }

        .table-responsive td span {
            margin: 5px;
        }

        .button-container {
            display: flex;
            flex-direction: row;
            /*justify-content: space-between;*/
            align-items: center;
        }

        .vertical-buttons {
            display: flex;
            flex-direction: column;
        }

        /*.button {
            margin-bottom: 10px;
        }*/
    </style>
@endsection

@section('header-content')
    <h1>
        @if (@$title)
            {{ @$title }}
        @endif
    </h1>
<!--    <div class="section-header-button">
        <button class="btn btn-primary mr-2" id="add_link">Add new</button>
    </div>-->
@endsection

@section('content')
    <div class="row">
        <div class="col-12 col-md-12 col-lg-5">
        <div class="card profile-widget">
                <div class="profile-widget-description">
                    <div>
                        <form method="POST" accept-charset="UTF-8" id="inviteTextForm">
                            {{ csrf_field() }}
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="invite_text">{{ __('forms.invite_text.add_text') }}</label>
                                            <textarea class="form-control" name="invite_text" id="invite_text" style="height: 300px">{{ !empty($inviteText) ? $inviteText->text : "" }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button type="submit" class="btn btn-primary" id="save_btn">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="cover-spin"></div>
@endsection

@section('scripts')
    <script>
        var csrfToken = "{{ csrf_token() }}";
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script>
        $(document).on('submit', '#inviteTextForm', function (e){
            e.preventDefault();
            $(".text-danger").remove();
            var formData = $(this).serialize();

            $.ajax({
                url: "{{ url('challenge/invite-text/save') }}",
                // processData: false,
                // contentType: false,
                type: 'POST',
                data: formData,
                success:function(response){
                    $(".cover-spin").hide();
                    if(response.success == true){
                        showToastMessage('Invite text updated successfully.',true);
                        location.reload();
                    }
                    else {
                        if(response.errors) {
                            var errors = response.errors;
                            $.each(errors, function (key, value) {
                                $('#' + key).next('.text-danger').remove();
                                $('#' + key).after('<div class="text-danger">' + value[0] + '</div>');
                            });
                        }
                        else {
                            showToastMessage(response.message,false);
                        }
                    }
                },
                beforeSend: function (){
                    $(".cover-spin").show();
                },
                error: function (xhr) {
                    $(".cover-spin").hide();
                    showToastMessage('Something went wrong!!',false);
                },
            });
        })
    </script>
@endsection
