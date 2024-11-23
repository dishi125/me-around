<?php
/* response from paypal */
echo "<b>Response:-</b><br/><br/>";
foreach ($_POST as $key => $val) {
    echo $key . "=>" . $val . "<br/>";
}
echo "<br/><br/><br/>";

$pay_rst = (isset($_POST['PCD_PAY_RST'])) ? $_POST['PCD_PAY_RST'] : "";

if(empty($pay_rst) || $pay_rst == 'close'){
    header("Location: /me-talk/");
    die();
}

$pay_type_display = "신용카드";
$pay_type = (isset($_POST['PCD_PAY_TYPE'])) ? $_POST['PCD_PAY_TYPE'] : "";
$pay_goods = (isset($_POST['PCD_PAY_GOODS'])) ? $_POST['PCD_PAY_GOODS'] : "";
$payer_name = (isset($_POST['PCD_PAYER_NAME'])) ? $_POST['PCD_PAYER_NAME'] : "";
$pay_total = (isset($_POST['PCD_PAY_TOTAL'])) ? $_POST['PCD_PAY_TOTAL'] : "";
$pay_msg = (isset($_POST['PCD_PAY_MSG'])) ? $_POST['PCD_PAY_MSG'] : "";
$pay_time = (isset($_POST['PCD_PAY_TIME'])) ? $_POST['PCD_PAY_TIME'] : "";

$pay_date = !empty($pay_time) ? date("Y.m.d", strtotime($pay_time)) : '';

?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="/me-talk/public/css/paypal_style.css" rel="stylesheet" />
<div class="device__layout">
    <div class="line_setter has_bg">
        <div class="demo_result">
            <div class="tit__demo_result">
                <div class="logo"><img src="/me-talk/public/image/logo_full_white.svg" alt="" class="res"></div>
                <div class="date"> <?php echo $pay_date; ?> </div>
            </div>
            <div class="smr__demo_result">
                <div class="icon"><img src="/me-talk/public/image/icon-card.svg" alt="" class="res"></div>
                <!-- <div class="success-message"> 카드등록 및 결제가 완료되었습니다. </div> -->
                <div class="success-message"> <?php echo $pay_msg; ?> </div>
            </div>
            <div class="ctn__demo_result">
                <div class="detail-block-outer">
                    <div class="detail-block">
                        <div class="block-key">결제방식</div>
                        <div class="block-value"> <?php echo $pay_type_display; ?> </div>
                    </div>
                    <div class="detail-block">
                        <div class="block-key">상품명</div>
                        <div class="block-value"> <?php echo $pay_goods; ?> </div>
                    </div>
                    <div class="detail-block">
                        <div class="block-key">구매자명</div>
                        <div class="block-value"> <?php echo $payer_name; ?> </div>
                    </div>
                </div>
                <div class="total-block dp_flex flc_jc_sb flc_al_center has_spacer_y_16">
                    <div class="block-key">결제금액</div>
                    <div class="block-value"><span class="total-amount"><?php echo $pay_total; ?></span>원 </div>
                </div>
            </div>
        </div>
<!--        <div class="result-button">
            <a href="/me-talk/" class="btn btn_full_width cl_info btn_rounded btn_md_sub"> 처음으로 이동하기 </a>
        </div>-->
    </div>
</div>
