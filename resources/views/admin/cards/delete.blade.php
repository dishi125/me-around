<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Delete @if (@$title) {{ @$title }} @endif</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">
            <h6>Are you sure you want to delete this record?</h6>
        </div>
        <div class="modal-footer">
            <form method="POST" action="javascript:void(0)" accept-charset="UTF-8">
             
                @csrf
                <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" card-id="{{ $id }}" id="cardDelete">Confirm</button>
            </form>
        </div>
    </div>
</div>
