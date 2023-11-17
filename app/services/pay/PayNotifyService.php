<?php

declare (strict_types=1);

namespace app\services\pay;

use app\model\GoodModel;
use app\model\OrderGoodsModel;
use app\model\OrderLogModel;
use app\model\OrderModel;
use app\model\UserAIMoneyChangeModel;
use app\services\BaseService;
use app\services\index\UserAiMoneyService;
use app\services\OrderLogService;
use think\facade\Db;
use think\facade\Log;

class PayNotifyService extends BaseService
{
    /**
     * 支付成功
     * @param $action
     * @param $orderSn
     * @param array $extra
     * @return bool|string
     * @author LWW
     */
    public static function handle($action, $orderSn, $extra = [])
    {
        Db::startTrans();
        try {
            self::$action($orderSn, $extra);
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            $record = [
                __CLASS__,
                __FUNCTION__,
                $e->getFile(),
                $e->getLine(),
                $e->getMessage()
            ];
            Log::record(implode('-', $record), 'error');
            return $e->getMessage();
        }
    }

    /**
     * 下单回调
     * @param string $orderSn
     * @param array $extra
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    private static function order(string $orderSn, array $extra = [])
    {
        $time = time();
        /*** 更新订单状态 ***/
        $objOrder = OrderModel::findWithOrderSn($orderSn);
        //增加会员消费累计额度
        $objOrder->pay_status = OrderModel::ISPAID;
        $objOrder->pay_time = $time;

        if (isset($extra['transaction_id'])) {
            $objOrder->transaction_id = $extra['transaction_id'];
        }
        $objOrder->save();

        //订单日志
        OrderLogService::record(
            OrderLogModel::TYPE_USER,
            OrderLogModel::USER_PAID_ORDER,
            $objOrder->id,
            $objOrder->user_id,
            OrderLogModel::USER_PAID_ORDER
        );
        $objOrderGood = OrderGoodsModel::findByOrderId($objOrder->id);

        if ($objOrderGood) {

            // 增加个人ai次数
            $aiNum = $objOrderGood->ai_num * $objOrder->num;

            (new UserAiMoneyService())->chatAiMoney(
                $objOrder->user_id,
                $aiNum,
                UserAIMoneyChangeModel::user_buy,
                $objOrder->id
            );
            // 增加销量
            $objGood = GoodModel::findWithId($objOrderGood->goods_id);
            GoodModel::edit($objGood, ['sale_num' => $objOrderGood->sale_num + $objOrderGood->num]);
        }
    }
}