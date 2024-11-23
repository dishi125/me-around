<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Delete {{ $title }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">
            <h6>Are you sure you want to delete this record?</h6>
        </div>
        <div class="modal-footer">
            <form method="POST" class="bussiness_client_destroy_form" action="javascript::void(0)" data-id="{{ $id }}" data-type="{{ $type }}" accept-charset="UTF-8">
                <input name="_method" type="hidden" value="DELETE">
                @csrf
                <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                <input type="button" value="Confirm" class="btn btn-danger destroyForm"></input>
            </form>
        </div>
    </div>
</div>
