<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Shop Price Category</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-footer">
           <form id="viewPriceCategory" data-shopId='<?= $id ?>' data-catId='<?= $cat_id ?>' style="width: 100%;" method="POST" action="javascript:void(0)" accept-charset="UTF-8">
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group mt-3">
                            <input type="text" class="form-control" value="<?= $title ?>" id="title"
                                name="title"
                                placeholder="Title"  >
                        </div>
                    </div>
                </div>   
                            
                <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Close</button>
                <button type="submit" id="saveShopPriceCategory" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>
</div>

<script>

    $("form#viewPriceCategory").submit(function(e) {  
        e.preventDefault();

        $.ajax({
            url: baseUrl + '/admin/business-client/save/shop/price/category',
            type: 'POST',
            data: {
                '_token': $('input[name=_token]').val(),
                'title': $('#title').val(),
                'shop_id' : $('form#viewPriceCategory').data('shopid'),
                'cat_id' : $('form#viewPriceCategory').data('catid'),
            },
            success: function(results) {
                $(".cover-spin").hide();
                
                if(results.response == true) {
                    iziToast.success({
                        title: '',
                        message: results.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 1000,
                    });
                    $("#profileModal").modal('hide');
                    if(results.is_edit == true){
                        console.log('edited');
                        $("ul.shopPriceCategoryBlock > #list_"+ $('form#viewPriceCategory').data('catid') +" > span.name").text($('#title').val());
                    }else{
                        $("ul.shopPriceCategoryBlock").append(results.html);
                    }
                    
                }else {
                    iziToast.error({
                        title: '',
                        message: results.message,
                        position: 'topRight',
                        progressBar: false,
                        timeout: 2000,
                    });
                    //$("#deletePostModal").modal('hide');
                }
                
            },
            beforeSend: function(){ $(".cover-spin").show(); },  
            error: function(data) {
                
            }
        });
    });  
   
    $('form#updateToShopForm').validate({
        rules: {
            'shop_name': {
                required: true
            },
            'category': {
                required: true
            },
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
