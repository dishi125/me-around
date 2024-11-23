<title>카드결제</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<script src="{!! asset('plugins/jquery.min.js') !!}"></script>
<link rel="stylesheet" href="{!! asset('plugins/bootstrap/css/bootstrap.min.css') !!}">
<link rel="stylesheet" href="{!! asset('css/paypal_style.css') !!}">
<script src="{!! asset('plugins/bootstrap/js/bootstrap.min.js') !!}"></script>
{{--<script src="https://democpay.payple.kr/js/v1/payment.js"></script> <!-- 테스트(TEST) -->--}}
<script src="https://cpay.payple.kr/js/v1/payment.js"></script> <!-- 운영(REAL) -->

<div id="loader"></div>

<div class="page">
    <div class="device__layout form-layout">
        <div class="line_setter">
            <h1><img src="{{ asset('img/paypal_logo_full.svg') }}" alt="" /> 카드결제 </h1>
            <form id="orderForm" name="orderForm" method="POST">
                {{ csrf_field() }}
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <div class="row">
                                <div class="col-4">
                                    <label for="card_ver">결제방식</label>
                                </div>
                                <div class="col-8">
                                    <select id="card_ver" name="card_ver" class="form-control" disabled>
                                        <option value="01" <?php echo ($bill->card_ver == '01') ? 'selected' : ''; ?>>정기 결제</option>
                                        <option value="02" <?php echo ($bill->card_ver == '02') ? 'selected' : ''; ?>>앱카드 결제</option>
                                    </select>

                                    <select id="pay_work" name="pay_work" class="form-control mt-2" disabled>
                                        <option value="PAY" <?php echo ($bill->pay_work == 'PAY') ? 'selected' : ''; ?>>카드등록 및 결제</option>
                                        <option value="AUTH" <?php echo ($bill->pay_work == 'AUTH') ? 'selected' : ''; ?>>카드등록</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-4">
                                    <label for="pay_goods">상품명</label>
                                </div>
                                <div class="col-8">
                                    <input type="text" name="pay_goods" id="pay_goods" value="<?= $bill->pay_goods ?>" class="form-control" readonly disabled />
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="row">
                                <div class="col-4">
                                    <label for="pay_total">결제금액</label>
                                </div>
                                <div class="col-8">
                                    <input type="text" name="pay_total" id="pay_total" value="<?= $bill->pay_total ?>" class="form-control" readonly disabled />
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="row">
                                <div class="col-4">
                                    <label for="payer_name">구매자명</label>
                                </div>
                                <div class="col-8">
                                    <input type="text" required placeholder="성함 및 사업자명" name="payer_name" id="payer_name" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="row">
                                <div class="col-4">
                                    <label for="payer_hp">구매자 휴대폰번호</label>
                                </div>
                                <div class="col-8">
                                    <input type="text" required placeholder="휴대폰 번호 입력" name="payer_hp" id="payer_hp" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="row">
                                <div class="col-4">
                                    <label for="payer_email">구매자 이메일</label>
                                </div>
                                <div class="col-8">
                                    <input type="text" required placeholder="이메일 입력" name="payer_email" id="payer_email" class="form-control" />
                                </div>
                            </div>
                        </div>

                        <div class="form-group form-btn-outer mt-4">
                            <input type="submit" name="submit" id="payAction" class="form-control form-btn" value="결제하기" />
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(window).on('load', function() {
        if ($('select[name="card_ver"]').length) {
            $('select[name="card_ver"]').trigger('change');
        }
    });
    $('body').on('change', '#card_ver', function() {
        if ($(this).val() == '01') {
            $('#pay_work').show();
        } else {
            $('#pay_work').hide();
        }
    });

    $('body').on('submit', '#orderForm', function(event) {
        event.preventDefault();
        // $('#loader').show();
        // processPayment("","");

        $.ajax({
            url: "{{ route('paypal.authenticate') }}",
            type: 'Post',
            data: {
                '_token': "{{ csrf_token() }}",
            },
            beforeSend: function (){
                $('#loader').show();
            },
            success: function(data) {
                if(data.success == true){
                    console.log("data: "+data);
                    processPayment(data.cst_id,data.custKey,data.AuthKey,data.return_url);
                }
                else {
                    alert(data.message);
                }
            },
            error: function (){
                alert("Something went wrong !!");
            }
        })
    });

    function processPayment(cst_id,custKey,AuthKey,return_url){
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            }
        });

        var pay_type = "card";
        var pay_work = $("#pay_work").val();
        var payer_id = "";
        var payer_no = "";
        var payer_name = $("#payer_name").val();
        var payer_hp = $("#payer_hp").val();
        var payer_email = $("#payer_email").val();
        var pay_goods = $("#pay_goods").val();
        var pay_total = $("#pay_total").val();
        var pay_taxtotal = "";
        var pay_istax = "Y";
        var pay_oid = "";
        var taxsave_flag = "";
        var simple_flag = "N";
        var card_ver = $("#card_ver").val();
        var payer_authtype = "";
        var is_direct = "N";
        var pcd_rst_url = "";
        var server_name = "<?= $_SERVER['HTTP_HOST'] ?>";

        // 결제창 방식 설정 - 팝업(상대경로), 다이렉트(절대경로)
        if (is_direct == 'Y') pcd_rst_url = "http://" + server_name + "/paypal/payment/result/{{ $bill->id }}";
        else pcd_rst_url = "{{ url('paypal/payment/result/'.$bill->id) }}";
        /*if (is_direct == 'Y') pcd_rst_url = "http://" + server_name + "/paypal_payment_result.php";
        else pcd_rst_url = "{{ url('paypal_payment_result.php') }}";*/

        var obj = new Object();
        obj._token = "{{ csrf_token() }}";
        /* 결제연동 파라미터 */

        //DEFAULT SET 1
        obj.PCD_PAY_TYPE = pay_type; // (필수) 결제수단 (transfer|card)
        obj.PCD_PAY_WORK = pay_work; // (필수) 결제요청 방식 (AUTH | PAY | CERT)

        // 카드결제 시 필수 (카드 세부 결제방식)
        if (pay_type == "card") obj.PCD_CARD_VER = card_ver; // Default: 01 (01: 간편/정기결제, 02: 앱카드)

        /* 결제요청 방식별(PCD_PAY_WORK) 파라미터 설정 */
        /*
         * 1. 빌링키 등록
         * PCD_PAY_WORK : AUTH
         */
        if (pay_work == 'AUTH') {
            obj.PCD_PAYER_NO = payer_no; // (선택) 결제자 고유번호 (파트너사 회원 회원번호) (결과전송 시 입력값 그대로 RETURN)
            obj.PCD_PAYER_NAME = payer_name; // (선택) 결제자 이름
            obj.PCD_PAYER_HP = payer_hp; // (선택) 결제자 휴대전화번호
            obj.PCD_PAYER_EMAIL = payer_email; // (선택) 결제자 이메일
            obj.PCD_TAXSAVE_FLAG = taxsave_flag; // (선택) 현금영수증 발행요청 (Y|N)
            obj.PCD_SIMPLE_FLAG = simple_flag; // (선택) 간편결제 여부 (Y|N)
        }

        /*
         * 2. 빌링키 등록 및 결제
         * PCD_PAY_WORK : PAY | CERT
         */
        if (pay_work != 'AUTH') {
            // 2.1 첫결제 및 단건(일반,비회원)결제
            if (simple_flag != 'Y' || payer_id == '') {
                obj.PCD_PAY_GOODS = pay_goods; // (필수) 상품명
                obj.PCD_PAY_TOTAL = pay_total; // (필수) 결제요청금액
                obj.PCD_PAYER_NO = payer_no; // (선택) 결제자 고유번호 (파트너사 회원 회원번호) (결과전송 시 입력값 그대로 RETURN)
                obj.PCD_PAYER_NAME = payer_name; // (선택) 결제자 이름
                obj.PCD_PAYER_HP = payer_hp; // (선택) 결제자 휴대전화번호
                obj.PCD_PAYER_EMAIL = payer_email; // (선택) 결제자 이메일
                obj.PCD_PAY_TAXTOTAL = pay_taxtotal; // (선택) 부가세(복합과세 적용 시)
                obj.PCD_PAY_ISTAX = pay_istax; // (선택) 과세여부
                obj.PCD_PAY_OID = pay_oid; // (선택) 주문번호 (미입력 시 임의 생성)
                obj.PCD_TAXSAVE_FLAG = taxsave_flag; // (선택) 현금영수증 발행요청 (Y|N)
            }

            // 2.2 간편결제 (빌링키결제)
            if (simple_flag == 'Y' && payer_id != '') {
                // PCD_PAYER_ID 는 소스상에 표시하지 마시고 반드시 Server Side Script 를 이용하여 불러오시기 바랍니다.
                obj.PCD_PAYER_ID = payer_id; // (필수) 빌링키 - 결제자 고유ID (본인인증 된 결제회원 고유 KEY)
                obj.PCD_SIMPLE_FLAG = 'Y'; // (필수) 간편결제 여부 (Y|N)
                obj.PCD_PAY_GOODS = pay_goods; // (필수) 상품명
                obj.PCD_PAY_TOTAL = pay_total; // (필수) 결제요청금액
                obj.PCD_PAYER_NO = payer_no; // (선택) 결제자 고유번호 (파트너사 회원 회원번호) (결과전송 시 입력값 그대로 RETURN)
                obj.PCD_PAY_TAXTOTAL = pay_taxtotal; // (선택) 부가세(복합과세인 경우 필수)
                obj.PCD_PAY_ISTAX = pay_istax; // (선택) 과세여부
                obj.PCD_PAY_OID = pay_oid; // (선택) 주문번호 (미입력 시 임의 생성)
                obj.PCD_TAXSAVE_FLAG = taxsave_flag; // (선택) 현금영수증 발행요청 (Y|N)
            }
        }

        // DEFAULT SET 2
        obj.PCD_PAYER_AUTHTYPE = payer_authtype; // (선택) 비밀번호 결제 인증방식 (pwd : 패스워드 인증)
        obj.PCD_RST_URL = pcd_rst_url; // (필수) 결제(요청)결과 RETURN URL
        //obj.callbackFunction = getResult; // (선택) 결과를 받고자 하는 callback 함수명 (callback함수를 설정할 경우 PCD_RST_URL 이 작동하지 않음)
        obj.PCD_AUTH_KEY = AuthKey;
        obj.PCD_PAY_URL = return_url;
        /* 파트너 인증 - 클라이언트 키(clientKey) */
        obj.clientKey = "{{ env('clientKey') }}";

        // setTimeout(function() {
        PaypleCpayAuthCheck(obj);
        // }, 5000);
    }
</script>
