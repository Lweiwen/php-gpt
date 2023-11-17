<?php

namespace app\services\gpt;

use app\exceptions\ApiException;
use app\services\BaseService;
use app\traits\ChatResponseTrait;
use Orhanerday\OpenAi\OpenAi;
use think\facade\Log;

class OpenAiService extends BaseService
{
    use ChatResponseTrait;

    protected $openAi = null;//会话对象

    protected $config = [];
    protected $apiConfig = [];

    /**
     * OpenAiService constructor.
     */
    public function __construct()
    {
        $this->config = config('openai');
        $this->_initialize();
    }

    /**
     * 初始化数据
     * @author LWW
     */
    public function _initialize()
    {
        if (empty($this->config['api_key'])) {
            //todo::补充apikey
            $this->config['api_key'] = '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';
        }
        $apiKey = $this->config['api_key'];
        if (empty($this->config['api_key'])) {
            throw new ApiException('请设置apikey');
        }
        $this->apiConfig = $this->config['chatgpt'];
        $this->openAi = new OpenAi($apiKey);
        //设置代理地址
        $this->openAi->setBaseURL($this->config['base_url']); //设置基础url
    }

    /**
     * 推送信息
     * @param array $messages
     * @return array
     * @author LWW
     */
    public function pushMessage(array $messages)
    {
        $opts = [
            'model'             => 'gpt-3.5-turbo',
            'messages'          => $messages,
            'temperature'       => 1.0,
//            'max_tokens'        => 50,
            'frequency_penalty' => 1.0,
            'presence_penalty'  => 1.0,
            'stream'            => true,
        ];
        $dataBuffer = '';  //记录返回未解析buffer
        $allTxt = '';
        $complete = $this->openAi->chat(
            $opts,
            function ($curl_info, $data) use (&$allTxt, &$dataBuffer) {
                $obj = json_decode($data);
                if (isset($obj->error)) {
                    self::recordLog($obj->error->code, $obj->error->message);
                } else {
                    $buffer = $dataBuffer . $data;
                    $dataBuffer = '';

                    /*
                        此处步骤仅针对 openai 接口而言
                        每次触发回调函数时，里边会有多条data数据，需要分割
                        如某次收到 $data 如下所示：
                        data: {"id":"chatcmpl-6wimHHBt4hKFHEpFnNT2ryUeuRRJC","object":"chat.completion.chunk","created":1679453169,"model":"gpt-3.5-turbo-0301","choices":[{"delta":{"role":"assistant"},"index":0,"finish_reason":null}]}\n\ndata: {"id":"chatcmpl-6wimHHBt4hKFHEpFnNT2ryUeuRRJC","object":"chat.completion.chunk","created":1679453169,"model":"gpt-3.5-turbo-0301","choices":[{"delta":{"content":"以下"},"index":0,"finish_reason":null}]}\n\ndata: {"id":"chatcmpl-6wimHHBt4hKFHEpFnNT2ryUeuRRJC","object":"chat.completion.chunk","created":1679453169,"model":"gpt-3.5-turbo-0301","choices":[{"delta":{"content":"是"},"index":0,"finish_reason":null}]}\n\ndata: {"id":"chatcmpl-6wimHHBt4hKFHEpFnNT2ryUeuRRJC","object":"chat.completion.chunk","created":1679453169,"model":"gpt-3.5-turbo-0301","choices":[{"delta":{"content":"使用"},"index":0,"finish_reason":null}]}

                        最后两条一般是这样的：
                        data: {"id":"chatcmpl-6wimHHBt4hKFHEpFnNT2ryUeuRRJC","object":"chat.completion.chunk","created":1679453169,"model":"gpt-3.5-turbo-0301","choices":[{"delta":{},"index":0,"finish_reason":"stop"}]}\n\ndata: [DONE]

                        根据以上 openai 的数据格式，分割步骤如下：
                    */

                    // 1、把所有的 'data: {' 替换为 '{' ，'data: [' 换成 '['
                    $buffer = str_replace('data: {', '{', $buffer);
                    $buffer = str_replace('data: [', '[', $buffer);

                    // 2、把所有的 '}\n\n{' 替换维 '}[br]{' ， '}\n\n[' 替换为 '}[br]['
                    $buffer = str_replace("}\n\n{", '}[br]{', $buffer);
                    $buffer = str_replace("}\n\n[", '}[br][', $buffer);

                    // 3、用 '[br]' 分割成多行数组
                    $lines = explode('[br]', $buffer);

                    // 4、循环处理每一行，对于最后一行需要判断是否是完整的json
                    $lineCount = count($lines);

                    foreach ($lines as $li => $line) {
                        if (trim($line) == '[DONE]') {
                            //数据传输结束
                            break;
                        }

                        $lineData = json_decode(trim($line), true);
                        if (!is_array(
                                $lineData
                            ) || !isset($lineData['choices']) || !isset($lineData['choices'][0])) {
                            if ($li == ($lineCount - 1)) {
                                //如果是最后一行
                                $dataBuffer = $line;
                            }
                            //如果是中间行无法json解析，则写入错误日志中
//                            file_put_contents('./log/error.'.$this->qmd5.'.log', json_encode(['i'=>$this->counter, 'line'=>$line, 'li'=>$li], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL.PHP_EOL, FILE_APPEND);
//                            continue;
                        }

                        if (isset($lineData['choices'][0]['delta']) && isset($lineData['choices'][0]['delta']['content'])) {
                            $allTxt .= $lineData['choices'][0]['delta']['content'];
                            self::response($line);
                        }
                    }
                }
                ob_flush();
                flush();
                return strlen($data);
            }
        );
        return [$complete, $allTxt];
    }

}