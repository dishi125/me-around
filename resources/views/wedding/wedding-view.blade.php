<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="apple-touch-icon" sizes="96x96" href="{!! asset('favicon/favicon-96x96.png') !!}">
    <link rel="icon" type="image/png" sizes="96x96" href="{!! asset('favicon/favicon-96x96.png') !!}">
    <link rel="shortcut icon" href="{!! asset('favicon/favicon-32x32.png') !!}">
    <link rel="manifest" href="{!! asset('favicon/manifest.json') !!}">
    <meta name="msapplication-TileColor" content="#43425D">
    <meta name="msapplication-TileImage" content="{!! asset('favicon/ms-icon-144x144.png') !!}">
    <meta name="theme-color" content="#43425D">

    <meta property="og:title" content="{{$weddingData->his_name}} ♥ {{$weddingData->her_name}} 결혼합니다" />
    <meta property="og:site_name" content="{{$weddingData->his_name}} ♥ {{$weddingData->her_name}} 결혼합니다" />
    <meta property="og:description" content="{{date('F dS H:i A',strtotime($weddingData->wedding_date))}}" />
    <meta property="og:image" content="{{$weddingData->wedding_photo ?? ''}}" />
    <meta property="og:type" content="website" />
    <title>{{$weddingData->his_name}} ♥ {{$weddingData->her_name}} 결혼합니다</title>

    <!-- General CSS Files -->

    <link rel="stylesheet" href="{!! asset('plugins/fontawesome/css/all.min.css') !!}">
    <link rel="stylesheet" href="{!! asset('plugins/bootstrap/css/bootstrap.min.css') !!}">
    <link rel="stylesheet" href="{!! asset('plugins/datatables/datatables.min.css') !!}">
    <link rel="stylesheet" href="{!! asset('plugins/slick/slick.css') !!}">
    <link rel="stylesheet" href="{!! asset('plugins/aos/aos.css') !!}">
    <link rel="stylesheet" href="{!! asset('css/wedding.css') !!}">
    @yield('styles')

</head>

