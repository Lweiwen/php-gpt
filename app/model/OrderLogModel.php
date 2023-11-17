<?php
namespace app\model;
use think\model\concern\SoftDelete;

class OrderLogModel extends BaseModel
{
    use SoftDelete;

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'order_log';

    //操作人类型
    const TYPE_USER     = 0;//会员
    const TYPE_SHOP     = 1;//门店
    const TYPE_SYSTEM   = 2;//系统

    //订单动作
    const USER_ADD_ORDER        = 101; //提交订单
    const USER_CANCEL_ORDER     = 102; //取消订单
    const USER_DEL_ORDER        = 103; //删除订单
    const USER_PAID_ORDER       = 104; //支付订单

    const SYSTEM_CANCEL_ORDER   = 301; //系统取消订单

    //订单动作明细
    public static function getLogDesc($log)
    {
        $desc = [
            self::USER_ADD_ORDER        => '会员提交订单',
            self::USER_CANCEL_ORDER     => '会员取消订单',
            self::USER_DEL_ORDER        => '会员删除订单',
            self::USER_PAID_ORDER       => '会员支付订单',

            self::SYSTEM_CANCEL_ORDER   => '系统取消订单',
        ];

        if ($log === true) {
            return $desc;
        }

        return isset($desc[$log]) ? $desc[$log] : $log;
    }

    //订单日志
    public static function getOrderLog(int $orderId)
    {
        $logs = self::where('order_id', $orderId)
            ->select();

        foreach ($logs as &$log){
            $log['create_time'] = date('Y-m-d H:i:s', $log['create_time']);
            $log['channel'] = self::getLogDesc($log['channel']);
        }

        return $logs;
    }

    /**
     * 新增记录
     * @param array $data
     * @return OrderLogModel|\think\Model
     * @author LWW
     */
    public static function addLog(array $data)
    {
        return self::create($data);
    }
}