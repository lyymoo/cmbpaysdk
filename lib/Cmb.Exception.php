<?php
/**
 * 
 * 招商银行一网通支付API异常类
 * @author moz
 *
 */
class CmbException extends Exception {
    public function errorMessage()
    {
        return $this->getMessage();
    }
}
