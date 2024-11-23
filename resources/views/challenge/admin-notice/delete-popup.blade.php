<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Delete Admin Notice</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">
            <h6>Are you sure you want to delete this admin notice?</h6>
        </div>
        <div class="modal-footer">
           <form method="POST" action="javascript:void(0)" accept-charset="UTF-8">
                @csrf
                <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary" notice-id="{{ $id }}" id="deleteNotice">Confirm</button>
            </form>
        </div>
    </div>
</div>
