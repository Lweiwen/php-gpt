<?php

namespace app\services\index;

use app\model\Client_;
use app\exceptions\ApiException;
use app\model\UserAIMoneyChangeModel;
use app\model\UserModel;
use app\services\auth\PasswordLoginService;
use app\services\BaseService;
use think\facade\Db;

class UserService extends BaseService
{
    /**
     * 手机账号密码注册
     * @param array $post
     * @return array
     * @throws \Throwable
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function register(array $post)
    {
        $client = isset($post['client']) ?? Client_::mnp;
        $objUser = UserModel::findWithMobile($post['mobile']);
        if ($objUser) {
            throw new ApiException('手机号账号已存在');
        }
        Db::startTrans();
        try {
            $nikeName = '用户' . createUserSn();
            $userId = UserModel::addUser(
                [
                    'nickname'    => $nikeName,
                    'mobile'      => $post['mobile'],
                    'password'    => password_hash($post['password'], PASSWORD_BCRYPT),
                    'create_time' => time(),
                    'status'      => 1,
                ]
            );
            (new UserAiMoneyService())->chatAiMoney($userId, 3, UserAIMoneyChangeModel::default_add);
            Db::commit();
            return (new PasswordLoginService())->getUserLoginToken(
                $userId,
                $nikeName,
                '',
                $post['mobile']
            );
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 获取用户信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function info()
    {
        $user = request()->user();
        if (empty($user)) {
            throw new ApiException('用户不存在');
        }
        $aiNum = (new UserAiMoneyService())->getAiMoney($user['id']);
        return [
            'id'        => idToCode($user['id']),
            'nickname'  => $user['nickname'],
            'mobile'    => $user['mobile'],
            'real_name' => $user['real_name'],
            'avatar'    => $user['avatar'],
            'ai_num'    => $aiNum
        ];
    }

    /**
     * 修改用户信息
     * @param int $userId
     * @param array $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function setInfo(int $userId, array $request)
    {
        $objUser = UserModel::findWithId($userId);
        if (!$objUser) {
            throw new ApiException('用户不存在');
        }
        $updateData = [];
        foreach ($request as $key => $val) {
            if (!empty($val) && $objUser->$key != $val) {
                $updateData[$key] = $val;
            }
        }
        if (!empty($updateData)) {
            UserModel::editUser($objUser, $updateData);
        }
    }
}