<?php
ini_set('date.timezone','Asia/Shanghai');

require_once 'lib/Cmb.Request.php';
require_once 'lib/Cmb.Builder.php';
require_once 'lib/log.php';

$logHandler= new CLogFileHandler("logs/".date('Y-m-d').'.ccb.log');
$log = Log::Init($logHandler, 15);

//获取通知的数据
$json = $_POST;
Log::DEBUG("register-notice:" . json_encode($json, JSON_UNESCAPED_UNICODE));
try {
    $result = CmbApi::signNotice($json['jsonRequestData']);
    //*****************************//
    // 执行业务处理逻辑
    //*****************************//
} catch(Exception $e) {
    Log::DEBUG("register-notice error:" . $e);
    header('HTTP/1.1 400');
}
