<?php

declare (strict_types=1);

namespace app\services\wechat;

use app\exceptions\ApiException;
use app\model\UserAuthModel;
use app\model\UserModel;
use app\services\BaseService;
use think\facade\Db;

class WxUserService extends BaseService
{
    /**
     * 查找并创建授权用户
     * (通过openid)
     * @param string $openid
     * @param string $unionid
     * @return UserAuthModel|array|mixed|\think\db\Query|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function createUserAuthByOpenid(string $openid, string $unionid = '')
    {
        $objU = UserAuthModel::findWithOpenid($openid);
        if (!$objU) {
            $objU = UserAuthModel::addUniqueUser(['openid' => $openid, 'user_id' => 0, 'unionid' => $unionid]);
            if (!$objU) {
                throw new ApiException('无法创建信息，请稍后再登录', 0);
            }
        } else {
            if (!empty($unionid) && $unionid != $objU->unionid) {
                UserAuthModel::editUniqueUser($objU, ['unionid' => $unionid]);
            }
        }
        return $objU;
    }

    /**
     * 新建用户
     * @param UserAuthModel $objU
     * @param array $options
     * @return int|string
     * @throws \Throwable
     * @author LWW
     */
    public function createUser(UserAuthModel $objU, array $options = [])
    {
        Db::startTrans();
        try {
            $nikeName = $options['nickname'] ?? '微信用户' . createUserSn();
            $headImg = $options['headimgurl'] ?? '';
            $userId = UserModel::addUser(
                [
                    'nickname'    => $nikeName,
                    'avatar'      => $headImg,
                    'create_time' => time(),
                    'status'      => 1,
                ]
            );
            UserAuthModel::editUniqueUser($objU, ['user_id' => $userId]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
        return $userId;
    }

}