<?php

namespace app\services\data;

use app\services\BaseService;

class StatisticsService extends BaseService
{
    /**
     * 获取今天的年月日
     * @return int
     */
    private function getTodayYMD()
    {
        return intval(date('Ymd', time()));
    }
}