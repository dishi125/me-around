<div class="col-md-5 new-form-group pb-5">
    @if($index != 0)
        <a style="position: absolute; top: -10px; right: -25px;" href="javascript:void(0);" id="remove_new_user" class="btn btn-default">
            <i class="fas fa-minus"></i>
        </a>
    @endif
    <div class="row form_group">
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label("details_$index"."_username", __(Lang::get('forms.user.username'))); !!}
                {!! Form::text("details[$index][username]", '', ['id' => "details_$index"."_username", 'class' => 'required form-control', 'placeholder' => __(Lang::get('forms.user.username')) ]); !!}
            
                @if ($errors->has('username'))
                <span class="error" role="alert">
                    <strong>{{ $errors->first('username') }}</strong>
                </span>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label("details_$index"."_email", __(Lang::get('forms.user.email'))); !!}
                {!! Form::text("details[$index][email]", '', ['id' => "details_$index"."_email", 'class' => 'required_email form-control', 'placeholder' => __(Lang::get('forms.user.email')) ]); !!}
            
                @if ($errors->has('email'))
                <div class="error">
                    <strong>{{ $errors->first('email') }}</strong>
                </div>
                @endif
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {!! Form::label("details_$index"."_phone_number", __(Lang::get('forms.user.phone_number'))); !!}
                {!! Form::text("details[$index][phone_number]", '', [ 'id' => "details_$index"."_phone_number", 'class' => 'required form-control', 'placeholder' => __(Lang::get('forms.user.phone_number')) ]); !!}

                @if ($errors->has('phone_number'))
                <div class="error">
                    <strong>{{ $errors->first('phone_number') }}</strong>
                </div>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label("details_$index"."_password", __(Lang::get('forms.user.password'))); !!}
                {!! Form::text("details[$index][password]", '', ['id' =>"details_$index"."_password", 'class' => 'required form-control', 'placeholder' => __(Lang::get('forms.user.password')) ]); !!}

                @if ($errors->has('password'))
                <div class="error">
                    <strong>{{ $errors->first('password') }}</strong>
                </div>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label("details_$index"."_password_confirmation", __(Lang::get('forms.user.password_confirmation'))); !!}
                {!! Form::text("details[$index][password_confirmation]", '', ['id' => "details_$index"."_password_confirmation", 'class' => 'required form-control', 'placeholder' => __(Lang::get('forms.user.password_confirmation')) ]); !!}

                @if ($errors->has('password_confirmation'))
                <div class="error">
                    <strong>{{ $errors->first('password_confirmation') }}</strong>
                </div>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label("details_$index"."_gender", __(Lang::get('forms.user.gender'))); !!} 
                {!! Form::select("details[$index][gender]", array('Male' => 'Male', 'Female' => 'Female'), ['id' => "details_$index"."_gender", 'class' => 'form-control'. ( $errors->has('gender ') ? ' is-invalid' : '' ), 'placeholder' => __(Lang::get('forms.user.gender')) ]); !!}

                @if ($errors->has('gender'))
                <div class="error">
                    <strong>{{ $errors->first('gender') }}</strong>
                </div>
                @endif
            </div>
        </div>
    </div>  
</div>