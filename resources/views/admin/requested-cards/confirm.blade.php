<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>{{ $title }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">
            <h6>Are you sure you want to process this card?</h6>
        </div>
        <div class="modal-footer">
            <form method="POST" id="process_form" class="process_form" action="javascript::void(0)" data-id="{{ $card_id }}" accept-charset="UTF-8">
                @csrf
                <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                <input type="submit" value="Confirm" class="btn btn-danger processForm" />
            </form>
        </div>
    </div>
</div>
