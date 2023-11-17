<?php

namespace app\model;


class Client_
{
    const pc = 1;   //pc
    const oa = 2;   //公众号
    const mnp = 3;  //小程序
    const h5 = 4;   //h5(非微信环境h5)
    /**
     * 获取对应名
     * @param $value
     * @return string
     * @author LWW
     */
    function getName($value)
    {
        switch ($value) {
            case self::mnp:
                $name = '小程序';
                break;
            case self::h5:
                $name = 'h5';
                break;
            case self::oa:
                $name = '公众号';
                break;
        }
        return $name;
    }

    public static function getClient($type = true)
    {
        $desc = [
            self::pc  => 'pc端',
            self::h5  => 'h5端',
            self::oa  => '公众号',
            self::mnp => '小程序',
        ];
        if ($type === true) {
            return $desc;
        }
        return $desc[$type] ?? '未知';
    }
}