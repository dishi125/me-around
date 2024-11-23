<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Edit</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-footer">
           <form id="editForm" style="width: 100%;" method="POST" action="{{route('admin.regular-payment.edit',$id)}}" accept-charset="UTF-8">
                @csrf
               <div class="row">
                   <div class="col-md-12">
                       <div class="form-group mt-3">
                           <label for="instagram_account">Instagram Account Name</label>
                           <input type="text" class="form-control" value="{{$data->instagram_account}}"
                                  name="instagram_account" required id="instagram_account" />
                       </div>
                   </div>
               </div>
               <div class="row">
                   <div class="col-md-12">
                       <div class="form-group mt-3">
                           <label for="pay_goods">Product Name</label>
                           <input type="text" class="form-control" value="{{$data->pay_goods}}"
                                  name="pay_goods" required id="pay_goods" />
                       </div>
                   </div>
               </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group mt-3">
                            <label for="amount">Amount</label>
                            <input type="text" class="form-control" value="{{$data->pay_total}}"
                                name="amount" required id="amount" />
                        </div>
                    </div>
                </div>
               <div class="row">
                   <div class="col-md-12">
                       <div class="form-group mt-3">
                           <label for="start_date">Starting date</label>
                           <input type="date" class="form-control" value="{{$data->start_date}}"
                                  name="start_date" required id="start_date" />
                       </div>
                   </div>
               </div>

                <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Close</button>
                <button type="submit" id="form_submit" class="btn btn-primary">Confirm</button>
                <button type="button" id="removePaymentBtn" class="btn btn-danger" style="float: right" data-id="{{ $data->id }}">Remove</button>
            </form>
        </div>
    </div>
</div>

<script>
    $('#editForm').validate({
        rules: {
            'pay_goods': {
                required: true
            },
            'amount': {
                required: true
            },
            'start_date': {
                required: true
            },
            'instagram_account': {
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
