<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Edit Next Payment Date</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-footer">
           <form id="editNextPayForm" style="width: 100%;" method="POST" action="{{route('admin.regular-payment.edit-next-payment',$id)}}" accept-charset="UTF-8">
                @csrf
               <div class="row">
                   <div class="col-md-12">
                       <div class="form-group mt-3">
                           <label for="next_payment_date">Next Payment date</label>
                           <input type="date" class="form-control" value="{{$data->next_payment_date}}"
                                  name="next_payment_date" required id="next_payment_date" />
                       </div>
                   </div>
               </div>

                <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Close</button>
                <button type="submit" id="form_submit" class="btn btn-primary">Confirm</button>
            </form>
        </div>
    </div>
</div>

<script>
    $('#editNextPayForm').validate({
        rules: {
            'next_payment_date': {
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
