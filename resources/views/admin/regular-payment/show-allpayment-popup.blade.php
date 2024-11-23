<div class="modal-dialog" style="max-width: 70%;">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            {{--            <h5>Messages</h5>--}}
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">
            <div class="row align-items-xl-center mb-3">
                <div class="w-100 tab-content" id="myTabContent2">
                    <div class="tab-pane fade show active" id="referral-data" role="tabpanel" aria-labelledby="referral-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="all-payment-log-data-table">
                                <thead>
                                <tr>
                                    <th class="mr-3">Product</th>
                                    <th class="mr-3">Instagram Name</th>
                                    <th class="mr-3">Payer Name</th>
                                    <th class="mr-3">Payer Phone</th>
                                    <th class="mr-3">Payer E-mail</th>
                                    <th class="mr-3">Amount</th>
                                    <th class="mr-3">Date</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($payment_logs as $payment_log)
                                    <tr>
                                        <td>{{ $payment_log->product_name }}</td>
                                        <td>{{ $payment_log->billpayment->instagram_account }}</td>
                                        <td>{{ $payment_log->billpayment->payer_name }}</td>
                                        <td>{{ $payment_log->billpayment->payer_phone }}</td>
                                        <td>{{ $payment_log->billpayment->payer_email }}</td>
                                        <td>{{ $payment_log->amount }}</td>
                                        <?php
                                        $created_at = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s',\Carbon\Carbon::parse($payment_log->created_at), "UTC")->setTimezone($adminTimezone)->toDateTimeString();
                                        ?>
                                        <td>{{ $created_at }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    $("#all-payment-log-data-table").DataTable({
        "order": [[ 6, "desc" ]]
    });
</script>
