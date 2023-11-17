<?php

namespace app\controller\admin;

use app\BaseController;
use app\services\admin\OrderService;

class Order extends BaseController
{
    /**
     * 订单列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function list()
    {
        $param = $this->request->getMore(
            [
                ['order_sn', ''],
                ['pay_status', -1],
                ['begin_time', 0],
                ['end_time', 0]
            ]
        );
//        $this->validate();
        list($limit, $offset) = $this->pagination();
        return $this->apiResponse((new OrderService())->list($param, $limit, $offset));
    }

    /**
     * 订单详情
     * @param int $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function details(int $id)
    {
        return $this->apiResponse((new OrderService())->details($id));
    }
}