<?php

namespace app\controller\admin;

use app\BaseController;
use app\services\admin\UserService;

class User extends BaseController
{
    /**
     * 用户列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function list()
    {
        list($param['keywords'], $param['begin_time'], $param['end_time']) = $this->request->getMore(
            [
                ['keywords', ''],
                ['begin_time', 0],
                ['end_time', 0]
            ],
            true
        );
        if (!empty($param['keywords']) && strlen($param['keywords']) == 6) {
            $param['user_id'] = codeToId($param['keywords']);
            if ($param['user_id'] > 0) {
                unset($param['keywords']);
            }
        }
        list($limit, $offset) = $this->pagination();
        return $this->apiResponse((new UserService())->list($param, $limit, $offset));
    }
}