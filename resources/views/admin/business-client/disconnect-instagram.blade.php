<form action="{{route('admin.disconnect.instagram',[$id])}}" method="post">
    @csrf 

    <input type="hidden" value="{{$insta_id}}" name="insta_id" />
    <button class="btn btn-primary btn-sm connect ml-3 mr-1 p-1 pl-2 pr-2 rounded" href="javascript:void(0);" onclick="">
        Disconnect Instagram
    </button>
</form>