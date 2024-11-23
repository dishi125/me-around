@extends($header.'layouts.app')

@section('header-content')
<div class="col-md-11">
    <h1>@if (@$title) {{ @$title }} @endif</h1>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
@endsection

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12 col-md-12 col-lg-12">
            <div class="card">
                <div class="card-header">
                    <div class="col-md-10">
                        <h3>{{$association->association_name}}</h3>
                    </div>
                </div>
                {{ Form::hidden('association_id', $association->id , array('id' => 'association_id')) }}
                <div class="card-body">
                    <ul class="nav nav-pills mb-4" id="myTab3" role="tablist">
                        <li class="nav-item mr-3">
                            <a class="nav-link active btn btn-primary filterButton" id="manager" data-filter="{{\App\Models\AssociationUsers::MANAGER}}" data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="true">Manager</a>
                        </li>
                        <li class="nav-item mr-3">
                            <a class="nav-link btn btn-primary filterButton" id="member" data-filter="{{\App\Models\AssociationUsers::MEMBER}}" data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="true">Member</a>
                        </li>
                        <li class="nav-item mr-3">
                            <a class="nav-link btn btn-primary filterButton" id="kick" data-filter="{{\App\Models\AssociationUsers::MEMBER}}-kick" data-toggle="tab" href="#" role="tab" aria-controls="shop" aria-selected="true">Kick</a>
                        </li>
                    </ul>
                    <div class="tab-content" id="myTabContent2">
                        <div class="tab-pane fade show active" id="memberData" role="tabpanel" aria-labelledby="all-data">
                            <div class="table-responsive">
                                <table class="table table-striped" id="member-data">
                                    <thead>
                                        <tr>
                                            <th>User Name</th>
                                            <th>Activate name/Shop name</th>
                                            <th>Address</th>
                                            <th>Phone Number</th>
                                            <th>Date</th>
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
</div>
@endsection

@section('scripts')
<script type="text/javascript">
    var associationTableData = "{{ route('user.association.details') }}";
    var csrfToken = "{{csrf_token()}}";
    var association_name = "<?php echo $association->association_name; ?>";
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script type="text/javascript" src="{!! asset('plugins/datatables/jszip.min.js') !!}"></script>
<script src="{!! asset('js/pages/user-js/association/association.js') !!}"></script>
@endsection