## 招商银行支付SDK原生封装

> 招商银行一网通支付竟然不提供线上可用的开发SDK，所以自己写了一个，代码和逻辑都很简单

## 运行环境
- PHP 5.0+

## 接口说明
- 签约(register.php)
- 签约通知(register-notice.php)
- 支付(pay.php)
- 支付通知(pay-notice.php)
- 免密支付(payment.php)
- 免密支付通知(payment-notice.php)
- 查询签约状态(query-contract.php)
- 查询签约并取消(cancel-contract.php)
- 关闭免密支付(close-nopwd.php)

## 配置说明
- 支付网关配置(lib/Cmb.Config.php)

## 代码贡献
由于测试及使用环境的限制，本项目中只开发了「无感支付停车费」业务场景下的相关支付网关。

如果您有其它支付网关的需求，或者发现本项目中需要改进的代码，**_欢迎 Fork 并提交 PR！_**

## LICENSE
MIT