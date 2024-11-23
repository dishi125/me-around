@extends('challenge-layouts.app')
@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
{{--    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.css" />--}}
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

        #calendar_regular_payment .fc-title {
            color: white;
            float: left;
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
        <a class="btn btn-primary mr-2" href="{{ route('challenge.calendar.period-challenge.index') }}">{{ __('general.period_challenge') }} ({{ $count_today_period_challenges }})</a>
        <a class="btn btn-primary mr-2" href="{{ route('challenge.calendar.challenge.index') }}">{{ __('general.challenge') }} ({{ $count_today_challenges }})</a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div id='calendar_page'></div>
                </div>
            </div>
        </div>
    </div>
    <div class="cover-spin"></div>
@endsection

<div class="modal fade" id="seeChallengeModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>

@section('scripts')
    <script>
        var csrfToken = "{{ csrf_token() }}";
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <!-- Include FullCalendar JavaScript -->
    <script src='https://cdn.jsdelivr.net/npm/rrule@2.6.4/dist/es5/rrule.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/rrule@6.1.10/index.global.min.js'></script>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            var day_wise_challenges = @json($day_wise_challenges);
            var events_arr1 = day_wise_challenges.map(function (event) {
                return {
                    title: event.title + " (" + event.verify_time + ")",
                    rrule: {
                        freq: 'weekly',
                        // interval: 7,
                        byweekday: event.days,
                        dtstart: event.start_date, // will also accept '20120201T103000'
                        until: event.end_date, // will also accept '20120201'
                    },
                    extendedProps: {
                        challengeId: event.id  // Add your hidden attribute and its value here
                    }
                };
            })

            var events_arr = [events_arr1];
            events_arr = [].concat.apply([], events_arr);

            var calendarEl = document.getElementById('calendar_page');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                editable: true,
                dayMaxEventRows: true, // for all non-TimeGrid views
                views: {
                    timeGrid: {
                        dayMaxEventRows: 6 // adjust to 6 only for timeGridWeek/timeGridDay
                    }
                },
                dayMaxEvents: true,
                events: events_arr,
                eventClick: function (info) {
                    // Fetch hidden attribute's value on click
                    var challengeId = info.event.extendedProps.challengeId;
                    $.get(baseUrl + '/challenge/challenge-page/view/' + challengeId, function (data, status) {
                        $('#seeChallengeModal').html('');
                        $('#seeChallengeModal').html(data);
                        $('#seeChallengeModal').modal('show');
                    });
                }
            });

            calendar.render();
        });
    </script>
@endsection

