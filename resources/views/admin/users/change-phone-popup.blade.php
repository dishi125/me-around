<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Edit Phone Number</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-footer">
           <form id="editPhoneForm" style="width: 100%;" method="POST" action="{{route('admin.user.edit-phone',$id)}}" accept-charset="UTF-8">
                @csrf
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group mt-3">
                            <input type="text" class="form-control" value="{{$userdata->mobile}}"
                                name="phone"
                                placeholder="Phone" required
                                id="phone" />
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
    $('#editPhoneForm').validate({
        rules: {
            'phone': {
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
