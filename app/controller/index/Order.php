<?php

namespace app\controller\index;

use app\BaseController;
use app\services\index\OrderService;
use app\validate\index\OrderValidata;

class Order extends BaseController
{
    /**
     * 新建订单
     * @return mixed
     * @throws \Throwable
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function createOrder()
    {
        $uid = (int)$this->request->uid();
        $post = $this->request->postMore(
            [
                ['goods_id', 0],
                ['goods_num', 1],
                ['pay_way', 1],
            ],
            false
        );
        $this->validate(
            $post,
            OrderValidata::class,
            'create_order'
        );
        return $this->apiResponse((new OrderService())->createOrder($post, $uid));
    }

    /**
     * 订单列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function orderList()
    {
        $uid = (int)$this->request->uid();
        list($limit, $offset) = $this->pagination();
        list($payStatus) = $this->request->getMore([['pay_status', -1]], true);
        return $this->apiResponse((new OrderService())->orderList($uid, $limit, $offset, $payStatus));
    }

    /**
     * 订单详情
     * @param string $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function orderDetails(string $id)
    {
        $uid = (int)$this->request->uid();
        $id = codeToId($id);
        return $this->apiResponse((new OrderService())->orderDetails($id, $uid));
    }
}