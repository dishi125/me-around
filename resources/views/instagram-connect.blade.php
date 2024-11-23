
<meta property="og:title" content="Connecting Instagram Link" />
<meta property="og:image" content="{{ asset('img/deeplink_image.png') }}" />

<meta name="twitter:title" content="Connecting Instagram Link" />
<meta name="twitter:image" content="{{ asset('img/deeplink_image.png') }}" />
<title>Connecting Instagram Link</title>
<script>
    var baseUrl = "{{ url('/') }}";
</script>
<script src="{!! asset('plugins/jquery.min.js') !!}"></script>
<script src="{!! asset('plugins/bootstrap/js/bootstrap.min.js') !!}"></script>
<script src="{!! asset('js/custom.js') !!}"></script>
<link rel="stylesheet" href="{!! asset('plugins/bootstrap/css/bootstrap.min.css') !!}">
<link rel="stylesheet" href="{!! asset('css/style.css') !!}">

<!--<div class="text-center mt-5 pt-5">
    <a class="btn btn-primary btn-sm connect ml-3 mr-1 p-1 pl-2 pr-2 rounded" href="javascript:void(0);"
        onclick="connectInstagram('https://www.instagram.com/oauth/authorize?client_id={{ config('app.client_id') }}&redirect_uri={{ route('social-redirect') }}&scope=user_profile,user_media&response_type=code','{{ $id }}', '{{ $linkid }}');">
        Connect Instagram
    </a>
</div>-->

<script type="text/javascript">
$(document).ready(function() {
    connectInstagram('https://www.instagram.com/oauth/authorize?client_id={{ config('app.client_id') }}&redirect_uri={{ route('social-redirect') }}&scope=user_profile,user_media&response_type=code','{{ $id }}', '{{ $linkid }}');
});
</script>
