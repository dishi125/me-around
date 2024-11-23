<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Credit Logs</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">        
            <div class="row align-items-xl-center mb-3">
                <div class="card col-md-12">
                    <div class="card-body">
                        <ul class="nav nav-pills mb-4" id="myTab3" role="tablist">
                            <li class="nav-item mr-3">
                                <a class="nav-link active btn btn-success" id="credit-data" data-toggle="tab" href="#creditData" role="tab" aria-controls="shop" aria-selected="true">Reload</a>
                            </li>
                            <li class="nav-item mr-3">
                                <a class="nav-link btn btn-danger" id="debit-data" data-toggle="tab" href="#debitData" role="tab" aria-controls="shop" aria-selected="false">Deducted</a>
                            </li>
                        </ul>

                        <div class="tab-content" id="myTabContent2">
                            <div class="tab-pane fade show active" id="creditData" role="tabpanel" aria-labelledby="credit-data">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="credit-log-table">
                                        <thead>
                                            <tr>
                                                <th class="mr-3">Activity Log</th>
                                                <th class="mr-3">Credits</th>
                                                <th class="mr-3">Action</th>
                                                <th class="mr-3">Date</th>
                                            </tr>                        
                                        </thead>
                                        <tbody>
                                        @foreach($dataCredit as $data)
                                            <tr>
                                                <td>{{$data->type}}</td>
                                                <td>{{ number_format($data->amount,0) }}</td>                            
                                                <td>{{$data->transaction}}</td>
                                                <td>{{$data->created_at}}</td>
                                            </tr>                        
                                        @endforeach                     
                                        </tbody>
                                    </table>    
                                </div>
                            </div>
                            <div class="tab-pane fade" id="debitData" role="tabpanel" aria-labelledby="debit-data">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="debit-log-table">
                                        <thead>
                                            <tr>
                                                <th class="mr-3">Activity Log</th>
                                                <th class="mr-3">Credits</th>
                                                <th class="mr-3">Action</th>
                                                <th class="mr-3">Date</th>
                                            </tr>                        
                                        </thead>
                                        <tbody>
                                        @foreach($dataDebit as $data)
                                            <tr>
                                                <td>{{$data->type}}</td>
                                                <td>{{ number_format($data->amount,0) }}</td>                            
                                                <td>{{$data->transaction}}</td>
                                                <td>{{$data->created_at}}</td>
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
    </div>
</div>

<script>
$("#credit-log-table").DataTable({order: [[ 3, "desc" ]]});
$("#debit-log-table").DataTable({order: [[ 3, "desc" ]]});
</script>