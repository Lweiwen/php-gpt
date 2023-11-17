<?php

namespace app\services\auth;

use app\exceptions\ApiException;
use app\model\AdminModel;
use app\model\UserModel;
use app\services\BaseService;
use app\traits\JwtAuthModelTrait;

class PasswordLoginService extends BaseService
{
    use JwtAuthModelTrait;

    /**
     * 后台登录
     * @param string $account
     * @param string $password
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function getAdminLoginToken(string $account, string $password)
    {
        $objUser = UserModel::findWithUserAccount($account);
        if (!$objUser || !password_verify($password, $objUser->password)) {
            throw new ApiException('账号或密码错误');
        }
        //验证用户是否总后台用户，已经是否允许登录
        $objMember = AdminModel::findWithUserId($objUser->id);
        if (!$objMember) {
            throw new ApiException('该账号无法在此登录', 0);
        }

        if ($objMember->status == 0) {
            throw new ApiException('该账号禁止登录', 0);
        }
        //设置密钥
        self::setSignatureKey('admin');
        $token = self::encrypt(
            [
                'uid'  => $objUser->id,
                'path' => 'admin'
            ]
        );
        //更新用户最后登录时间
        AdminModel::editLastLogin($objMember);
        //todo::记录日志

        //返回token和用户权限名称
        return [
            'token'     => $token,
//            'user_info' => [
//                'id'     => $objUser->id,
//                'avatar' => $objUser->avatar,
//                'role'   => $objMember->role,
//            ],
//            'name'      => $objMember->name,
//            'mobile'    => $mobile,
        ];
    }

    /**
     * 手机账号密码登录
     * @param int $userId
     * @param string $nickname
     * @param string $avatar
     * @param string $mobile
     * @return array
     * @throws \Exception
     * @author LWW
     */
    public function getUserLoginToken(
        int $userId,
        string $nickname,
        string $avatar = '',
        string $mobile = ''
    ): array {
        self::setSignatureKey('web');
        $token = self::encrypt(
            [
                'uid'    => $userId,
                'path'   => 'web'
            ]
        );
        return [
            'token'    => $token,
            'userInfo' => [
                'id'       => idToCode($userId),
                'avatar'   => $avatar,
                'nickname' => $nickname,
                'mobile'   => $mobile,
            ],
        ];
    }

}