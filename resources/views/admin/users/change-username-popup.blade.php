<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header justify-content-center">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-footer">
            <form id="edituserForm" style="width: 100%;" method="POST" action="{{route('admin.user.edit-username',$id)}}" accept-charset="UTF-8">
                @csrf
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group mt-3">
                            <label for="">User name</label>
                            <input type="text" class="form-control" value="{{$user_detail->name}}" name="username" placeholder="User Name" required id="username" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="">Gender</label>
                            <select class="form-select" name="is_show_gender" id="is_show_gender" style="float: right">
                                <option value="1" @if($user->is_show_gender==1) selected @endif>Show</option>
                                <option value="0" @if($user->is_show_gender==0) selected @endif>Hide</option>
                            </select>
                            <select class="form-control" name="gender" id="gender">
                                <option value="Female" @if($user_detail->gender=="Female" || $user_detail->gender=="여성" || $user_detail->gender=="女性") selected @endif>Female</option>
                                <option value="Male" @if($user_detail->gender=="Male" || $user_detail->gender=="남성" || $user_detail->gender=="男性") selected @endif>Male</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="">MBTI</label>
                            <select class="form-select" name="is_show_mbti" id="is_show_mbti" style="float: right">
                                <option value="1" @if($user->is_show_mbti==1) selected @endif>Show</option>
                                <option value="0" @if($user->is_show_mbti==0) selected @endif>Hide</option>
                            </select>
                            <select class="form-control" name="mbti" id="mbti">
                                <option value="I don't know" @if($user_detail->mbti=="I don't know" || $user_detail->mbti=="잘 모르겠어요" || $user_detail->mbti=="よくわかりません。") selected @endif>I don't know</option>
                                <option value="ISTJ" @if($user_detail->mbti=="ISTJ") selected @endif>ISTJ</option>
                                <option value="ESTJ" @if($user_detail->mbti=="ESTJ") selected @endif>ESTJ</option>
                                <option value="ISFJ" @if($user_detail->mbti=="ISFJ") selected @endif>ISFJ</option>
                                <option value="ENFP" @if($user_detail->mbti=="ENFP") selected @endif>ENFP</option>
                                <option value="ESFJ" @if($user_detail->mbti=="ESFJ") selected @endif>ESFJ</option>
                                <option value="INFP" @if($user_detail->mbti=="INFP") selected @endif>INFP</option>
                                <option value="ISFP" @if($user_detail->mbti=="ISFP") selected @endif>ISFP</option>
                                <option value="INTJ" @if($user_detail->mbti=="INTJ") selected @endif>INTJ</option>
                                <option value="ESFP" @if($user_detail->mbti=="ESFP") selected @endif>ESFP</option>
                                <option value="ISTP" @if($user_detail->mbti=="ISTP") selected @endif>ISTP</option>
                                <option value="ESTP" @if($user_detail->mbti=="ESTP") selected @endif>ESTP</option>
                                <option value="INTP" @if($user_detail->mbti=="INTP") selected @endif>INTP</option>
                                <option value="ENTJ" @if($user_detail->mbti=="ENTJ") selected @endif>ENTJ</option>
                                <option value="ENTP" @if($user_detail->mbti=="ENTP") selected @endif>ENTP</option>
                                <option value="ENFJ" @if($user_detail->mbti=="ENFJ") selected @endif>ENFJ</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                    <div class="form-group">
                    <label for="">Business Profile</label>
                    <div class="row">
                        @foreach($shop_profiles as $shop_profile)
                        <div class="col-md-12 mt-1">
                                {{ $shop_profile->main_name }} @if(!empty($shop_profile->shop_name) && !empty($shop_profile->main_name)) ( @endif {{ $shop_profile->shop_name }} @if(!empty($shop_profile->shop_name) && !empty($shop_profile->main_name)) ) @endif
                                <select class="form-select" name="is_show_shop[{{ $shop_profile->id }}]" id="is_show_shop" style="float: right">
                                    <option value="1" @if($shop_profile->is_show==1) selected @endif>Show</option>
                                    <option value="0" @if($shop_profile->is_show==0) selected @endif>Hide</option>
                                </select>
                        </div>
                        @endforeach
                    </div>
                    </div>
                    </div>
                </div>

                <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Close</button>
                <button type="submit" id="form_submit" class="btn btn-primary">Confirm</button>
            </form>
        </div>
    </div>
</div>

<script>

    $('#passForm').validate({
        rules: {
            'password': {
                required: true
            },
        },
        highlight: function (input) {
            $(input).parents('.form-line').addClass('error');
        },
        unhighlight: function (input) {
            $(input).parents('.form-line').removeClass('error');
        },
        errorPlacement: function (error, element) {
            $(element).parents('.form-group').append(error);
        },
    });
</script>
