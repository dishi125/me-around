<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>{{$title ?? ''}}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">
            <div class="row align-items-xl-center mb-3">
                <div class="w-100" id="myTabContent2">
                    <div class="fade show active" id="referral-data" role="tabpanel" aria-labelledby="credit-data">
                        <div class="table-responsive">
                            <table class="table table-striped" id="click-data-table">
                                <thead>
                                    <tr>
                                        <th class="mr-3">Name</th>
                                        <th class="mr-3">Email</th>
                                        <th class="mr-3">User name</th>
                                        <th class="mr-3">Date</th>
                                        <th class="mr-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($detail as $data)
                                    <tr>
                                        <td>{{$data->name}}</td>
                                        <td>{{$data->email}}</td>
                                        <td>{{$data->user_name}}</td>
                                        <td>{{ \App\Http\Controllers\Controller::formatDateTimeCountryWise($data->created_at, $adminTimezone)}}</td>
                                        <td>
                                            @if ($data->profile_id)
                                                @if ($type == 'hospitals')
                                                    <a class="btn btn-primary" href="{{route('admin.business-client.hospital.show',['id' => $data->profile_id])}}">See Profile</a>
                                                @else
                                                    <a class="btn btn-primary" href="{{route('admin.business-client.shop.show',['id' => $data->profile_id])}}">See Profile</a>
                                                @endif
                                            @endif
                                        </td>
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
    $("#click-data-table").DataTable({order: [[ 3, "desc" ]]});
</script>
