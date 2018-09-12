<?php
ini_set('date.timezone','Asia/Shanghai');
header('Content-Type:text/html; charset=utf-8');

require_once 'lib/Cmb.Request.php';
require_once 'lib/Cmb.Builder.php';
require_once 'lib/log.php';

$logHandler= new CLogFileHandler("logs/".date('Y-m-d').'.ccb.log');
$log = Log::Init($logHandler, 15);
//参数
$payNo = '1000000001';
$amount = '0.01'; //单位为分
$agrNo = '100000000000001';
$userId = '100000000000001';
$mobile = '13600000000';

//支付
$inputObj = new CmbPay();
$inputObj->SetDate(date('Ymd'));
$inputObj->SetOrderNo($payNo);
$inputObj->SetAmount($amount);
$inputObj->SetExpireTimeSpan('30');
$inputObj->SetPayNoticeUrl('http://yourserver/bank/cmb/pay-notice.php');
$inputObj->SetPayNoticePara('');
$inputObj->SetReturnUrl('http://yourserver/bank/cmb/test.php');
$inputObj->SetAgrNo($agrNo);
$inputObj->SetUserID($userId);
$inputObj->SetMobile($mobile);
$inputObj->SetRiskLevel('3');

$gateway = 'http://121.15.180.66:801';
$result = CmbApi::pay($gateway, $inputObj);

echo $result;