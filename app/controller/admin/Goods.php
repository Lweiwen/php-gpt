<?php

namespace app\controller\admin;

use app\BaseController;
use app\services\admin\GoodsService;
use app\validate\admin\GoodsValidata;
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
     * 新增商品
     * @return mixed
     * @throws \Throwable
     * @author LWW
     */
    public function add()
    {
        $data = $this->request->postMore(
            [
                ['goods_name', ''],
                ['image', ''],
                ['content', ''],
                ['price', 0],
                ['original_price', 0],
                ['sale_status', 0],
                ['ai_num', 0],
            ]
        );
        $this->validate(
            $data,
            GoodsValidata::class,
            'add'
        );
        //价格转换
        $data['price'] = intval(round($data['price'] * 100));
        $data['original_price'] = intval(round($data['original_price'] * 100));
        $this->service->addGoods($data);
        return $this->apiResponse();
    }

    /**
     * 编辑商品
     * @param int $id
     * @return mixed
     * @throws \Throwable
     * @author LWW
     */
    public function edit(int $id)
    {
        $data = $this->request->postMore(
            [
                ['goods_name', ''],
                ['image', ''],
                ['content', ''],
                ['price', 0],
                ['original_price', 0],
                ['sale_status', 0],
                ['ai_num', 0],
            ]
        );
        $this->validate(
            $data,
            GoodsValidata::class,
            'add'
        );
        //价格转换
        $data['price'] = intval(round($data['price'] * 100));
        $data['original_price'] = intval(round($data['original_price'] * 100));
        $this->service->editGoods($id, $data);
        return $this->apiResponse();
    }

    /**
     * 上下架修改
     * @param int $id
     * @return mixed
     * @throws \Throwable
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function editSaleStatus(int $id)
    {
        $data = $this->request->postMore(
            [
                'sale_status',
            ]
        );
        $this->validate(
            $data,
            GoodsValidata::class,
            'edit_sale_status'
        );
        $this->service->editGoods($id, $data);
        return $this->apiResponse();
    }

    /**
     * 列表
     * @return mixed
     * @throws \think\db\exception\DbException
     * @author LWW
     */
    public function list()
    {
        list($param['goods_name'], $param['sale_status']) = $this->request->getMore(
            [
                ['keyword', ''],
                ['sale_status', '-1']
            ],
            true
        );
        $this->validate(
            $param,
            GoodsValidata::class,
            'select'
        );
        list($limit, $offset) = $this->pagination();
        $result = $this->service->listGoods($limit, $offset, $param);
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