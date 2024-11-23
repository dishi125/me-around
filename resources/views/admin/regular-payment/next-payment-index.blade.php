@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
    <style>
        .table-responsive button#show-profile {
            width: auto;
            margin: 5px 5px 5px 0;
            white-space: normal;
        }

        .table-responsive .shops-date button#show-profile {
            width: 180px;
        }

        .table-responsive .shops-rate button#show-profile {
            width: 80px;
        }

        .table-responsive td span {
            margin: 5px;
        }
    </style>
@endsection

@section('header-content')
    <h1>
        @if (@$title)
            {{ @$title }}
        @endif
    </h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="tab-content" id="myTabContent2">
                        <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="comment-data">
                            <div class="table-responsive">
                                <table class="table table-striped" id="regular-payment-table">
                                    <thead>
                                    <tr>
                                        <th></th>
                                        <th>Product</th>
                                        <th>Amount</th>
                                        <th>Instagram Name</th>
                                        <th>Payer Name</th>
                                        <th>Payer phone</th>
                                        <th>Payer E-mail</th>
                                        <th>Starting Date</th>
                                        <th>Card No.</th>
                                        <th>Card Name</th>
                                        <th></th>
                                        <th></th>
                                        <th>Recent Payment</th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="cover-spin"></div>
@endsection

<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>

<div class="modal fade" id="paymentLogModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

@section('scripts')
    <script>
        var csrfToken = "{{ csrf_token() }}";
        var allTable = "{!! route('admin.regular-payment.next-payment.table') !!}";
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script type="text/javascript">
        $(function() {
            var all = $("#regular-payment-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                // "order": [[ 4, "desc" ]],
                ajax: {
                    url: allTable,
                    dataType: "json",
                    type: "POST",
                    data: { _token: csrfToken, next_payment_date: "{{ $next_payment }}" }
                },
                columns: [
                    { data: "payment_log", orderable: false },
                    { data: "pay_goods", orderable: false },
                    { data: "pay_total", orderable: false },
                    { data: "instagram_account", orderable: false },
                    { data: "payer_name", orderable: false },
                    { data: "payer_phone", orderable: false },
                    { data: "payer_email", orderable: false },
                    { data: "start_date", orderable: false },
                    { data: "card_number", orderable: false },
                    { data: "card_name", orderable: false },
                    { data: "action", orderable: false },
                    { data: "mark_today", orderable: false },
                    { data: "recent_payment", orderable: false },
                ]
            });
        });

        function rePaymentRegular(payment_id){
            $.ajax({
                url: "{{ route('admin.regular-payment.repayment') }}",
                type: 'POST',
                data: { payment_id: payment_id },
                beforeSend: function (){
                    $(".cover-spin").show();
                },
                success:function(response){
                    $(".cover-spin").hide();
                    if(response.success == true){
                        showToastMessage(response.message,true);
                        $('#regular-payment-table').DataTable().ajax.reload();
                    }
                    else {
                        showToastMessage('Something went wrong!!',false);
                    }
                },
                error: function(response) {
                    $(".cover-spin").hide();
                    showToastMessage('Something went wrong!!',false);
                },
            });
        }

        function editData(url){
            $.get(url, function (data, status) {
                $("#editModal").html('');
                $("#editModal").html(data);
                $("#editModal").modal('show');
            });
        }

        $(document).on("submit","#editForm",function(e){
            e.preventDefault();
            var ajaxurl = $(this).attr('action');

            $.ajax({
                method: 'POST',
                cache: false,
                data: $(this).serialize(),
                url: ajaxurl,
                success: function(results) {
                    $(".cover-spin").hide();
                    if(results.success == true) {
                        $('#regular-payment-table').DataTable().ajax.reload();
                        iziToast.success({
                            title: '',
                            message: results.message,
                            position: 'topRight',
                            progressBar: false,
                            timeout: 1000,
                        });
                    }else {
                        iziToast.error({
                            title: '',
                            message: results.message,
                            position: 'topRight',
                            progressBar: false,
                            timeout: 2000,
                        });
                    }
                    $("#editModal").modal('hide');
                },
                beforeSend: function(){ $(".cover-spin").show(); },
                error: function(response) {
                    $(".cover-spin").hide();
                    if( response.responseJSON.success === false ) {
                        var errors = response.responseJSON.errors;

                        $.each(errors, function (key, val) {
                            console.log(val);
                            var errorHtml = '<label class="error">'+val+'</label>';
                            $('#'+key).parent().append(errorHtml);
                        });
                    }
                }
            });
        });

        function editNextPayment(url){
            $.get(url, function (data, status) {
                $("#editModal").html('');
                $("#editModal").html(data);
                $("#editModal").modal('show');
            });
        }

        $(document).on("submit","#editNextPayForm",function(e){
            e.preventDefault();
            var ajaxurl = $(this).attr('action');

            $.ajax({
                method: 'POST',
                cache: false,
                data: $(this).serialize(),
                url: ajaxurl,
                success: function(results) {
                    $(".cover-spin").hide();
                    if(results.success == true) {
                        $('#regular-payment-table').DataTable().ajax.reload();
                        iziToast.success({
                            title: '',
                            message: results.message,
                            position: 'topRight',
                            progressBar: false,
                            timeout: 1000,
                        });
                    }else {
                        iziToast.error({
                            title: '',
                            message: results.message,
                            position: 'topRight',
                            progressBar: false,
                            timeout: 2000,
                        });
                    }
                    $("#editModal").modal('hide');
                },
                beforeSend: function(){ $(".cover-spin").show(); },
                error: function(response) {
                    $(".cover-spin").hide();
                    if( response.responseJSON.success === false ) {
                        var errors = response.responseJSON.errors;

                        $.each(errors, function (key, val) {
                            console.log(val);
                            var errorHtml = '<label class="error">'+val+'</label>';
                            $('#'+key).parent().append(errorHtml);
                        });
                    }
                }
            });
        });

        function paymentLog(payment_id){
            $.get(baseUrl + '/admin/regular-payment/payment-log/' + payment_id, function (data, status) {
                $('#paymentLogModal').html('');
                $('#paymentLogModal').html(data);
                $('#paymentLogModal').modal('show');
            });
        }
    </script>
@endsection
