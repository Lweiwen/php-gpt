<?php

namespace app\services\admin;

use app\model\OrderModel;
use app\model\UserAiMoneyModel;
use app\model\UserModel;
use app\services\BaseService;

class UserService extends BaseService
{
    /**
     * 获取用户列表
     * @param array $param
     * @param int $limit
     * @param int $offset
     * @return array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function list(array $param, int $limit, int $offset)
    {
        $result = ['list' => []];
        if ($offset <= 0) {
            $result['query'] = UserModel::countForUser($param);
            if ($result['query'] <= 0) {
                return $result;
            }
        }
        $objUser = UserModel::listForUser($param, $limit, $offset);
        if ($objUser->count() <= 0) {
            return $result;
        }
        $userIds = array_column($objUser->toArray(), 'id');
        $objUserAiNum = UserAiMoneyModel::listByUserIds($userIds);
        $arrUserAiNum = array_column($objUserAiNum->toArray(), 'usable_ainum', 'user_id');
        $objUserAiNum = null;
        $objUserSale = OrderModel::sumForUserIds($userIds);
        $arrUserSale = array_column($objUserSale->toArray(), 'order_amount', 'user_id');
        $objUserSale = null;
        foreach ($objUser as $user) {
            $result['list'][] = [
                'id'                 => idToCode($user->id),
                'nickname'           => $user->nickname,
                'mobile'             => $user->mobile,
                'avatar'             => $user->avatar,
                'status'             => $user->status,
                'create_time'        => $user->create_time,
                'ai_num'             => $arrUserAiNum[$user->id] ?? 0,
                'total_order_amount' => sprintf2(($arrUserSale[$user->id] ?? 0) / 100)
            ];
        }
        return $result;
    }
}