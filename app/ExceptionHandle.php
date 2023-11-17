<?php
namespace app;

use app\exceptions\ApiException;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\facade\Env;
use think\facade\Log;
use think\Response;
use Throwable;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
        ApiException::class
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param  Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        if (!$this->isIgnoreReport($exception)) {
            $data = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $this->getMessage($exception),
                'code' => $this->getCode($exception),
            ];
            //日志内容
            $log = [
//                request()->userId(),                                                                 //管理员ID
                request()->ip(),                                                                      //客户ip
                ceil(msectime() - (request()->time(true) * 1000)),                               //耗时（毫秒）
                request()->rule()->getMethod(),                                                       //请求类型
                str_replace("/", "", request()->rootUrl()),                             //应用
                request()->baseUrl(),                                                                 //路由
                json_encode(request()->param(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),//请求参数
                json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),             //报错数据

            ];
            Log::write(implode("|", $log), "error");
        }
        // 使用内置的方式记录异常日志
//        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request   $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // 添加自定义异常处理机制
        $massageData = Env::get('app_debug', false) ? [
            'throwable_type' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace()
        ] : [];
        // 添加自定义异常处理机制
        if ($e instanceof DbException) {
            return app('json')->apiResponse(
                '数据获取失败',
                $e->getCode() ?: 0,
                $massageData
            );
        } elseif ($e instanceof ValidateException || $e instanceof ApiException) {
            return app('json')->apiResponse($e->getMessage(), $e->getCode() ?: 0);
        }else {
            return app('json')->apiResponse(
                '很抱歉!系统开小差了',
                $e->getCode() ?: 0,
                $massageData
            );
        }
        // 其他错误交给系统处理
        return parent::render($request, $e);
    }
}
