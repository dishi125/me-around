<!doctype html>
<html>

<head>
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{!! $subjectTitle !!}</title>
    <style>
        img.small-list-icon {
            width: 30px;
            height: 30px;
        }
        input#regular_service {
            width: 25px;
            height: 25px;
        }
    </style>
</head>

<body class="">
<table border="0" style="border:1px solid #000;" cellspacing="0" align="left">
    <tr>
        <th colspan="2" style="padding:15px 50px;background-color: #ccc;font-size: 22px;">{!! $subjectTitle !!}
        </th>
    </tr>
    <tr>
        <th style="padding:15px;">Shop Name</th>
        <td style="padding:15px;">
            <div>{{ $shopData->shop_name }}</div> {{ $shopData->main_name }}
            <div>
                {{ $shopData->mobile }}
                @if($user->connect_instagram == true)
                    <img class="small-list-icon" src="{{ asset('img/connect_instagram.png') }}" style="margin-left: 2px"/>
                @endif
            </div>
        </td>
    </tr>
    <tr>
        <th style="padding:15px;">Instagram Link</th>
        <td style="padding:15px;">
            <div>Today: 0, Month: 0, Total: 0</div> <a style="color:#000;" href="javascript:void(0);">{{ $shopPost->insta_link }}</a>
            @if(isset($shopPost->post_order_date) && $shopPost->post_order_date!=null)
            <div>{{ \Carbon\Carbon::parse($shopPost->post_order_date)->format('Y-m-d H:i:s') }}</div>
            @endif
        </td>
    </tr>
    <tr>
        <th style="padding:15px;">Service</th>
        <td style="padding:15px;">
            <div class="update_service" id="{{ $shopPost->shop_id }}">
                <div class="count_days">{{ $shopData->count_days }}</div>
                <div class="expiry_date">{{ Carbon::now()->addDays($shopData->count_days)->format('Y-m-d') }}</div>
                <?php
                $service_checked = "";
                if ($shopData->is_regular_service) {
                    $service_checked = "checked";
                }
                ?>
                <div class="service"><input id="regular_service" {{ $service_checked }} type="checkbox" name="regular_service" value="1" class="form-check-input " disabled style="margin-left: 0px"><label style="margin-left: 4px; padding-left: 1px; padding-top: 2px">Regular Service</label></div>
            </div>
        </td>
    </tr>
    <tr>
        <th style="padding:15px;">Images</th>
        <td style="padding:15px;">
            @if(empty($shopPost->video_thumbnail) && !empty($shopPost->post_item))
            <img src="{{ $shopPost->post_item }}" class="reported-client-images pointer" width="50" height="50" style="margin:1px">
            @elseif(!empty($shopPost->video_thumbnail))
            <img src="{{ $shopPost->video_thumbnail }}" class="reported-client-images pointer" width="50" height="50" style="margin:1px">
            @endif
        </td>
    </tr>

</table>
</body>

</html>
