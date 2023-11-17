<?php

namespace app\services\index;

use app\exceptions\ApiException;
use app\model\GoodModel;
use app\services\BaseService;

class GoodsService extends BaseService
{
    /**
     * 返回商品列表
     * @param int $limit
     * @param int $offset
     * @return array|array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function listGoods(int $limit, int $offset)
    {
        $result = [
            'list' => [],
        ];
        $params = ['sale_status' => 1];
        $objGoods = GoodModel::listForGoods($limit, $offset, ['sort' => 'desc', 'id' => 'asc'], $params);
        if ($objGoods->count() <= 0) {
            return $result;
        }
        foreach ($objGoods as $good) {
            $result['list'][] = [
                'id'             => $good->id,
                'goods_name'     => $good->goods_name,
                'image'          => $good->image,
                'content'        => $good->content,
//                'sort'           => $good->sort,
                'price'          => sprintf2($good->price / 100),
                'original_price' => sprintf2($good->original_price / 100),
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
        if ($objGoods->sale_status != 1) {
            throw new ApiException('商品已下架');
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