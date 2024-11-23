@extends('layouts.app')

@section('header-content')
<div class="col-md-11">
    <h1>@if (@$title) {{ @$title }} @endif</h1>
</div>
<div class="col-md-1">
    <a href="{{ route('admin.association.index') }}" class="btn note-btn btn-primary mr-3">Back</a>                   
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
@endsection

@section('content')
<div class="section-body">
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-12 mt-sm-3 pt-3">
            <div class="card">
                <div class="card-header">
                    <div class="col-md-10">
                        <h3>Association Details</h3>
                    </div>
                    
                </div>
                <div class="card-body">
                    <div class="row">
                        {{ Form::hidden('association_id', $association->id , array('id' => 'association_id')) }}
                        <div class="form-group col-md-6 col-12">
                            <label>Name</label>
                            <p>{{$association->association_name}}</p>
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label>President</label>
                            <p>{{$president}}</p>
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label>Managers</label>
                            <p>{{$managers}}</p>
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label>Members</label>
                            <p>{{$members}}</p>
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label>Supporter</label>
                            <p>{{$supporter}}</p>
                        </div>
                    </div>
                </div>
            </div>
            
           
            <div class="card">
                <div class="card-header">
                    <div class="col-md-10">
                        <h3>Association Categories</h3>
                    </div>
                    <div class="col-md-2">
                        <a href="javascript:void(0)" onclick='associateCategoryForm({{$association->id}})' class="btn note-btn btn-primary mr-3">Create Category</a>
                    </div> 
                </div>
                <div class="card-body">
                    <div class="tab-content" id="myTabContent2">
                        <div class="tab-pane fade show active" id="categoryData" role="tabpanel" aria-labelledby="all-data">
                            <div class="table-responsive">
                                <table class="table table-striped" id="category-data">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Order</th>
                                            <th>Action</th>
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
</div>
</div>
</div>
@endsection

<!-- Modal -->
<div class="modal fade" id="myCategoryModel" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>

@section('scripts')
<script type="text/javascript">
    var associationCategoryTableData = "{{ route('admin.association.category.data') }}";
    var csrfToken = "{{csrf_token()}}";    
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/association/association-category.js') !!}"></script>
@endsection