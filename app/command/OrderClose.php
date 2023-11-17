<?php

namespace app\command;

use app\model\GoodModel;
use app\model\OrderModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

class OrderClose extends Command
{
    /**
     * 名称介绍
     * @author LWW
     */
    protected function configure()
    {
        $this->setName('order_close')
            ->setDescription('关闭订单');
    }

    /**
     * 关闭订单
     * @param Input $input
     * @param Output $output
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    protected function execute(Input $input, Output $output)
    {
        //取消订单时长
        $orderCancelTime = OrderModel::CANCELTIME;
        $now = time();
        if ($orderCancelTime == 0) {
            return false;
        }
        $where = [
            'pay_status'   => OrderModel::UNPAID,
            'order_status' => OrderModel::ORDER_NORMAL,
            'end_time'     => $now - $orderCancelTime,
        ];
        $objOrder = OrderModel::listForOrder(0, 0, $where, [], ['orderGoods']);
        Db::startTrans();
        try {
            foreach ($objOrder as $order) {
                OrderModel::edit($order, ['order_status' => OrderModel::ORDER_CLOSE]);
                if ($order->orderGoods->goods_id > 0) {
                    if (isset($updateTotalStock[$order->orderGoods->goods_id])) {
                        $updateTotalStock[$order->orderGoods->goods_id] += $order->num;
                    } else {
                        $updateTotalStock[$order->orderGoods->goods_id] = $order->num;
                    }
                }
            }
            if (!empty($updateTotalStock)) {
                foreach ($updateTotalStock as $id => $stock) {
                    $obj = GoodModel::findWithId($id);
                    if ($obj && $obj->is_stock) {
                        GoodModel::edit($obj, ['sock' => Db::raw('stock+' . $stock)]);
                    }
                }
            }
            Db::commit();
        } catch (\Throwable $e) {
            $record = [
                __CLASS__,
                __FUNCTION__,
                $e->getFile(),
                $e->getLine(),
                $e->getMessage()
            ];
            Log::record(implode('-', $record), 'error');
            Db::rollback();
        }
        return true;
    }
}