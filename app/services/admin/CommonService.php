<?php

namespace app\services\admin;

use app\model\OrderModel;
use app\model\UserModel;
use app\services\BaseService;

class CommonService extends BaseService
{
    /**
     * 统计数据
     * @return array
     * @throws \think\db\exception\DbException
     * @author LWW
     */
    public function homeStatics()
    {
        //销售额
        //今日销售额
        $todaySales = OrderModel::todaySales('today');
        //昨日销售额
        $yesterdaySales = OrderModel::todaySales('yesterday');
        //订单数量
        //今日订单量
        $todayOrder = OrderModel::todaySalesCount('today');
        //昨日订单量
        $yesterdayOrder = OrderModel::todaySalesCount('yesterday');
        //新增用户
        //今日新增用户
        $todayUser = UserModel::todayAddUser('today');
        //昨日新增用户
        $yesterdayUser = UserModel::todayAddUser('yesterday');
        return [
            'today_sales'     => sprintf2($todaySales / 100),
            'yesterday_sales' => sprintf2($yesterdaySales / 100),
            'today_order'     => $todayOrder,
            'yesterday_order' => $yesterdayOrder,
            'today_user'      => $todayUser,
            'yesterday_user'  => $yesterdayUser
        ];
    }
}