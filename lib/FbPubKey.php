<?php
ini_set('date.timezone','Asia/Shanghai');
require_once 'Cmb.Request.php';
require_once 'Cmb.Builder.php';

/**
 * 获取招行公钥
 */
class FbPubKey
{
    /**
     * fbPubKey: 招行公钥
     */
    public static function getFbPubKey($pubKey="tmps/FbPubKey.Config.php") {
        $pubKeyArr = array();
        $flag = false;
        if (file_exists($pubKey)) {
            $pubKeyArr = json_decode(substr(file_get_contents($pubKey), 14), true);
        }
        if (isset($pubKeyArr['dateTime']) && isset($pubKeyArr['fbPubKey'])) {
            if (date('Ymd', strtotime($pubKeyArr['dateTime'])) < date('Ymd') && date('Hi') > '0215') {
                $flag = true;
            }
        } else {
            $flag = true;
        }
        if ($flag) {
            //重新获取招行公钥
            $inputObj = new CmbPubKeyQuery();
            //请求地址
            $gateway = 'http://121.15.180.72';
            $result = CmbApi::queryCmbPubKey($gateway, $inputObj);
            file_put_contents($pubKey, '<?php die();//' . json_encode($result['rspData']));
            return $result['rspData']['fbPubKey'];

        } else {
            return $pubKeyArr['fbPubKey'];
        }
        
    }

}