<?php
require_once "Cmb.Config.php";
require_once "Cmb.Exception.php";
require_once "fbPubKey.php";

/**
 * 
 * 数据对象基础类，该类中定义数据类最基本的行为，包括：
 * 计算/设置/获取签名、输出json格式的参数、从json读取数据对象等
 * @author moz
 *
 */
class CmbBuilderBase
{
    protected $values = array();
    protected $reqData = array();
    protected $rspData = array();
    protected $noticeData = array();

    /**
    * 设置接口版本号,固定为“1.0”
    * @param string $value 
    **/
    public function SetVersion()
    {
        $this->values['version'] = '1.0';
        return '1.0';
    }
    
    /**
    * 获取接口版本号
    * @return 值
    **/
    public function GetVersion()
    {
        return $this->values['version'];
    }
    
    /**
    * 设置参数编码,固定为“UTF-8”
    * @param string $value 
    **/
    public function SetCharset()
    {
        $this->values['charset'] = 'UTF-8';
        return 'UTF-8';
    }
    
    /**
    * 获取参数编码
    * @return 值
    **/
    public function GetCharset()
    {
        return $this->values['charset'];
    }
    
    /**
    * 设置签名，详见签名生成算法
    * @param string $value 
    **/
    public function SetSign($signArr)
    {
        $sign = $this->MakeSign($signArr);
        $this->values['sign'] = $sign;
        return $sign;
    }
    
    /**
    * 获取签名，详见签名生成算法的值
    * @return 值
    **/
    public function GetSign()
    {
        return $this->values['sign'];
    }
    
    /**
    * 判断签名，详见签名生成算法是否存在
    * @return true 或 false
    **/
    public function IsSignSet()
    {
        return array_key_exists('sign', $this->values);
    }

    /**
    * 设置签名算法,固定为“SHA-256”
    * @param string $value 
    **/
    public function SetSignType($value='SHA-256')
    {
        $this->values['signType'] = $value;
        return $value;
    }
    
    /**
    * 获取签名算法
    * @return 值
    **/
    public function GetSignType()
    {
        return $this->values['signType'];
    }

    /**
    * 设置请求数据
    * @param string $value 
    **/
    public function SetReqData()
    {
        $this->values['reqData'] = $this->reqData;
        return $this->reqData;
    }
    
    /**
    * 获取请求数据
    * @return 值
    **/
    public function GetReqData()
    {
        return $this->values['reqData'];
    }

    /**
    * 设置响应数据
    * @param string $value 
    **/
    public function SetRspData()
    {
        $this->values['rspData'] = $this->rspData;
        return $this->rspData;
    }
    
    /**
    * 获取响应数据
    * @return 值
    **/
    public function GetRspData()
    {
        return $this->values['rspData'];
    }

    /**
    * 设置响应数据
    * @param string $value 
    **/
    public function SetNoticeData()
    {
        $this->values['noticeData'] = $this->noticeData;
        return $this->noticeData;
    }
    
    /**
    * 获取响应数据
    * @return 值
    **/
    public function GetNoticeData()
    {
        return $this->values['noticeData'];
    }
    
    /**
     * 输出json字符
     * @throws CmbException
    **/
    public function ToJson()
    {
        if(!is_array($this->values) 
            || count($this->values) <= 0)
        {
            throw new CmbException("数组数据异常！");
        }
        
        return mb_convert_encoding(json_encode($this->values, JSON_UNESCAPED_UNICODE), $this->GetCharset());
    }
    
    /**
     * 将json转为array
     * @param string $json
     * @throws CmbException
     */
    public function FromJson($json)
    {    
        if(!$json) {
            throw new CmbException("json数据异常！");
        }
        //将JSON转为array
        $this->values = json_decode($json, true);
        if(array_key_exists('rspData', $this->values)) {
            $this->rspData = $this->values['rspData'];
        }
        if(array_key_exists('noticeData', $this->values)) {
            $this->noticeData = $this->values['noticeData'];
        }
        return $this->values;
    }
    
