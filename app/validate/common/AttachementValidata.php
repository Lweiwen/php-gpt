<?php

namespace app\validate\common;

use app\model\AttachmentModel;
use think\Validate;


class AttachementValidata extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'images|图片' => [
            'require',
            'fileSize:' . AttachmentModel::maxSize,
            'fileExt:' . AttachmentModel::allowType['image']['extension'],
            'fileMime:' . AttachmentModel::allowType['image']['mime_type'],
        ],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [

    ];

    /**
     * 需要验证的规则方法
     * @var \string[][]
     */
    protected $scene = [
        'upload' => ['images'],
    ];

}