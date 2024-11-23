<style>
    .selectformtd .select2-container .select2-selection--single .select2-selection__rendered {
        padding-top: 2px !important;
    }
</style>
<div class="modal-dialog " style="max-width: 70%">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>Client Shops</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">
            <div class="row align-items-xl-center mb-3">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th class="pr-4">Activate Name</th>
                                <th class="pr-4">Shop Name</th>
                                <th class="pr-4">Speciality of</th>
                                <th class="pr-4">Category</th>
                                <th class="pr-4">Rate</th>
                                <th class="pr-4">Import Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($shops as $shop)
                                <tr id="shop_id_{{ $shop->id }}">
                                    <td><button type="button" id="show-profile"
                                            class="btn btn-outline-primary btn-lg">{{ $shop->main_name ? $shop->main_name : '-' }}</button>
                                    </td>
                                    <td><button type="button" id="show-profile"
                                            class="btn btn-outline-primary btn-lg">{{ $shop->shop_name }}</button></td>
                                    <td><button type="button" id="show-profile"
                                            class="btn btn-outline-primary btn-lg">{{ $shop->speciality_of ?? '-' }}</button>
                                    </td>
                                    <td><button type="button" id="show-profile"
                                                class="btn btn-outline-primary btn-lg">{{ $shop->category ?? '-' }}</button>
                                    </td>
                                    <td class="shops-rate"><button type="button" id="show-profile"
                                            class="btn btn-outline-primary btn-lg">{{ $shop->avg_rating ? $shop->avg_rating : '-' }}</button>
                                    </td>
                                    <td class="shops-date"><button type="button" id="show-profile"
                                            class="btn btn-outline-primary btn-lg">{{ $shop->created_at }}</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