    /**
     * 格式化参数格式化成url参数
     */
    public function ToUrlParams($signArr)
    {
        $buff = "";
        foreach ($signArr as $k => $v)
        {
            if($k != "sign" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }
        
        $buff = trim($buff, "&");
        return $buff;
    }
    
    /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function MakeSign($signArr)
    {
        //签名步骤一：对待验签的所有请求参数按从a到z的字典顺序排列
        ksort($signArr);
        $string = $this->ToUrlParams($signArr);
        //签名步骤二：将商户签名密钥增加“&”附加到待签名字符串后
        $string = $string . "&".CmbConfig::MERKEY;
        //签名步骤三：对strToSign&merkey进行sha256签名运算获得签名结果byte数组
        $string = hash('sha256', $string);
        //签名步骤四：将byte数组转换为16进制即为最终的签名结果
        //$result = bin2hex($string);
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 使用招行公钥验证通知签名
     * @return boolen 验签结果
     */
    public function VerifyNoticeSign($signArr)
    {
        //公钥
        $pub_key = FbPubKey::getFbPubKey();
        //待验证签名字符串
        ksort($signArr);
        $toSign_str = $this->ToUrlParams($signArr);
        //签名结果
        $sig_dat = $this->GetSign();
        //处理证书
        $pem = chunk_split($pub_key, 64, "\n");
        $pem = "-----BEGIN PUBLIC KEY-----\n" . $pem . "-----END PUBLIC KEY-----\n";
        $pkid = openssl_pkey_get_public($pem);
        if (empty($pkid)) {
            return false;
        }
        //验证
        $res = openssl_verify($toSign_str, base64_decode($sig_dat), $pkid, OPENSSL_ALGO_SHA1);
        if ($res) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 获取设置的值
     */
    public function GetValues()
    {
        return $this->values;
    }
}

/**
 * 
 * 接口调用结果类
 * @author huache
 *
 */
class CmbResults extends CmbBuilderBase
{
    /**
     * 
     * 检测签名
     */
    public function CheckSign()
    {
        //fix异常
        if(!$this->IsSignSet()){
            throw new CmbException("签名错误！");
        }
        
        $sign = $this->MakeSign($this->rspData);
        if($this->GetSign() == $sign){
            return true;
        }
        throw new CmbException("签名错误！");
    }
    
    /**
     * 
     * 使用数组初始化
     * @param array $array
     */
    public function FromArray($array)
    {
        $this->values = $array;
    }
    
    /**
     * 
     * 使用数组初始化对象
     * @param array $array
     * @param 是否检测签名 $noCheckSign
     */
    public static function InitFromArray($array, $noCheckSign = false)
    {
        $obj = new self();
        $obj->FromArray($array);
        if($noCheckSign == false){
            $obj->CheckSign();
        }
        return $obj;
    }
    
    /**
     * 
     * 设置参数
     * @param string $key
     * @param string $value
     */
    public function SetData($key, $value)
    {
        $this->values[$key] = $value;
    }
    
    /**
     * 将json字符串转为array
     * @param string $json
     * @throws CmbException
     */
    public static function Init($json)
    {    
        $obj = new self();
        $obj->FromJson($json);
        if($obj->rspData['rspCode'] != 'SUC0000'){
            return $obj->GetValues();
        }
        $obj->CheckSign();
        return $obj->GetValues();
    }
}

/**
 * 
 * 一网通支付
 * @author huache
 *
 */
class CmbPay extends CmbBuilderBase
{    
    /**
     * 设置请求时间
     * @param string $value 
     */
    public function SetDateTime($value)
    {
        $this->reqData['dateTime'] = $value;
    }

    /**
     * 获取请求时间
     * @return 值
     */
    public function GetDateTime()
    {
        return $this->reqData['dateTime'];
    }

    /**
     * 设置分行号，4位数字
     * @param string $value 
     */
    public function SetBranchNo($value)
    {
        $this->reqData['branchNo'] = $value;
    }

    /**
     * 获取分行号，4位数字
     * @return 值
     */
    public function GetBranchNo()
    {
        return $this->reqData['branchNo'];
    }

    /**
     * 设置商户号，6位数字
     * @param string $value 
     */
    public function SetMerchantNo($value)
    {
        $this->reqData['merchantNo'] = $value;
    }

    /**
     * 获取商户号，6位数字
     * @return 值
     */
    public function GetMerchantNo()
    {
        return $this->reqData['merchantNo'];
    }

    /**
     * 设置订单日期,格式：yyyyMMdd
     * @param string $value 
     */
    public function SetDate($value)
    {
        $this->reqData['date'] = $value;
    }

    /**
     * 获取订单日期,格式：yyyyMMdd
     * @return 值
     */
    public function GetDate()
    {
        return $this->reqData['date'];
    }

    /**
     * 设置订单号。 支持6-32位（含6和32）间任意位数的订单号，支持不固定位数，支持数字+字母（大小字母）随意组合。由商户生成，一天内不能重复。 订单日期+订单号唯一定位一笔订单。
     * @param string $value 
     */
    public function SetOrderNo($value)
    {
        $this->reqData['orderNo'] = $value;
    }

    /**
     * 获取订单号。 支持6-32位（含6和32）间任意位数的订单号，支持不固定位数，支持数字+字母（大小字母）随意组合。由商户生成，一天内不能重复。 订单日期+订单号唯一定位一笔订单。
     * @return 值
     */
    public function GetOrderNo()
    {
        return $this->reqData['orderNo'];
    }

    /**
     * 设置金额，格式：xxxx.xx
     * @param string $value 
     */
    public function SetAmount($value)
    {
        $this->reqData['amount'] = $value;
    }

    /**
     * 获取金额，格式：xxxx.xx
     * @return 值
     */
    public function GetAmount()
    {
        return $this->reqData['amount'];
    }

    /**
     * 设置过期时间跨度，必须为大于零的整数，单位为分钟。该参数指定当前支付请求必须在指定时间跨度内完成（从系统收到支付请求开始计时），否则按过期处理。一般适用于航空客票等对交易完成时间敏感的支付请求。
     * @param string $value 
     */
    public function SetExpireTimeSpan($value)
    {
        $this->reqData['expireTimeSpan'] = $value;
    }

    /**
     * 获取过期时间跨度，必须为大于零的整数，单位为分钟。该参数指定当前支付请求必须在指定时间跨度内完成（从系统收到支付请求开始计时），否则按过期处理。一般适用于航空客票等对交易完成时间敏感的支付请求。
     * @return 值
     */
    public function GetExpireTimeSpan()
    {
        return $this->reqData['expireTimeSpan'];
    }

    /**
     * 设置商户接收成功支付结果通知的地址。
     * @param string $value 
     */
    public function SetPayNoticeUrl($value)
    {
        $this->reqData['payNoticeUrl'] = $value;
    }

    /**
     * 获取商户接收成功支付结果通知的地址。
     * @return 值
     */
    public function GetPayNoticeUrl()
    {
        return $this->reqData['payNoticeUrl'];
    }

    /**
     * 设置成功支付结果通知附加参数,该参数在发送成功支付结果通知时，将原样返回商户注意：该参数可为空，商户如果需要不止一个参数，可以自行把参数组合、拼装，但组合后的结果不能带有’&’字符。
     * @param string $value 
     */
    public function SetPayNoticePara($value)
    {
        $this->reqData['payNoticePara'] = $value;
    }

    /**
     * 获取成功支付结果通知附加参数,该参数在发送成功支付结果通知时，将原样返回商户注意：该参数可为空，商户如果需要不止一个参数，可以自行把参数组合、拼装，但组合后的结果不能带有’&’字符。
     * @return 值
     */
    public function GetPayNoticePara()
    {
        return $this->reqData['payNoticePara'];
    }

    /**
     * 设置成功页返回商户地址，支付成功页面上“返回商户”按钮跳转地址。为空则不显示返回商户按钮。原生APP可传入一个特定地址（例如:Http://CMBNPRM），并拦截处理自行决定跳转交互。
     * @param string $value 
     */
    public function SetReturnUrl($value)
    {
        $this->reqData['returnUrl'] = $value;
    }

    /**
     * 获取成功页返回商户地址，支付成功页面上“返回商户”按钮跳转地址。为空则不显示返回商户按钮。原生APP可传入一个特定地址（例如:Http://CMBNPRM），并拦截处理自行决定跳转交互。
     * @return 值
     */
    public function GetReturnUrl()
    {
        return $this->reqData['returnUrl'];
    }

    /**
     * 设置商户取得的客户IP，如果有多个IP用逗号”,”分隔。
     * @param string $value 
     */
    public function SetClientIP($value)
    {
        $this->reqData['clientIP'] = $value;
    }

    /**
     * 获取商户取得的客户IP，如果有多个IP用逗号”,”分隔。
     * @return 值
     */
    public function GetClientIP()
    {
        return $this->reqData['clientIP'];
    }

    /**
     * 设置允许支付的卡类型，默认对支付卡种不做限制，储蓄卡和信用卡均可支付
     * @param string $value 
     */
    public function SetCardType($value)
    {
        $this->reqData['cardType'] = $value;
    }

    /**
     * 允许支付的卡类型，默认对支付卡种不做限制，储蓄卡和信用卡均可支付
     * @return 值
     */
    public function GetCardType()
    {
        return $this->reqData['cardType'];
    }

    /**
     * 设置客户协议号。支持数字、字母（大小字母）、“-””_”两个特殊字符（注意-_是英文半角的）。未签约（首次支付）客户，填写新协议号，用于协议开通；已签约（再次支付）客户，填写该客户已有的协议号。商户必须对协议号进行管理，确保客户与协议号一一对应。
     * @param string $value 
     */
    public function SetAgrNo($value)
    {
        $this->reqData['agrNo'] = $value;
    }

    /**
     * 获取客户协议号。支持数字、字母（大小字母）、“-””_”两个特殊字符（注意-_是英文半角的）。未签约（首次支付）客户，填写新协议号，用于协议开通；已签约（再次支付）客户，填写该客户已有的协议号。商户必须对协议号进行管理，确保客户与协议号一一对应。
     * @return 值
     */
    public function GetAgrNo()
    {
        return $this->reqData['agrNo'];
    }

    /**
     * 设置协议开通请求流水号，开通协议时必填。
     * @param string $value 
     */
    public function SetMerchantSerialNo($value)
    {
        $this->reqData['merchantSerialNo'] = $value;
    }

    /**
     * 获取协议开通请求流水号，开通协议时必填。
     * @return 值
     */
    public function GetMerchantSerialNo()
    {
        return $this->reqData['merchantSerialNo'];
    }

    /**
     * 设置商户用户ID,用于标识商户用户的唯一ID。商户系统内用户唯一标识，不超过20位，数字字母都可以，建议纯数字
     * @param string $value 
     */
    public function SetUserID($value)
    {
        $this->reqData['userID'] = $value;
    }

    /**
     * 获取商户用户ID,用于标识商户用户的唯一ID。商户系统内用户唯一标识，不超过20位，数字字母都可以，建议纯数字
     * @return 值
     */
    public function GetUserID()
    {
        return $this->reqData['userID'];
    }

    /**
     * 设置商户用户的手机号
     * @param string $value 
     */
    public function SetMobile($value)
    {
        $this->reqData['mobile'] = $value;
    }

    /**
     * 获取商户用户的手机号
     * @return 值
     */
    public function GetMobile()
    {
        return $this->reqData['mobile'];
    }

    /**
     * 设置经度，商户app获取的手机定位数据，如30.949505
     * @param string $value 
     */
    public function SetLon($value)
    {
        $this->reqData['lon'] = $value;
    }

    /**
     * 获取经度，商户app获取的手机定位数据，如30.949505
     * @return 值
     */
    public function GetLon()
    {
        return $this->reqData['lon'];
    }

    /**
     * 设置纬度，商户app获取的手机定位数据，如50.949506
     * @param string $value 
     */
    public function SetLat($value)
    {
        $this->reqData['lat'] = $value;
    }

    /**
     * 获取纬度，商户app获取的手机定位数据，如50.949506
     * @return 值
     */
    public function GetLat()
    {
        return $this->reqData['lat'];
    }

    /**
     * 设置风险等级,用户在商户系统内风险等级标识
     * @param string $value 
     */
    public function SetRiskLevel($value)
    {
        $this->reqData['riskLevel'] = $value;
    }

    /**
     * 获取风险等级,用户在商户系统内风险等级标识
     * @return 值
     */
    public function GetRiskLevel()
    {
        return $this->reqData['riskLevel'];
    }

    /**
     * 设置成功签约结果通知地址,首次签约，必填。商户接收成功签约结果通知的地址。
     * @param string $value 
     */
    public function SetSignNoticeUrl($value)
    {
        $this->reqData['signNoticeUrl'] = $value;
    }

    /**
     * 获取成功签约结果通知地址,首次签约，必填。商户接收成功签约结果通知的地址。
     * @return 值
     */
    public function GetSignNoticeUrl()
    {
        return $this->reqData['signNoticeUrl'];
    }

    /**
     * 设置成功签约结果通知附加参数,该参数在发送成功签约结果通知时，将原样返回商户
     * @param string $value 
     */
    public function SetSignNoticePara($value)
    {
        $this->reqData['signNoticePara'] = $value;
    }

    /**
     * 获取成功签约结果通知附加参数,该参数在发送成功签约结果通知时，将原样返回商户
     * @return 值
     */
    public function GetSignNoticePara()
    {
        return $this->reqData['signNoticePara'];
    }

    /**
     * 设置扩展信息，json格式写入的扩展信息，并使用extendInfoEncrypType指定的算法加密使用详见扩展信息注意：1.加密后的密文必须转换为16进制字符串2.如果扩展信息字段非空，该字段必填
     * @param string $value 
     */
    public function SetExtendInfo($value)
    {
        $this->reqData['extendInfo'] = $value;
    }

    /**
     * 获取扩展信息，json格式写入的扩展信息，并使用extendInfoEncrypType指定的算法加密使用详见扩展信息注意：1.加密后的密文必须转换为16进制字符串2.如果扩展信息字段非空，该字段必填
     * @return 值
     */
    public function GetExtendInfo()
    {
        return $this->reqData['extendInfo'];
    }

    /**
     * 设置扩展信息的加密算法,扩展信息加密类型，取值为RC4或DES
     * @param string $value 
     */
    public function SetExtendInfoEncrypType($value)
    {
        $this->reqData['extendInfoEncrypType'] = $value;
    }

    /**
     * 获取扩展信息的加密算法,扩展信息加密类型，取值为RC4或DES
     * @return 值
     */
    public function GetExtendInfoEncrypType()
    {
        return $this->reqData['extendInfoEncrypType'];
    }
}

/**
 * 
 * 一网通刷卡/扫码支付
 * @author huache
 *
 */
class CmbScanPay extends CmbBuilderBase
{
    /**
     * 设置商户发起该请求的当前时间，精确到秒格式：yyyyMMddHHmmss
     * @param string $value 
     */
    public function SetDateTime($value)
    {
        $this->reqData['dateTime'] = $value;
    }

    /**
     * 获取商户发起该请求的当前时间，精确到秒格式：yyyyMMddHHmmss
     * @return 值
     */
    public function GetDateTime()
    {
        return $this->reqData['dateTime'];
    }

    /**
     * 设置交易码,固定为“FBPK”
     * @param string $value 
     */
    public function SetTxCode($value)
    {
        $this->reqData['txCode'] = $value;
    }

    /**
     * 获取交易码,固定为“FBPK”
     * @return 值
     */
    public function GetTxCode()
    {
        return $this->reqData['txCode'];
    }

    /**
     * 设置商户分行号，4位数字
     * @param string $value 
     */
    public function SetBranchNo($value)
    {
        $this->reqData['branchNo'] = $value;
    }

    /**
     * 获取商户分行号，4位数字
     * @return 值
     */
    public function GetBranchNo()
    {
        return $this->reqData['branchNo'];
    }

    /**
     * 设置商户号，6位数字
     * @param string $value 
     */
    public function SetMerchantNo($value)
    {
        $this->reqData['merchantNo'] = $value;
    }

    /**
     * 获取商户号，6位数字
     * @return 值
     */
    public function GetMerchantNo()
    {
        return $this->reqData['merchantNo'];
    }

    /**
     * 设置商户交易流水号，全局唯一
     * @param string $value 
     */
    public function SetMerchantSerialNo($value)
    {
        $this->reqData['merchantSerialNo'] = $value;
    }

    /**
     * 获取商户交易流水号，全局唯一
     * @return 值
     */
    public function GetMerchantSerialNo()
    {
        return $this->reqData['merchantSerialNo'];
    }

    /**
     * 设置客户签约的协议号
     * @param string $value 
     */
    public function SetAgrNo($value)
    {
        $this->reqData['agrNo'] = $value;
    }

    /**
     * 获取客户签约的协议号
     * @return 值
     */
    public function GetAgrNo()
    {
        return $this->reqData['agrNo'];
    }

    /**
     * 设置币种，目前只支持人民币，固定为 RMB
     * @param string $value 
     */
    public function SetCurrency($value)
    {
        $this->reqData['currency'] = $value;
    }

    /**
     * 获取币种，目前只支持人民币，固定为 RMB
     * @return 值
     */
    public function GetCurrency()
    {
        return $this->reqData['currency'];
    }

    /**
     * 设置交易金额，以分为单位，如2000表示20.00元
     * @param string $value 
     */
    public function SetAmount($value)
    {
        $this->reqData['amount'] = $value;
    }

    /**
     * 获取交易金额，以分为单位，如2000表示20.00元
     * @return 值
     */
    public function GetAmount()
    {
        return $this->reqData['amount'];
    }

    /**
     * 设置交易摘要，简要描述交易的关键信息
     * @param string $value 
     */
    public function SetTrnAbs($value)
    {
        $this->reqData['trnAbs'] = $value;
    }

    /**
     * 获取交易摘要，简要描述交易的关键信息
     * @return 值
     */
    public function GetTrnAbs()
    {
        return $this->reqData['trnAbs'];
    }

    /**
     * 设置结果异步通知URL (地址仅支持http 80端口和https 443端口），用于银行异步发送交易结果
     * @param string $value 
     */
    public function SetNoticeUrl($value)
    {
        $this->reqData['noticeUrl'] = $value;
    }

    /**
     * 获取结果异步通知URL (地址仅支持http 80端口和https 443端口），用于银行异步发送交易结果
     * @return 值
     */
    public function GetNoticeUrl()
    {
        return $this->reqData['noticeUrl'];
    }
}

/**
 * 
 * 查询招行公钥
 * @author huache
 *
 */
class CmbPubKeyQuery extends CmbBuilderBase
{
    /**
     * 设置商户发起该请求的当前时间，精确到秒格式：yyyyMMddHHmmss
     * @param string $value 
     */
    public function SetDateTime($value)
    {
        $this->reqData['dateTime'] = $value;
    }

    /**
     * 获取商户发起该请求的当前时间，精确到秒格式：yyyyMMddHHmmss
     * @return 值
     */
    public function GetDateTime()
    {
        return $this->reqData['dateTime'];
    }

    /**
     * 设置交易码,固定为“FBPK”
     * @param string $value 
     */
    public function SetTxCode($value)
    {
        $this->reqData['txCode'] = $value;
    }

    /**
     * 获取交易码,固定为“FBPK”
     * @return 值
     */
    public function GetTxCode()
    {
        return $this->reqData['txCode'];
    }

    /**
     * 设置商户分行号，4位数字
     * @param string $value 
     */
    public function SetBranchNo($value)
    {
        $this->reqData['branchNo'] = $value;
    }

    /**
     * 获取商户分行号，4位数字
     * @return 值
     */
    public function GetBranchNo()
    {
        return $this->reqData['branchNo'];
    }

    /**
     * 设置商户号，6位数字
     * @param string $value 
     */
    public function SetMerchantNo($value)
    {
        $this->reqData['merchantNo'] = $value;
    }

    /**
     * 获取商户号，6位数字
     * @return 值
     */
    public function GetMerchantNo()
    {
        return $this->reqData['merchantNo'];
    }
}

/**
 * 
 * 成功签约结果通知
 * @author huache
 *
 */
class CmbSignNotice extends  CmbBuilderBase
{
    /**
     * 
     * 检测签名
     */
    public function CheckSign()
    {
        //fix异常
        if(!$this->IsSignSet()){
            throw new CmbException("签名错误！");
        }
        
        $verify = $this->VerifyNoticeSign($this->noticeData);
        if($verify){
            return true;
        }
        throw new CmbException("签名错误！");
    }
    
    /**
     * 
     * 使用数组初始化
     * @param array $array
     */
    public function FromArray($array)
    {
        $this->values = $array;
    }
    
    /**
     * 
     * 使用数组初始化对象
     * @param array $array
     * @param 是否检测签名 $noCheckSign
     */
    public static function InitFromArray($array, $noCheckSign = false)
    {
        $obj = new self();
        $obj->FromArray($array);
        if($noCheckSign == false){
            $obj->CheckSign();
        }
        return $obj;
    }
    
    /**
     * 
     * 设置参数
     * @param string $key
     * @param string $value
     */
    public function SetData($key, $value)
    {
        $this->values[$key] = $value;
    }
    
    /**
     * 将json字符串转为array
     * @param string $json
     * @throws CmbException
     */
    public static function Init($json)
    {    
        $obj = new self();
        $obj->FromJson($json);
        if($obj->noticeData['rspCode'] != 'SUC0000'){
            return $obj->GetValues();
        }
        $obj->CheckSign();
        return $obj->GetValues();
    }
}

/**
 * 
 * 成功支付结果通知
 * @author huache
 *
 */
class CmbPayNotice extends  CmbBuilderBase
{
    /**
     * 
     * 检测签名
     */
    public function CheckSign()
    {
        //fix异常
        if(!$this->IsSignSet()){
            throw new CmbException("签名错误！");
        }
        
        $verify = $this->VerifyNoticeSign($this->noticeData);
        if($verify){
            return true;
        }
        throw new CmbException("签名错误！");
    }
    
    /**
     * 
     * 使用数组初始化
     * @param array $array
     */
    public function FromArray($array)
    {
        $this->values = $array;
    }
    
    /**
     * 
     * 使用数组初始化对象
     * @param array $array
     * @param 是否检测签名 $noCheckSign
     */
    public static function InitFromArray($array, $noCheckSign = false)
    {
        $obj = new self();
        $obj->FromArray($array);
        if($noCheckSign == false){
            $obj->CheckSign();
        }
        return $obj;
    }
    
    /**
     * 
     * 设置参数
     * @param string $key
     * @param string $value
     */
    public function SetData($key, $value)
    {
        $this->values[$key] = $value;
    }
    
    /**
     * 将json字符串转为array
     * @param string $json
     * @throws CmbException
     */
    public static function Init($json)
    {    
        $obj = new self();
        $obj->FromJson($json);
        if($obj->noticeData['rspCode'] != 'SUC0000'){
            return $obj->GetValues();
        }
        $obj->CheckSign();
        return $obj->GetValues();
    }
}

/**
 * 签约
 * @author huache
 */
class CmbSignContract extends CmbBuilderBase
{
    /**
     * 设置请求时间,商户发起该请求的当前时间，精确到秒。格式：yyyyMMddHHmmss
     * @param string $value 
     */
    public function SetDateTime($value)
    {
        $this->reqData['dateTime'] = $value;
    }

    /**
     * 获取请求时间
     * @return 值
     */
    public function GetDateTime()
    {
        return $this->reqData['dateTime'];
    }

    /**
     * 设置协议开通请求流水号,商户生成，同一交易日期唯一，长度不超过20位，数字字母都可以，建议纯数字
     * @param string $value 
     */
    public function SetMerchantSerialNo($value)
    {
        $this->reqData['merchantSerialNo'] = $value;
    }

    /**
     * 获取协议开通请求流水号
     * @return 值
     */
    public function GetMerchantSerialNo()
    {
        return $this->reqData['merchantSerialNo'];
    }

    /**
     * 设置客户协议号。不超过32位的数字字母组合。未签约（首次支付）客户，填写新协议号，用于协议开通；已签约（再次支付）客户，填写该客户已有的协议号。商户必须对协议号进行管理，确保客户与协议号一一对应。
     * @param string $value 
     */
    public function SetAgrNo($value)
    {
        $this->reqData['agrNo'] = $value;
    }

    /**
     * 获取客户协议号
     * @return 值
     */
    public function GetAgrNo()
    {
        return $this->reqData['agrNo'];
    }

    /**
     * 设置商户分行号，4位数字
     * @param string $value 
     */
    public function SetBranchNo($value)
    {
        $this->reqData['branchNo'] = $value;
    }

    /**
     * 获取商户分行号，4位数字
     * @return 值
     */
    public function GetBranchNo()
    {
        return $this->reqData['branchNo'];
    }

    /**
     * 设置商户号，6位数字
     * @param string $value 
     */
    public function SetMerchantNo($value)
    {
        $this->reqData['merchantNo'] = $value;
    }

    /**
     * 获取商户号，6位数字
     * @return 值
     */
    public function GetMerchantNo()
    {
        return $this->reqData['merchantNo'];
    }

    /**
     * 设置商户用户的手机号
     * @param string $value 
     */
    public function SetMobile($value)
    {
        $this->reqData['mobile'] = $value;
    }

    /**
     * 获取商户用户的手机号
     * @return 值
     */
    public function GetMobile()
    {
        return $this->reqData['mobile'];
    }

    /**
     * 设置用于标识商户用户的唯一ID。商户系统内用户唯一标识，不超过20位，数字字母都可以，建议纯数字
     * @param string $value 
     */
    public function SetUserID($value)
    {
        $this->reqData['userID'] = $value;
    }

    /**
     * 获取用于标识商户用户的唯一ID。商户系统内用户唯一标识，不超过20位，数字字母都可以，建议纯数字
     * @return 值
     */
    public function GetUserID()
    {
        return $this->reqData['userID'];
    }

    /**
     * 设置经度，商户app获取的手机定位数据
     * @param string $value 
     */
    public function SetLon($value)
    {
        $this->reqData['lon'] = $value;
    }

    /**
     * 获取经度，商户app获取的手机定位数据
     * @return 值
     */
    public function GetLon()
    {
        return $this->reqData['lon'];
    }

    /**
     * 设置纬度，商户app获取的手机定位数据
     * @param string $value 
     */
    public function SetLat($value)
    {
        $this->reqData['lat'] = $value;
    }

    /**
     * 获取纬度，商户app获取的手机定位数据
     * @return 值
     */
    public function GetLat()
    {
        return $this->reqData['lat'];
    }

    /**
     * 设置用户在商户系统内风险等级标识
     * @param string $value 
     */
    public function SetRiskLevel($value)
    {
        $this->reqData['riskLevel'] = $value;
    }

    /**
     * 获取用户在商户系统内风险等级标识
     * @return 值
     */
    public function GetRiskLevel()
    {
        return $this->reqData['riskLevel'];
    }

    /**
     * 设置商户接收成功签约结果通知的地址。
     * @param string $value 
     */
    public function SetNoticeUrl($value)
    {
        $this->reqData['noticeUrl'] = $value;
    }

    /**
     * 获取商户接收成功签约结果通知的地址。
     * @return 值
     */
    public function GetNoticeUrl()
    {
        return $this->reqData['noticeUrl'];
    }

    /**
     * 设置该参数在发送成功签约结果通知时，将原样返回商户.注意：该参数可为空，商户如果需要不止一个参数，可以自行把参数组合、拼装，但组合后的结果不能带有‘&’字符。
     * @param string $value 
     */
    public function SetNoticePara($value)
    {
        $this->reqData['noticePara'] = $value;
    }

    /**
     * 获取该参数在发送成功签约结果通知时
     * @return 值
     */
    public function GetNoticePara()
    {
        return $this->reqData['noticePara'];
    }

    /**
     * 设置签约成功页面上“返回商户”按钮跳转地址，默认值：http://CMBNPRM，采用默认值的需要商户app拦截该请求，自行决定跳转交互
     * @param string $value 
     */
    public function SetReturnUrl($value)
    {
        $this->reqData['returnUrl'] = $value;
    }

    /**
     * 获取签约成功页面上“返回商户”按钮跳转地址
     * @return 值
     */
    public function GetReturnUrl()
    {
        return $this->reqData['returnUrl'];
    }
}

/**
 * 按商户日期查询已结账订单
 * @author huache
 */
class CmbQuerySettledOrder extends CmbBuilderBase
{
    /**
     * 设置请求时间,商户发起该请求的当前时间，精确到秒。格式：yyyyMMddHHmmss
     * @param string $value 
     */
    public function SetDateTime($value)
    {
        $this->reqData['dateTime'] = $value;
    }

    /**
     * 获取请求时间
     * @return 值
     */
    public function GetDateTime()
    {
        return $this->reqData['dateTime'];
    }

    /**
     * 设置商户分行号，4位数字
     * @param string $value 
     */
    public function SetBranchNo($value)
    {
        $this->reqData['branchNo'] = $value;
    }

    /**
     * 获取商户分行号
     * @return 值
     */
    public function GetBranchNo()
    {
        return $this->reqData['branchNo'];
    }

    /**
     * 设置商户号，6位数字
     * @param string $value 
     */
    public function SetMerchantNo($value)
    {
        $this->reqData['merchantNo'] = $value;
    }

    /**
     * 获取商户号
     * @return 值
     */
    public function GetMerchantNo()
    {
        return $this->reqData['merchantNo'];
    }

    /**
     * 设置开始日期,格式：yyyyMMdd
     * @param string $value 
     */
    public function SetBeginDate($value)
    {
        $this->reqData['beginDate'] = $value;
    }

    /**
     * 获取开始日期
     * @return 值
     */
    public function GetBeginDate()
    {
        return $this->reqData['beginDate'];
    }

    /**
     * 设置结束日期,格式：yyyyMMdd
     * @param string $value 
     */
    public function SetEndDate($value)
    {
        $this->reqData['endDate'] = $value;
    }

    /**
     * 获取结束日期
     * @return 值
     */
    public function GetEndDate()
    {
        return $this->reqData['endDate'];
    }

    /**
     * 设置操作员号,商户结账系统的操作员号
     * @param string $value 
     */
    public function SetOperatorNo($value)
    {
        $this->reqData['operatorNo'] = $value;
    }

    /**
     * 获取操作员号
     * @return 值
     */
    public function GetOperatorNo()
    {
        return $this->reqData['operatorNo'];
    }

    /**
     * 设置续传键值,长度只能为0或40；首次查询填“空”；后续查询，按应答报文中返回的nextKeyValue值原样传入。
     * @param string $value 
     */
    public function SetNextKeyValue($value)
    {
        $this->reqData['nextKeyValue'] = $value;
    }

    /**
     * 获取续传键值
     * @return 值
     */
    public function GetNextKeyValue()
    {
        return $this->reqData['nextKeyValue'];
    }
}

/**
 * 查询入账明细
 * @author huache
 */
class CmbQueryAccountList extends CmbBuilderBase
{
    /**
     * 设置请求时间，商户发起该请求的当前时间，精确到秒。格式：yyyyMMddHHmmss
     * @param string $value 
     */
    public function SetDateTime($value)
    {
        $this->reqData['dateTime'] = $value;
    }

    /**
     * 获取请求时间
     * @return 值
     */
    public function GetDateTime()
    {
        return $this->reqData['dateTime'];
    }

    /**
     * 设置商户分行号，4位数字
     * @param string $value 
     */
    public function SetBranchNo($value)
    {
        $this->reqData['branchNo'] = $value;
    }

    /**
     * 获取商户分行号
     * @return 值
     */
    public function GetBranchNo()
    {
        return $this->reqData['branchNo'];
    }

    /**
     * 设置商户号，6位数字
     * @param string $value 
     */
    public function SetMerchantNo($value)
    {
        $this->reqData['merchantNo'] = $value;
    }

    /**
     * 获取商户号，6位数字
     * @return 值
     */
    public function GetMerchantNo()
    {
        return $this->reqData['merchantNo'];
    }

    /**
     * 设置查询日期,格式：yyyyMMdd
     * @param string $value 
     */
    public function SetDate($value)
    {
        $this->reqData['date'] = $value;
    }

    /**
     * 获取查询日期,格式：yyyyMMdd
     * @return 值
     */
    public function GetDate()
    {
        return $this->reqData['date'];
    }

    /**
     * 设置操作员号,商户结账系统的操作员
     * @param string $value 
     */
    public function SetOperatorNo($value)
    {
        $this->reqData['operatorNo'] = $value;
    }

    /**
     * 获取操作员号,商户结账系统的操作员
     * @return 值
     */
    public function GetOperatorNo()
    {
        return $this->reqData['operatorNo'];
    }

    /**
     * 设置续传键值,首次查询填“空”; 后续查询，按应答报文中返回的nextKeyValue值原样传入。
     * @param string $value 
     */
    public function SetNextKeyValue($value)
    {
        $this->reqData['nextKeyValue'] = $value;
    }

    /**
     * 获取续传键值,首次查询填“空”; 后续查询，按应答报文中返回的nextKeyValue值原样传入。
     * @return 值
     */
    public function GetNextKeyValue()
    {
        return $this->reqData['nextKeyValue'];
    }
}

/**
 * 查询单笔订单
 * @author huache
 */
class CmbQueryOrder extends CmbBuilderBase
{
    /**
     * 设置请求时间,商户发起该请求的当前时间，精确到秒。格式：yyyyMMddHHmmss
     * @param string $value 
     */
    public function SetDateTime($value)
    {
        $this->reqData['dateTime'] = $value;
    }

    /**
     * 获取请求时间,商户发起该请求的当前时间，精确到秒。格式：yyyyMMddHHmmss
     * @return 值
     */
    public function GetDateTime()
    {
        return $this->reqData['dateTime'];
    }

    /**
     * 设置商户分行号，4位数字
     * @param string $value 
     */
    public function SetBranchNo($value)
    {
        $this->reqData['branchNo'] = $value;
    }

    /**
     * 获取商户分行号，4位数字
     * @return 值
     */
    public function GetBranchNo()
    {
        return $this->reqData['branchNo'];
    }

    /**
     * 设置商户号，6位数字
     * @param string $value 
     */
    public function SetMerchantNo($value)
    {
        $this->reqData['merchantNo'] = $value;
    }

    /**
     * 获取商户号，6位数字
     * @return 值
     */
    public function GetMerchantNo()
    {
        return $this->reqData['merchantNo'];
    }

    /**
     * 设置查询类型，A：按银行订单流水号查询B：按商户订单日期和订单号查询；
     * @param string $value 
     */
    public function SetType($value)
    {
        $this->reqData['type'] = $value;
    }

    /**
     * 获取查询类型，A：按银行订单流水号查询B：按商户订单日期和订单号查询；
     * @return 值
     */
    public function GetType()
    {
        return $this->reqData['type'];
    }

    /**
     * 设置银行订单流水号,type=A时必填
     * @param string $value 
     */
    public function SetBankSerialNo($value)
    {
        $this->reqData['bankSerialNo'] = $value;
    }

    /**
     * 获取银行订单流水号,type=A时必填
     * @return 值
     */
    public function GetBankSerialNo()
    {
        return $this->reqData['bankSerialNo'];
    }

    /**
     * 设置商户订单日期，格式：yyyyMMdd
     * @param string $value 
     */
    public function SetDate($value)
    {
        $this->reqData['date'] = $value;
    }

    /**
     * 获取商户订单日期，格式：yyyyMMdd
     * @return 值
     */
    public function GetDate()
    {
        return $this->reqData['date'];
    }

    /**
     * 设置type=B时必填商户订单号
     * @param string $value 
     */
    public function SetOrderNo($value)
    {
        $this->reqData['orderNo'] = $value;
    }

    /**
     * 获取type=B时必填商户订单号
     * @return 值
     */
    public function GetOrderNo()
    {
        return $this->reqData['orderNo'];
    }

    /**
     * 设置商户结账系统的操作员号
     * @param string $value 
     */
    public function SetOperatorNo($value)
    {
        $this->reqData['operatorNo'] = $value;
    }

    /**
     * 获取商户结账系统的操作员号
     * @return 值
     */
    public function GetOperatorNo()
    {
        return $this->reqData['operatorNo'];
    }
}

/**
 * 退款
 * @author huache
 */
class CmbRefund extends CmbBuilderBase
{
    /**
     * 设置商户发起该请求的当前时间，精确到秒。 格式：yyyyMMddHHmmss
     * @param string $value 
     */
    public function SetDateTime($value)
    {
        $this->reqData['dateTime'] = $value;
    }

    /**
     * 获取商户发起该请求的当前时间，精确到秒。 格式：yyyyMMddHHmmss
     * @return 值
     */
    public function GetDateTime()
    {
        return $this->reqData['dateTime'];
    }

    /**
     * 设置商户分行号，4位数字
     * @param string $value 
     */
    public function SetBranchNo($value)
    {
        $this->reqData['branchNo'] = $value;
    }

    /**
     * 获取商户分行号，4位数字
     * @return 值
     */
    public function GetBranchNo()
    {
        return $this->reqData['branchNo'];
    }

    /**
     * 设置商户号，6位数字
     * @param string $value 
     */
    public function SetMerchantNo($value)
    {
        $this->reqData['merchantNo'] = $value;
    }

    /**
     * 获取商户号，6位数字
     * @return 值
     */
    public function GetMerchantNo()
    {
        return $this->reqData['merchantNo'];
    }

    /**
     * 设置商户订单日期，支付时的订单日期。格式：yyyyMMdd
     * @param string $value 
     */
    public function SetDate($value)
    {
        $this->reqData['date'] = $value;
    }

    /**
     * 获取商户订单日期，支付时的订单日期。格式：yyyyMMdd
     * @return 值
     */
    public function GetDate()
    {
        return $this->reqData['date'];
    }

    /**
     * 设置商户订单号，支付时的订单号
     * @param string $value 
     */
    public function SetOrderNo($value)
    {
        $this->reqData['orderNo'] = $value;
    }

    /**
     * 获取商户订单号，支付时的订单号
     * @return 值
     */
    public function GetOrderNo()
    {
        return $this->reqData['orderNo'];
    }

    /**
     * 设置退款流水号,商户生成，同一笔订单内，同一退款流水号只能退款一次。可用于防重复退款。
     * @param string $value 
     */
    public function SetRefundSerialNo($value)
    {
        $this->reqData['refundSerialNo'] = $value;
    }

    /**
     * 获取退款流水号,商户生成，同一笔订单内，同一退款流水号只能退款一次。可用于防重复退款。
     * @return 值
     */
    public function GetRefundSerialNo()
    {
        return $this->reqData['refundSerialNo'];
    }

    /**
     * 设置退款金额,格式xxxx.xx
     * @param string $value 
     */
    public function SetAmount($value)
    {
        $this->reqData['amount'] = $value;
    }

    /**
     * 获取退款金额,格式xxxx.xx
     * @return 值
     */
    public function GetAmount()
    {
        return $this->reqData['amount'];
    }

    /**
     * 设置退款描述
     * @param string $value 
     */
    public function SetDesc($value)
    {
        $this->reqData['desc'] = $value;
    }

    /**
     * 获取退款描述
     * @return 值
     */
    public function GetDesc()
    {
        return $this->reqData['desc'];
    }

    /**
     * 设置商户结账系统的操作员号
     * @param string $value 
     */
    public function SetOperatorNo($value)
    {
        $this->reqData['operatorNo'] = $value;
    }

    /**
     * 获取商户结账系统的操作员号
     * @return 值
     */
    public function GetOperatorNo()
    {
        return $this->reqData['operatorNo'];
    }

    /**
     * 设置操作员密码加密算法,RC4：使用RC4算法对操作员密码进行加密，加密密钥为支付密钥。DES：使用DES算法对操作员密码进行加密，加密密钥为商户支付密钥的前8位，不足8位则右补0。空：默认不加密；
     * @param string $value 
     */
    public function SetEncrypType($value)
    {
        $this->reqData['encrypType'] = $value;
    }

    /**
     * 获取操作员密码加密算法
     * @return 值
     */
    public function GetEncrypType()
    {
        return $this->reqData['encrypType'];
    }

    /**
     * 设置操作员登录密码。使用encrypType算法加密后的密码。注意：加密后的密文必须转换为16进制字符串表示
     * @param string $value 
     */
    public function SetPwd($value)
    {
        $this->reqData['pwd'] = $value;
    }

    /**
     * 获取操作员登录密码
     * @return 值
     */
    public function GetPwd()
    {
        return $this->reqData['pwd'];
    }
}

/**
 * 退款查询
 * @author huache
 */
class CmbRefundQuery extends CmbBuilderBase
{
    /**
     * 设置请求时间,商户发起该请求的当前时间，精确到秒。 格式：yyyyMMddHHmmss
     * @param string $value 
     */
    public function SetDateTime($value)
    {
        $this->reqData['dateTime'] = $value;
    }

    /**
     * 获取请求时间
     * @return 值
     */
    public function GetDateTime()
    {
        return $this->reqData['dateTime'];
    }

    /**
     * 设置商户分行号，4位数字
     * @param string $value 
     */
    public function SetBranchNo($value)
    {
        $this->reqData['branchNo'] = $value;
    }

    /**
     * 获取商户分行号，4位数字
     * @return 值
     */
    public function GetBranchNo()
    {
        return $this->reqData['branchNo'];
    }

    /**
     * 设置商户号，6位数字
     * @param string $value 
     */
    public function SetMerchantNo($value)
    {
        $this->reqData['merchantNo'] = $value;
    }

    /**
     * 获取商户号，6位数字
     * @return 值
     */
    public function GetMerchantNo()
    {
        return $this->reqData['merchantNo'];
    }

    /**
     * 设置退款日期 格式：yyyyMMdd
     * @param string $value 
     */
    public function SetBeginDate($value)
    {
        $this->reqData['beginDate'] = $value;
    }

    /**
     * 获取退款日期 格式：yyyyMMdd
     * @return 值
     */
    public function GetBeginDate()
    {
        return $this->reqData['beginDate'];
    }

    /**
     * 设置结束日期,格式：yyyyMMdd
     * @param string $value 
     */
    public function SetEndDate($value)
    {
        $this->reqData['endDate'] = $value;
    }

    /**
     * 获取结束日期,格式：yyyyMMdd
     * @return 值
     */
    public function GetEndDate()
    {
        return $this->reqData['endDate'];
    }

    /**
     * 设置商户结账系统的操作员号
     * @param string $value 
     */
    public function SetOperatorNo($value)
    {
        $this->reqData['operatorNo'] = $value;
    }

    /**
     * 获取商户结账系统的操作员号
     * @return 值
     */
    public function GetOperatorNo()
    {
        return $this->reqData['operatorNo'];
    }

    /**
     * 设置续传键值,首次查询填“空”; 后续查询，按应答报文中返回的nextKeyValue值原样传入。
     * @param string $value 
     */
    public function SetNextKeyValue($value)
    {
        $this->reqData['nextKeyValue'] = $value;
    }

    /**
     * 获取续传键值,首次查询填“空”; 后续查询，按应答报文中返回的nextKeyValue值原样传入。
     * @return 值
     */
    public function GetNextKeyValue()
    {
        return $this->reqData['nextKeyValue'];
    }
}

/**
 * 查询协议
 * @author huache
 */
class CmbSignContractQuery extends CmbBuilderBase
{
    /**
     * 设置请求时间,商户发起该请求的当前时间，精确到秒。格式：yyyyMMddHHmmss
     * @param string $value 
     */
    public function SetDateTime($value)
    {
        $this->reqData['dateTime'] = $value;
    }

    /**
     * 获取请求时间
     * @return 值
     */
    public function GetDateTime()
    {
        return $this->reqData['dateTime'];
    }

    /**
     * 设置交易码,固定为“CMCX”
     * @param string $value 
     */
    public function SetTxCode($value)
    {
        $this->reqData['txCode'] = $value;
    }

    /**
     * 获取交易码,固定为“CMCX”
     * @return 值
     */
    public function GetTxCode()
    {
        return $this->reqData['txCode'];
    }

    /**
     * 设置商户分行号，4位数字
     * @param string $value 
     */
    public function SetBranchNo($value)
    {
        $this->reqData['branchNo'] = $value;
    }

    /**
     * 获取商户分行号，4位数字
     * @return 值
     */
    public function GetBranchNo()
    {
        return $this->reqData['branchNo'];
    }

    /**
     * 设置商户号，6位数字
     * @param string $value 
     */
    public function SetMerchantNo($value)
    {
        $this->reqData['merchantNo'] = $value;
    }

    /**
     * 获取商户号，6位数字
     * @return 值
     */
    public function GetMerchantNo()
    {
        return $this->reqData['merchantNo'];
    }

    /**
     * 设置商户做此查询请求的流水号
     * @param string $value 
     */
    public function SetMerchantSerialNo($value)
    {
        $this->reqData['merchantSerialNo'] = $value;
    }

    /**
     * 获取商户做此查询请求的流水号
     * @return 值
     */
    public function GetMerchantSerialNo()
    {
        return $this->reqData['merchantSerialNo'];
    }

    /**
     * 设置客户签约的协议号
     * @param string $value 
     */
    public function SetAgrNo($value)
    {
        $this->reqData['agrNo'] = $value;
    }

    /**
     * 获取客户签约的协议号
     * @return 值
     */
    public function GetAgrNo()
    {
        return $this->reqData['agrNo'];
    }
}

/**
 * 取消协议
 * @author huache
 */
class CmbCancelContract extends CmbBuilderBase
{
    /**
     * 设置商户发起该请求的当前时间，精确到秒。 格式：yyyyMMddHHmmss
     * @param string $value 
     */
    public function SetDateTime($value)
    {
        $this->reqData['dateTime'] = $value;
    }

    /**
     * 获取商户发起该请求的当前时间，精确到秒。 格式：yyyyMMddHHmmss
     * @return 值
     */
    public function GetDateTime()
    {
        return $this->reqData['dateTime'];
    }

    /**
     * 设置交易码,本接口固定为CMQX
     * @param string $value 
     */
    public function SetTxCode($value)
    {
        $this->reqData['txCode'] = $value;
    }

    /**
     * 获取交易码,本接口固定为CMQX
     * @return 值
     */
    public function GetTxCode()
    {
        return $this->reqData['txCode'];
    }

    /**
     * 设置商户分行号，4位数字
     * @param string $value 
     */
    public function SetBranchNo($value)
    {
        $this->reqData['branchNo'] = $value;
    }

    /**
     * 获取商户分行号，4位数字
     * @return 值
     */
    public function GetBranchNo()
    {
        return $this->reqData['branchNo'];
    }

    /**
     * 设置商户号，6位数字
     * @param string $value 
     */
    public function SetMerchantNo($value)
    {
        $this->reqData['merchantNo'] = $value;
    }

    /**
     * 获取商户号，6位数字
     * @return 值
     */
    public function GetMerchantNo()
    {
        return $this->reqData['merchantNo'];
    }

    /**
     * 设置商户做此查询请求的流水号
     * @param string $value 
     */
    public function SetMerchantSerialNo($value)
    {
        $this->reqData['merchantSerialNo'] = $value;
    }

    /**
     * 获取商户做此查询请求的流水号
     * @return 值
     */
    public function GetMerchantSerialNo()
    {
        return $this->reqData['merchantSerialNo'];
    }

    /**
     * 设置客户签约的协议号
     * @param string $value 
     */
    public function SetAgrNo($value)
    {
        $this->reqData['agrNo'] = $value;
    }

    /**
     * 获取客户签约的协议号
     * @return 值
     */
    public function GetAgrNo()
    {
        return $this->reqData['agrNo'];
    }
}
