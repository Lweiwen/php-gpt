<?php

namespace app\traits;

use think\facade\Log;

trait ChatResponseTrait
{

    /**
     * 记录日志(GPT内部报错信息)
     * @param $code
     * @param $msg
     * @author LWW
     */
    public static function recordLog($code, $msg)
    {
        if (is_null($code)) {
            $code = 'error';
        }
        $code = self::getErrorCode($code, $msg);
        $errorJson = json_encode(['error_code' => $code, 'msg' => $msg]);
        $record = [
            __CLASS__,
            __FUNCTION__,
            $errorJson
        ];
        Log::channel('openai')->write(implode('-', $record), 'error');
    }

    /**
     * 错误码
     * @param $code
     * @param $msg
     * @return string
     * @author LWW
     */
    public static function getErrorCode($code, $msg): string
    {
        $errorCode = $code;
        if (strpos($msg, "Rate limit reached") === 0) { //访问频率超限错误返回的code为空，特殊处理一下
            $errorCode = "rate_limit_reached";
        }
        if (strpos($msg, "Your access was terminated") === 0) { //违规使用，被封禁，特殊处理一下
            $errorCode = "access_terminated";
        }
        if (strpos($msg, "You didn't provide an API key") === 0) { //未提供API-KEY
            $errorCode = "no_api_key";
        }
        if (strpos($msg, "You exceeded your current quota") === 0) { //API-KEY余额不足
            $errorCode = "insufficient_quota";
        }
        if (strpos($msg, "That model is currently overloaded") === 0) { //OpenAI服务器超负荷
            $errorCode = "model_overloaded";
        }
        return $errorCode;
    }

    /**
     * 回复打印输出内容
     * @param $responseData
     * @author LWW
     */
    public static function response($responseData)
    {
        if (!empty($responseData))
            echo "data: {$responseData}\n\n";
    }

    /**
     * 输出错误
     * @param $code
     * @param $msg
     * @author LWW
     */
    public static function pushError($code, $msg)
    {
        $errorJson = json_encode(['error_code' => $code, 'msg' => $msg]);
        self::response($errorJson);
    }

    /**
     * 结束标志
     * @author LWW
     */
    public static function end()
    {
        self::response('end');
    }

    /**
     * 开始对话标识
     * @author LWW
     */
    public static function start()
    {
        self::response('start');
    }

}