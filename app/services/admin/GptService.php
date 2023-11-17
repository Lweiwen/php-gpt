<?php

namespace app\services\admin;

use app\exceptions\ApiException;
use app\services\BaseService;
use chatGpt\Gpt;

class GptService extends BaseService
{
    public function balance()
    {
        $config = config('gpt');
        if ($config) {
            if ($config['channel'] == 2) {
                $aiconfig = $config['api2d'];
                $gpt = new Gpt(['channel' => 2, 'api_key' => $aiconfig['forward_key'], 'diy_host' => '']);
            } else {
                $aiconfig = $config['openai'];
                $gpt = new Gpt(
                    ['channel' => 1, 'api_key' => $aiconfig['api_key'], 'diy_host' => $aiconfig['base_url']]
                );
            }
        } else {
            throw new ApiException('未检查到配置信息');
        }
        $result = $gpt->Billing()->creditGrants();
        var_dump($result);
        die;
    }
}