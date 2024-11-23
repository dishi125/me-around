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
    <div class="section-header-button">
        <button class="btn btn-primary mr-2" id="add_new_bill">New billing</button>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="tab-content" id="myTabContent2">
                        <div class="tab-pane fade show active" id="allData" role="tabpanel" aria-labelledby="comment-data">
                            <div class="table-responsive">
                                <table class="table table-striped" id="Billing-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Amount</th>
                                            <th>Payment method</th>
                                            <th></th>
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

<div class="modal fade" id="newBillModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="BillingForm" method="post">
                {{ csrf_field() }}
                <div class="modal-header justify-content-center">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body justify-content-center">
                    <div class="align-items-xl-center mb-3">
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>Payment method</label>
                            </div>
                            <div class="col-md-8">
                                <select id="card_ver" name="card_ver" class="form-control" required>
                                    <option selected disabled value="">Select...</option>
                                    <option value="01">Regular payment</option>
                                    <option value="02">App card payment</option>
                                </select>
<!--                                <select id="pay_work" name="pay_work" class="form-control mt-2" style="display: none">
                                    <option selected disabled value="">Select...</option>
                                    <option value="PAY">Card registration and payment</option>
                                    <option value="AUTH">Card registration</option>
                                </select>-->
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>product name</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="pay_goods" id="pay_goods" class="form-control" required/>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>Amount of payment</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="pay_total" id="pay_total" class="form-control" required/>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label>Starting date</label>
                            </div>
                            <div class="col-md-8">
                                <input type="date" name="start_date" id="start_date" class="form-control" required/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{!! __(Lang::get('general.close')) !!}</button>
                    <button type="submit" class="btn btn-primary" id="save_btn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
    <script>
        var csrfToken = "{{ csrf_token() }}";
        var allTable = "{!! route('admin.paypal.table') !!}";
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script>
        $(function() {
            var all = $("#Billing-table").DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                deferRender: true,
                // "order": [[ 4, "desc" ]],
                ajax: {
                    url: allTable,
                    dataType: "json",
                    type: "POST",
                    data: { _token: csrfToken }
                },
                columns: [
                    { data: "product_name", orderable: false },
                    { data: "amount", orderable: false },
                    { data: "payment_method", orderable: false },
                    { data: "link", orderable: false },
                ]
            });
        });

        $(document).on('click', '#add_new_bill', function (){
            $("#newBillModal").modal('show');
        })

        /*$(document).on('change', '#card_ver', function() {
            if ($(this).val() == '01') {
                $('#pay_work').show();
            } else {
                $('#pay_work').hide();
            }
        });*/

        $(document).on('submit', '#BillingForm', function (e){
            e.preventDefault();
            var formData = new FormData($("#BillingForm")[0]);

            $.ajax({
                url: "{{ url('admin/paypal/add-bill') }}",
                processData: false,
                contentType: false,
                type: 'POST',
                data: formData,
                success:function(response){
                    if(response.success == true){
                        $("#newBillModal").modal('hide');
                        showToastMessage('Billing added successfully.',true);
                        $('#Billing-table').DataTable().ajax.reload();
                    }
                    else {
                        showToastMessage('Something went wrong!!',false);
                    }
                },
                error: function(response) {
                    showToastMessage('Something went wrong!!',false);
                },
            });
        })

        $('#newBillModal').on('hidden.bs.modal', function () {
            $("#BillingForm")[0].reset();
            // $('#pay_work').hide();
        })
    </script>
@endsection
