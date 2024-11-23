<div class="form-group">
    {!! Form::label($field_name,$field_label) !!}

    @if($field_type == 'file')
        {!! Form::file($field_name, ['required' => true, 'accept' => $accept, 'class' => 'form-control', 'placeholder' => __($field_label) ]); !!}
    @else
        {!! Form::text($field_name, $field_value, ['required' => true, 'class' => 'form-control', 'placeholder' => __($field_label) ]); !!}
    @endif
</div>