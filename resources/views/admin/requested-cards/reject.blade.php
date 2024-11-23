<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>{{ $title }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <form method="POST" id="reject_form" class="reject_form" action="javascript::void(0)" data-id="{{ $card_id }}" accept-charset="UTF-8">
            <div class="modal-body justify-content-center">
                <h6>Are you sure you want to reject this card?</h6>
                
                <input type="text" name="reason" id="reason" class="form-control" placeholder="Reject reason" required />
            </div>
            <div class="modal-footer">
                @csrf
                <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                <input type="submit" value="Confirm" class="btn btn-danger rejectForm" />
            </div>
        </form>
    </div>
</div>
