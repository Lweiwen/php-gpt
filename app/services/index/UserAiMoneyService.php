<?php

namespace app\services\index;

use app\model\UserAIMoneyChangeModel;
use app\model\UserAiMoneyModel;
use app\services\BaseService;

class UserAiMoneyService extends BaseService
{
    /**
     * 更改用户Ai次数
     * @param int $userId
     * @param int $aiNum
     * @param int $subjectType
     * @param int $orderId
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function chatAiMoney(int $userId, int $aiNum, int $subjectType, int $orderId = 0)
    {
        /*** 更新用户ai次数 ***/
        $objUAM = UserAiMoneyModel::findWithUserId($userId);
        $totalAinum = $usableAinum = $freezeAinum = 0;
        if ($objUAM) {
            $totalAinum = $objUAM->total_ainum;
            $usableAinum = $objUAM->usable_ainum;
            $freezeAinum = $objUAM->freeze_ainum;
        }
        $afterTotalAinum = $totalAinum;
        $afterUsableAinum = $usableAinum;
        $afterFreezeAinum = $freezeAinum;
        $changeType = 0;
        switch ($subjectType) {
            case UserAIMoneyChangeModel::user_buy:
            case UserAIMoneyChangeModel::admin_add:
            case UserAIMoneyChangeModel::default_add:
                $changeType = 1;
                $afterTotalAinum += $aiNum;
                $afterUsableAinum += $aiNum;
                break;
            case UserAIMoneyChangeModel::cancel_order_refund:
            case UserAIMoneyChangeModel::admin_reduce:
            case UserAIMoneyChangeModel::user_use:
                $changeType = 2;
                $afterTotalAinum -= $aiNum;
                $afterUsableAinum -= $aiNum;
                break;
        }
        /*** 更新用户ai次数 ***/
        $objUAM = UserAiMoneyModel::findWithUserId($userId);
        if ($objUAM) {
            UserAiMoneyModel::editAiMoney(
                $objUAM,
                [
                    'total_ainum'  => $afterTotalAinum,
                    'usable_ainum' => $afterUsableAinum
                ]
            );
        } else {
            UserAiMoneyModel::addAiMoney(
                [
                    'user_id'      => $userId,
                    'total_ainum'  => $afterTotalAinum,
                    'usable_ainum' => $afterUsableAinum
                ]
            );
        }
        UserAIMoneyChangeModel::createChange(
            [
                'user_id'         => $userId,
                'change_type'     => $changeType,
                'subject_type'    => $subjectType,
                'subject'         => UserAIMoneyChangeModel::getDesc($subjectType),
                'order_id'        => $orderId,
                'ainum'           => $aiNum,
                'usable_ainum'    => $afterUsableAinum,
                'freeze_ainum'    => $afterFreezeAinum,
                'total_ainum'     => $afterTotalAinum,
                'bf_usable_ainum' => $usableAinum,
                'bf_freeze_ainum' => $freezeAinum,
                'bf_total_ainum'  => $totalAinum,
                'description'     => ''
            ]
        );
    }

    /**
     * 获取个人ai次数
     * @param int $userId
     * @return int|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function getAiMoney(int $userId)
    {
        $objAiMoney = UserAiMoneyModel::findWithUserId($userId);
        return $objAiMoney ? $objAiMoney->usable_ainum : 0;
    }
}