<body>
    <div id="app" class="">
        @if($weddingData)
        <?php 
            $design = $weddingData->design ?? 'design_1';
            $address_latitude = $weddingData->address_latitude ?? "0";
            $address_longitude = $weddingData->address_longitude ?? "0";
        ?>
        <div class="wedding-view theme {{$design}}">
            @if(isset($weddingData->audio_file))
            <audio autoplay loop id="playAudio">
                <source src="{{getSettingURL($weddingData->audio_file)}}">
            </audio>
            <a id="playButton"> <i class="fas fa-volume-mute" style="font-size: 30px"></i> </a>
            @endif
            <div class="wedding-intro">
                <div class="title">
                    <div class="groom">{{$weddingData->his_name}}</div>
                    @if($design == 'design_1')
                    <div class="dash"></div>
                    @elseif($design == 'design_3')
                    <div class="date">
                        <div class="month">{{date('m',strtotime($weddingData->wedding_date))}}</div>
                        <div class="divider">
                            <div class="divider2"></div>
                        </div>
                        <div class="day">{{date('d',strtotime($weddingData->wedding_date))}}</div>
                    </div>
                    @else
                    <div class="">&</div>
                    @endif
                    <div class="bride">{{$weddingData->her_name}}</div>
                    @if($design == 'design_2')
                    <div class="date-place d-block">
                        <div>{{date('l, F d, Y',strtotime($weddingData->wedding_date))}}
                            at {{date('H:i A',strtotime($weddingData->wedding_date))}}
                        </div>
                    </div>
                    @endif
                </div>
                <div class="photo-wrap">
                    @if(isset($weddingData->wedding_photo))
                    <div class="intro-blend-wrap photo">
                        <img src="{{$weddingData->wedding_photo}}" class="intro-blend-image">
                        <video id="intro-player" autoplay="autoplay" loop="loop" playsinline="" muted="muted"
                            class="intro-blend-video" style="opacity: 1;">
                            @if(isset($weddingData->video_file))
                            <source src={{getSettingURL($weddingData->video_file)}}>
                            @else
                            <source src={{asset("img/flower_00.mp4")}}>
                            @endif
                        </video>
                    </div>
                    @endif

                    @if($design == 'design_1' || $design == 'design_3')
                    <div class="date-place">
                        <div>{{date('l, F d, Y',strtotime($weddingData->wedding_date))}}
                            at {{date('H:i A',strtotime($weddingData->wedding_date))}}
                            <br>
                            {{$weddingData->address_details ?? ''}}
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            @if(isset($weddingData->wedding_details) && ($design == 'design_2' || $design == 'design_3'))
            <div class="paragraph-wrap fade-in">
                <div class="m-subtitle fade-in" data-aos="fade-up">
                    <img src="{{asset('img/icon_flower_09.png')}}" style="height: 2.6rem;">
                </div>
                <div class="text fade-in" data-aos="fade-up">
                    <div>{!! nl2br($weddingData->wedding_details) !!}</div>
                </div>
            </div>
            @endif

            <div class="greetings-wrap">
                @if(isset($weddingData->invite_text))
                <div class="title fade-in" data-aos="fade-up">
                    <div class="m-subtitle" style="font-size: 0.8rem;">{{date('Y.m.d',strtotime($weddingData->wedding_date))}}</div>
                    <div class="greetings-title">
                        @if($design == 'design_1')
                        ♡초대합니다♡
                        @else
                        초대합니다
                        @endif
                    </div>
                </div>
                @endif
                @if(isset($weddingData->invite_text))
                <div class="text fade-in" data-aos="fade-up">
                    <p>
                        {!! nl2br($weddingData->invite_text) !!}
                    </p>
                </div>
                @endif
                @if(isset($weddingData->photo))
                <div class="image fade-in" data-aos="fade-up">
                    <img src="{{$weddingData->photo}}" />
                </div>
                @endif

                <div class="members-wrap fade-in" data-aos="fade-up">
                    @if(isset($weddingData->son_of))
                    <div>
                        <span class="name">{{$weddingData->his_name}}</span>
                        <span class="relation">의 장남</span>
                        <span class="name">{{$weddingData->son_of}}</span>
                        @if(isset($weddingData->bridegroom_contact) &&
                        isset(collect($weddingData->bridegroom_contact)->first()->number))
                        <span class="phone-circle">
                            <a href="tel:{{collect($weddingData->bridegroom_contact)->first()->number}}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                    <path
                                        d="M497.39 361.8l-112-48a24 24 0 0 0-28 6.9l-49.6 60.6A370.66 370.66 0 0 1 130.6 204.11l60.6-49.6a23.94 23.94 0 0 0 6.9-28l-48-112A24.16 24.16 0 0 0 122.6.61l-104 24A24 24 0 0 0 0 48c0 256.5 207.9 464 464 464a24 24 0 0 0 23.4-18.6l24-104a24.29 24.29 0 0 0-14.01-27.6z" />
                                </svg>
                            </a>
                        </span>
                        @endif
                    </div>
                    @endif
                    @if(isset($weddingData->daughter_of))
                    <div>
                        <span class="name">{{$weddingData->her_name}}</span>
                        <span class="relation">의 장녀</span>
                        <span class="name">{{$weddingData->daughter_of}}</span>
                        @if(isset($weddingData->bride_contact) &&
                        isset(collect($weddingData->bride_contact)->first()->number))
                        <span class="phone-circle">
                            <a href="tel:{{collect($weddingData->bride_contact)->first()->number}}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                    <path
                                        d="M497.39 361.8l-112-48a24 24 0 0 0-28 6.9l-49.6 60.6A370.66 370.66 0 0 1 130.6 204.11l60.6-49.6a23.94 23.94 0 0 0 6.9-28l-48-112A24.16 24.16 0 0 0 122.6.61l-104 24A24 24 0 0 0 0 48c0 256.5 207.9 464 464 464a24 24 0 0 0 23.4-18.6l24-104a24.29 24.29 0 0 0-14.01-27.6z" />
                                </svg>
                            </a>
                        </span>
                        @endif
                    </div>
                    @endif
                </div>
                @if((isset($weddingData->bridegroom_contact) &&
                isset(collect($weddingData->bridegroom_contact)->first()->number)) ||
                (isset($weddingData->bride_contact) && isset(collect($weddingData->bride_contact)->first()->number)))
                <div class="contact-wrap fade-in" data-aos="fade-up">
                    <div class="contact-box" onclick="openConatctModal();">혼주에게 연락하기</div>
                </div>
                @endif
            </div>

            <div class="calendar-wrap">
                <div class="title fade-in" data-aos="fade-up">
                    <div>
                        {{date('F d',strtotime($weddingData->wedding_date))}}
                    </div>
                    <div style="color: rgb(51, 51, 51);">
                        {{date('H:i A',strtotime($weddingData->wedding_date))}}
                    </div>
                </div>
                <div class="wedding-calendar" data-aos="fade-up">
                    <div id="datepicker" data-aos="fade-up"></div>
                </div>

                <div class="dday-wrap fade-in" data-aos="fade-up">
                    <div>
                        {{$weddingData->his_name}} <span class="highlight" style="font-size: 11px;">♥</span>
                        {{$weddingData->her_name}} 결혼식이
                        <span>
                            @if($daysDiff)<span class="highlight">{{$daysDiff}}일</span> @endif
                            <!----> <span>{{$wedding_date_text}}.</span>
                        </span>
                    </div>
                </div>
            </div>

            @if(isset($weddingData->wedding_gallery))
            <div class="gallery-wrap">
                <div class="title fade-in" data-aos="fade-up">
                    갤러리
                </div>
                @if($design == 'design_3')
                <div class="m-subtitle fade-in"  data-aos="fade-up">GALLERY</div>
                @endif
                <div class="gallery-container fade-in" data-aos="fade-up">
                    @if($design == 'design_1')
                    <div class="gallery-square">
                        @foreach($weddingData->wedding_gallery as $key => $item)
                        <div class="item" onclick="openModal({{$key}});">
                            <div class="ph"></div>
                            <img src="{{$item}}" />
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="gallary-custom-slider">
                        @foreach($weddingData->wedding_gallery as $item)
                        <div style="height: 540px">
                            <div>
                                <img src="{{$item}}" />
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <div class="map-wrap">
                <div class="title fade-in" >
                    오시는 길
                </div>
                @if($design == 'design_3')
                    <div class="m-subtitle fade-in" data-aos="fade-up">LOCATION</div>
                @endif
                <div class="location-wrap">
                    <div class="location-hall fade-in" data-aos="fade-up">
                        <div>{{$weddingData->address_details ?? ''}}</div>
                    </div>
                    <div class="location-address fade-in" data-aos="fade-up">
                        <div>{{$weddingData->address ?? ''}}</div>
                    </div>
                </div>
                @if(isset($weddingData->address))
                <div id="address-map-container" class="mt-2" style="width:100%;height:240px; " data-aos="fade-up">
                    <div style="width: 100%; height: 100%" id="address-map"></div>
                </div>

                <div>
                    <div class="waytocome-wrap">
                        <div class="box fade-in" data-aos="fade-up">
                            <div class="title"><span style="vertical-align: middle;">자가용</span></div>
                            <div class="content">
                                <div>내비게이션 : 히든베이호텔 또는 주소 검색</div>
                            </div>
                        </div>
                        @if(isset($weddingData->bus_details))
                        <div class="box fade-in" data-aos="fade-up">
                            <div class="title"><span style="vertical-align: middle;">버스</span></div>
                            <div class="content">
                                <div>{{$weddingData->bus_details}}</div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            @if(isset($weddingData->bridegroom_bank) || isset($weddingData->bride_bank))
            <div class="account-wrap">
                <div class="m-subtitle fade-in" data-aos="fade-up"><img src="{{asset('img/icon_flower_07.png')}}">
                </div>
                <div class="title fade-in" data-aos="fade-up"><span>마음 전하실 곳</span>
                </div>
                <!---->
                <div class="c-account fade-in" data-aos="fade-up">
                    @if(isset($weddingData->bridegroom_bank) &&
                    isset(collect($weddingData->bridegroom_bank)->first()->bank_name))
                    <div class="item">
                        <div class="title">
                            <div>
                                신랑측 계좌번호
                            </div>
                            <div class="arrow"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg></div>
                        </div>
                        @foreach($weddingData->bridegroom_bank as $bank)
                        <div class="text gothic" style="display: none;">
                            <div class="inner"><span>{{$bank->bank_name}} {{$bank->account_number}}</span><br>
                                <span>{{$bank->name}}</span>
                            </div>
                            <div class="btn-copy" onclick="copyNumberFunction({{$bank->account_number}});">
                                복사하기
                            </div>
                            <!---->
                        </div>
                        @endforeach
                    </div>
                    @endif

                    @if(isset($weddingData->bride_bank) && isset(collect($weddingData->bride_bank)->first()->bank_name))
                    <div class="item">
                        <div class="title">
                            <div>
                                신부측 계좌번호
                            </div>
                            <div class="arrow"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg></div>
                        </div>
                        @foreach($weddingData->bride_bank as $bank)
                        <div class="text gothic" style="display: none;">
                            <div class="inner"><span>{{$bank->bank_name}} {{$bank->account_number}}</span><br>
                                <span>{{$bank->name}}</span>
                            </div>
                            <div class="btn-copy" onclick="copyNumberFunction({{$bank->account_number}});">
                                복사하기
                            </div>
                            <!---->
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @endif

            @if(isset($weddingData->notice_text) && isset($weddingData->notice_title))
            <div class="notice-wrap">
                <div class="box fade-in" data-aos="fade-up">
                    <div>
                        <div class="title fade-in" data-aos="fade-up">
                            {{$weddingData->notice_title ?? ''}}
                        </div>
                        <div class="text fade-in" data-aos="fade-up">
                            <div>{!! nl2br($weddingData->notice_text) !!}</div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="guestbook-wrap">
                <div class="title fade-in" data-aos="fade-up" style="margin-bottom: 20px;">방명록</div>
                <div class="c-guestbook gothic">
                    <div class="comments fade-in">
                        @if($weddingData->weddingGuests && count($weddingData->weddingGuests))
                        @foreach($weddingData->weddingGuests as $guest)
                        <div class="item" data-id="{{$guest->id}}" data-aos="fade-up">
                            <div class="close">
                                <span class="date">{{date('Y.m.d',strtotime($guest->created_at))}}</span>
                                <span class="icon" onClick="removeComment({{$guest->id}})">
                                    <svg data-name="Layer 1" id="Layer_1" viewBox="0 0 64 64"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <title></title>
                                        <path
                                            d="M8.25,0,32,23.75,55.75,0,64,8.25,40.25,32,64,55.75,55.75,64,32,40.25,8.25,64,0,55.75,23.75,32,0,8.25Z"
                                            data-name="<Compound Path>" id="_Compound_Path_"></path>
                                    </svg>
                                </span>
                            </div>
                            <div class="name">{{$guest->name}}</div>
                            <div class="text">{{$guest->description}}</div>
                        </div>
                        @endforeach

                        @else
                        <div class="item empty" data-aos="fade-up"><svg height="436pt" viewBox="-6 0 436 436"
                                width="436pt" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="m405.269531 95.183594c-15.378906-15.332032-38.886719-19.011719-58.214843-9.109375v-36.074219c-.03125-27.601562-22.398438-49.96875-50-50h-247.054688c-27.601562.03125-49.96875 22.398438-50 50v336c.03125 27.601562 22.398438 49.96875 50 50h247.054688c27.601562-.03125 49.96875-22.398438 50-50v-153.125l62.601562-62.601562c19.527344-19.527344 19.527344-51.183594 0-70.710938zm-220.289062 187.9375 36.425781 36.761718-49.578125 13.152344zm55.722656 27.820312-46.8125-47.242187 135.117187-134.730469 46.835938 46.832031zm86.351563 75.058594c-.019532 16.5625-13.4375 29.980469-30 30h-247.054688c-16.5625-.019531-29.980469-13.4375-30-30v-336c.019531-16.5625 13.4375-29.980469 30-30h247.054688c16.5625.019531 29.984374 13.4375 30 30v52.664062l-154.34375 153.90625c-1.257813 1.253907-2.160157 2.820313-2.609376 4.535157l-21.992187 83.457031c-.90625 3.441406.085937 7.105469 2.605469 9.625 2.519531 2.515625 6.183594 3.5 9.628906 2.585938l82.890625-21.992188c1.703125-.449219 3.257813-1.34375 4.507813-2.59375l79.3125-79.3125zm68.457031-229.867188-5.527344 5.527344-46.816406-46.816406 5.574219-5.558594c11.730468-11.65625 30.675781-11.636718 42.386718.039063l4.382813 4.382812c11.699219 11.722657 11.695312 30.703125 0 42.425781zm0 0">
                                </path>
                            </svg>
                            첫 번째 축하 글을 남겨주세요
                        </div>

                        @endif

                        <div class="writing float-right" data-aos="fade-up">
                            <button onclick="openPopup('guest-form-model');">
                                작성하기
                            </button>
                        </div>
                    </div>
                    <!---->
                </div>
            </div>


            <div class="footer-wrap">
                <div class="kakao-wrap">
                    <div class="btn-kakao" onclick="javascript:sendLink()">
                        <svg id="Bold" enable-background="new 0 0 24 24" height="512" viewBox="0 0 24 24" width="512"
                            xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                            <path d="m9.462 9.306-.692 1.951h1.383z"></path>
                            <path
                                d="m12 1c-6.627 0-12 4.208-12 9.399 0 3.356 2.246 6.301 5.625 7.963-1.299 4.45-1.333 4.47-1.113 4.599.276.161.634-.005 5.357-3.311.692.097 1.404.148 2.131.148 6.627 0 12-4.208 12-9.399s-5.373-9.399-12-9.399zm-5.942 12.023c0 .362-.311.657-.692.657s-.692-.295-.692-.657v-4.086h-1.08c-.375 0-.679-.302-.679-.673s.303-.674.678-.674h3.545c.375 0 .679.302.679.673s-.305.674-.679.674h-1.08zm5.378.648c-.72 0-.587-.565-.919-1.195h-2.111c-.329.625-.2 1.195-.919 1.195-.693.001-.815-.421-.604-1.071l1.656-4.33c.117-.33.471-.669.922-.679.452.01.807.349.923.679 1.093 3.39 2.654 5.402 1.052 5.401zm3.939-.092h-2.221c-1.159 0-.454-1.565-.663-5.301 0-.379.317-.688.707-.688s.707.308.707.688v4.04h1.471c.366 0 .663.283.663.63-.001.348-.298.631-.664.631zm5.419-.518c-.025.181-.122.344-.269.454-.955.721-1.661-1.381-2.593-2.271l-.24.239v1.5c0 .38-.31.688-.693.688-.382 0-.692-.308-.692-.688v-4.705c0-.379.31-.688.692-.688s.692.308.692.688v1.478c1.277-.958 1.985-2.67 2.792-1.869.792.786-.848 1.474-1.527 2.422 1.604 2.212 1.909 2.267 1.838 2.752z">
                            </path>
                        </svg>
                        <span style="display: inline-block; width: 8.2rem; margin-left: 6px;">
                            <div>카카오톡으로 공유하기</div>
                        </span>
                    </div>
                    <div class="btn-kakao" onClick="copyTextFunction();"><svg xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z"
                                clip-rule="evenodd"></path>
                        </svg> <span style="display: inline-block; width: 8.2rem; margin-left: 6px;">
                            <div>청첩장 주소 복사하기</div>
                        </span></div>
                </div>
                <div class="copyright-wrap">
                    <div class="copyright">
                        <span>Copyright {{date('Y')}}. </span>
                        <span class="brand">
                            <span>MeAround</span>
                        </span>
                        <span>. All rights reserved.</span>
                    </div>
                </div>
            </div>

            <div class="modal-mask" id="guest-delete-form-model" style="display: none;">
                <div class="modal-wrapper-guest">
                    <div class="guest-modal-wrap">
                        <div class="c-guestbook-delete-modal">
                            <form action="{{route('remove.guest.book')}}" id="delete-guest" method="POST">
                                @csrf
                                <div class="content">
                                    <input type="hidden" name="guest_id" value="" />
                                    <input type="password" onkeyup="validateDeleteForm();" maxlength="15"
                                        placeholder="비밀번호" name="delete-pass" />
                                </div>
                                <div class="action"><button class="delete-form-btn" disabled="disabled">삭제하기 <div
                                            class="loader">
                                            <div class="loading">
                                            </div>
                                        </div></button></div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-mask" id="guest-form-model" style="display: none;">
                <div class="modal-wrapper-guest">
                    <div class="guest-modal-wrap">
                        <div class="c-guestbook-delete-modal">
                            <form autocomplete="false" id="guest-form" method="post"
                                action="{{route('save.guest.book',[$uuid])}}">
                                @csrf
                                <div class="content">
                                    <div class="writing">
                                        <div>
                                            <input onkeyup="validateForm();" autocomplete="false" name="name"
                                                type="text" maxlength="8" placeholder="이름" />
                                            <input onkeyup="validateForm();" autocomplete="false" name="pass"
                                                type="password" maxlength="15" placeholder="비밀번호" />
                                        </div>
                                        <textarea onkeyup="validateForm();" name="description"
                                            maxlength="100"></textarea>
                                    </div>
                                </div>
                                <div class="action">
                                    <button class="form-btn" disabled="disabled">작성하기
                                        <div class="loader">
                                            <div class="loading">
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @if((isset($weddingData->bridegroom_contact) &&
            isset(collect($weddingData->bridegroom_contact)->first()->number)) ||
            (isset($weddingData->bride_contact) && isset(collect($weddingData->bride_contact)->first()->number)))
            <div class="modal-mask" id="contact-model" style="display: none;">
                <div class="modal-wrapper-contact">
                    <div class="contact-modal-wrap">
                        <div class="contact-modal-inner">
                            <div class="title">
                                <div>혼주에게 연락하기</div>
                            </div>
                            <div class="content">
                                @foreach($weddingData->bridegroom_contact as $contact)
                                <div>
                                    <div class="cell"><span style="color: rgb(102, 142, 170);">신랑 父</span></div>
                                    <div class="cell"><span> {{$contact->name}}</span></div>
                                    <div class="cell">
                                        <a href="tel:{{$contact->number}}" class="phone-square">
                                            <svg height="512" viewBox="0 0 128 128" width="512"
                                                xmlns="http://www.w3.org/2000/svg" style="fill: rgb(102, 142, 170);">
                                                <g id="icon">
                                                    <path
                                                        d="m54.048 43.653-3.928 6.122a7.725 7.725 0 0 0 .829 9.434l8.566 9.277 9.277 8.566a7.726 7.726 0 0 0 9.434.829l6.638-4.259 16.65 17.219-6.748 8.553a8.839 8.839 0 0 1 -9.277 3.039c-13.659-3.633-25.501-11.763-36.672-23.25-11.487-11.171-19.617-23.013-23.251-36.672a8.84 8.84 0 0 1 3.04-9.276l8.605-6.79z">
                                                    </path>
                                                    <path
                                                        d="m85.054 73.5-1 .641 17.126 17.123.375-.475a5.5 5.5 0 0 0 -.434-7.3l-9.237-9.237a5.493 5.493 0 0 0 -6.83-.752z">
                                                    </path>
                                                    <path
                                                        d="m37.212 26.445-.475.375 17.123 17.125.641-1a5.493 5.493 0 0 0 -.75-6.829l-9.237-9.237a5.5 5.5 0 0 0 -7.302-.434z">
                                                    </path>
                                                </g>
                                            </svg>
                                        </a>
                                        <a href="mailto:{{$contact->email}}" class="phone-square">
                                            <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg"
                                                xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                                viewBox="0 0 512 512" xml:space="preserve"
                                                style="fill: rgb(102, 142, 170); width: 16px;">
                                                <g>
                                                    <g>
                                                        <path d="M507.49,101.721L352.211,256L507.49,410.279c2.807-5.867,4.51-12.353,4.51-19.279V121
                            C512,114.073,510.297,107.588,507.49,101.721z"></path>
                                                    </g>
                                                </g>
                                                <g>
                                                    <g>
                                                        <path d="M467,76H45c-6.927,0-13.412,1.703-19.279,4.51l198.463,197.463c17.548,17.548,46.084,17.548,63.632,0L486.279,80.51
                            C480.412,77.703,473.927,76,467,76z"></path>
                                                    </g>
                                                </g>
                                                <g>
                                                    <g>
                                                        <path
                                                            d="M4.51,101.721C1.703,107.588,0,114.073,0,121v270c0,6.927,1.703,13.413,4.51,19.279L159.789,256L4.51,101.721z">
                                                        </path>
                                                    </g>
                                                </g>
                                                <g>
                                                    <g>
                                                        <path d="M331,277.211l-21.973,21.973c-29.239,29.239-76.816,29.239-106.055,0L181,277.211L25.721,431.49
                            C31.588,434.297,38.073,436,45,436h422c6.927,0,13.412-1.703,19.279-4.51L331,277.211z">
                                                        </path>
                                                    </g>
                                                </g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                                @endforeach
                                <div
                                    style="height: 1px; border-bottom: 1px solid rgb(216, 216, 216); width: 90%; margin: 20px auto;">
                                </div>

                                @foreach($weddingData->bride_contact as $contact)
                                <div>
                                    <div class="cell"><span style="color: rgb(217, 124, 116);">신부 父</span></div>
                                    <div class="cell"><span>{{$contact->name}}</span></div>
                                    <div class="cell">
                                        <a href="tel:{{$contact->number}}" class="phone-square">
                                            <svg height="512" viewBox="0 0 128 128" width="512"
                                                xmlns="http://www.w3.org/2000/svg" style="fill: rgb(217, 124, 116);">
                                                <g id="icon">
                                                    <path
                                                        d="m54.048 43.653-3.928 6.122a7.725 7.725 0 0 0 .829 9.434l8.566 9.277 9.277 8.566a7.726 7.726 0 0 0 9.434.829l6.638-4.259 16.65 17.219-6.748 8.553a8.839 8.839 0 0 1 -9.277 3.039c-13.659-3.633-25.501-11.763-36.672-23.25-11.487-11.171-19.617-23.013-23.251-36.672a8.84 8.84 0 0 1 3.04-9.276l8.605-6.79z">
                                                    </path>
                                                    <path
                                                        d="m85.054 73.5-1 .641 17.126 17.123.375-.475a5.5 5.5 0 0 0 -.434-7.3l-9.237-9.237a5.493 5.493 0 0 0 -6.83-.752z">
                                                    </path>
                                                    <path
                                                        d="m37.212 26.445-.475.375 17.123 17.125.641-1a5.493 5.493 0 0 0 -.75-6.829l-9.237-9.237a5.5 5.5 0 0 0 -7.302-.434z">
                                                    </path>
                                                </g>
                                            </svg>
                                        </a>
                                        <a href="mailto:{{$contact->email}}" class="phone-square"><svg version="1.1"
                                                id="Capa_1" xmlns="http://www.w3.org/2000/svg"
                                                xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                                viewBox="0 0 512 512" xml:space="preserve"
                                                style="fill: rgb(217, 124, 116); width: 16px;">
                                                <g>
                                                    <g>
                                                        <path d="M507.49,101.721L352.211,256L507.49,410.279c2.807-5.867,4.51-12.353,4.51-19.279V121
                            C512,114.073,510.297,107.588,507.49,101.721z"></path>
                                                    </g>
                                                </g>
                                                <g>
                                                    <g>
                                                        <path d="M467,76H45c-6.927,0-13.412,1.703-19.279,4.51l198.463,197.463c17.548,17.548,46.084,17.548,63.632,0L486.279,80.51
                            C480.412,77.703,473.927,76,467,76z"></path>
                                                    </g>
                                                </g>
                                                <g>
                                                    <g>
                                                        <path
                                                            d="M4.51,101.721C1.703,107.588,0,114.073,0,121v270c0,6.927,1.703,13.413,4.51,19.279L159.789,256L4.51,101.721z">
                                                        </path>
                                                    </g>
                                                </g>
                                                <g>
                                                    <g>
                                                        <path d="M331,277.211l-21.973,21.973c-29.239,29.239-76.816,29.239-106.055,0L181,277.211L25.721,431.49
                            C31.588,434.297,38.073,436,45,436h422c6.927,0,13.412-1.703,19.279-4.51L331,277.211z">
                                                        </path>
                                                    </g>
                                                </g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                                <g></g>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            @endif

            @if(isset($weddingData->wedding_gallery))
            <div class="modal-mask" id="gallery-model" style="display: none;">
                <div class="modal-wrapper">
                    <div class="modal-container">
                        <div>
                            <div class="carousel-wrap">
                                <div class="custom-slider">
                                    @foreach($weddingData->wedding_gallery as $item)
                                    <div style="height: 540px">
                                        <div>
                                            <img src="{{$item}}" />
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="gallery-modal-btn">
                            <div class="gallery-modal-btn-outer">
                                <span onclick="prevSlide();"><svg version="1.1" id="Capa_1"
                                        xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                        x="0px" y="0px" viewBox="0 0 55.753 55.753" xml:space="preserve">
                                        <g>
                                            <path d="M12.745,23.915c0.283-0.282,0.59-0.52,0.913-0.727L35.266,1.581c2.108-2.107,5.528-2.108,7.637,0.001
                                    c2.109,2.108,2.109,5.527,0,7.637L24.294,27.828l18.705,18.706c2.109,2.108,2.109,5.526,0,7.637
                                    c-1.055,1.056-2.438,1.582-3.818,1.582s-2.764-0.526-3.818-1.582L13.658,32.464c-0.323-0.207-0.632-0.445-0.913-0.727
                                    c-1.078-1.078-1.598-2.498-1.572-3.911C11.147,26.413,11.667,24.994,12.745,23.915z">
                                            </path>
                                        </g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                    </svg></span>
                                <span onclick="nextSlide();"><svg version="1.1" id="Capa_1"
                                        xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                        x="0px" y="0px" viewBox="0 0 55.752 55.752" xml:space="preserve">
                                        <g>
                                            <path d="M43.006,23.916c-0.28-0.282-0.59-0.52-0.912-0.727L20.485,1.581c-2.109-2.107-5.527-2.108-7.637,0.001
                                    c-2.109,2.108-2.109,5.527,0,7.637l18.611,18.609L12.754,46.535c-2.11,2.107-2.11,5.527,0,7.637c1.055,1.053,2.436,1.58,3.817,1.58
                                    s2.765-0.527,3.817-1.582l21.706-21.703c0.322-0.207,0.631-0.444,0.912-0.727c1.08-1.08,1.598-2.498,1.574-3.912
                                    C44.605,26.413,44.086,24.993,43.006,23.916z"></path>
                                        </g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                        <g></g>
                                    </svg></span>
                                <span onclick="closeModal();"><svg data-name="Layer 1" id="Layer_1" viewBox="0 0 64 64"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <title></title>
                                        <path
                                            d="M8.25,0,32,23.75,55.75,0,64,8.25,40.25,32,64,55.75,55.75,64,32,40.25,8.25,64,0,55.75,23.75,32,0,8.25Z"
                                            data-name="<Compound Path>" id="_Compound_Path_"></path>
                                    </svg></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            <!-- WeddingData -->
        </div>
        @endif
        <!-- Page Specific JS File -->

        <script src="{!! asset('plugins/jquery.min.js') !!}"></script>
        <script src="{!! asset('plugins/popper.js') !!}"></script>
        <script src="{!! asset('plugins/tooltip.js') !!}"></script>
        <script src="{!! asset('plugins/bootstrap/js/bootstrap.min.js') !!}"></script>
        <script src="{!! asset('plugins/slick/slick.js') !!}"></script>
        <script src="{!! asset('plugins/aos/aos.js') !!}"></script>
        <script src="{!! asset('js/jquery-ui.js') !!}"></script>
        <script src="https://developers.kakao.com/sdk/js/kakao.js"></script>
        {{-- <script type="text/javascript" src="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js">
        </script> --}}
        <script
            src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDlfhV6gvSJp_TvqudE0z9mV3bBlexZo3M&callback=initMap&v=weekly&channel=2&region=KR&language=ko"
            async></script>


        <script type="text/javascript">
            Kakao.init('{{env('KAKAO_APP_KEY')}}');
                function sendLink() {
                  Kakao.Link.sendDefault({
                    objectType: 'feed',
                    content: {
                      title: '{{$title}}',
                      description: "{{$weddingData->invite_text ? trim(preg_replace('/\s\s+/', ' ',$weddingData->invite_text)) : ''}}",
                      imageUrl:
                        '{{$weddingData->wedding_photo ?? ''}}',
                      link: {
                        mobileWebUrl: window.location.href,
                        webUrl: window.location.href,
                      },
                    },
                    social: {
                      likeCount: 286,
                      commentCount: 45,
                      sharedCount: 845,
                    },
                    buttons: [
                      {
                        title: 'View on Web',
                        link: {
                          mobileWebUrl: window.location.href,
                          webUrl: window.location.href,
                        },
                      },
                    ],
                  })
                }
        </script>
        <script type="text/javascript">
            window.addEventListener('load', function() {

                var myAudio = document.getElementById('playAudio');
                if (myAudio.duration > 0 && !myAudio.paused) {
                    $(".fa-volume-mute").addClass('fa-volume-up').removeClass('fa-volume-mute');
                } else {
                    $(".fa-volume-up").addClass('fa-volume-mute').removeClass('fa-volume-up');
                }

                $(document).on("click",'.fa-volume-mute',function(){
                    document.getElementById("playAudio").play();
                    $(".fa-volume-mute").addClass('fa-volume-up').removeClass('fa-volume-mute');
                });
                $(document).on("click",'.fa-volume-up',function(){
                    document.getElementById("playAudio").pause();
                    document.getElementById("playAudio").currentTime = 0;
                    $(".fa-volume-up").addClass('fa-volume-mute').removeClass('fa-volume-up');
                });
            })
            /*  */
            AOS.init({
                duration: 800,
                offset: 200, 
                delay: 200,
                anchorPlacement: 'top-bottom',
            });

            $(document).on('submit','#delete-guest',function(e){
                e.preventDefault();
                var formData = new FormData(this);
                $.ajax({
                    url: $(this).attr('action'),
                    type:"POST",
                    contentType: false, 
                    processData: false,
                    data: formData,
                    beforeSend: function() {
                        $('.loader').show();
                        $('.form-btn').attr('disabled',true);
                        closeModal();
                        $("#delete-guest")[0].reset();
                    },
                    success:function(response) {
                        $('.loader').hide();
                        $('.form-btn').attr('disabled',false);
                        closeModal();
                        $("#delete-guest")[0].reset();

                        if(response.success){
                            $('div[data-id='+response.guest_id+']').remove();
                        }else{
                            alert('삭제할 수 없습니다.');
                        }
                    },
                    error:function (response, status) {
                        $('.loader').hide();
                        $('.form-btn').attr('disabled',false);
                        closeModal();
                        $("#delete-guest")[0].reset();
                    }
                });
            });

            $(document).on('submit','#guest-form',function(e){
                e.preventDefault();
                var formData = new FormData(this);
                $.ajax({
                    url: $(this).attr('action'),
                    type:"POST",
                    contentType: false, 
                    processData: false,
                    data: formData,
                    beforeSend: function() {
                        $('.loader').show();
                        $('.form-btn').attr('disabled',true);
                        closeModal();
                        $("#guest-form")[0].reset();
                    },
                    success:function(response) {
                        $('.loader').hide();
                        $('.form-btn').attr('disabled',false);
                        if(response.success){
                            if($(".item.empty").length){
                                $(".item.empty").remove();
                            }
                            $(".comments").prepend(response.html);
                        }
                        closeModal();
                        $("#guest-form")[0].reset();
                    },
                    error:function (response, status) {
                        $('.loader').hide();
                        $('.form-btn').attr('disabled',false);
                        closeModal();
                        $("#guest-form")[0].reset();
                    }
                });
            });

            function removeComment(id){
                if(id){
                    $('input[name="guest_id"]').val(id);
                    $("#guest-delete-form-model").fadeIn();
                }
            }
            
            $("#datepicker").datepicker({
                dayNamesMin: ["S", "M", "T", "W", "T", "F", "S"],
                daysOfWeekDisabled: [0],
                beforeShowDay: function(date) {
                    var show = true;
                    if(date.getDay()==0) show=false
                    return [show];
                }
            });
            $('#datepicker').datepicker("setDate", new Date({{date('Y,m,d',strtotime($weddingData->wedding_date))}}) );

            $(".c-account .item .title").click(function(){
                $(this).parent().find(".text").slideToggle(800);
            });

            $('body').on('click',function(event){
                if($(event.target).is('.modal-mask')){
                    closeModal();
                }
            });

            $('.gallary-custom-slider').slick({
                slidesToShow: 1,
                slidesToScroll: 1,
                infinite: true,
                prevArrow: false,
                nextArrow: false,
                dots: true,
            });
            let slider = $('.custom-slider').slick({
                slidesToShow: 1,
                slidesToScroll: 1,
                infinite: true,
                prevArrow: false,
                nextArrow: false
            });

            function nextSlide(){
                slider.slick('slickNext');
            }
            function prevSlide(){
                slider.slick('slickPrev');
            }

            function closeModal(){
                $('body').removeClass('modal-open');
                $(".modal-mask").fadeOut();
            }

            function openPopup(model){
                $('body').addClass('modal-open');
                $("#"+model).fadeIn();
            }
            function openConatctModal(){
                $('body').addClass('modal-open');
                $("#contact-model").fadeIn();
            }
            function openModal(index){
                $('body').addClass('modal-open');
                slider.slick('slickGoTo', index);
                $("#gallery-model").fadeIn();
            }

            function validateDeleteForm(){
                const pass = $('input[name="delete-pass"]').val();
                if(pass){
                    $('.delete-form-btn').attr('disabled',false);
                }else{
                    $('.delete-form-btn').attr('disabled',true);
                }
            }

            function validateForm(){
                const name = $('input[name="name"]').val();
                const pass = $('input[name="pass"]').val();
                const description = $('textarea[name="description"]').val();

                if(name && pass && description){
                    $('.form-btn').attr('disabled',false);
                }else{
                    $('.form-btn').attr('disabled',true);
                }
            }

            function copyNumberFunction(number) {
                navigator.clipboard.writeText(number);
                alert("계좌번호("+number+")가 복사되었습니다. \n필요한 곳에 붙여넣기 하세요.")
            }
            function copyTextFunction() {
                navigator.clipboard.writeText(window.location.href);
                
                alert("Copied the text: ");
            }

            function initMap() {
                // $weddingData->wedding_date
                const lat = {{$address_latitude}};
                const lng = {{$address_longitude}};
                //if(lat != 0 ){
                    const myLatLng = { lat: lat, lng: lng };
                    const map = new google.maps.Map(document.getElementById("address-map"), {
                        zoom: 16,
                        center: myLatLng,
                        zoomControl: false,
                        scaleControl: false,
                        mapTypeControl: false,
                        streetViewControl: false,
                        rotateControl: false,
                        fullscreenControl: false,
                        styles: [{"featureType":"poi.attraction","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"poi.business","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"poi.government","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"poi.medical","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"poi.park","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"poi.place_of_worship","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"poi.school","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"poi.sports_complex","elementType":"labels","stylers":[{"visibility":"off"}]}]
                        ///styles: [{"featureType":"all","elementType":"labels.text","stylers":[{"color":"#878787"}]},{"featureType":"all","elementType":"labels.text.stroke","stylers":[{"visibility":"off"}]},{"featureType":"landscape","elementType":"all","stylers":[{"color":"#f9f5ed"}]},{"featureType":"road.highway","elementType":"all","stylers":[{"color":"#f5f5f5"}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"color":"#c9c9c9"}]},{"featureType":"water","elementType":"all","stylers":[{"color":"#aee0f4"}]}]
                    });

                    new google.maps.Marker({
                        position: myLatLng,
                        map,
                        title: "MeAround",
                        icon: "{{asset('img/marker-default.png')}}"
                        //
                    });
                //}
            }
        </script>

</body>

</html>