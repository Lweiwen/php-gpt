<?php

namespace app\controller;

use app\BaseController;
use app\services\AttachmentService;
use app\validate\common\AttachementValidata;

class Attachment extends BaseController
{
    public function upload()
    {
        $file = $this->request->file('images');
        if (!$file) {
            return $this->apiResponse('请上传图片', 0);
        }

        $this->validate(['images' => $file], AttachementValidata::class, 'upload');
        $files = [];
        if (is_array($file)) {
            if (count($file) > 9) {
                return $this->apiResponse('最多上传9张图片', 0);
            }
            $files = $file;
        } else {
            $files[0] = $file;
        }
        $userId = $this->request->uid();
        $result = (new AttachmentService())->upload($userId,$files);
        return $this->apiResponse($result);
    }
}