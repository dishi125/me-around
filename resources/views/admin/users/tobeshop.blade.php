<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Update to Business</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-footer">
           <form id="updateToShopForm" data-userId='<?= $id ?>' style="width: 100%;" method="POST" action="javascript:void(0)" accept-charset="UTF-8">
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group mt-3">
                            <input type="text" class="form-control" value="" id="shop_name"
                                name="shop_name"
                                placeholder="Company/Shop Name"  >
                        </div>
                        <div class="form-group">
                            {!!Form::select('user_type', ['shop' => 'Shop','hospital' => 'Hospital'], '' , ['id' => 'user_type', 'class' => 'form-control'])!!}
                        </div>
                        <div class="form-group" id="shop-cat">
                            {!!Form::select('category', $category, '' , ['id' => 'category', 'class' => 'form-control','placeholder' => 'Select Category'])!!}
                        </div>
                        <div class="form-group" id="hospital-cat" style="display: none;">
                            {!!Form::select('hospital_category', $hospitalCategory, '' , ['id' => 'hospital-category', 'class' => 'form-control','placeholder' => 'Select Category'])!!}
                        </div>
                        
                        <div class="form-group mt-3">
                            <input type="text" class="form-control" value="" id="supporter_code"
                            name="supporter_code"
                            placeholder="Supporter Code"  >
                        </div>
                    </div>
                </div>   
                            
                <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Close</button>
                <button type="submit" id="updateToShop" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>
</div>

<script>

    $("form#updateToShopForm").submit(function(e) {  
        e.preventDefault();

        $.ajax({
            url: baseUrl + '/admin/update/user/to/shop',
            type: 'POST',
            data: {
                '_token': $('input[name=_token]').val(),
                'shop_name': $('#shop_name').val(),
                'category': $('#category').val(),
                'id' : $('form#updateToShopForm').data('userid'),
                'supporter_code' : $('#supporter_code').val(),
                'user_type' : $('#user_type').val(),
                'hospital_category' : $('#hospital_category').val(),
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
                    $("#deletePostModal").modal('hide');
                    $('#all-table').dataTable().api().ajax.reload();
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
   
   $(document).on('change','#user_type', function(){
        var type = $(this).val();
        if(type == 'shop'){
            $('#shop-cat').show();
            $('#hospital-cat').hide();
        }else{
            $('#hospital-cat').show();
            $('#shop-cat').hide();
        }
   });

    $('form#updateToShopForm').validate({
        rules: {
            'shop_name': {
                required: true
            },
            'category': {
                required: function(){
                    return $("#user_type").val() == "shop";
                }
            },
            'hospital_category': {
                required: function(){
                    return $("#user_type").val() == "hospital";
                }
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
