<?php
header('Content-Type:application/json; charset=utf-8');
ini_set('date.timezone','Asia/Shanghai');

require_once 'lib/Cmb.Request.php';
require_once 'lib/Cmb.Builder.php';
require_once 'lib/log.php';

$logHandler= new CLogFileHandler("logs/".date('Y-m-d').'.ccb.log');
$log = Log::Init($logHandler, 15);

//参数
$merchantSerialNo = '1000000001';
$agrNo = '100000000000001';
$attach = 'XX贸易有限公司';
$amount = '1'; //单位为分

//支付
$inputObj = new CmbScanPay();
$inputObj->SetAgrNo($agrNo);
$inputObj->SetMerchantSerialNo($merchantSerialNo);
$inputObj->SetAmount($amount);
$inputObj->SetTrnAbs($attach);
$inputObj->SetNoticeUrl('http://yourserver/bank/cmb/payment-notice.php');

$gateway = 'http://121.15.180.72';
$result = CmbApi::scanPay($gateway, $inputObj);

echo json_encode($result);