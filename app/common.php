<?php

// 应用公共文件
use Endroid\QrCode\QrCode;

if (!function_exists('msectime')) {
    /**
     * 获取毫秒数
     * @return float
     */
    function msectime()
    {
        list($msec, $sec) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }
}

if (!function_exists('sprintf2')) {
    /**
     * 保留2为小数
     * @param $val
     * @return float
     * @author shao
     */
    function sprintf2($val)
    {
//        return floatval(sprintf("%.2f", substr(sprintf("%.3f", $val), 0, -1)));
        return sprintf("%.2f", substr(sprintf("%.3f", $val), 0, -1));
    }
}
if (!function_exists('makeOrderNo')) {
    /**
     * 生成订单号
     * @param $business
     * @return string
     */
    function makeOrderNo($business)
    {
        return $business . date('YmdHis') . GetNumberCode(6);
    }
}

if (!function_exists('getNumberCode')) {
    /**
     * 随机数生成生成
     * @param int $length
     * @return string
     */
    function getNumberCode($length = 6)
    {
        $code = '';
        for ($i = 0; $i < intval($length); $i++) {
            $code .= rand(0, 9);
        }

        return $code;
    }
}

/**
 * 是否在cli模式
 */
if (!function_exists('is_cli')) {
    function is_cli()
    {
        return preg_match("/cli/i", php_sapi_name()) ? true : false;
    }
}

if (!function_exists('createUserSn')) {
    function createUserSn($prefix = '', $length = 8)
    {
        return $prefix . getNumberCode($length);
    }
}


if (!function_exists('getQRCode')) {
    /**
     *
     * @param string $content 内容
     * @param string $errorCorrectionLevel 设置二维码的纠错率，可以有low、medium、quartile、hign多个纠错率
     * @param int $margin
     * @return string
     * @author LWW
     */
    function getQRCode(string $content = '', string $errorCorrectionLevel = '', int $margin = 2)
    {
        if (empty($errorCorrectionLevel)) {
            $errorCorrectionLevel = \Endroid\QrCode\ErrorCorrectionLevel::LOW;
        }
        $qrCode = new QrCode();
        $qrCode->setText($content);
        $qrCode->setErrorCorrectionLevel($errorCorrectionLevel);
        $qrCode->setMargin($margin);
        return $qrCode->writeDataUri();
    }
}
if (!function_exists('idToCode')) {
//加密函数
    function idToCode($data, int $length = 6): string
    {
        $key = 'lww.com';
        $hashids = new \Hashids\Hashids($key, $length);
        return $hashids->encode($data);
    }
}

if (!function_exists('codeToId')) {
//解密函数
    function codeToId($encryptedData, int $length = 6)
    {
        if (empty($encryptedData)) {
            return 0;
        }
        $key = 'lww.com';
        $hashids = new \Hashids\Hashids($key, $length);
        $arr = $hashids->decode($encryptedData);
        if (!empty($arr)) {
            return $arr[0];
        }
        return 0;
    }
}


if (!function_exists('encryptGpt')) {
//加密函数
    function maxIdToCode($data, int $length = 11): string
    {
        return idToCode($data, $length);
    }
}

if (!function_exists('encryptGpt')) {
//加密函数
    function maxCodeToId($data, int $length = 11): string
    {
        return codeToId($data, $length);
    }
}


