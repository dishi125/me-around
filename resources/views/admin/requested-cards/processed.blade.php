<div class="modal-dialog" style="max-width: 45%;">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>{{ $title }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">
            <div class="row">
                <div class="col-md-6">
                    <strong>Recipient Name : </strong> {{$user_card->recipient_name}}
                </div>
                <div class="col-md-6">
                    <strong>Bank Name : </strong> {{$user_card->bank_name}}
                </div>
                <div class="col-md-6">
                    <strong>Bank Account Number : </strong> {{$user_card->bank_account_number}}
                </div>
                <div class="col-md-6">
                    <strong>Price : </strong> ${{$user_card->default_riv_detail->usd_price}}
                </div>
            </div>
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
