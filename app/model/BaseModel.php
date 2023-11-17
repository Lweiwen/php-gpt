<?php

namespace app\model;

use think\Model;

/**
 * BaseModel
 * Class BaseModel
 * @package app\model
 */
class BaseModel extends Model
{
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;

    // 定义一个访问器，用于获取create_time字段的原始值
    public function getCreateTimeAttr($value)
    {
        return $value;
    }

    // 定义一个访问器，用于获取update_time字段的原始值
    public function getUpdateTimeAttr($value)
    {
        return $value;
    }
}
