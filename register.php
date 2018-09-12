<?php
ini_set('date.timezone','Asia/Shanghai');
header('Content-Type:text/html; charset=utf-8');

require_once 'lib/Cmb.Request.php';
require_once 'lib/Cmb.Builder.php';
require_once 'lib/log.php';

$logHandler= new CLogFileHandler("logs/".date('Y-m-d').'.ccb.log');
$log = Log::Init($logHandler, 15);

//参数
$merchantSerialNo = '100000000000001';
$agrNo = '100000000000001';
$mobile = '13600000000';
$userId = '100000000000001';

//签约
$inputObj = new CmbSignContract();
$inputObj->SetMerchantSerialNo($merchantSerialNo);
$inputObj->SetAgrNo($agrNo);
$inputObj->SetMobile($mobile);
$inputObj->SetUserID($userId);
$inputObj->SetNoticeUrl('http://yourserver/bank/cmb/register-notice.php');
$inputObj->SetNoticePara('');
$inputObj->SetReturnUrl('http://yourserver/bank/cmb/test.php');

$gateway = 'http://121.15.180.66:801';
$result = CmbApi::signContract($gateway, $inputObj);

echo $result;