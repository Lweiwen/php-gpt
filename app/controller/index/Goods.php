<?php

namespace app\controller\index;

use app\BaseController;
use app\services\index\GoodsService;
use think\facade\App;

class Goods extends BaseController
{
    /**
     * @var GoodsService
     */
    protected $service;

    /**
     * 初始化
     */
    public function __construct(App $app, GoodsService $GoodsService)
    {
        parent::__construct($app);
        $this->service = $GoodsService;
    }

    /**
     * 列表
     * @return mixed
     * @throws \think\db\exception\DbException
     * @author LWW
     */
    public function list()
    {
        list($limit, $offset) = $this->pagination();
        $result = $this->service->listGoods($limit, $offset);
        return $this->apiResponse($result);
    }

    /**
     * 获取详情
     * @param int $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function details(int $id)
    {
        $result = $this->service->goodsDetails($id);
        return $this->apiResponse($result);
    }
}