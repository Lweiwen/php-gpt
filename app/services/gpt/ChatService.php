<?php

namespace app\services\gpt;

use app\exceptions\ApiException;
use app\model\ChatMsgGroupModel;
use app\model\ChatMsgModel;
use app\model\UserAIMoneyChangeModel;
use app\model\UserAiMoneyModel;
use app\services\BaseService;
use app\services\index\UserAiMoneyService;
use app\traits\ChatResponseTrait;
use chatGpt\Gpt;
use DfaFilter\SensitiveHelper;
use think\facade\Db;
use think\facade\Log;

class ChatService extends BaseService
{
    use ChatResponseTrait;

    /**
     * 获取分组列表
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @param array $selectParam
     * @return array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function getGroupList(int $userId, int $limit, int $offset, array $selectParam): array
    {
        $result = [
            'list' => [],
        ];
        $selectParam['user_id'] = $userId;
        $objGroup = ChatMsgGroupModel::listForGroup($limit, $offset, ['id' => 'desc'], $selectParam);
        foreach ($objGroup as $val) {
            $result['list'][] = [
                'id'          => maxIdToCode($val->id),
                'title'       => $val->title,
                'create_time' => $val->create_time
            ];
        }
        return $result;
    }

    /**
     * 编辑分组
     * @param int $userId
     * @param int $groupId
     * @param array $request
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function editGroup(int $userId, int $groupId, array $request)
    {
        $obj = ChatMsgGroupModel::findByUserIdAndGroupId($userId, $groupId);
        if (!$obj) {
            throw new ApiException('信息分组不存在');
        }
        $updateData = [
            'title' => $request['title'],
        ];
        ChatMsgGroupModel::editGroup($obj, $updateData);
        return true;
    }

    /**
     * 删除分组
     * @param int $userId
     * @param int $groupId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function delGroup(int $userId, int $groupId): bool
    {
        $obj = ChatMsgGroupModel::findByUserIdAndGroupId($userId, $groupId);
        if (!$obj) {
            throw new ApiException('信息分组不存在');
        }
        ChatMsgGroupModel::del($obj);
        return true;
    }

    /**
     * 获取分组信息
     * @param int $userId
     * @param int $groupId
     * @return array|array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function getGroupChatMsg(int $userId, int $groupId)
    {
        $result = [
            'list' => [],
        ];
        $objGroup = ChatMsgGroupModel::findByUserIdAndGroupId($userId, $groupId);
        if (!$objGroup) {
            throw new ApiException('分组信息错误');
        }
        $objMsg = ChatMsgModel::getByGroupIdAndUserId($groupId, $userId, ['id' => 'asc']);
        foreach ($objMsg as $val) {
            $result['list'][] = [
                'id'          => maxIdToCode($val->id),
                'inversion'   => true,
                'message'     => $val->message,
                'create_time' => $val->create_time
            ];
            $result['list'][] = [
                'id'          => maxIdToCode($val->id),
                'inversion'   => false,
                'message'     => $val->response ?: '',
                'create_time' => $val->create_time
            ];
        }
        return $result;
    }

    /**
     * 推送文字信息
     * @param int $userId
     * @param string $message
     * @param int $messageId
     * @param int $groupId
     * @param string $chatModel
     * @throws \DfaFilter\Exceptions\PdsBusinessException
     * @throws \DfaFilter\Exceptions\PdsSystemException
     * @throws \Throwable
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function sendText(
        int $userId,
        string $message,
        int $messageId = 0,
        int $groupId = 0,
        string $chatModel = 'gpt35'
    ) {

        $maxLength = 4000;

        $objUserAi = UserAiMoneyModel::findWithUserId($userId);
        if (!$objUserAi || $objUserAi->usable_ainum <= 0) {
            throw new ApiException('AI响应次数不足，请充值');
        }
        if (!empty($groupId)) {
            $objGroup = ChatMsgGroupModel::findByUserIdAndGroupId($userId, $groupId);
            if (!$objGroup) {
                throw new ApiException('分组不存在');
            }
        }

        //判断是否再次请求过去信息
        $objChatMsg = null;
        if (!empty($messageId)) {
            $objChatMsg = ChatMsgModel::findById($messageId, $groupId);
            $message = $objChatMsg ? $objChatMsg->message : '';
        }
        //判断对话
        if (empty($message)) {
            throw new ApiException('请输入您的问题');
        }
        //判断对话token
        $token = $this->estimateTokens($message);
        if ($token > $maxLength) {
            throw new ApiException('输入问题过长，请重新输入');
        }
        //检查敏感词 todo::后面需要加上
        $this->checkNoTxt($message, []);


        $messages = [];
        //获取前5条对话信息
        if (!empty($groupId) && $token < $maxLength) {
            $objLastMsg = ChatMsgModel::listForGroupMsg(
                ['user_od' => $userId, 'group_id' => $groupId, 'max_id' => $messageId],
                ['id' => 'desc'],
                5
            );
            if ($objLastMsg->count() > 0) {
                foreach ($objLastMsg as $val) {
                    if (!empty($val->response) && !empty($val->message)) {
                        $token = $token + $this->estimateTokens($val->message) + $this->estimateTokens($val->response);
                        if ($token < $maxLength) {
                            $messages[] = [
                                'role'    => 'assistant',
                                'content' => $val->response
                            ];
                            $messages[] = [
                                'role'    => 'user',
                                'content' => $val->message
                            ];
                        }
                    }
                }
                $messages = array_reverse($messages);
            }
        }
        //拼凑对话信息
        $messages[] = [
            "role"    => "user",
            "content" => $message
        ];
        //处理新增信息、分组
        Db::startTrans();
        try {
            if (empty($groupId)) {
                //截取分组名称
                $title = substr($message, 0, 60);
                $groupId = ChatMsgGroupModel::addGroup(
                    [
                        'title'       => $title,
                        'user_id'     => $userId,
                        'create_time' => time(),
                    ]
                );
            }
            if (empty($messageId)) {
                $objChatMsg = ChatMsgModel::addMsg(
                    [
                        'user_id'  => $userId,
                        'group_id' => $groupId,
                        'message'  => $message,
                    ]
                );
                $messageId = $objChatMsg->id;
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
        //获取通讯去到配置
        //todo::判断使用模型
        $config = config('gpt');
        if (isset($config)) {
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
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Origin, X-Requested-With, Content-Type, Accept');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        self::response(json_encode(['message_id' => maxIdToCode($messageId), 'group_id' => maxIdToCode($groupId)]));
        $response = '';
        $error = false;
        $dataBuffer = '';
        $gpt->chat()->sendText(
            $messages,
            function ($ch, $data) use ($gpt, $message, &$response, &$error, &$dataBuffer) {
                $complete = @json_decode($data);
                if (isset($complete->error)) {
                    $error = true;
                    self::recordLog($complete->error->code, $complete->error->message);
                } elseif (@$complete->object == 'error') {
                    $error = true;
                    self::recordLog('error', $complete->message);
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
                        }

                        if (isset($lineData['choices'][0]['delta']) && isset($lineData['choices'][0]['delta']['content'])) {
                            $response .= $lineData['choices'][0]['delta']['content'];
                            self::response($line);
                        }
                    }
                }


//                list($echoWord, $word) = $gpt->chat()->parseData($data);
////                $word = str_replace("\n", '<br/>', $word);
//                if ($complete) {//一次性完整输出
//                    if (!empty($word)) {
//                        $response = $word;
//                        self::response($echoWord);
//                    }
//                } else {//流式
//                    if ($word == 'data: [DONE]' || $word == 'data: [CONTINUE]') {
//                        self::end();
//                    } else {
//                        $response .= $word;
//                        self::response($echoWord);
//                    }
//                }
                ob_flush();
                flush();
                return strlen($data);
            },
            [
                'temperature' => $aiconfig['temperature'],
                'max_tokens'  => $aiconfig['max_tokens'],
                'model'       => $aiconfig['model'],
                'stream'      => $aiconfig['stream']
            ]
        );
        if ($error && empty($response)) {
            self::pushError('http_error', '网络错误!');
            self::end();
            ChatMsgModel::updateMsg($objChatMsg, '网络错误!');
        }

        //记录返回新增信息分组
        Db::startTrans();
        try {
            // 传递信息id+分组id
            ChatMsgModel::updateMsg($objChatMsg, $response);
            (new UserAiMoneyService())->chatAiMoney($userId, 1, UserAIMoneyChangeModel::user_use, 0);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            $record = [
                __CLASS__,
                __FUNCTION__,
                $e->getFile(),
                $e->getLine(),
                $e->getMessage()
            ];
            Log::record(implode('-', $record));
        }
        exit();
    }

    /**
     * 检测敏感词
     * @param $message
     * @param $filterText
     * @return bool
     * @throws \DfaFilter\Exceptions\PdsBusinessException
     * @throws \DfaFilter\Exceptions\PdsSystemException
     * @author LWW
     */
    protected function checkNoTxt($message, $filterText)
    {
        if (empty($filterText)) {
            return true;
        }
        $handle = SensitiveHelper::init()->setTree($filterText);
        $isLegal = $handle->islegal($message);
        if ($isLegal) {
            $markedContent = $handle->mark($message, '<mark>', '</mark>');
            throw new ApiException('你的提问包含敏感的语句,请修改后重新提问！' . $markedContent);
        }
        return true;
    }

    /**
     * 计算验证openai请求信息token
     * @param $str
     * @return int
     * @author LWW
     */
    private function estimateTokens($str): int
    {
        $totalTokens = 0;

        //统计汉字数量
        preg_match_all("/[\x{4e00}-\x{9fa5}]/u", $str, $chineseMatches);
        $totalTokens += count($chineseMatches[0]);


        //统计英文字母数量
        preg_match_all("/[a-zA-Z]/", $str, $letterMatches);
        $totalTokens += count($letterMatches[0]);


        //统计数字数量
        preg_match_all("/[0-9]/", $str, $numberMatches);
        $totalTokens += count($numberMatches[0]);


        //统计标点符号数量
        preg_match_all("/[^\w\s]|_/", $str, $punctuationMatches);
        $totalTokens += count($punctuationMatches[0]);


        //统计空格数量
        preg_match_all("/\s/", $str, $spaceMatches);
        $totalTokens += count($spaceMatches[0]);


        return $totalTokens;
    }
}