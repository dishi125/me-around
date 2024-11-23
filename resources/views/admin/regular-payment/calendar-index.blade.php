@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.css" />
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
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <a class="btn btn-primary mb-3" id="thisMonthPays">This month payment ({{ $this_month_pays }})</a>
                    <a class="btn btn-primary mb-3" id="todayPays">Today ({{ $today_pays }})</a>
                    <div id='calendar_regular_payment'></div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.js"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            var count_next_pays = @json($count_next_pays);
            var events_arr1 = count_next_pays.map(function (event) {
                return {
                    title: event.count,
                    start: event.next_payment_date,
                };
            });

            var next_pays = @json($next_pays);
            var events_arr2 = next_pays.map(function (event) {
                var title = event.payer_name;
                if(event.instagram_account!=null){
                    title = event.instagram_account + " " + event.payer_name;
                }

                return {
                    title: title,
                    start: event.next_payment_date,
                };
            });

            var events_arr = [events_arr1,events_arr2];
            events_arr = [].concat.apply([], events_arr);

            var calendar = $('#calendar_regular_payment').fullCalendar({
                displayEventTime: false,
                editable: true,
                eventLimit: true, // allow "more" link when too many events
                eventLimitText: "More",
                eventRender: function (event, element, view) {
                    if (event.allDay === 'true') {
                        event.allDay = true;
                    } else {
                        event.allDay = false;
                    }
                },
                selectable: true,
                selectHelper: true,
                plugins: ['dayGrid'],
                events: events_arr,
                eventClick: function (calEvent, jsEvent, view) {
                    // The calEvent object contains information about the clicked event
                    var clickedDate = calEvent.start.format('YYYY-MM-DD');
                    var url = "{{ url('admin/regular-payment') }}" + "/" + clickedDate;
                    window.open(url, '_blank');
                },
                viewRender: function (view, element) {
                    var month = view.title.split(" ")[0];
                    var year = view.title.split(" ")[1];
                    var monthNumber = new Date(Date.parse(month + " 1, 2022")).getMonth() + 1; // Converting month name to number
                    var paddedMonthNumber = ('0' + monthNumber).slice(-2);
                    $.ajax({
                        method: 'POST',
                        cache: false,
                        data: { month: paddedMonthNumber, year: year },
                        url: "{{ route('admin.regular-payment.month-payments') }}",
                        success: function(response) {
                            $(".cover-spin").hide();
                            $("#thisMonthPays").html(`This month payment (${response.count_month_pays})`);
                        },
                        beforeSend: function(){ $(".cover-spin").show(); },
                        error: function(response) {
                            $(".cover-spin").hide();
                        }
                    });
                }
            });
        });
    </script>
@endsection
