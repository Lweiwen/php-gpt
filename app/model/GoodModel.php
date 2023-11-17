<?php

namespace app\model;

use think\model\concern\SoftDelete;

class GoodModel extends BaseModel
{
    use SoftDelete;

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'goods';

    /**
     * 新增商品
     * @param array $goods
     * @return GoodModel|\think\Model
     * @author LWW
     */
    public static function addGoods(array $goods)
    {
        return self::create($goods);
    }

    /**
     *
     * @param int $id
     * @return GoodModel|array|mixed|\think\db\Query|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function findWithId(int $id)
    {
        return self::where('id', $id)->find();
    }

    /**
     * 修改商品
     * @param GoodModel $obj
     * @param array $param
     * @return bool
     * @author LWW
     */
    public static function edit(self $obj, array $param)
    {
        if (!empty($param)) {
            foreach ($param as $k => $val) {
                $obj->$k = $val;
            }
            return $obj->save();
        }
        return true;
    }

    /**
     * 处理搜寻条件
     * @param array $param
     * @return GoodModel|\think\db\Query
     * @author LWW
     */
    public static function dealForSelect(array $param)
    {
        $where = [];
        if (isset($param['goods_name']) && !empty($param['goods_name'])) {
            $where[] = ['goods_name', 'like', '%' . $param['goods_name'] . '%'];
        }
        if (isset($param['sale_status']) && $param['sale_status'] >= 0) {
            $where[] = ['sale_status', '=', $param['sale_status']];
        }
        return self::where($where);
    }

    /**
     * 返回数量
     * @param array $params
     * @return int|\think\db\Query
     * @throws \think\db\exception\DbException
     * @author LWW
     */
    public static function countForPc(array $params)
    {
        $obj = self::dealForSelect($params);
        return $obj->count();
    }

    /**
     * 获取商品列表
     * @param int $limit
     * @param int $offset
     * @param array $orderBy
     * @param array $params
     * @return GoodModel[]|array|\think\Collection|\think\db\Query[]|\think\model\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function listForGoods(int $limit, int $offset, array $orderBy = [], array $params = [])
    {
        $obj = self::dealForSelect($params);
        if (!empty($orderBy)) {
            foreach ($orderBy as $key => $val) {
                $obj->order($key, $val);
            }
        }
        if ($limit > 0) {
            $obj->limit($offset, $limit);
        }
        return $obj->select();
    }
}