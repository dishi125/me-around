@extends('challenge-layouts.app')
@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.css" />
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
        .eventWithComment {
            white-space: pre-line;
        }
        .participated{
            background-color: deeppink;
        }
    </style>
@endsection

@section('header-content')
    <h1>
        @if (@$title)
            {{ @$title }}
        @endif
    </h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div id='user_calendar_page'></div>
                </div>
            </div>
        </div>
    </div>
    <div class="cover-spin"></div>
@endsection

@section('scripts')
    <script>
        var csrfToken = "{{ csrf_token() }}";
    </script>
    <script src="{!! asset('plugins/datatables/datatables.min.js') !!}"></script>
    <!-- Include FullCalendar JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/rrule@2.6.4/dist/es5/rrule.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/rrule@6.1.10/index.global.min.js"></script>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('user_calendar_page');

            var period_challenges = @json($period_challenges);
            var events_arr1 = period_challenges.map(function (event) {
                // var html_title = event.title + ` <span style="color: red;">(${event.participated_at})</span>`;
                return {
                    title: event.title,
                    rrule: {
                        freq: 'weekly',
                        byweekday: event.days,
                        dtstart: event.start_date, // will also accept '20120201T103000'
                        until: event.end_date // will also accept '20120201'
                    },
                    className: ['eventWithComment'],
                };
            })

            var challenges = @json($challenges);
            var events_arr2 = challenges.map(function (event) {
                // var html_title = event.title + ` <span style="color: red;">(${event.participated_at})</span>`;
                return {
                    title: event.title,
                    start: event.date,
                    className: ['eventWithComment'],
                };
            })

            var participated = @json($participated);
            var events_arr3 = participated.map(function (event) {
                // var html_title = event.title + ` <span style="color: red;">(${event.participated_at})</span>`;
                return {
                    title: "Participated",
                    start: event.participated_at,
                    className: ['eventWithComment','participated'],
                };
            })

            var events_arr = [events_arr3,events_arr1,events_arr2];
            events_arr = [].concat.apply([], events_arr);

            var calendar = new FullCalendar.Calendar(calendarEl, {
                editable: true,
                events: events_arr,
                /*events: [
                    {
                        title: 'rrule event',
                        rrule: {
                            freq: 'weekly',
                            // interval: 7,
                            byweekday: [ 'mo', 'fr' ],
                            dtstart: '2012-02-01', // will also accept '20120201T103000'
                            // until: '2012-06-01' // will also accept '20120201'
                        }
                    }
                ]*/
            });

            calendar.render();
        });
    </script>
@endsection

