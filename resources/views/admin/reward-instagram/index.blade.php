@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
@endsection

@section('header-content')
<h1>@if (@$title) {{ @$title }} @endif</h1>
@endsection

@section('content')
<div class="row">
    
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                
                <div class="confirm-btn">
                    <input type="submit" class="btn btn-primary" id="reward_degree" name="reward_degree" value="Reward Degree">
                    <input type="submit" class="btn btn-primary ml-2" id="reject_notice_edit" name="reject_notice_edit" value="Reject Notice Edit">
                    <input type="submit" class="btn btn-primary ml-2" id="penalty_mention" name="penalty_mention" value="Penalty Mention">
                </div>
                <div class="table-responsive">
                    <table class="table table-striped" id="reward-instagram-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 0 !Important;padding-left: 5px !important;">
                                    <div class="custom-checkbox custom-control">
                                        <input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-all">
                                        <label for="checkbox-all" class="custom-control-label">&nbsp;</label>
                                    </div>
                                </th>
                                <th>Shop Name</th>
                                <th>Shop Profile</th>
                                <th>SNS</th>
                                <th>Penalty Times</th>
                                <th>Reject Times</th>
                                <th>Reward Times</th>                                        
                                <th>Request Times</th>                                        
                                <th>Phone Number</th>                                        
                                <th>Date</th>                                
                                <th>Actions</th> 
                            </tr>
                        </thead>
                    </table>
                </div>
                    
            </div>
        </div>
    </div>
</div>
<div class="cover-spin"></div>
<div class="modal fade" id="shopImageModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
</div>
@include('admin.reward-instagram.all-popup')
<!-- Modal -->
@endsection

@section('scripts')
<script>
var rewardInstagramIndex = "{!! route('admin.reward-instagram.all.table') !!}";
var csrfToken = "{{csrf_token()}}";
var profileModal = $("#shopImageModal");
var rewardMultiple = "{!! route('admin.reward-instagram.reward.multiple') !!}";
var rejectMentionRequest = "{!! route('admin.reward-instagram.reject.mention') !!}";
</script>
<script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
<script src="{!! asset('js/pages/reward-instagram/index.js') !!}"></script>
@endsection
