<?php

namespace app\model;

use think\model\concern\SoftDelete;

class AttachmentModel extends BaseModel
{
    use SoftDelete;

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'attachment';

    //允许最大文件大小，以b做单位(1kb=1024b)
    const maxSize = 20 * 1024 * 1024;     //20MB
    //文件上传类型
    const allowType = [
        'image' => [
            'extension' => 'jpg,gif,png,bmp,jpeg',
            'mime_type' => 'image/jpeg,image/pjpeg,image/png,image/gif,image/x-png,image/bmp',
        ],
    ];

    /**
     * 新增数据
     * @param array $param
     * @return AttachmentModel|\think\Model
     * @author LWW
     */
    public static function add(array $param)
    {
        return self::create($param);
    }

    /**
     * 根据hash查找已删除数据
     * @param string $fileHash
     * @return AttachmentModel|array|mixed|\think\db\Query|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function findForDeleteFile(string $fileHash)
    {
        return self::onlyTrashed()->where('hash', $fileHash)->find();
    }

    /**
     * 更新资料
     * @param AttachmentModel $obj
     * @param array $param
     * @return bool
     * @author LWW
     */
    public static function edit(self $obj, array $param): bool
    {
        if (!$obj) {
            return false;
        }
        foreach ($param as $key => $value) {
            $obj->$key = $value;
        }
        return $obj->save();
    }

    /**
     * 查找存在的文件
     * @param array $fileHash
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function listForCheckSameMd5(array $fileHash)
    {
        return self::whereIn('hash', $fileHash)->select();
    }

}