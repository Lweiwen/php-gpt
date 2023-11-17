<?php
namespace app\controller\admin;
use app\BaseController;
use app\services\admin\GptService;

class Gpt extends BaseController
{
    public function balance()
    {
        $this->apiResponse((new GptService())->balance());
    }

    public function modelList()
    {

    }
}