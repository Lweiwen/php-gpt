<?php

namespace app\services;


use app\model\OrderLogModel;

/**
 * 订单记录日志
 * Class OrderLogLogic
 * @package app\common\logic
 */
class OrderLogService extends BaseService
{
    public static function record(
        int $type,
        int $channel,
        int $orderId,
        int $handleId,
        int $content,
        string $desc = ''
    ) {
        if (empty($content)) {
            return true;
        }
        $insertData = [
            'type'      => $type,
            'order_id'  => $orderId,
            'channel'   => $channel,
            'handle_id' => $handleId,
            'content'   => OrderLogModel::getLogDesc($content),
        ];

        if ($desc != '') {
            $insertData['content'] = $insertData['content'] . '(' . $desc . ')';
        }
        OrderLogModel::addLog($insertData);

        return true;
    }
}