@extends('layouts.app')
@section('styles')
    <link rel="stylesheet" href="{!! asset('css/custom.css') !!}">
@endsection
@section('header-content')
    <h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')

    <?php
    $shopCategory = \App\Models\CategoryTypes::SHOP;
    $data = (object)array();
    if (!empty($englishPrice)) {
        $data = $englishPrice;
    }
    $id = !empty($data->id) ? $data->id : '' ;
    $name= !empty($data->name) ? $data->name : '' ;
    $price= !empty($data->price) ? $data->price : '' ;
    $discount= !empty($data->discount) ? $data->discount : '' ;
    ?>
    <div class="section-body">
        <div class="row mt-sm-4">
            <div class="col-12 col-md-12 col-lg-12">
                <div class="card profile-widget">
                    <div class="profile-widget-description">
                        <div class="">
                            @if (isset($englishPrice))
                                {!! Form::open(['route' => ['admin.important-setting.global-price-setting.price.update', $id], 'id' =>"PriceUpdateForm", 'method' => 'post', 'enctype' => 'multipart/form-data']) !!}
                            @else
                                {!! Form::open(['route' => 'admin.important-setting.global-price-setting.price.store', 'id' =>"PriceForm", 'enctype' => 'multipart/form-data']) !!}
                            @endif
                            @csrf
                            <div class="card-body">
                                <input type="hidden" name="price_category_id" value="{{ isset($price_category_id) ? $price_category_id : '' }}">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('name', __(Lang::get('forms.category.name')).'(English)'); !!}
                                            {!! Form::text('name', $name, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.category.name')).'(English)' ]); !!}
                                            @error('name')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('name') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                    @foreach($postLanguages as $postLanguage)
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <?php
                                                $label = __(Lang::get('forms.category.name')).'('.$postLanguage->name.')';
                                                $cName = array_key_exists($postLanguage->id,$priceLanguages) ? $priceLanguages[$postLanguage->id] : '';
                                                ?>
                                                {!! Form::label('cname', $label); !!}
                                                {!! Form::text('cname['.$postLanguage->id.']', $cName, ['class' => 'form-control', 'placeholder' => $label ]); !!}
                                                @error('name')
                                                <div class="invalid-feedback">
                                                    {{ $errors->get('name') }}
                                                </div>
                                                @enderror
                                            </div>
                                        </div>
                                    @endforeach

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('price', __(Lang::get('forms.category.price'))); !!}
                                            {!! Form::text('price', $price, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.category.price')) ]); !!}
                                            @error('price')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('price') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('discount_price', __(Lang::get('forms.category.discount_price'))); !!}
                                            {!! Form::text('discount_price', $discount, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.category.discount_price')) ]); !!}
                                            @error('discount_price')
                                            <div class="invalid-feedback">
                                                {{ $errors->get('discount_price') }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>

                                </div>

                            </div>
                            <div class="card-footer text-right">
                                <button type="submit"
                                        class="btn btn-primary">{{ __(Lang::get('general.save')) }}</button>
                                <a href="{{ route('admin.important-setting.global-price-setting.index')}}"
                                   class="btn btn-default">{{ __(Lang::get('general.cancel')) }}</a>
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
        var base_url = "{{ url('/admin') }}";
        $('#PriceForm').validate({
            rules: {
                'name': {
                    required: true
                },
                'price': {
                    required: true
                }
            },
            messages: {
                'name':'This field is required',
                'price':'This field is required',
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
        });

        $('#PriceUpdateForm').validate({
            rules: {
                'name': {
                    required: true
                },
                'price': {
                    required: true
                }
            },
            messages: {
                'name':'This field is required',
                'price':'This field is required',
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
        });
    </script>
@endsection
