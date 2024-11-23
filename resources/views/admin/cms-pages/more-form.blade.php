<div class="row index_{{$field}}">
    @foreach($more_field as $key => $field_group)
        @foreach($field_group as $childKey => $childField)
            <div class="col-md-{{$childField['col']}}">
                <div class="form-group">
                    @if($childField['type'] == "text")
                        {!! Form::text("$field"."[".$index."]"."[$childKey]", '', ['id' => $childKey, 'class' => 'required form-control', 'placeholder' => __($childField['label']) ]); !!}
                    @endif
                </div>
            </div>
        @endforeach
    @endforeach
    <div class="col-md-2">
        <button  type="button" id="remove_more" class="btn btn-default">
            <i class="fas fa-minus"></i>
        </button>
    </div>
</div>