<?php

namespace app\services;

use app\exceptions\ApiException;
use app\model\AttachmentModel;
use think\facade\Db;
use think\facade\Filesystem;

class AttachmentService extends BaseService
{
    public function upload(int $userId, array $files)
    {
        $arrFiles = [];
        foreach ($files as $key => $file) {
            if ($file->isValid()) {
                $tmpHash = md5_file($file);
                //保留重复md5文件的文件信息
                $arrFiles[$tmpHash]['file'][$key] = $file;
            }
        }
        //处理数据库已存在数据
        $arrFileHash = array_keys($arrFiles);
        $objAttachment = AttachmentModel::listForCheckSameMd5($arrFileHash);
        if ($objAttachment->count() > 0) {
            foreach ($objAttachment as $attachment) {
                if (isset($arrFiles[$attachment->hash])) {
                    $arrFiles[$attachment->hash]['url'] = $attachment->url;
                    $arrFiles[$attachment->hash]['id'] = $attachment->id;
                }
            }
        }
        Db::startTrans();
        try {
            $result = [];
            foreach ($arrFiles as $fileMd5 => $item) {
                if (isset($item['url'])) {
                    foreach ($item['file'] as $key => $val) {
                        $result[$key] = ['id' => $item['id'], 'url' => $item['url']];
                        @unlink($val);
                    }
                } else {
                    $files = $item['file'];
                    $file = array_shift($item['file']);
                    $ext = $file->extension();
                    $saveFilename = self::generateFilename($ext);  //新的文件名称
                    $saveDir = self::generateSaveDir();       //保存目录
                    $size = $file->getSize();              //大小
                    $uploadType = env('File_Upload_Type', 0);    //上传类型
                    $mine = $file->getOriginalMime();
                    list($currYear, $currMonth, $currDay, $currHour) = explode('/', $saveDir);

                    switch ($uploadType) {
                        case 0:
                            $images = Filesystem::disk('public')->putFileAs($saveDir, $file, $saveFilename);
                            $url = env('BACK_URL') . '/storage/' . $images;
                            break;
                        case 1: //上传到阿里云OSS
                            throw new ApiException('上传设置错误');
                        default:
                            throw new ApiException('上传设置错误');
                    }
                    $data = [
                        'type'       => $uploadType,
                        'user_id'    => $userId,
                        'name'       => $saveFilename,
                        'url'        => $url,
                        'ext'        => $ext,
                        'size'       => $size,
                        'is_image'   => 1,
                        'hash'       => $fileMd5,
                        'mime'       => $mine,
                        'year'       => $currYear,
                        'month'      => $currMonth,
                        'day'        => $currDay,
                        'hour'       => $currHour,
                        'deleted_at' => null,
                    ];
                    //先判断是否已经有相同的，已删除的记录，有则更新相关信息，无则新建记录
                    $objAttachment = AttachmentModel::findForDeleteFile($fileMd5);
                    if ($objAttachment) {
                        AttachmentModel::edit($objAttachment, $data);
                    } else {
                        $objAttachment = AttachmentModel::add($data);
                        if (!$objAttachment) {
                            throw new ApiException('添加上传文件记录失败');
                        }
                    }
                    foreach ($files as $key => $val) {
                        $result[$key] = [
                            'id'  => $objAttachment->id,
                            'url' => $url,
                        ];
                    }
                }
            }
            Db::commit();
            return $result;
        } catch (\Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * 生成文件名(时间+随机字符)
     * @param string $ext
     * @param string $prefix
     * @return string
     */
    private static function generateFilename(string $ext, string $prefix = '')
    {
        $tmp_name = date('His');
        $tmp_name .= sprintf('%06d', (float)microtime() * 1000);
        $tmp_name .= sprintf('%04d', mt_rand(0, 9999));
        $tmp_name .= self::createRandom(6, false);
        return (empty($prefix) ? '' : $prefix . '_') . $tmp_name . '.' . $ext;
    }

    /**
     * 生成随机字符串
     * @param int $lenth 长度
     * @param bool $strong
     * @return string 字符串
     */
    private static function createRandom(int $lenth = 6, bool $strong = false)
    {
        $string = '123456789abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ';
        if (isset($strong) && $strong == true) {
            $string .= '~!@#$%^*(){}[],.;|';
        }
        return self::random($lenth, $string);
    }

    /**
     * 产生随机字符串
     * @param int $length 输出长度
     * @param string $chars 可选的 ，默认为 0123456789
     * @return   string     字符串
     */
    private static function random(int $length, string $chars = '0123456789')
    {
        $hash = '';
        $max = mb_strlen($chars, 'utf-8') - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }

        return $hash;
    }

    /**
     * 获取保存路径
     * @return false|string
     */
    public static function generateSaveDir()
    {
        return date('Y/m/d/H');
    }
}