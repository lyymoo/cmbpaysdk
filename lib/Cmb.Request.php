<?php
ini_set('date.timezone','Asia/Shanghai');
require_once "Cmb.Exception.php";
require_once "Cmb.Config.php";
require_once "Cmb.Builder.php";

/**
 * 
 * 接口访问类，包含所有招商银行支付API列表的封装，类中方法为static方法，
 * 每个接口有默认超时时间（除提交被扫支付为10s，上报超时时间为1s外，其他均为6s）
 * 招商银行一网通开发文档：
 * http://openhome.cmbchina.com/pay/Default.aspx
 * 
 * - 数据类型
 * String(x)表示最大长度为x字节的变长字符串，如有固定长度等特殊要求在接口文档中说明。
 * String表示长度不做限制的变长字符串
 * 长度的计算说明：所有长度均按字节计算，中文算两个字节，英文、数字算一个字节。
 * - 参数值是否必填约定
 * M  必需填写的参数
 * C  某些条件成立时必需填写的参数。具体条件在描述中说明
 * O  可选择填写的参数
 * - 请求返回码
 * SUC0000表示成功，其他表示错误，具体错误码见详细API定义。
 * 
 * @author moz
 *
 */
class CmbApi
{
    /**
     * 一网通支付API
     * @param CmbPay $inputObj 输入对象<必设参数见文档>
     * @param int $timeOut
     * @throws CmbException
     * @return 成功时返回，其他抛异常
     */
    public static function pay($host, $inputObj, $timeOut = 30)
    {
        $url = $host . "/netpayment/BaseHttp.dll?MB_EUserPay";
        //设置公共参数
        $inputObj->SetVersion();
        $inputObj->SetCharset();
        $inputObj->SetSignType();
        //设置请求参数
        $inputObj->SetDateTime(date('YmdHis'));
        $inputObj->SetBranchNo(CmbConfig::BRANCHNO);
        $inputObj->SetMerchantNo(CmbConfig::MERCHANTNO);
        $inputObj->SetReqData();
        //签名
        $inputObj->SetSign($inputObj->GetReqData());
        //转化为json字符串
        $json = $inputObj->ToJson();
        $response = self::postJsonCurl($json, $url, false, $timeOut);
        return mb_convert_encoding($response, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');
    }

    /**
     * 一网通刷卡无感支付API
     * @param CmbPay $inputObj 输入对象<必设参数见文档>
     * @param int $timeOut
     * @throws CmbException
     * @return 成功时返回，其他抛异常
     */
    public static function scanPay($host, $inputObj, $timeOut = 6)
    {
        $url = $host . "/CmbBank_B2B/UI/NetPay/DoBusiness.ashx";
        //设置公共参数
        $inputObj->SetVersion();
        $inputObj->SetCharset();
        $inputObj->SetSignType();
        //设置请求参数
        $inputObj->SetDateTime(date('YmdHis'));
        $inputObj->SetTxCode('CMSK');
        $inputObj->SetBranchNo(CmbConfig::BRANCHNO);
        $inputObj->SetMerchantNo(CmbConfig::MERCHANTNO);
        $inputObj->SetCurrency('RMB');
        $inputObj->SetReqData();
        //签名
        $inputObj->SetSign($inputObj->GetReqData());
        //转化为json字符串
        $json = $inputObj->ToJson();
        $response = self::postJsonCurl($json, $url, false, $timeOut);
        $result = CmbResults::Init($response);
        
        return $result;
    }
    
    /**
     * 查询招商银行公钥API
     * @param CmbPubKeyQuery $inputObj 输入对象<无>
     * @param int $timeOut
     * @throws CmbException
     * @return 成功时返回，其他抛异常
     */
    public static function queryCmbPubKey($host, $inputObj, $timeOut = 6)
    {
        $url = $host . "/CmbBank_B2B/UI/NetPay/DoBusiness.ashx";
        //设置公共参数
        $inputObj->SetVersion();
        $inputObj->SetCharset();
        $inputObj->SetSignType();
        //设置请求参数
        $inputObj->SetDateTime(date('YmdHis'));
        $inputObj->SetTxCode('FBPK');
        $inputObj->SetBranchNo(CmbConfig::BRANCHNO);
        $inputObj->SetMerchantNo(CmbConfig::MERCHANTNO);
        $inputObj->SetReqData();
        //签名
        $inputObj->SetSign($inputObj->GetReqData());
        //转化为json字符串
        $json = $inputObj->ToJson();
        $response = self::postJsonCurl($json, $url, false, $timeOut);
        $result = CmbResults::Init($response);
        
        return $result;
    }

    /**
     * 成功签约结果通知API
     * @param string $string 通知的json报文字符串
     * @throws CmbException
     * @return 成功时返回，其他抛异常
     */
    public static function signNotice($string)
    {
        $result = CmbSignNotice::Init($string);
        return $result;
    }

    /**
     * 成功支付结果通知API
     * @param string $string 通知的json报文字符串
     * @throws CmbException
     * @return 成功时返回，其他抛异常
     */
    public static function payNotice($string)
    {
        $result = CmbPayNotice::Init($string);
        return $result;
    }
    
    /**
     * 签约绑卡API
     * @param CmbSignContract $inputObj 输入对象<必设参数如下>
     * merchantSerialNo 协议开通请求流水号,商户生成
     * agrNo 客户协议号。不超过32位的数字字母组合。
     * userID 用于标识商户用户的唯一ID。
     * noticeUrl 商户接收成功签约结果通知的地址。
     * noticePara 该参数在发送成功签约结果通知时，将原样返回商户
     * returnUrl 签约成功页面上“返回商户”按钮跳转地址
     * @param int $timeOut
     * @throws CmbException
     * @return 成功时返回，其他抛异常
     */
    public static function signContract($host, $inputObj, $timeOut = 6)
    {
        $url = $host . "/mobilehtml/DebitCard/M_NetPay/OneNetRegister/NP_BindCard.aspx";
        //设置公共参数
        $inputObj->SetVersion();
        $inputObj->SetCharset();
        $inputObj->SetSignType();
        //设置请求参数
        $inputObj->SetDateTime(date('YmdHis'));
        $inputObj->SetBranchNo(CmbConfig::BRANCHNO);
        $inputObj->SetMerchantNo(CmbConfig::MERCHANTNO);
        $inputObj->SetReqData();
        //签名
        $inputObj->SetSign($inputObj->GetReqData());
        //转化为json字符串
        $json = $inputObj->ToJson();
        $response = self::postJsonCurl($json, $url, false, $timeOut);
        return mb_convert_encoding($response, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');;
    }
    
    /**
     * 签约免密API
     * @param CmbSignContract $inputObj 输入对象<必设参数如下>
     * merchantSerialNo 协议开通请求流水号,商户生成
     * agrNo 客户协议号。不超过32位的数字字母组合。
     * userID 用于标识商户用户的唯一ID。
     * noticeUrl 商户接收成功签约结果通知的地址。
     * noticePara 该参数在发送成功签约结果通知时，将原样返回商户
     * returnUrl 签约成功页面上“返回商户”按钮跳转地址
     * @param int $timeOut
     * @throws CmbException
     * @return 成功时返回，其他抛异常
     */
    public static function signContractNoPwd($host, $inputObj, $timeOut = 6)
    {
        $url = $host . "/mobilehtml/DebitCard/M_NetPay/OneNetRegister/NP_NoPwdPay.aspx";
        //设置公共参数
        $inputObj->SetVersion();
        $inputObj->SetCharset();
        $inputObj->SetSignType();
        //设置请求参数
        $inputObj->SetDateTime(date('YmdHis'));
        $inputObj->SetBranchNo(CmbConfig::BRANCHNO);
        $inputObj->SetMerchantNo(CmbConfig::MERCHANTNO);
        $inputObj->SetReqData();
        //签名
        $inputObj->SetSign($inputObj->GetReqData());
        //转化为json字符串
        $json = $inputObj->ToJson();
        $response = self::postJsonCurl($json, $url, false, $timeOut);
        return mb_convert_encoding($response, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');
    }

    /**
     * 按商户日期查询已结账订单API
     * @param CmbQuerySettledOrder $inputObj 输入对象<必设参数如下>
     * beginDate 开始日期,格式：yyyyMMdd
     * endDate 结束日期,格式：yyyyMMdd
     * operatorNo 操作员号,商户结账系统的操作员号
     * nextKeyValue 续传键值,长度只能为0或40；首次查询填“空”；后续查询，按应答报文中返回的nextKeyValue值原样传入。
     * @param int $timeOut
     * @throws CmbException
     * @return 成功时返回，其他抛异常
     */
    public static function settledOrderQuery($host, $inputObj, $timeOut = 6)
    {
        $url = $host . "/NetPayment_dl/BaseHttp.dll?QuerySettledOrderByMerchantDate";
        //设置公共参数
        $inputObj->SetVersion();
        $inputObj->SetCharset();
        $inputObj->SetSignType();
        //设置请求参数
        $inputObj->SetDateTime(date('YmdHis'));
        $inputObj->SetBranchNo(CmbConfig::BRANCHNO);
        $inputObj->SetMerchantNo(CmbConfig::MERCHANTNO);
        $inputObj->SetReqData();
        //签名
        $inputObj->SetSign($inputObj->GetReqData());
        //转化为json字符串
        $json = $inputObj->ToJson();
        $response = self::postJsonCurl($json, $url, false, $timeOut);
        $result = CmbResults::Init($response);
        
        return $result;
    }
    
    /**
     * 查询入账明细API
     * @param CmbQueryAccountList $inputObj 输入对象<必设参数如下>
     * date 查询日期,格式：yyyyMMdd
     * operatorNo 操作员号,商户结账系统的操作员号
     * nextKeyValue 续传键值,首次查询填“空”; 后续查询，按应答报文中返回的nextKeyValue值原样传入。
     * @param int $timeOut
     * @throws CmbException
     * @return 成功时返回，其他抛异常
     */
    public static function accountListQuery($host, $inputObj, $timeOut = 6)
    {
        $url = $host . "/NetPayment_dl/BaseHttp.dll?QueryAccountList";
        //设置公共参数
        $inputObj->SetVersion();
        $inputObj->SetCharset();
        $inputObj->SetSignType();
        //设置请求参数
        $inputObj->SetDateTime(date('YmdHis'));
        $inputObj->SetBranchNo(CmbConfig::BRANCHNO);
        $inputObj->SetMerchantNo(CmbConfig::MERCHANTNO);
        $inputObj->SetReqData();
        //签名
        $inputObj->SetSign($inputObj->GetReqData());
        //转化为json字符串
        $json = $inputObj->ToJson();
        $response = self::postJsonCurl($json, $url, false, $timeOut);
        $result = CmbResults::Init($response);
        
        return $result;
    }

    /**
     * 查询单笔订单API
     * @param CmbQueryOrder $inputObj 输入对象<必设参数如下>
     * type 查询类型，A：按银行订单流水号查询 B：按商户订单日期和订单号查询；
     * bankSerialNo 银行订单流水号,type=A时必填
     * date 商户订单日期，格式：yyyyMMdd
     * orderNo type=B时必填商户订单号
     * operatorNo 商户结账系统的操作员号
     * @param int $timeOut
     * @throws CmbException
     * @return 成功时返回，其他抛异常
     */
    public static function orderQuery($host, $inputObj, $timeOut = 6)
    {
        $url = $host . "/Netpayment_dl/BaseHttp.dll?QuerySingleOrder";
        //设置公共参数
        $inputObj->SetVersion();
        $inputObj->SetCharset();
        $inputObj->SetSignType();
        //设置请求参数
        $inputObj->SetDateTime(date('YmdHis'));
        $inputObj->SetBranchNo(CmbConfig::BRANCHNO);
        $inputObj->SetMerchantNo(CmbConfig::MERCHANTNO);
        $inputObj->SetReqData();
        //签名
        $inputObj->SetSign($inputObj->GetReqData());
        //转化为json字符串
        $json = $inputObj->ToJson();
        $response = self::postJsonCurl($json, $url, false, $timeOut);
        $result = CmbResults::Init($response);
        
        return $result;
    }
    
    /**
     * 退款API
     * @param CmbRefund $inputObj 输入对象<必设参数如下>
     * date 商户订单日期，支付时的订单日期 格式：yyyyMMdd
     * orderNo 商户订单号，支付时的订单号
     * refundSerialNo 退款流水号,商户生成，同一笔订单内，同一退款流水号只能退款一次。可用于防重复退款。
     * amount 退款金额,格式xxxx.xx
     * desc 退款描述(100)
     * operatorNo 商户结账系统的操作员号
     * encrypType 操作员密码加密算法,RC4：使用RC4算法对操作员密码进行加密，加密密钥为支付密钥。DES：使用DES算法对操作员密码进行加密，加密密钥为商户支付密钥的前8位，不足8位则右补0。空：默认不加密；
     * pwd 操作员登录密码。使用encrypType算法加密后的密码 注意：加密后的密文必须转换为16进制字符串表示
     * @param int $timeOut
     * @throws CmbException
     * @return 成功时返回，其他抛异常
     */
    public static function refund($host, $inputObj, $timeOut = 6)
    {
        $url = $host . "/NetPayment/BaseHttp.dll?DoRefund";
        //设置公共参数
        $inputObj->SetVersion();
        $inputObj->SetCharset();
        $inputObj->SetSignType();
        //设置请求参数
        $inputObj->SetDateTime(date('YmdHis'));
        $inputObj->SetBranchNo(CmbConfig::BRANCHNO);
        $inputObj->SetMerchantNo(CmbConfig::MERCHANTNO);
        $inputObj->SetReqData();
        //签名
        $inputObj->SetSign($inputObj->GetReqData());
        //转化为json字符串
        $json = $inputObj->ToJson();
        $response = self::postJsonCurl($json, $url, false, $timeOut);
        $result = CmbResults::Init($response);
        
        return $result;
    }
    
    /**
     * 退款查询API
     * @param CmbRefundQuery $inputObj 输入对象<必设参数如下>
     * beginDate 退款日期 格式：yyyyMMdd
     * endDate 结束日期,格式：yyyyMMdd
     * operatorNo 商户结账系统的操作员号
     * nextKeyValue 续传键值,首次查询填“空”; 后续查询，按应答报文中返回的nextKeyValue值原样传入。
     * @param int $timeOut
     * @throws CmbException
     * @return 成功时返回，其他抛异常
     */
    public static function refundQuery($host, $inputObj, $timeOut = 6)
    {
        $url = $host . "/Netpayment_dl/BaseHttp.dll?QueryRefundByDate";
        //设置公共参数
        $inputObj->SetVersion();
        $inputObj->SetCharset();
        $inputObj->SetSignType();
        //设置请求参数
        $inputObj->SetDateTime(date('YmdHis'));
        $inputObj->SetBranchNo(CmbConfig::BRANCHNO);
        $inputObj->SetMerchantNo(CmbConfig::MERCHANTNO);
        $inputObj->SetReqData();
        //签名
        $inputObj->SetSign($inputObj->GetReqData());
        //转化为json字符串
        $json = $inputObj->ToJson();
        $response = self::postJsonCurl($json, $url, false, $timeOut);
        $result = CmbResults::Init($response);
        
        return $result;
    }
    
    /**
     * 查询协议API
     * @param CmbSignContractQuery $inputObj 输入对象<必设参数如下>
     * merchantSerialNo 商户做此查询请求的流水号
     * agrNo 客户签约的协议号
     * @param int $timeOut
     * @throws CmbException
     * @return 成功时返回，其他抛异常
     */
    public static function signContractQuery($host, $inputObj, $timeOut = 6)
    {
        $url = $host . "/CmbBank_B2B/UI/NetPay/DoBusiness.ashx";
        //设置公共参数
        $inputObj->SetVersion();
        $inputObj->SetCharset();
        $inputObj->SetSignType();
        //设置请求参数
        $inputObj->SetDateTime(date('YmdHis'));
        $inputObj->SetTxCode('CMCX');
        $inputObj->SetBranchNo(CmbConfig::BRANCHNO);
        $inputObj->SetMerchantNo(CmbConfig::MERCHANTNO);
        $inputObj->SetReqData();
        //签名
        $inputObj->SetSign($inputObj->GetReqData());
        //转化为json字符串
        $json = $inputObj->ToJson();
        $response = self::postJsonCurl($json, $url, false, $timeOut);
        $result = CmbResults::Init($response);
        
        return $result;
    }
    
    /**
     * 取消协议API
     * @param CmbCancelContract $inputObj 输入对象<必设参数如下>
     * merchantSerialNo 商户做此查询请求的流水号
     * agrNo 客户签约的协议号
     * @param int $timeOut
     * @throws CmbException
     */
    public static function cancelContract($host, $inputObj, $timeOut = 6)
    {
        $url = $host . "/CmbBank_B2B/UI/NetPay/DoBusiness.ashx";
        //设置公共参数
        $inputObj->SetVersion();
        $inputObj->SetCharset();
        $inputObj->SetSignType();
        //设置请求参数
        $inputObj->SetDateTime(date('YmdHis'));
        $inputObj->SetTxCode('CMQX');
        $inputObj->SetBranchNo(CmbConfig::BRANCHNO);
        $inputObj->SetMerchantNo(CmbConfig::MERCHANTNO);
        $inputObj->SetReqData();
        //签名
        $inputObj->SetSign($inputObj->GetReqData());
        //转化为json字符串
        $json = $inputObj->ToJson();
        $response = self::postJsonCurl($json, $url, false, $timeOut);
        $result = CmbResults::Init($response);
        
        return $result;
    }
    
    /**
     * 关闭免密支付协议API
     * @param CmbCancelContract $inputObj 输入对象<必设参数如下>
     * merchantSerialNo 商户做此查询请求的流水号
     * agrNo 客户签约的协议号
     * @param int $timeOut
     * @throws CmbException
     */
    public static function closeContractNoPwd($host, $inputObj, $timeOut = 6)
    {
        $url = $host . "/CmbBank_B2B/UI/NetPay/DoBusiness.ashx";
        //设置公共参数
        $inputObj->SetVersion();
        $inputObj->SetCharset();
        $inputObj->SetSignType();
        //设置请求参数
        $inputObj->SetDateTime(date('YmdHis'));
        $inputObj->SetTxCode('CMGB');
        $inputObj->SetBranchNo(CmbConfig::BRANCHNO);
        $inputObj->SetMerchantNo(CmbConfig::MERCHANTNO);
        $inputObj->SetReqData();
        //签名
        $inputObj->SetSign($inputObj->GetReqData());
        //转化为json字符串
        $json = $inputObj->ToJson();
        $response = self::postJsonCurl($json, $url, false, $timeOut);
        $result = CmbResults::Init($response);
        
        return $result;
    }
    
    /**
     * 以post方式提交json到对应的接口url
     * 
     * @param string $json  需要post的json数据
     * @param string $url  url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second   url执行超时时间，默认30s
     * @throws CmbException
     */
    private static function postJsonCurl($json, $url, $useCert = false, $second = 30)
    {        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0');
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        
        //如果有配置代理这里就设置代理
        if(false && CmbConfig::CURL_PROXY_HOST != "0.0.0.0" 
            && CmbConfig::CURL_PROXY_PORT != 0){
            curl_setopt($ch,CURLOPT_PROXY, CmbConfig::CURL_PROXY_HOST);
            curl_setopt($ch,CURLOPT_PROXYPORT, CmbConfig::CURL_PROXY_PORT);
        }
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    
        if($useCert == true){
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, '');
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, '');
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "jsonRequestData=".$json);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else { 
            $error = curl_errno($ch);
            curl_close($ch);
            throw new CmbException("curl出错，错误码:$error");
        }
    }
}

