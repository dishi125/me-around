@extends('layouts.app')

@section('header-content')
    <h1>
        @if (@$title)
            {{ @$title }}
        @endif
    </h1>
@endsection

@section('styles')
    <style>
        .section-body img {
            width: 100%;
        }
    </style>
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
    <link rel="stylesheet" href="{!! asset('css/chocolat.css') !!}">
@endsection

@section('content')
    <div class="section-body">
        <div class="row mt-sm-4">
            <div class="col-12 col-md-12 col-lg-12 mt-sm-3 pt-3">
                <div class="card">
                    <div class="card-header">
                        <h4>Details</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-4 col-12">
                                <strong>User Name : </strong>
                                <span>{{ $reportData->user_name }}</span>
                            </div>
                            <div class="form-group col-md-4 col-12">
                                <strong>Shop Name : </strong>
                                <span>{{ $reportData->shopname }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-header">
                        <h4>Attachments</h4>
                    </div>
                    <div class="card-body">
                        @if (count($reportData->attachments))
                            <div class="row">
                                @foreach ($reportData->attachments as $attachments)
                                    <div class="form-group col-md-4">
                                        @if ($attachments->type == 'image')
                                            <img src="{{ $attachments->attachment_item_url }}" />
                                        @else
                                            <video width="100%" height="250" controls
                                                poster="{{ $attachments->video_thumbnail_url }}">
                                                <source src="{{ $attachments->attachment_item_url }}" type="video/mp4">
                                                Your browser does not support the video tag.
                                            </video>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
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
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <script src="{!! asset('js/chocolat.js') !!}"></script>
@endsection
