<style>
    .selectformtd .select2-container .select2-selection--single .select2-selection__rendered {
        padding-top: 2px !important;
    }
</style>
<div class="modal-dialog " style="max-width: 70%">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <h5>This number is already used in here</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body justify-content-center">
            <div class="row align-items-xl-center mb-3">
                <div class="table-responsive">
                    <table cellpadding="10">
                        <thead>
                        <tr>
                            <th class="mr-3">&nbsp;</th>
                            <th class="mr-3">Activate Name</th>
                            <th class="mr-3">Shop Name</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($exists_shops as $shop)
                            <tr id="shop_id_{{ $shop->id }}">
                                <td><a role='button'
                                       href="{{ route('admin.business-client.shop.show', $shop->id) }}"
                                       title='' data-original-title='View'
                                       class='btn btn-primary btn-lg mr-3' data-toggle='tooltip' target="_blank">See Profile</a>
                                </td>
                                <td><button type="button" id="show-profile"
                                            class="btn btn-outline-primary btn-lg">{{ $shop->main_name ? $shop->main_name : '-' }}</button>
                                </td>
                                <td><button type="button" id="show-profile"
                                            class="btn btn-outline-primary btn-lg">{{ $shop->shop_name }}</button></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
