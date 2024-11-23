@extends('layouts.app')
@section('styles')
<link rel="stylesheet" href="{!! asset('css/custom.css') !!}">
@endsection
@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')
<?php
$id = (isset($InstagramCategory)) ? $InstagramCategory->id : '' ;
$title = (isset($InstagramCategory) && isset($InstagramCategory->title)) ? $InstagramCategory->title : '' ;
$sub_title = (isset($InstagramCategory) && isset($InstagramCategory->sub_title)) ? $InstagramCategory->sub_title : '' ;
?>
<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-12">
            <div class="card profile-widget">
                <div class="profile-widget-description">
                    <div class="">
                        @if (isset($InstagramCategory))
                            {!! Form::open(['route' => ['admin.insta-category.update', $id], 'id' =>"InstaCategoryForm", 'method' => 'post', 'enctype' => 'multipart/form-data']) !!}
                        @else
                            <form id="InstaCategoryForm" method="post" action="{{ route('admin.insta-category.store') }}" enctype="multipart/form-data">
                        @endif
                        @csrf
                        <div class="card-body" id="card_body_div">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('title', __(Lang::get('forms.insta-category.title')).'(English)'); !!}
                                        {!! Form::text('title', $title, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.insta-category.title')).'(English)', 'required' ]); !!}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('sub_title', __(Lang::get('forms.insta-category.sub_title')).'(English)'); !!}
                                        {!! Form::text('sub_title', $sub_title, ['class' => 'form-control', 'placeholder' => __(Lang::get('forms.insta-category.sub_title')).'(English)', 'required' ]); !!}
                                    </div>
                                </div>
                            </div>
                            @foreach($postLanguages as $postLanguage)
                                <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <?php
                                        $label = __(Lang::get('forms.insta-category.title')).'('.$postLanguage->name.')';
                                        $cName = array_key_exists($postLanguage->id,$categoryLanguages) ? $categoryLanguages[$postLanguage->id] : '';
                                        ?>
                                        {!! Form::label('cname', $label); !!}
                                        {!! Form::text('cname['.$postLanguage->id.']', $cName, ['class' => 'form-control', 'placeholder' => $label ]); !!}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <?php
                                        $label = __(Lang::get('forms.insta-category.sub_title')).'('.$postLanguage->name.')';
                                        $sName = array_key_exists($postLanguage->id,$subTitleLanguages) ? $subTitleLanguages[$postLanguage->id] : '';
                                        ?>
                                        {!! Form::label('sname', $label); !!}
                                        {!! Form::text('sname['.$postLanguage->id.']', $sName, ['class' => 'form-control', 'placeholder' => $label ]); !!}
                                    </div>
                                </div>
                                </div>
                            @endforeach

                           {{-- <div class="row">
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-primary" id="btn_add_country">{{ __(Lang::get('forms.insta-category.add_option')) }}</button>
                                </div>
                            </div>--}}

                            <div class="row mt-2" style="display: none" id="country_div">
                            </div>

                            <div class="row mt-2" style="border: 1px solid; border-radius: 10px" id="">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="options" class="">Options</label>
                                        <div class="repeater-content" id="repeater-content">
                                            @if(!empty($InstagramCategory->categoryoption))
                                                <?php $cnt = 1; ?>
                                                @foreach($InstagramCategory->categoryoption as $key=>$option)
                                                    <div class="row index_category_options">
                                                        <input type="hidden" id="order" value="">
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <input id="option_title_4" class="form-control option_title" placeholder="{{ __(Lang::get('forms.insta-category.option_title')).'(English)' }}" name="option_title" type="text" value="{{ $option->title }}" required>
                                                                @foreach($postLanguagesOption as $postLanguage)
                                                                    <?php $title = \App\Models\InstagramCategoryOptionLanguage::where('entity_id',$option->id)->where('language_id',$postLanguage->id)->pluck('title')->first(); ?>
                                                                    <input type="hidden" id="option_title_{{ $postLanguage->id }}" class="option_title" value="{{ $title }}">
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="form-group">
                                                                <input id="option_price_4" class="form-control option_price" placeholder="{{ __(Lang::get('forms.insta-category.option_price')).'(English)' }}" name="option_price" type="text" value="{{ $option->price }}">
                                                                @foreach($postLanguagesOption as $postLanguage)
                                                                    <?php $price = \App\Models\InstagramCategoryOptionLanguage::where('entity_id',$option->id)->where('language_id',$postLanguage->id)->pluck('price')->first(); ?>
                                                                    <input type="hidden" id="option_price_{{ $postLanguage->id }}" class="option_price" value="{{ $price }}">
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="form-group">
                                                                <input id="option_link_4" class="form-control option_link" placeholder="{{ __(Lang::get('forms.insta-category.option_link')).'(English)' }}" name="option_link" type="text" value="{{ $option->link }}">
                                                                @foreach($postLanguagesOption as $postLanguage)
                                                                    <?php $link = \App\Models\InstagramCategoryOptionLanguage::where('entity_id',$option->id)->where('language_id',$postLanguage->id)->pluck('link')->first(); ?>
                                                                    <input type="hidden" id="option_link_{{ $postLanguage->id }}" class="option_link" value="{{ $link }}">
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2" id="btn_add_remove">
                                                            <button type="button" id="edit_option" class="btn btn-default btn_edit_option" cnt="{{ $cnt }}"><i class="fa fa-edit"></i></button>
                                                            @if($key==0)
                                                            <button type="button" id="add_more" class="btn btn-default btn_plus_minus"><i class="fas fa-plus"></i></button>
                                                            @else
                                                            <button type="button" id="remove_more" class="btn btn-default btn_plus_minus"><i class="fas fa-minus"></i></button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <?php $cnt++; ?>
                                                @endforeach
                                            @else
                                                <div class="row index_category_options">
                                                    <input type="hidden" id="order" value="">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <input id="option_title_4" class="form-control option_title" placeholder="{{ __(Lang::get('forms.insta-category.option_title')).'(English)' }}" name="option_title" type="text" value="" required>
                                                            @foreach($postLanguagesOption as $postLanguage)
                                                            <input type="hidden" id="option_title_{{ $postLanguage->id }}" class="option_title">
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <input id="option_price_4" class="form-control option_price" placeholder="{{ __(Lang::get('forms.insta-category.option_price')).'(English)' }}" name="option_price" type="text" value="">
                                                            @foreach($postLanguagesOption as $postLanguage)
                                                            <input type="hidden" id="option_price_{{ $postLanguage->id }}" class="option_price">
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <input id="option_link_4" class="form-control option_link" placeholder="{{ __(Lang::get('forms.insta-category.option_link')).'(English)' }}" name="option_link" type="text" value="">
                                                            @foreach($postLanguagesOption as $postLanguage)
                                                            <input type="hidden" id="option_link_{{ $postLanguage->id }}" class="option_link">
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2" id="btn_add_remove">
                                                        <button type="button" id="edit_option" class="btn btn-default btn_edit_option" cnt="1"><i class="fa fa-edit"></i></button>
                                                        <button type="button" id="add_more" class="btn btn-default btn_plus_minus"><i class="fas fa-plus"></i></button>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="card-footer text-right">
                            <button type="submit" class="btn btn-primary" id="btn_submit">{{ __(Lang::get('general.save')) }}</button>
                            <a href="{{ route('admin.insta-category.index')}}" class="btn btn-default">{{ __(Lang::get('general.cancel')) }}</a>
                        </div>

                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="cover-spin"></div>

<!-- Modal -->
<div class="modal fade" id="EditOptionModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body justify-content-center">
                <form method="post" id="edit_option_form">
                <input type="hidden" name="cnt">
                @foreach($postLanguagesOption as $postLanguage)
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <input id="option_title_{{ $postLanguage->id }}" class="form-control option_title" placeholder="{{ __(Lang::get('forms.insta-category.option_title')).'('.$postLanguage->name.')' }}" name="option_title[{{ $postLanguage->id }}]" type="text" value="" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <input id="option_price_{{ $postLanguage->id }}" class="form-control option_price" placeholder="{{ __(Lang::get('forms.insta-category.option_price')).'('.$postLanguage->name.')' }}" name="option_price[{{ $postLanguage->id }}]" type="text" value="">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <input id="option_link_{{ $postLanguage->id }}" class="form-control option_link" placeholder="{{ __(Lang::get('forms.insta-category.option_link')).'('.$postLanguage->name.')' }}" name="option_link[{{ $postLanguage->id }}]" type="text" value="">
                        </div>
                    </div>
                </div>
                @endforeach
                </form>
            </div>
            <div class="modal-footer">
{{--                <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>--}}
                <button type="button" class="btn btn-primary" id="btn_submit_option">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{!! asset('plugins/jquery-ui/jquery-ui.js') !!}"></script>
<script type="text/javascript">
var base_url = "{{ url('/admin') }}";
var cnt = {{ isset($cnt) ? $cnt : 2 }};
$(function () {
    $("#repeater-content").sortable({
        items: ".index_category_options",
        cursor: "move",
        opacity: 0.6,
        update: function () {
            // sendOrderToServer();
            $(".repeater-content").each(function (){
                $(this).children('.index_category_options').each(function (index, val){
                    if(index == 0){
                        $(this).find('.btn_plus_minus').attr('id','add_more');
                        $(this).find('.btn_plus_minus').html(`<i class="fas fa-plus"></i>`);
                    }
                    else{
                        $(this).find('.btn_plus_minus').attr('id','remove_more');
                        $(this).find('.btn_plus_minus').html(`<i class="fas fa-minus"></i>`);
                    }
                })
            })
        },
    });
});

function validate_form() {
    var valid = true;
    $('#InstaCategoryForm').validate({
        rules: {
            'title': {
                required: true
            },
            'sub_title' :{
                required: true
            },
        },
        messages: {
            'title':'This field is required',
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

    $(".categoryOptionForm").each(function (){
        $(this).validate({
            rules: {
                'option_title': {
                    required: true
                },
                'option_price' :{
                    required: true
                },
            },
            messages: {
                'option_title':'This field is required',
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

        if(!$(this).valid()){
            valid = false;
        }
    })

    if(!$('#InstaCategoryForm').valid()){
        valid = false;
    }

    $('.repeater-content').nextAll('.error').remove();
    return valid;
}

$(document).on('click','#add_more',function(){
    var html = `<div class="row index_category_options">
                                                    <input type="hidden" id="order" value="">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <input id="option_title_4" class="form-control option_title" placeholder="{{ __(Lang::get('forms.insta-category.option_title')).'(English)' }}" name="option_title" type="text" value="" required>
                                                            @foreach($postLanguagesOption as $postLanguage)
    <input type="hidden" id="option_title_{{ $postLanguage->id }}" class="option_title">
                                                            @endforeach
    </div>
</div>
<div class="col-md-3">
    <div class="form-group">
        <input id="option_price_4" class="form-control option_price" placeholder="{{ __(Lang::get('forms.insta-category.option_price')).'(English)' }}" name="option_price" type="text" value="">
                                                            @foreach($postLanguagesOption as $postLanguage)
    <input type="hidden" id="option_price_{{ $postLanguage->id }}" class="option_price">
                                                            @endforeach
    </div>
</div>
<div class="col-md-3">
    <div class="form-group">
        <input id="option_link_4" class="form-control option_link" placeholder="{{ __(Lang::get('forms.insta-category.option_link')).'(English)' }}" name="option_link" type="text" value="">
                                                            @foreach($postLanguagesOption as $postLanguage)
    <input type="hidden" id="option_link_{{ $postLanguage->id }}" class="option_link">
                                                            @endforeach
    </div>
</div>
<div class="col-md-2" id="btn_add_remove">
    <button type="button" id="edit_option" class="btn btn-default btn_edit_option" cnt="${cnt}"><i class="fa fa-edit"></i></button>
    <button type="button" id="remove_more" class="btn btn-default btn_plus_minus"><i class="fas fa-minus"></i></button>
</div>
</div>`;

    $(this).parents('.repeater-content:first').append(html);
    cnt++;
});

$(document).on('click','#remove_more',function(){
    $(this).parent().parent().remove();
});

$(document).on('click', '#edit_option', function (){
    $("#edit_option_form")[0].reset();
    $("#EditOptionModal").find('input[name=cnt]').val($(this).attr('cnt'));

    var index_category_options = $(this).parents('.index_category_options:first');
    $(index_category_options).find('.option_title').each(function (){
        var id = $(this).attr('id');
        $("#EditOptionModal").find('#'+id).val($(this).val());
    })

    $(index_category_options).find('.option_price').each(function (){
        var id = $(this).attr('id');
        $("#EditOptionModal").find('#'+id).val($(this).val());
    })

    $(index_category_options).find('.option_link').each(function (){
        var id = $(this).attr('id');
        $("#EditOptionModal").find('#'+id).val($(this).val());
    })

    $("#EditOptionModal").modal('show');
})

$(document).on('click', '#btn_submit_option', function (){
    var cnt_val = $("#EditOptionModal").find('input[name=cnt]').val();

    $(".btn_edit_option").each(function (){
        var index_category_options = $(this).parents('.index_category_options:first');
        if($(this).attr('cnt') == cnt_val){
            $("#EditOptionModal").find('.option_title').each(function (){
                var id = $(this).attr('id');
                $(index_category_options).find('#'+id).val($(this).val());
            })

            $("#EditOptionModal").find('.option_price').each(function (){
                var id = $(this).attr('id');
                $(index_category_options).find('#'+id).val($(this).val());
            })

            $("#EditOptionModal").find('.option_link').each(function (){
                var id = $(this).attr('id');
                $(index_category_options).find('#'+id).val($(this).val());
            })
        }
    })

    $("#EditOptionModal").modal('hide');
})

$(document).on('submit',"#InstaCategoryForm",function(e){
    e.preventDefault();
    // $('.error').remove();

    $(".repeater-content").each(function (){
        $(this).children('.index_category_options').each(function (index, val){
            $(this).find('#order').val(parseInt(index)+1);
        })
    })

    var formData = new FormData($("#InstaCategoryForm")[0]);
    var cnt = 1;
    $(".index_category_options").each(function (){
        $(this).find('.option_title').each(function (){
            formData.append($(this).attr('id')+"_"+cnt, $(this).val());
        })

        $(this).find('.option_price').each(function (){
            formData.append($(this).attr('id')+"_"+cnt, $(this).val());
        })

        $(this).find('.option_link').each(function (){
            formData.append($(this).attr('id')+"_"+cnt, $(this).val());
        })

        formData.append("option_order_"+cnt, $(this).find("#order").val());
        cnt++;
    })
    formData.append("total_options", cnt-1);

    // if(validate_form()) {
        $.ajax({
            url: $("#InstaCategoryForm").attr('action'),
            processData: false,
            contentType: false,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('.cover-spin').show();
            },
            success: function (data) {
                $('.cover-spin').hide();
                showToastMessage(data.message, data.success);
                location.href = "{{ route('admin.insta-category.index') }}";
            },
            error: function (data) {
                $('.cover-spin').hide();
                showToastMessage(data.message, data.success);
            }
        });
    // }
})
</script>
@endsection
