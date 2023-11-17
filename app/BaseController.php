<?php


namespace app;

use think\facade\App;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * @var
     */
    protected $services;


    /**
     * 构造方法
     * @access public
     * @param App $app 应用对象
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        // 使用过来请求
        $this->request = app('request');
        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
    }

    /**
     * 验证数据
     * @param array $data
     * @param $validate
     * @param null $message
     * @param bool $batch
     * @return bool
     */
    final protected function validate(array $data, $validate, $message = null, bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new \think\facade\Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                list($validate, $scene) = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }

            if (is_string($message) && empty($scene)) {
                $v->scene($message);
            }
        }

        if (is_array($message)) {
            $v->message($message);
        }


        // 是否批量验证
        if ($batch) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * 分页参数获取
     * $limit 每页数目,默认20,最大50
     * $offset($page) 页数，前端传来的offset表示页数，接收参数后换算成数据查询偏移量的意思
     * @return array 返回按顺序包含 搜索数目、偏移量 的数组
     * @author lww
     */
    public function pagination(): array
    {
        $page = app()->request->param('offset', 1);
        $limit = app()->request->param('limit', 20);
        $limit = $limit > 0 ? $limit <= 50 ? $limit : 20 : 20;
        $offset = ($page - 1) * $limit;
        return array($limit, $offset);
    }

    /**
     * 返回数据
     * @return mixed
     * @author LWW
     */
    public function apiResponse($msg = 'success', int $code = 1, array $data = [], ?array $header = [])
    {
        if (!is_integer($code)) {
            $code = (is_numeric($code)) ? intval($code) : 0;
        }
        //数组直接返回success
        if (is_array($msg)) {
            $data = $msg;
            $msg = 'success';
        }
        return app('json')->apiResponse($msg, $code, $data, $header);
    }
}
