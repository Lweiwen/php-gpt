<?php

namespace app\controller\admin;

use app\BaseController;
use app\services\admin\CommonService;

class Common extends BaseController
{
    /**
     * 后台首页数据统计
     * @return array
     * @throws \think\db\exception\DbException
     * @author LWW
     */
    public function homeStatics()
    {
        return $this->apiResponse((new CommonService())->homeStatics());
    }
}