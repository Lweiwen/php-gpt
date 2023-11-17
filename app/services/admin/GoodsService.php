<?php

namespace app\services\admin;

use app\exceptions\ApiException;
use app\model\GoodModel;
use app\services\BaseService;
use think\facade\Db;

class GoodsService extends BaseService
{
    /**
     * 新增商品
     * @param array $request
     * @return bool
     * @throws \Throwable
     * @author LWW
     */
    public function addGoods(array $request): bool
    {
        Db::startTrans();
        try {
            $addGoodData = [
                'goods_name'     => $request['goods_name'],
                'image'          => $request['image'],
                'content'        => $request['content'],
                'price'          => $request['price'],
                'original_price' => $request['original_price'],
                'sale_status'    => $request['sale_status'],
                'ai_num'         => $request['ai_num'],
            ];
            $rs = GoodModel::addGoods($addGoodData);
            if (!$rs) {
                throw new ApiException('添加商品失败');
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();//事务回滚
            throw $e;
        }
        return true;
    }

    /**
     * 编辑商品
     * @param int $goodsId
     * @param array $request
     * @return bool
     * @throws \Throwable
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function editGoods(int $goodsId, array $request): bool
    {
        $userId = request()->uid();
        $objGoods = GoodModel::findWithId($goodsId);
        if (!$objGoods) {
            throw new ApiException('商品不存在');
        }
        Db::startTrans();
        try {
            $editData = [];
            $editPram = ['goods_name', 'image', 'content', 'price', 'original_price', 'sale_status', 'ai_num'];
            foreach ($editPram as $editField) {
                if (isset($request[$editField]) && $request[$editField] != $objGoods->$editField) {
                    $editData[$editField] = $request[$editField];
                }
            }
            if (!empty($editData)) {
                GoodModel::edit($objGoods, $editData);
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
        return true;
    }

    /**
     * 返回商品列表
     * @param int $limit
     * @param int $offset
     * @param array $selectParam
     * @return array|array[]
     * @throws \think\db\exception\DbException
     * @author LWW
     */
    public function listGoods(int $limit, int $offset, array $selectParam = [])
    {
        $result = [
            'list' => [],
        ];
        if ($offset == 0) {
            //查询金额
            $result['query'] = GoodModel::countForPc($selectParam);
            if ($result['query'] <= 0) {
                return $result;
            }
        }
        $objGoods = GoodModel::listForGoods(
            $limit,
            $offset,
            ['sort' => 'desc', 'id' => 'asc'],
            $selectParam
        );
        if ($objGoods->count() <= 0) {
            return $result;
        }
        foreach ($objGoods as $good) {
            $result['list'][] = [
                'id'             => $good->id,
                'goods_name'     => $good->goods_name,
//                'image'          => $good->image,
//                'content'        => $good->content,
                'sort'           => $good->sort,
                'price'          => sprintf2($good->price / 100),
                'original_price' => sprintf2($good->original_price / 100),
                'sale_status'    => $good->sale_status,
                'ai_num'         => $good->ai_num,
                'sale_num'       => $good->sale_num
            ];
        }
        return $result;
    }

    /**
     * 商品详情
     * @param int $goodsId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function goodsDetails(int $goodsId)
    {
        $objGoods = GoodModel::findWithId($goodsId);
        if (!$objGoods) {
            throw new ApiException('商品不存在');
        }
        return [
            'id'             => $objGoods->id,
            'goods_name'     => $objGoods->goods_name,
            'image'          => $objGoods->image,
            'content'        => $objGoods->content,
            'sort'           => $objGoods->sort,
            'price'          => sprintf2($objGoods->price / 100),
            'original_price' => sprintf2($objGoods->original_price / 100),
            'sale_status'    => $objGoods->sale_status,
            'ai_num'         => $objGoods->ai_num,
            'sale_num'       => $objGoods->sale_num
        ];
    }
}